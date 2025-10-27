<?php
session_start();
if (!isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Change Password - Smart Laboratory</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">

  <style>
    .strength-bar {
      height: 6px;
      width: 100%;
      background-color: #e0e0e0;
      margin-top: 4px;
    }

    .strength-bar-inner {
      height: 100%;
      width: 0%;
      transition: width 0.3s;
    }

    .weak { background-color: red; }
    .medium { background-color: orange; }
    .strong { background-color: green; }
  </style>

  <script>
    function togglePassword(fieldId, iconId) {
      const field = document.getElementById(fieldId);
      const icon = document.getElementById(iconId);
      if (field.type === "password") {
        field.type = "text";
        icon.classList.remove("bi-eye");
        icon.classList.add("bi-eye-slash");
      } else {
        field.type = "password";
        icon.classList.remove("bi-eye-slash");
        icon.classList.add("bi-eye");
      }
    }

    function validateForm() {
      const newPass = document.getElementById("new_password").value;
      const confirmPass = document.getElementById("confirm_password").value;
      const errorBox = document.getElementById("errorBox");
      const pattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#^])[A-Za-z\d@$!%*?&#^]{8,}$/;

      if (!pattern.test(newPass)) {
        errorBox.innerText = "❌ Password must be at least 8 characters long, contain uppercase, lowercase, digit, and special character.";
        return false;
      }

      if (newPass !== confirmPass) {
        errorBox.innerText = "❌ Passwords do not match.";
        return false;
      }

      errorBox.innerText = "";
      return true;
    }

    function checkStrength(password) {
      const bar = document.getElementById("strengthBar");
      const strength = document.getElementById("strengthText");
      let value = 0;

      if (password.length >= 8) value++;
      if (/[A-Z]/.test(password)) value++;
      if (/[a-z]/.test(password)) value++;
      if (/\d/.test(password)) value++;
      if (/[@$!%*?&#^]/.test(password)) value++;

      bar.className = "strength-bar-inner";
      if (value <= 2) {
        bar.style.width = "30%";
        bar.classList.add("weak");
        strength.innerText = "Weak";
      } else if (value <= 4) {
        bar.style.width = "60%";
        bar.classList.add("medium");
        strength.innerText = "Medium";
      } else {
        bar.style.width = "100%";
        bar.classList.add("strong");
        strength.innerText = "Strong";
      }
    }
  </script>
</head>
<body>

<div class="container mt-5" style="max-width: 500px;">
  <div class="card">
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0">🔐 Change Password</h5>
    </div>
    <div class="card-body">
      <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
      <?php endif; ?>

      <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
      <?php endif; ?>

      <form action="change_password_action.php" method="post" onsubmit="return validateForm()">
        <div class="mb-3">
          <label>New Password</label>
          <div class="input-group">
            <input type="password" name="new_password" id="new_password" class="form-control" required onkeyup="checkStrength(this.value)">
            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password', 'eye1')">
              <i class="bi bi-eye" id="eye1"></i>
            </button>
          </div>
          <div class="strength-bar">
            <div id="strengthBar" class="strength-bar-inner"></div>
          </div>
          <small id="strengthText" class="text-muted"></small>
        </div>

        <div class="mb-3">
          <label>Confirm Password</label>
          <div class="input-group">
            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password', 'eye2')">
              <i class="bi bi-eye" id="eye2"></i>
            </button>
          </div>
        </div>

        <div id="errorBox" class="text-danger mb-2 fw-bold"></div>

        <button type="submit" class="btn btn-success w-100">Update Password</button>
      </form>
    </div>
  </div>
</div>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>