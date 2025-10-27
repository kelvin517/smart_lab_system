<?php
// patients/forgot_password.php
session_start();
require_once '../config/db.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    // Check if email exists in patients table
    $stmt = $conn->prepare("SELECT id FROM patients WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        // For now, we simulate sending a reset link or redirect
        $_SESSION['reset_email'] = $email;
        header("Location: reset_password.php");
        exit;
    } else {
        $error = "Email not found. Please enter a registered email.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forgot Password - Smart Laboratory</title>
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
            <h5 class="card-title pb-0 fs-4">Forgot Password</h5>
            <p class="small">Enter your email to reset your password</p>
          </div>

          <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
          <?php endif; ?>

          <form method="POST" class="row g-3 needs-validation" novalidate>
            <div class="col-12">
              <label for="email" class="form-label">Registered Email</label>
              <input type="email" name="email" class="form-control" required>
              <div class="invalid-feedback">Please enter your email.</div>
            </div>

            <div class="col-12">
              <button class="btn btn-primary w-100" type="submit">Send Reset Link</button>
            </div>

            <div class="col-12 text-center">
              <p class="small mb-0"><a href="login.php">Back to Login</a></p>
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