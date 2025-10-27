<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['technician_id'])) {
    header("Location: technician_login.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("Patient ID missing.");
}

$patient_id = intval($_GET['id']);
$query = $conn->prepare("SELECT full_name, email FROM patients WHERE id = ?");
$query->bind_param("i", $patient_id);
$query->execute();
$res = $query->get_result();
$patient = $res->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = $_POST['subject'];
    $message = $_POST['message'];
    $to = $patient['email'];
    $headers = "From: lab@smartclinic.com";

    // Send email (in real use, configure SMTP in php.ini or use PHPMailer)
    if (mail($to, $subject, $message, $headers)) {
        $status = "Message sent to {$patient['full_name']}!";
    } else {
        $status = "Failed to send email. Check mail server.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Contact Patient</title>
  <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">
  <h3>Contact Patient</h3>
  <p><strong>To:</strong> <?= $patient['full_name'] ?> (<?= $patient['email'] ?>)</p>

  <?php if (isset($status)): ?>
    <div class="alert alert-info"><?= $status ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="mb-3">
      <label>Subject:</label>
      <input type="text" name="subject" class="form-control" required>
    </div>
    <div class="mb-3">
      <label>Message:</label>
      <textarea name="message" class="form-control" rows="5" required></textarea>
    </div>
    <button class="btn btn-primary">Send Email</button>
    <a href="technician_dashboard.php" class="btn btn-secondary">Back</a>
  </form>
</body>
</html>