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
    <h1>Billing Management</h1>
  </div>

  <section class="section">
    <div class="card">
      <div class="card-body pt-3">
        <table class="table table-bordered">
          <thead>
            <tr>
              <th>Billing ID</th>
              <th>Patient</th>
              <th>Test Type</th>
              <th>Amount</th>
              <th>Status</th>
              <th>Created</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
          <?php
            $sql = "SELECT b.id AS bill_id, p.full_name, bk.test_type, b.amount, b.status, b.created_at
                    FROM billing b
                    JOIN bookings bk ON b.booking_id = bk.id
                    JOIN patients p ON bk.patient_id = p.id
                    ORDER BY b.created_at DESC";

            $res = mysqli_query($conn, $sql);
            while ($row = mysqli_fetch_assoc($res)) {
              echo "<tr>
                <td>{$row['bill_id']}</td>
                <td>{$row['full_name']}</td>
                <td>{$row['test_type']}</td>
                <td>KES {$row['amount']}</td>
                <td>{$row['status']}</td>
                <td>{$row['created_at']}</td>
                <td><a href='generate_invoice.php?id={$row['bill_id']}' class='btn btn-sm btn-success'>Invoice</a></td>
              </tr>";
            }
          ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</main>
