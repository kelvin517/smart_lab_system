<?php
require_once '../config/db.php';

$token = $_GET['token'] ?? '';
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'], $_POST['confirm_password'], $_POST['token'])) {
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    $token = $_POST['token'];

    if ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $stmt = $conn->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->bind_result($email);
        if ($stmt->fetch()) {
            $stmt->close();
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE doctors SET password = ? WHERE email = ?");
            $update->bind_param("ss", $hashed, $email);
            if ($update->execute()) {
                $conn->query("DELETE FROM password_resets WHERE email = '$email'");
                $success = "Password reset successful. You can now <a href='doctor_login.php'>login</a>.";
            } else {
                $error = "Failed to update password.";
            }
        } else {
            $error = "Invalid or expired token.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password - Smart Lab</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- NiceAdmin CSS -->
  <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>

<main>
  <div class="container">

    <section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-lg-5 col-md-6 d-flex flex-column align-items-center justify-content-center">

            <div class="card mb-3 w-100">

              <div class="card-body">
                <div class="pt-4 pb-2">
                  <h5 class="card-title text-center pb-0 fs-4">Set New Password</h5>
                  <p class="text-center small">Please enter your new password below</p>
                </div>

                <?php if ($success): ?>
                  <div class="alert alert-success"><?= $success ?></div>
                <?php elseif ($error): ?>
                  <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST" class="row g-3 needs-validation" novalidate>
                  <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                  <div class="col-12">
                    <label class="form-label">New Password</label>
                    <input type="password" name="password" class="form-control" required minlength="6">
                    <div class="invalid-feedback">Password must be at least 6 characters.</div>
                  </div>

                  <div class="col-12">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                    <div class="invalid-feedback">Please confirm your password.</div>
                  </div>

                  <div class="col-12 d-grid">
                    <button class="btn btn-primary w-100" type="submit">Reset Password</button>
                  </div>
                </form>
              </div>
            </div>

            <div class="credits text-center">
              &copy; <?= date('Y') ?> <strong>Smart Laboratory System</strong>. All Rights Reserved.
            </div>

          </div>
        </div>
      </div>
    </section>

  </div>
</main>

<!-- Vendor JS -->
<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
  // Bootstrap validation
  (() => {
    'use strict'
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
      form.addEventListener('submit', event => {
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      });
    });
  })();
</script>
</body>
</html>