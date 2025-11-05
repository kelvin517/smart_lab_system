<?php
session_start();
include_once '../../config/db.php';

if (!isset($_SESSION['patient_id'])) {
    exit('Session expired. Please login again.');
}

$patient_id = $_SESSION['patient_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receiver_id = intval($_POST['receiver_id']);
    $subject = trim($_POST['subject']);
    $body = trim($_POST['body']);

    if ($receiver_id && $subject !== '' && $body !== '') {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, sender_role, receiver_role, subject, body) VALUES (?, ?, 'patient', 'doctor', ?, ?)");
        $stmt->bind_param("iiss", $patient_id, $receiver_id, $subject, $body);
        $stmt->execute();
        echo "✅ Message sent successfully!";
    } else {
        echo "⚠️ Please fill all fields.";
    }
}
