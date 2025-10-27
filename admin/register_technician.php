<?php
session_start();
include '../config/db.php';

// Optional: Check if user is an admin
 if (!isset($_SESSION['admin_id'])) {
     header("Location: ../admin_login.php");
     exit;
 }

$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Check if username already exists
    $stmt = $conn->prepare("SELECT id FROM technicians WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $error = "Technician username already exists.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $insert = $conn->prepare("INSERT INTO technicians (username, password) VALUES (?, ?)");
        $insert->bind_param("ss", $username, $hashed_password);
        if ($insert->execute()) {
            $success = "Technician registered successfully!";
        } else {
            $error = "Failed to register technician.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register Lab Technician</title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h3 class="mb-4">Register New Lab Technician</h3>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label for="username" class="form-label">Technician Username</label>
            <input type="text" name="username" id="username" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Technician Password</label>
            <input type="password" name="password" id="password" class="form-control" required minlength="6">
        </div>

        <button type="submit" class="btn btn-primary">Register Technician</button>
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </form>
</div>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
