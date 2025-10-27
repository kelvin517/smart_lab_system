<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['technician_id'])) {
    header("Location: technician_login.php");
    exit;
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<main id="main" class="main">
  <div class="pagetitle">
    <h1>All Lab Test Bookings</h1>
  </div>

  <section class="section">
    <div class="card">
      <div class="card-body pt-3">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>Patient Name</th>
              <th>Test Type</th>
              <th>Status</th>
              <th>Preferred Date</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $query = $conn->query("
              SELECT b.*, p.full_name 
              FROM bookings b
              JOIN patients p ON b.patient_id = p.id
              ORDER BY b.created_at DESC
            ");

            while ($row = $query->fetch_assoc()):
            ?>
              <tr>
                <td><?= htmlspecialchars($row['full_name']) ?></td>
                <td><?= htmlspecialchars($row['test_type']) ?></td>
                <td><?= htmlspecialchars($row['status']) ?></td>
                <td><?= htmlspecialchars($row['preferred_date']) ?></td>
                <td>
                  <a href="view_details.php?id=<?= $row['id'] ?>" class="btn btn-info btn-sm">View</a>
                  <a href="contact_patient.php?id=<?= $row['patient_id'] ?>" class="btn btn-secondary btn-sm">Contact</a>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</main>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>