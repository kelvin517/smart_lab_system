<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../admin_login.php");
    exit();
}

$id = $_GET['id'];
$result = mysqli_query($conn, "SELECT * FROM users WHERE id = $id");
$row = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];

    mysqli_query($conn, "UPDATE users SET full_name='$name', email='$email', phone='$phone', role='$role' WHERE id=$id");

    header("Location: view_staff.php");
    exit();
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<main id="main" class="main">
  <div class="pagetitle"><h1>Edit Staff</h1></div>
  <section class="section">
    <form method="POST" class="form-control">
      <label>Full Name</label>
      <input type="text" name="full_name" value="<?= $row['full_name'] ?>" class="form-control mb-2">

      <label>Email</label>
      <input type="email" name="email" value="<?= $row['email'] ?>" class="form-control mb-2">

      <label>Phone</label>
      <input type="text" name="phone" value="<?= $row['phone'] ?>" class="form-control mb-2">

      <label>Role</label>
      <select name="role" class="form-control mb-3">
        <option value="doctor" <?= $row['role'] == 'doctor' ? 'selected' : '' ?>>Doctor</option>
        <option value="technician" <?= $row['role'] == 'technician' ? 'selected' : '' ?>>Technician</option>
      </select>

      <button type="submit" class="btn btn-success">Update Staff</button>
    </form>
  </section>
</main>