<?php
session_start();
include '../config/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid test booking ID.");
}

$booking_id = $_GET['id'];
$message = "";

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $upload_dir = "../uploads/results/";
    $file = $_FILES['result_file'];
    $summary = trim($_POST['summary']);

    if ($file['error'] === 0) {
        $allowed_types = ['application/pdf', 'image/png', 'image/jpeg'];
        $file_type = mime_content_type($file['tmp_name']);

        if (in_array($file_type, $allowed_types)) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename = 'result_' . $booking_id . '_' . time() . '.' . $ext;
            $file_path = $upload_dir . $new_filename;

            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                // Save to DB
                $relative_path = "uploads/results/" . $new_filename;
                $stmt = $conn->prepare("UPDATE book_requests SET result = ?, result_date = NOW(), status = 'Completed' WHERE id = ?");
                $stmt->bind_param("si", $relative_path, $booking_id);
                $stmt->execute();

                $message = "Result uploaded successfully.";
            } else {
                $message = "Failed to move uploaded file.";
            }
        } else {
            $message = "Invalid file type. Only PDF, JPG, and PNG are allowed.";
        }
    } else {
        $message = "Please select a file to upload.";
    }
}

// Fetch booking info
$stmt = $conn->prepare("SELECT br.id, br.status, p.name AS patient_name, lt.test_name 
                        FROM book_requests br
                        JOIN patients p ON br.patient_id = p.id
                        JOIN lab_tests lt ON br.test_id = lt.id
                        WHERE br.id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Lab Result</title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h3>Upload Result for <?= htmlspecialchars($booking['patient_name']) ?> - <?= htmlspecialchars($booking['test_name']) ?></h3>

    <?php if ($message): ?>
        <div class="alert alert-info"><?= $message ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="result_file" class="form-label">Select Result File (PDF/Image):</label>
            <input type="file" class="form-control" name="result_file" id="result_file" required>
        </div>
        <div class="mb-3">
            <label for="summary" class="form-label">Optional Summary/Note:</label>
            <textarea class="form-control" name="summary" id="summary" rows="3" placeholder="(Optional) Include any observation notes."></textarea>
        </div>
        <button type="submit" class="btn btn-success">Upload Result</button>
        <a href="dashboard.php" class="btn btn-secondary">Back</a>
    </form>
</div>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
