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
    <h1>Patient Feedback</h1>
  </div>

  <section class="section">
    <div class="card">
      <div class="card-body pt-3">
        <table class="table table-bordered">
          <thead>
            <tr>
              <th>Feedback ID</th>
              <th>Patient</th>
              <th>Message</th>
              <th>Rating</th>
              <th>Submitted</th>
            </tr>
          </thead>
          <tbody>
          <?php
            $sql = "SELECT f.*, u.full_name FROM feedback f
                    JOIN users u ON f.patient_id = u.id
                    ORDER BY f.submitted_at DESC";
            $res = mysqli_query($conn, $sql);
            while ($row = mysqli_fetch_assoc($res)) {
              echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['full_name']}</td>
                <td>{$row['message']}</td>
                <td>{$row['rating']} ⭐</td>
                <td>{$row['submitted_at']}</td>
              </tr>";
            }
          ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</main>