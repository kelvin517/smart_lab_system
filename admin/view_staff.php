<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../admin_login.php");
    exit();
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<main id="main" class="main">
  <div class="pagetitle">
    <h1>Manage Staff</h1>
    <a href="add_staff.php" class="btn btn-primary btn-sm">+ Add Staff</a>
  </div>

  <section class="section">
    <div class="card">
      <div class="card-body pt-3">
        <table class="table table-bordered">
          <thead>
            <tr>
              <th>ID</th>
              <th>Full Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Role</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
          <?php
            $staff = mysqli_query($conn, "SELECT * FROM users WHERE role IN ('doctor', 'technician')");
            while ($row = mysqli_fetch_assoc($staff)) {
              echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['full_name']}</td>
                <td>{$row['email']}</td>
                <td>{$row['phone']}</td>
                <td>{$row['role']}</td>
                <td><a href='edit_staff.php?id={$row['id']}' class='btn btn-sm btn-warning'>Edit</a></td>
              </tr>";
            }
          ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</main>