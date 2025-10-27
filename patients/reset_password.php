<?php
// patients/reset_password.php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit;
}

$email = $_SESSION['reset_email'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE patients SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed, $email);

        if ($stmt->execute()) {
            unset($_SESSION['reset_email']);
            header("Location: login.php?reset=success");
            exit;
        } else {
            $error = "Failed to reset password. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password - Smart Laboratory</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="../assets/img/favicon.png" rel="icon">
  <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>

<main>
  <div class="container">
    <section class="section login min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
      <div class="card col-lg-6">
        <div class="card-body">
          <div class="pt-4 pb-2 text-center">
            <h5 class="card-title pb-0 fs-4">Reset Your Password</h5>
            <p class="small">For: <?= htmlspecialchars($email) ?></p>
          </div>

          <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
          <?php endif; ?>

          <form method="POST" class="row g-3 needs-validation" novalidate>
            <div class="col-12">
              <label for="password" class="form-label">New Password</label>
              <input type="password" name="password" class="form-control" required>
              <div class="form-text">Must be at least 6 characters long</div>
            </div>

            <div class="col-12">
              <label for="confirm_password" class="form-label">Confirm Password</label>
              <input type="password" name="confirm_password" class="form-control" required>
            </div>

            <div class="col-12">
              <button class="btn btn-success w-100" type="submit">Reset Password</button>
            </div>
          </form>

        </div>
      </div>
    </section>
  </div>
</main>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>