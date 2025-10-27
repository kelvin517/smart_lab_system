<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['doctor_id'])) {
    header("Location: doctor_login.php");
    exit;
}
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<main id="main" class="main">

  <div class="pagetitle">
    <h1>Patients</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Patients</li>
      </ol>
    </nav>
  </div>

  <section class="section">
    <div class="card">
      <div class="card-body pt-4">
        <h5 class="card-title">Registered Patients</h5>

        <table class="table datatable">
          <thead>
            <tr>
              <th>#</th>
              <th>Full Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Gender</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $stmt = $conn->query("SELECT id, full_name, email, phone, gender FROM patients ORDER BY full_name ASC");
            $count = 1;
            while ($row = $stmt->fetch_assoc()):
            ?>
              <tr>
                <td><?= $count++ ?></td>
                <td><?= htmlspecialchars($row['full_name']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= htmlspecialchars($row['phone']) ?></td>
                <td><?= htmlspecialchars($row['gender']) ?></td>
                <td>
                  <a href="patient_history.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary">
                    <i class="bi bi-eye"></i> View History
                  </a>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>

      </div>
    </div>
  </section>

</main>