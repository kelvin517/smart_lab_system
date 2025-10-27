<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['doctor_id'])) {
    header("Location: doctor_login.php");
    exit;
}

$doctor_id = $_SESSION['doctor_id'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (strlen($new_password) < 8 || !preg_match('/[A-Z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
        $error = "Password must be at least 8 characters long and contain at least 1 uppercase letter and 1 number.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE doctors SET password = ?, must_change_password = 1 WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $doctor_id);

        if ($stmt->execute()) {
            $success = "Password changed successfully. Redirecting to dashboard...";
            header("refresh:3; url=dashboard.php");
        } else {
            $error = "Failed to update password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Change Password - Doctor</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- NiceAdmin Style -->
  <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">

  <style>
    body {
      background: url('../assets/img/26807.jpg') no-repeat center center fixed;
      background-size: cover;
    }
    .change-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(5px);
    }
  </style>
</head>
<body>

<div class="container">
  <section class="section min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
    <div class="col-lg-5 card p-4 change-card shadow">
      <h4 class="text-center mb-3">Change Your Password</h4>
      <p class="text-muted text-center small">This is your first login. Please update your password.</p>

      <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
      <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
      <?php endif; ?>

      <form method="POST" class="needs-validation" novalidate>
        <div class="mb-3">
          <label for="new_password" class="form-label">New Password</label>
          <input type="password" name="new_password" class="form-control" required minlength="8">
        </div>

        <div class="mb-3">
          <label for="confirm_password" class="form-label">Confirm Password</label>
          <input type="password" name="confirm_password" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary w-100">Update Password</button>
      </form>
    </div>
  </section>
</div>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>