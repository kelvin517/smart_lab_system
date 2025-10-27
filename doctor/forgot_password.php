<?php
include '../config/db.php';
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $token = bin2hex(random_bytes(16));
    $expires = date("Y-m-d H:i:s", strtotime("+30 minutes"));

    // Check doctor by email and get full name
    $check = $conn->prepare("SELECT id, full_name FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $check->bind_result($doctor_id, $doctor_name);
        $check->fetch();

        // Save token to database
        $stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $email, $token, $expires);
        $stmt->execute();

        // Build reset email
        $reset_link = "http://localhost/smart-laboratory/doctor/reset_password_form.php?token=$token";
        $subject = "Password Reset Request for Dr. $doctor_name";
        $message = "Hello Dr. $doctor_name,\n\nWe received a request to reset your password.\n\n";
        $message .= "Click the link below to reset your password:\n$reset_link\n\n";
        $message .= "Note: This link will expire in 30 minutes.\n\nRegards,\nSmart Lab Team";

        // Send mail
        mail($email, $subject, $message, "From: no-reply@smartlab.com");

        // Save to log
        $log_msg = date("Y-m-d H:i:s") . " | Reset requested for Dr. $doctor_name ($email) | Token: $token | Expires: $expires\n";
        file_put_contents('../logs/reset_logs.txt', $log_msg, FILE_APPEND);

        $success = "Reset link sent to your email.";
    } else {
        $error = "No doctor account with that email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forgot Password - Smart Lab</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                  <h5 class="card-title text-center pb-0 fs-4">Forgot Your Password?</h5>
                  <p class="text-center small">Enter your registered email address</p>
                </div>

                <?php if ($success): ?>
                  <div class="alert alert-success"><?= $success ?></div>
                <?php elseif ($error): ?>
                  <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST" class="row g-3 needs-validation" novalidate>
                  <div class="col-12">
                    <label for="email" class="form-label">Your Email</label>
                    <input type="email" name="email" class="form-control" required placeholder="example@email.com">
                    <div class="invalid-feedback">Please enter a valid email address.</div>
                  </div>

                  <div class="col-12 d-grid">
                    <button class="btn btn-primary w-100" type="submit">Send Reset Link</button>
                  </div>

                  <div class="col-12 text-center">
                    <a href="doctor_login.php" class="small">Back to login</a>
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

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
  (function () {
    'use strict'
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
      form.addEventListener('submit', function (event) {
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    });
  })();
</script>
</body>
</html>
