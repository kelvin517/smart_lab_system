<?php
session_start();
include '../config/db.php';

// Secret key
$allowed_key = "kiptoo";

// ✅ Check if correct key is passed in URL
if (!isset($_GET['key']) || $_GET['key'] !== $allowed_key) {
    die("Access denied. Invalid or missing key.");
}

$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Check if admin already exists
    $stmt = $conn->prepare("SELECT id FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $error = "Admin username already exists.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $insert = $conn->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
        $insert->bind_param("ss", $username, $hashed_password);
        if ($insert->execute()) {
            $success = "Admin registered successfully!";
        } else {
            $error = "Failed to register admin.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register Admin</title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h3 class="mb-4">Register Admin Account</h3>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label for="username" class="form-label">Admin Username</label>
            <input type="text" name="username" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Admin Password</label>
            <input type="password" name="password" class="form-control" required minlength="6">
        </div>

        <button type="submit" class="btn btn-primary">Register Admin</button>
        <a href="admin_login.php" class="btn btn-secondary">Back to Login</a>
    </form>
</div>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
