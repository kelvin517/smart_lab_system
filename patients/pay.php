<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit;
}

$patient_id = $_SESSION['patient_id'];
$success = $error = '';

// Get the latest unpaid booking
$stmt = $conn->prepare("
    SELECT b.id AS booking_id, bl.id AS billing_id, bl.amount 
    FROM bookings b 
    JOIN billing bl ON bl.booking_id = b.id 
    WHERE b.patient_id = ? AND bl.status = 'Pending'
    ORDER BY b.created_at DESC LIMIT 1
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
$stmt->close();

if (!$booking) {
    $error = "No pending payments found.";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Simulate payment (real API integration goes here)
    $update = $conn->prepare("UPDATE billing SET status = 'Paid', updated_at = NOW() WHERE id = ?");
    $update->bind_param("i", $booking['billing_id']);
    if ($update->execute()) {
        $success = "Payment successful! Thank you.";
    } else {
        $error = "Failed to update payment.";
    }
    $update->close();
}
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<main id="main" class="main">
  <div class="pagetitle">
    <h1>Make Payment</h1>
  </div>

  <section class="section">
    <div class="card p-4">

      <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
      <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
      <?php endif; ?>

      <?php if (!empty($booking) && !$success): ?>
        <h5 class="mb-3">Test Booking ID: <?= $booking['booking_id'] ?></h5>
        <p>Amount Due: <strong>KES <?= number_format($booking['amount'], 2) ?></strong></p>

        <form method="POST">
          <button type="submit" class="btn btn-success">Simulate Payment</button>
          <a href="book_test.php" class="btn btn-secondary ms-2">Back</a>
        </form>
      <?php endif; ?>

    </div>
  </section>
</main>