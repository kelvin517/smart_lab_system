<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode([]);
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role']; // 'doctor', 'patient', 'technician'

// Get relevant notifications
$sql = "SELECT * FROM notifications 
        WHERE recipient_type = 'all' OR recipient_type = '$role'
        ORDER BY created_at DESC
        LIMIT 5";

$res = mysqli_query($conn, $sql);
$notifications = [];

while ($row = mysqli_fetch_assoc($res)) {
    $notifications[] = [
        'title' => $row['title'],
        'message' => $row['message'],
        'time' => date('M d, H:i', strtotime($row['created_at']))
    ];
}

echo json_encode($notifications);
?>