<?php
session_start();
include '../config/db.php';

// Check if booking ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid test booking ID.");
}

$booking_id = $_GET['id'];
$status_message = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_status = $_POST['status'];

    // Validate status
    $valid_statuses = ['Pending', 'Received', 'Testing', 'Completed'];
    if (!in_array($new_status, $valid_statuses)) {
        $status_message = "Invalid status selected.";
    } else {
        $stmt = $conn->prepare("UPDATE book_requests SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $booking_id);
        if ($stmt->execute()) {
            $status_message = "Status updated successfully!";
        } else {
            $status_message = "Error updating status.";
        }
        $stmt->close();
    }
}

// Get current test booking info
$stmt = $conn->prepare("SELECT br.id, br.status, br.booking_date, p.name AS patient_name, lt.test_name 
                        FROM book_requests br
                        JOIN patients p ON br.patient_id = p.id
                        JOIN lab_tests lt ON br.test_id = lt.id
                        WHERE br.id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
$stmt->close();

if (!$booking) {
    die("Booking not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Test Status</title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h3>Update Status for: <?= htmlspecialchars($booking['patient_name']) ?> (<?= htmlspecialchars($booking['test_name']) ?>)</h3>
    <p><strong>Booking Date:</strong> <?= htmlspecialchars($booking['booking_date']) ?></p>

    <?php if ($status_message): ?>
        <div class="alert alert-info"><?= $status_message ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="form-group mb-3">
            <label for="status">Select New Status:</label>
            <select name="status" id="status" class="form-control" required>
                <option value="">-- Select Status --</option>
                <?php
                $statuses = ['Pending', 'Received', 'Testing', 'Completed'];
                foreach ($statuses as $status) {
                    $selected = ($booking['status'] === $status) ? 'selected' : '';
                    echo "<option value='$status' $selected>$status</option>";
                }
                ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Update Status</button>
        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
