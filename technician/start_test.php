<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// start_test.php - Technician starts a test for a patient
session_start();
include '../config/db.php';

if (!isset($_SESSION['technician_id'])) {
    header('Location: login.php');
    exit();
}

$technician_id = $_SESSION['technician_id'];

// Validate booking ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid Test ID');
}

$booking_id = intval($_GET['id']);

// Fetch booking details
$stmt = $conn->prepare("SELECT b.id, b.patient_id, b.test_name, b.status, p.full_name, p.phone FROM bookings b JOIN patients p ON b.patient_id = p.patient_id WHERE b.id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
    die('Test record not found');
}

// Start test action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_time = date('Y-m-d H:i:s');

    $update = $conn->prepare("UPDATE bookings SET status = 'In Progress', started_at = ?, handled_by = ? WHERE id = ?");
    $update->bind_param("sii", $start_time, $technician_id, $id);

    if ($update->execute()) {
        header('Location: manage_tests.php?start=success');
        exit();
    } else {
        $error = "Failed to start test. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Start Test</title>
    <link href="../assets/css/niceadmin.css" rel="stylesheet">
</head>
<body>


<main id="main" class="main">
    <div class="pagetitle">
        <h1>Start Test</h1>
    </div>

    <section class="section">
        <div class="card p-4 shadow-lg">
            <h5 class="card-title">Patient Test Details</h5>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>Patient Name:</strong> <?= htmlspecialchars($booking['full_name']) ?><br>
                    <strong>Phone:</strong> <?= htmlspecialchars($booking['phone']) ?><br>
                </div>
                <div class="col-md-6">
                    <strong>Test Type:</strong> <?= htmlspecialchars($booking['test_name']) ?><br>
                    <strong>Status:</strong> <span class="badge bg-warning">Pending</span><br>
                </div>
            </div>

            <form method="POST">
                <button type="submit" class="btn btn-primary btn-lg">Start Test</button>
                <a href="manage_tests.php" class="btn btn-secondary btn-lg">Cancel</a>
            </form>
        </div>
    </section>
</main>

<script src="../assets/js/main.js"></script>
</body>
</html>
