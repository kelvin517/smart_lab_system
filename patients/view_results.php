<?php
// patients/view_results.php
session_start();
require_once '../config/db.php';

// Redirect if not logged in
if (!isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit;
}

$patient_id = $_SESSION['patient_id'];

// Fetch completed lab tests with reports
$stmt = $conn->prepare("
    SELECT b.id, b.test_type, b.status, b.preferred_date, b.report_file
    FROM bookings b
    WHERE b.patient_id = ? AND b.status = 'Completed'
    ORDER BY b.preferred_date DESC
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>View Test Results - Smart Laboratory</title>
  <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
  <style>
    body {
      background: #f4f6f9;
    }
    .card {
      background: #fff;
      border: 1px solid #dee2e6;
      box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075);
    }
  </style>
</head>
<body>

<div class="container mt-5">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4>📄 Your Lab Test Results</h4>
    <a href="dashboard.php" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left-circle"></i> Back to Dashboard</a>
  </div>

  <?php if ($result->num_rows > 0): ?>
    <div class="table-responsive">
      <table class="table table-bordered table-striped">
        <thead class="table-primary">
          <tr>
            <th>Test Type</th>
            <th>Date</th>
            <th>Status</th>
            <th>Report</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['test_type']) ?></td>
              <td><?= date("d M Y, H:i", strtotime($row['preferred_date'])) ?></td>
              <td><span class="badge bg-success"><?= htmlspecialchars($row['status']) ?></span></td>
              <td>
                <?php if (!empty($row['report_file']) && file_exists("../uploads/reports/" . $row['report_file'])): ?>
                  <a href="../uploads/reports/<?= $row['report_file'] ?>" class="btn btn-success btn-sm" download>
                    <i class="bi bi-download"></i> Download
                  </a>
                <?php else: ?>
                  <span class="text-muted">Not Available</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="alert alert-warning">No test results available yet. Please check back later.</div>
  <?php endif; ?>

</div>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>