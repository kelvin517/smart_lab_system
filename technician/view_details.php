<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['technician_id'])) {
    header("Location: technician_login.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("Booking ID not provided.");
}

$booking_id = intval($_GET['id']);
$success = $error = '';

// Fetch booking and patient details
$stmt = $conn->prepare("
    SELECT b.*, p.full_name, p.email, p.phone, p.gender 
    FROM bookings b 
    JOIN patients p ON b.patient_id = p.id 
    WHERE b.id = ?
");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();
$details = $result->fetch_assoc();
$stmt->close();

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'];
    $result_file = $details['result_file'];

    // Upload result file if provided
    if (!empty($_FILES['result_file']['name'])) {
        $upload_dir = '../uploads/';
        $file_name = time() . '_' . basename($_FILES['result_file']['name']);
        $target_path = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['result_file']['tmp_name'], $target_path)) {
            $result_file = $file_name;
        } else {
            $error = "Failed to upload result file.";
        }
    }

    // Update booking
    $update = $conn->prepare("UPDATE bookings SET status = ?, result_file = ? WHERE id = ?");
    $update->bind_param("ssi", $status, $result_file, $booking_id);

    if ($update->execute()) {
        $success = "Booking updated successfully.";
        $details['status'] = $status;
        $details['result_file'] = $result_file;
    } else {
        $error = "Failed to update booking.";
    }

    $update->close();
}
?>

<!-- Load NiceAdmin Header and Sidebar -->
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<main id="main" class="main">
  <div class="pagetitle">
    <h1>Booking Details</h1>
  </div>

  <section class="section">
    <div class="card">
      <div class="card-body pt-4">

        <?php if ($success): ?>
          <div class="alert alert-success"><?= $success ?></div>
        <?php elseif ($error): ?>
          <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($details): ?>
          <h5 class="card-title">Patient: <?= htmlspecialchars($details['full_name']) ?></h5>

          <ul class="list-group mb-3">
            <li class="list-group-item"><strong>Email:</strong> <?= htmlspecialchars($details['email']) ?></li>
            <li class="list-group-item"><strong>Phone:</strong> <?= htmlspecialchars($details['phone']) ?></li>
            <li class="list-group-item"><strong>Gender:</strong> <?= htmlspecialchars($details['gender']) ?></li>
            <li class="list-group-item"><strong>Test Type:</strong> <?= htmlspecialchars($details['test_type']) ?></li>
            <li class="list-group-item"><strong>Preferred Date:</strong> <?= htmlspecialchars($details['preferred_date']) ?></li>
            <li class="list-group-item"><strong>Status:</strong> <?= htmlspecialchars($details['status']) ?></li>
            <li class="list-group-item"><strong>Result File:</strong>
              <?= $details['result_file'] ? "<a href='../uploads/{$details['result_file']}' target='_blank'>View File</a>" : 'Not Uploaded' ?>
            </li>
          </ul>

          <!-- Update Form -->
          <form method="POST" enctype="multipart/form-data" class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Update Status</label>
              <select name="status" class="form-select" required>
                <option value="Received" <?= $details['status'] == 'Received' ? 'selected' : '' ?>>Received</option>
                <option value="Testing" <?= $details['status'] == 'Testing' ? 'selected' : '' ?>>Testing</option>
                <option value="Completed" <?= $details['status'] == 'Completed' ? 'selected' : '' ?>>Completed</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Upload Result File (PDF/Image)</label>
              <input type="file" name="result_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
            </div>

            <div class="col-12">
              <button type="submit" class="btn btn-primary">Update Booking</button>
              <a href="technician_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
          </form>

        <?php else: ?>
          <div class="alert alert-warning">Booking not found.</div>
        <?php endif; ?>
      </div>
    </div>
  </section>
</main>