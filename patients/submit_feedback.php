<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit;
}

$patient_id = $_SESSION['patient_id'];

// Handle POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $rating = intval($_POST['rating']);
    $comments = trim($_POST['comments']);

    if ($rating < 1 || $rating > 5) {
        $_SESSION['feedback_error'] = "Invalid rating value. Must be between 1 and 5.";
        header("Location: dashboard.php");
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO feedback (patient_id, rating, comments) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $patient_id, $rating, $comments);

    if ($stmt->execute()) {
        $_SESSION['feedback_success'] = "✅ Thank you! Your feedback has been submitted.";
    } else {
        $_SESSION['feedback_error'] = "❌ Failed to submit feedback. Please try again.";
    }

    $stmt->close();
    header("Location: dashboard.php");
    exit;
} else {
    // If accessed directly without POST
    header("Location: dashboard.php");
    exit;
}
?>