<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['doctor_id'])) {
    header("Location: doctor_login.php");
    exit;
}

$doctor_name = $_SESSION['doctor_name'];
$success = $error = "";

// Handle result upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_result'])) {
    $booking_id = intval($_POST['booking_id']);

    if (isset($_FILES['result_file']) && $_FILES['result_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['result_file']['tmp_name'];
        $file_name = time() . '_' . basename($_FILES['result_file']['name']);
        $destination = '../uploads/' . $file_name;

        if (move_uploaded_file($file_tmp, $destination)) {
            $uploaded_by = $_SESSION['doctor_id'];
            $uploaded_by_role = 'doctor';

            $update = $conn->prepare("UPDATE bookings SET result_file = ?, status = 'Completed', uploaded_by = ?, uploaded_by_role = ? WHERE id = ?");
            $update->bind_param("sisi", $file_name, $uploaded_by, $uploaded_by_role, $booking_id);

            if ($update->execute()) {
                $success = "Result uploaded successfully.";
            } else {
                $error = "Failed to update result.";
            }

            $update->close();
        } else {
            $error = "File upload failed.";
        }
    } else {
        $error = "Invalid result file.";
    }
}

// Filtering logic
$filter_technician = $_GET['technician'] ?? '';
$filter_date = $_GET['date'] ?? '';
$where = "1=1";
$params = [];
$types = "";

if (!empty($filter_technician)) {
    $where .= " AND b.uploaded_by = ?";
    $params[] = $filter_technician;
    $types .= "i";
}
if (!empty($filter_date)) {
    $where .= " AND DATE(b.created_at) = ?";
    $params[] = $filter_date;
    $types .= "s";
}

$sql = "
    SELECT b.*, p.full_name AS patient_name,
      CASE 
        WHEN b.uploaded_by_role = 'doctor' THEN (SELECT full_name FROM doctors WHERE id = b.uploaded_by)
        WHEN b.uploaded_by_role = 'technician' THEN (SELECT full_name FROM users WHERE id = b.uploaded_by)
        ELSE 'Not Tracked'
      END AS uploader_name
    FROM bookings b
    JOIN patients p ON b.patient_id = p.id
    WHERE $where
    ORDER BY b.created_at DESC
";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<main id="main" class="main">
  <div class="pagetitle">
    <h1>Completed Test Results</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">View Results</li>
      </ol>
    </nav>
  </div>

  <section class="section">
    <div class="card">
      <div class="card-body pt-4">

        <!-- Filter Form -->
        <h5 class="card-title">Filter Test Results</h5>
        <form class="row g-3 mb-4" method="GET">
          <div class="col-md-4">
            <label class="form-label">Technician</label>
            <select class="form-select" name="technician">
              <option value="">-- All --</option>
              <?php
              $techs = $conn->query("SELECT id, full_name FROM users WHERE role = 'technician'");
              while ($tech = $techs->fetch_assoc()):
              ?>
              <option value="<?= $tech['id'] ?>" <?= ($tech['id'] == $filter_technician) ? 'selected' : '' ?>>
                <?= htmlspecialchars($tech['full_name']) ?>
              </option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Date</label>
            <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($filter_date) ?>">
          </div>
          <div class="col-md-4 align-self-end">
            <button class="btn btn-primary" type="submit">Apply Filter</button>
          </div>
        </form>

        <!-- Upload Notification -->
        <?php if ($success): ?>
          <div class="alert alert-success"><?= $success ?></div>
        <?php elseif ($error): ?>
          <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <!-- Results Table -->
        <h5 class="card-title">Test Result List</h5>
        <table class="table table-bordered table-hover">
          <thead>
            <tr>
              <th>Patient</th>
              <th>Test Type</th>
              <th>Status</th>
              <th>Date</th>
              <th>Result</th>
              <th>Uploaded By</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = $result->fetch_assoc()):
              $file_path = '../uploads/' . $row['result_file'];
              $has_result = !empty($row['result_file']) && file_exists($file_path);
            ?>
            <tr>
              <td><?= htmlspecialchars($row['patient_name']) ?></td>
              <td><?= htmlspecialchars($row['test_type']) ?></td>
              <td>
                <?php if ($row['status'] === 'Completed'): ?>
                  <span class="badge bg-success">Completed</span>
                <?php else: ?>
                  <span class="badge bg-warning text-dark"><?= $row['status'] ?></span>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($row['created_at']) ?></td>
              <td>
                <?php if ($has_result): ?>
                  <a href="<?= $file_path ?>" target="_blank" class="btn btn-sm btn-primary">View</a>
                <?php else: ?>
                  <form method="POST" enctype="multipart/form-data" class="d-flex gap-2 align-items-center">
                    <input type="hidden" name="booking_id" value="<?= $row['id'] ?>">
                    <input type="file" name="result_file" class="form-control" required>
                    <button type="submit" name="upload_result" class="btn btn-sm btn-success">Upload</button>
                  </form>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($row['uploader_name']) ?></td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</main>