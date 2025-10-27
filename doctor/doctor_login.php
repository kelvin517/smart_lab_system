<?php
session_start();
include 'config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        // Fetch from `users` where role is 'doctor'
        $stmt = $conn->prepare("SELECT id, full_name, password, must_change_password FROM users WHERE email = ? AND role = 'doctor'");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 1) {
                $stmt->bind_result($id, $full_name, $hashed_password, $must_change_password);
                $stmt->fetch();

                if (password_verify($password, $hashed_password)) {
                    $_SESSION['doctor_id'] = $id;
                    $_SESSION['doctor_name'] = $full_name;

                    if ($must_change_password) {
                        header("Location: change_password.php?first_time=1");
                    } else {
                        header("Location: dashboard.php");
                    }
                    exit;
                } else {
                    $error = "Incorrect password.";
                }
            } else {
                $error = "No doctor account found with that email.";
            }

            $stmt->close();
        } else {
            $error = "Database error: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Doctor Login - ProHealth Medical Center</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="../assets/img/favicon.png" rel="icon">
  <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
  <style>
    :root {
      --primary: #2a7de1;
      --primary-dark: #1e5fb3;
      --secondary: #f8f9fa;
      --accent: #34c759;
      --text-dark: #333;
      --text-light: #6c757d;
      --border: #dee2e6;
    }
    
    body {
      background-color: #f5f7fa;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      color: var(--text-dark);
    }
    
    .login-container {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    
    .login-card {
      background: white;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
      overflow: hidden;
      max-width: 1000px;
      width: 100%;
    }
    
    .login-left {
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      color: white;
      padding: 40px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    
    .login-right {
      padding: 40px;
    }
    
    .brand-logo {
      font-size: 28px;
      font-weight: 700;
      margin-bottom: 20px;
      color: white;
    }
    
    .brand-tagline {
      font-size: 18px;
      margin-bottom: 30px;
      opacity: 0.9;
    }
    
    .feature-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    
    .feature-list li {
      margin-bottom: 15px;
      display: flex;
      align-items: center;
    }
    
    .feature-list i {
      margin-right: 10px;
      font-size: 18px;
    }
    
    .login-header {
      text-align: center;
      margin-bottom: 30px;
    }
    
    .login-header h3 {
      color: var(--primary);
      font-weight: 700;
      margin-bottom: 10px;
    }
    
    .login-header p {
      color: var(--text-light);
    }
    
    .form-label {
      font-weight: 600;
      color: var(--text-dark);
      margin-bottom: 8px;
    }
    
    .form-control {
      padding: 12px 15px;
      border: 1px solid var(--border);
      border-radius: 8px;
      transition: all 0.3s;
    }
    
    .form-control:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 0.2rem rgba(42, 125, 225, 0.25);
    }
    
    .btn-login {
      background-color: var(--primary);
      border: none;
      color: white;
      padding: 12px;
      border-radius: 8px;
      font-weight: 600;
      transition: all 0.3s;
    }
    
    .btn-login:hover {
      background-color: var(--primary-dark);
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(42, 125, 225, 0.3);
    }
    
    .forgot-password {
      color: var(--primary);
      text-decoration: none;
      transition: color 0.3s;
    }
    
    .forgot-password:hover {
      color: var(--primary-dark);
      text-decoration: underline;
    }
    
    .footer {
      margin-top: 30px;
      text-align: center;
      color: var(--text-light);
      font-size: 14px;
    }
    
    .contact-info {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      margin-top: 30px;
      padding-top: 20px;
      border-top: 1px solid var(--border);
    }
    
    .contact-item {
      flex: 1;
      min-width: 150px;
      margin-bottom: 15px;
    }
    
    .contact-item h6 {
      font-size: 14px;
      font-weight: 600;
      margin-bottom: 5px;
    }
    
    .contact-item p {
      font-size: 13px;
      margin: 0;
      color: var(--text-light);
    }
    
    @media (max-width: 768px) {
      .login-left {
        padding: 30px 20px;
      }
      
      .login-right {
        padding: 30px 20px;
      }
      
      .contact-info {
        flex-direction: column;
      }
    }
  </style>
</head>
<body>

<div class="login-container">
  <div class="login-card">
    <div class="row g-0">
      <!-- Left Side - Branding and Info -->
      <div class="col-lg-6 login-left">
        <div class="brand-logo">ProHealth</div>
        <p class="brand-tagline">Medical & Healthcare Center</p>
        <h2 class="mb-4">Don't Let Your Health Take a Backseat!</h2>
        <p class="mb-4">Access your professional dashboard to manage patient appointments, medical records, and healthcare services.</p>
        
        <ul class="feature-list">
          <li><i class="bi bi-calendar-check"></i> Manage Appointments</li>
          <li><i class="bi bi-file-medical"></i> Access Patient Records</li>
          <li><i class="bi bi-shield-check"></i> Secure & Private</li>
          <li><i class="bi bi-graph-up"></i> Track Medical Analytics</li>
        </ul>
        
        <div class="mt-4">
          <h5>Contact Information</h5>
          <div class="contact-info">
            <div class="contact-item">
              <h6>Phone</h6>
              <p>123-496-7890</p>
            </div>
            <div class="contact-item">
              <h6>Email</h6>
              <p>hellocontact@englishhealth.com</p>
            </div>
            <div class="contact-item">
              <h6>Location</h6>
              <p>823 Anywhere St., Amy City, 12545</p>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Right Side - Login Form -->
      <div class="col-lg-6 login-right">
        <div class="login-header">
          <img src="../assets/img/avatar-doctor.png" alt="Doctor Avatar" class="rounded-circle mb-3" width="80">
          <h3>Doctor Login</h3>
          <p>Enter your credentials to access your dashboard</p>
        </div>

        <?php if ($error): ?>
          <div class="alert alert-danger text-center"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" class="needs-validation" novalidate>
          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
          </div>

          <div class="mb-4">
            <label for="password" class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>

          <div class="d-grid mb-3">
            <button class="btn btn-login" type="submit">Login</button>
          </div>

          <div class="text-center">
            <a href="forgot_password.php" class="forgot-password">Forgot Password?</a>
          </div>
        </form>
        
        <div class="footer">
          <p>Copyright © 2025 kelvin.</p>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>