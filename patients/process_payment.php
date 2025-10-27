<?php
session_start();
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'])) {
    $booking_id = intval($_POST['booking_id']);
    $update = $conn->prepare("UPDATE billing SET status = 'Paid' WHERE booking_id = ?");
    $update->bind_param("i", $booking_id);
    if ($update->execute()) {
        header("Location: payment_success.php");
    } else {
        echo "Failed to update payment status.";
    }
}
?>
