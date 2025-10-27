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
$update = $conn->prepare("UPDATE bookings SET status = 'Completed' WHERE id = ?");
$update->bind_param("i", $booking_id);

if ($update->execute()) {
    header("Location: technician_dashboard.php?msg=Booking+archived+successfully");
    exit;
} else {
    echo "Failed to update booking.";
}