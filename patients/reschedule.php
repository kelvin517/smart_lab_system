<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit;
}

$booking_id = $_GET['id'] ?? null;

if (!$booking_id) {
    die("Booking ID missing.");
}

// Fetch current booking
$query = $conn->prepare("SELECT preferred_date FROM bookings WHERE id = ? AND patient_id = ?");
$query->bind_param("ii", $booking_id, $_SESSION['patient_id']);
$query->execute();
$result = $query->get_result();
$booking = $result->fetch_assoc();
$query->close();

if (!$booking) {
    die("Booking not found.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_date = $_POST['new_date'];

    $update = $conn->prepare("UPDATE bookings SET preferred_date = ? WHERE id = ? AND patient_id = ?");
    $update->bind_param("sii", $new_date, $booking_id, $_SESSION['patient_id']);

    if ($update->execute()) {
        header("Location: dashboard.php?rescheduled=1");
        exit;
    } else {
        $error = "❌ Failed to reschedule.";
    }
}

// AI Suggestion Logic — Recommend least busy hours
$recommended_times = [];
$day = date('Y-m-d');
for ($h = 9; $h <= 17; $h++) {
    $start = "$day " . sprintf('%02d:00:00', $h);
    $end = "$day " . sprintf('%02d:59:59', $h);

    $q = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE preferred_date BETWEEN '$start' AND '$end'");
    $count = $q ? $q->fetch_assoc()['total'] : 99;

    // Recommend if slot has less than 3 bookings
    if ($count < 3) {
        $recommended_times[] = "$day " . sprintf('%02d:00', $h);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reschedule Appointment</title>
  <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f2f2f2; }
    .recommendation-box {
      background: #fff3cd;
      padding: 10px;
      border-left: 4px solid #ffc107;
      margin-bottom: 15px;
    }
  </style>
</head>
<body>
<div class="container mt-5">
  <h3 class="mb-4">Reschedule Your Appointment</h3>

  <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <?php if (count($recommended_times)): ?>
    <div class="recommendation-box">
      <strong>AI Suggestion:</strong>
      <p>We recommend the following time slots with lower traffic:</p>
      <ul>
        <?php foreach ($recommended_times as $rt): ?>
          <li><a href="#" onclick="document.getElementById('new_date').value = '<?= date('Y-m-d\TH:i', strtotime($rt)) ?>'; return false;">
            <?= date('l, H:i A', strtotime($rt)) ?>
          </a></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post">
    <div class="mb-3">
      <label for="new_date" class="form-label">Select New Date & Time</label>
      <input type="datetime-local" name="new_date" id="new_date" class="form-control"
             value="<?= date('Y-m-d\TH:i', strtotime($booking['preferred_date'])) ?>" required>
    </div>
    <button type="submit" class="btn btn-primary">Reschedule</button>
    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
  </form>
</div>
</body>
</html>