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
    <h1>Lab Bookings</h1>
  </div>

  <section class="section">
    <div class="card">
      <div class="card-body pt-3">
        <table class="table table-bordered">
          <thead>
            <tr>
              <th>Booking ID</th>
              <th>Patient Name</th>
              <th>Test Type</th>
              <th>Preferred Date</th>
              <th>Status</th>
              <th>Created At</th>
              <th>Result File</th>
            </tr>
          </thead>
          <tbody>
          <?php
            $sql = "SELECT b.id, p.full_name, b.test_type, b.preferred_date, b.status, b.created_at, b.result_file 
                    FROM bookings b
                    JOIN patients p ON b.patient_id = p.id
                    ORDER BY b.id DESC";
            $res = mysqli_query($conn, $sql);
            while ($row = mysqli_fetch_assoc($res)) {
              echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['full_name']}</td>
                <td>{$row['test_type']}</td>
                <td>{$row['preferred_date']}</td>
                <td>{$row['status']}</td>
                <td>{$row['created_at']}</td>";
              
              if (!empty($row['result_file'])) {
                echo "<td><a href='../uploads/{$row['result_file']}' target='_blank'>Download</a></td>";
              } else {
                echo "<td><em>No file</em></td>";
              }

              echo "</tr>";
            }
          ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</main>