<?php
session_start();

// ✅ Error handling setup
ini_set('log_errors', 1);
ini_set('display_errors', 0); // hide errors from browser (production-safe)
ini_set('error_log', __DIR__ . '/../logs/error_log.txt');
error_reporting(E_ALL);

// ✅ Ensure logs directory exists
$logDir = __DIR__ . '/../logs';
if (!file_exists($logDir)) {
    mkdir($logDir, 0777, true);
}

include '../config/db.php';

// ✅ Check DB connection
if (!$conn) {
    error_log("Database connection failed: " . mysqli_connect_error());
    die("<p style='color:red; text-align:center;'>Database connection error. Please contact admin.</p>");
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // ✅ Secure prepared statement
    $sql = "SELECT patient_id, full_name, password FROM patients WHERE email = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        die("<p style='color:red; text-align:center;'>Internal server error. Please try again later.</p>");
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($patient_id, $full_name, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $_SESSION['patient_id'] = $patient_id;
            $_SESSION['patient_name'] = $full_name;

            if (file_exists('dashboard.php')) {
                header("Location: dashboard.php");
            } else {
                error_log("Redirect failed: dashboard.php missing for patient_id=$patient_id");
                echo "<p style='color:red; text-align:center;'>Dashboard file missing.</p>";
            }
            exit;
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "Email not found.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Patient Login - Smart Laboratory</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      margin: 0;
      padding: 0;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .login-container {
      background: white;
      border-radius: 15px;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
      overflow: hidden;
      width: 100%;
      max-width: 400px;
      margin: 20px;
    }
    .login-header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 40px 30px 30px;
      text-align: center;
    }
    .login-header h1 { font-size: 28px; font-weight: 300; margin: 0 0 10px 0; }
    .login-header h2 { font-size: 32px; font-weight: 700; margin: 0; letter-spacing: 1px; }
    .login-body { padding: 30px; }
    .form-group { margin-bottom: 20px; }
    .form-label { display: block; font-weight: 600; color: #333; margin-bottom: 8px; font-size: 14px; }
    .form-control {
      width: 100%;
      padding: 12px 15px;
      border: 2px solid #e1e5e9;
      border-radius: 8px;
      font-size: 14px;
      transition: all 0.3s;
      box-sizing: border-box;
    }
    .form-control:focus {
      border-color: #667eea;
      outline: none;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    .btn-login {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border: none;
      padding: 12px;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 600;
      width: 100%;
      cursor: pointer;
      transition: all 0.3s;
      margin-top: 10px;
    }
    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 7px 14px rgba(102, 126, 234, 0.3);
    }
    .forgot-password { text-align: center; margin-top: 20px; }
    .forgot-password a { color: #667eea; text-decoration: none; font-size: 14px; font-weight: 500; }
    .forgot-password a:hover { text-decoration: underline; }
    .alert { padding: 10px 15px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
    .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
  </style>
</head>
<body>

<div class="login-container">
  <div class="login-header">
    <h1>Welcome</h1>
    <h2>LOGIN</h2>
  </div>

  <div class="login-body">
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
      </div>

      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
      </div>

      <button type="submit" class="btn-login">Login</button>

      <div class="forgot-password">
        <a href="forgot_password.php">Forgot Password?</a>
      </div>
    </form>
  </div>
</div>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
