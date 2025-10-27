<?php
session_start();
include 'config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Ensure correct role and email match
    $stmt = $conn->prepare("SELECT id, full_name, password FROM users WHERE email = ? AND role = 'technician'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $full_name, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $_SESSION['technician_id'] = $id;
            $_SESSION['technician_username'] = $full_name;
            header("Location: technician_dashboard.php");
            exit;
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "Technician not found.";
    }
    $stmt->close();
}
?>

<!-- Technician Login Form -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Technician Login</title>
  <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
  <h3 class="mb-4">Lab Technician Login</h3>
  <?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>
  <form method="post">
    <div class="mb-3">
      <label>Email:</label>
      <input type="email" name="email" class="form-control" required>
    </div>
    <div class="mb-3">
      <label>Password:</label>
      <input type="password" name="password" class="form-control" required>
    </div>
    <button class="btn btn-primary w-100" type="submit">Login</button>
  </form>
</div>
</body>
</html>
