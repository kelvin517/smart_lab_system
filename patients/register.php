<?php
include '../config/db.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $gender = $_POST['gender'];
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);

    $check = $conn->prepare("SELECT id FROM patients WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $error = "Email is already registered.";
    } else {
        $stmt = $conn->prepare("INSERT INTO patients (full_name, email, phone, gender, password) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $email, $phone, $gender, $password);

        if ($stmt->execute()) {
            $success = "Registration successful. <a href='login.php'>Click here to login</a>.";
        } else {
            $error = "Error: " . $stmt->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Patient Registration - Smart Laboratory</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
  <style>
    body {
      background-color: #002147;
      font-family: 'Segoe UI', sans-serif;
    }
    .register-container {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
    }
    .register-box {
      background: #003366;
      padding: 40px;
      border-radius: 10px;
      width: 100%;
      max-width: 400px;
    }
    .form-control {
      background: transparent;
      border: none;
      border-bottom: 2px solid #fff;
      border-radius: 0;
      color: white;
    }
    .form-control::placeholder {
      color: #ccc;
    }
    .form-control:focus {
      background: transparent;
      box-shadow: none;
      border-color: #66bfff;
    }
    .register-box h2 {
      font-weight: bold;
      text-align: center;
      margin-bottom: 30px;
      font-family: 'Georgia', serif;
    }
    .btn-register {
      background: #007bff;
      border: none;
    }
    .right-img {
      background: #002147;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .right-img img {
      max-width: 100%;
      height: auto;
    }
    label {
      margin-top: 10px;
    }
  </style>
</head>
<body>

<div class="container-fluid register-container">
  <div class="row w-100">
    <!-- Left: Registration Form -->
    <div class="col-md-6 d-flex align-items-center justify-content-center">
      <div class="register-box">
        <div class="text-center mb-3">
          <img src="../assets/img/avatar.png" class="rounded-circle" width="80" alt="Avatar">
        </div>
        <h2>Registration</h2>

        <?php if ($success): ?>
          <div class="alert alert-success text-center"><?= $success ?></div>
        <?php elseif ($error): ?>
          <div class="alert alert-danger text-center"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" action="">
          <div class="mb-2">
            <label>Full Name</label>
            <input type="text" name="full_name" class="form-control" placeholder="Enter your full name" required>
          </div>

          <div class="mb-2">
            <label>Email</label>
            <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
          </div>

          <div class="mb-2">
            <label>Phone</label>
            <input type="text" name="phone" class="form-control" placeholder="07XXXXXXXX" required>
          </div>

          <div class="mb-2">
            <label>Gender</label>
            <select name="gender" class="form-control" required>
              <option value="">-- Select Gender --</option>
              <option>Male</option>
              <option>Female</option>
              <option>Other</option>
            </select>
          </div>

          <div class="mb-4">
            <label>Password</label>
            <input type="password" name="password" class="form-control" placeholder="Enter a strong password" required>
          </div>

          <div class="d-grid">
            <button type="submit" class="btn btn-register text-white">Registration</button>
          </div>

          <div class="text-center mt-3">
            Click here <a href="login.php" class="text-info"><strong>Login</strong></a>
          </div>
        </form>
      </div>
    </div>

    <!-- Right: Illustration -->
    <div class="col-md-6 right-img d-none d-md-flex">
      <img src="../assets/img/4e16d7162b915b8de14a767f1d15b26d520511d6.png" alt="Registration Illustration">
    </div>
  </div>
</div>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>