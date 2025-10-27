<?php
session_start();
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $test_id = $_POST['test_id'];
    $doctor_email = $_POST['doctor_email'];

    $stmt = $conn->prepare("INSERT INTO test_shares (test_id, doctor_email) VALUES (?, ?)");
    $stmt->bind_param("is", $test_id, $doctor_email);
    $stmt->execute();

    // Optional: Send email to doctor here
    header("Location: view_reports.php?shared=1");
}
?>
