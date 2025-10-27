<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = md5($_POST['password']);

    $sql = "SELECT * FROM admins WHERE email = '$email' AND password = '$password'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows == 1) {
        $_SESSION['admin_id'] = $result->fetch_assoc()['id'];
        header('Location:dashboard.php');
        exit;
    } else {
        echo "<script>alert('Invalid credentials');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | Smart Laboratory</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <style>
    body {
      margin: 0;
      font-family: 'Poppins', sans-serif;
      background-color: #f5f7fb;
      display: flex;
      height: 100vh;
    }
    .left-panel {
      background: #ffffff;
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      box-shadow: 3px 0 10px rgba(0,0,0,0.05);
    }
    .left-panel img { width: 120px; margin-bottom: 15px; }
    .left-panel h2 { font-weight: 700; color: #003366; }
    .left-panel p { color: #666; font-size: 0.9rem; margin-top: 10px; }
    .right-panel {
      flex: 1.2;
      background: #0d47a1;
      display: flex;
      justify-content: center;
      align-items: center;
      color: white;
    }
    .login-box {
      background: white;
      color: #333;
      border-radius: 10px;
      width: 400px;
      padding: 40px;
      box-shadow: 0 5px 20px rgba(0,0,0,0.2);
    }
    .login-box h3 { text-align: center; color: #003366; margin-bottom: 25px; font-weight: 700; }
    .form-label { font-weight: 500; color: #003366; }
    .form-control { border-radius: 5px; padding: 10px; }
    .btn-login {
      background: #0d47a1;
      color: white;
      font-weight: 600;
      width: 100%;
      border-radius: 6px;
      padding: 10px;
      transition: 0.3s;
    }
    .btn-login:hover { background: #1565c0; }
    .login-footer { text-align: center; font-size: 0.9rem; margin-top: 15px; }
    .login-footer a { color: #0d47a1; text-decoration: none; font-weight: 500; }
    .login-footer a:hover { text-decoration: underline; }
    @media (max-width: 768px) {
      body { flex-direction: column; }
      .left-panel, .right-panel { flex: none; width: 100%; height: auto; }
    }
  </style>
</head>
<body>
  <div class="left-panel">
    <img src="../assets/img/logo.jpeg" alt="SmartLab Logo">
    <h2>Smart Laboratory System</h2>
    <p>Modern, Secure, and Intelligent Health Diagnostics</p>
  </div>

  <div class="right-panel">
    <div class="login-box">
      <h3><i class="fa-solid fa-user-shield me-2"></i>Admin Login</h3>
      <form method="POST">
        <div class="mb-3">
          <label class="form-label">Your Email</label>
          <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
        </div>
        <div class="mb-3 form-check">
          <input type="checkbox" class="form-check-input" id="remember">
          <label class="form-check-label" for="remember">Remember me</label>
        </div>
        <button type="submit" class="btn btn-login">SIGN IN</button>
      </form>
      <div class="login-footer">
        <a href="../index.php">← Back to Home</a> |
        <a href="#">Recover Password</a>
      </div>
    </div>
  </div>
</body>
</html>
