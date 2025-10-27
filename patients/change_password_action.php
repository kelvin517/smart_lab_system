<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit;
}

$patient_id = $_SESSION['patient_id'];
$new_password = $_POST['new_password'];
$confirm_password = $_POST['confirm_password'];

if ($new_password !== $confirm_password) {
    $_SESSION['error'] = "Passwords do not match.";
    header("Location: change_password.php");
    exit;
}

// Server-side strength validation
$pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#^])[A-Za-z\d@$!%*?&#^]{8,}$/';
if (!preg_match($pattern, $new_password)) {
    $_SESSION['error'] = "Password must be 8+ characters and include uppercase, lowercase, digit, special character.";
    header("Location: change_password.php");
    exit;
}

$hashed = password_hash($new_password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE patients SET password = ? WHERE id = ?");
$stmt->bind_param("si", $hashed, $patient_id);
$stmt->execute();

$_SESSION['success'] = "Password updated successfully!";
header("Location: change_password.php");
exit;