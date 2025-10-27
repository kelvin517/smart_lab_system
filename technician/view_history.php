<?php
session_start();
include '../config/db.php';

if (!isset($_GET['patient']) || !is_numeric($_GET['patient'])) {
    die("Invalid patient ID.");
}

$patient_id = $_GET['patient'];

// Fetch patient name
$stmt = $conn->prepare("SELECT name FROM patients WHERE id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$stmt->bind_result($patient_name);
$stmt->fetch();
$stmt->close();

if (!$patient_name) {
    die("Patient not found.");
}

// Fetch test history
$sql = "SELECT br.id, br.booking_date, br.status, br.result_date, br.result, 
               lt.test_name 
        FROM book_requests br
        JOIN lab_tests lt ON br.test_id = lt.id
        WHERE br.patient_id = ?
        ORDER BY br.booking_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Test History</title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h3>Test History for Patient: <?= htmlspecialchars($patient_name) ?></h3>

    <table class="table table-bordered mt-3">
        <thead class="table-dark">
            <tr>
                <th>Test Name</th>
                <th>Booking Date</th>
                <th>Status</th>
                <th>Result Date</th>
                <th>Result File</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['test_name']) ?></td>
                    <td><?= htmlspecialchars($row['booking_date']) ?></td>
                    <td><?= htmlspecialchars($row['status']) ?></td>
                    <td><?= htmlspecialchars($row['result_date'] ?? '-') ?></td>
                    <td>
                        <?php if (!empty($row['result'])): ?>
                            <a href="../<?= $row['result'] ?>" target="_blank" class="btn btn-sm btn-info">View</a>
                            <a href="../<?= $row['result'] ?>" download class="btn btn-sm btn-success">Download</a>
                        <?php else: ?>
                            <span class="text-muted">No file</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="5" class="text-center">No test history found for this patient.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
</div>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
