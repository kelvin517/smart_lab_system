<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['doctor_id'])) {
    header("Location: doctor_login.php");
    exit;
}

$success = $error = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = intval($_POST['booking_id']);
    $note = trim($_POST['diagnosis_note']);

    if ($booking_id > 0 && !empty($note)) {
        // Check if diagnosis already exists
        $check_stmt = $conn->prepare("SELECT id FROM diagnosis WHERE booking_id = ?");
        $check_stmt->bind_param("i", $booking_id);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $error = "Diagnosis already exists for the selected test.";
        } else {
            // Insert diagnosis
            $insert_stmt = $conn->prepare("INSERT INTO diagnosis (booking_id, diagnosis_note, created_at) VALUES (?, ?, NOW())");
            $insert_stmt->bind_param("is", $booking_id, $note);

            if ($insert_stmt->execute()) {
                $success = "Diagnosis added successfully.";
            } else {
                $error = "Failed to add diagnosis.";
            }

            $insert_stmt->close();
        }
        $check_stmt->close();
    } else {
        $error = "Please select a test and enter a diagnosis.";
    }
}
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<main id="main" class="main">

  <div class="pagetitle">
    <h1>Add Diagnosis</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Add Diagnosis</li>
      </ol>
    </nav>
  </div>

  <section class="section">
    <div class="card">
      <div class="card-body pt-4">

        <h5 class="card-title">Select a Completed Test</h5>

        <?php if ($success): ?>
          <div class="alert alert-success"><?= $success ?></div>
        <?php elseif ($error): ?>
          <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label">Completed Test</label>
            <div class="col-sm-9">
              <select name="booking_id" class="form-select" required>
                <option value="">-- Select Test --</option>
                <?php
                $stmt = $conn->prepare("
                  SELECT b.id, p.full_name, b.test_type, b.created_at
                  FROM bookings b
                  JOIN patients p ON p.id = b.patient_id
                  LEFT JOIN diagnosis d ON d.booking_id = b.id
                  WHERE b.status = 'Completed' AND d.id IS NULL
                  ORDER BY b.created_at DESC
                ");
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()):
                ?>
                  <option value="<?= $row['id'] ?>">
                    <?= htmlspecialchars($row['full_name']) ?> - <?= htmlspecialchars($row['test_type']) ?> (<?= $row['created_at'] ?>)
                  </option>
                <?php endwhile; $stmt->close(); ?>
              </select>
            </div>
          </div>

          <div class="row mb-3">
            <label class="col-sm-3 col-form-label">Diagnosis Note</label>
            <div class="col-sm-9">
              <textarea name="diagnosis_note" class="form-control" rows="5" required></textarea>
            </div>
          </div>

          <div class="text-center">
            <button type="submit" class="btn btn-primary">Save Diagnosis</button>
          </div>
        </form>

      </div>
    </div>
  </section>

</main>