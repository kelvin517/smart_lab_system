<?php
// ✅ Display errors for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/db.php';

// ✅ Redirect if doctor not logged in
if (!isset($_SESSION['doctor_id'])) {
    header("Location: doctor_login.php");
    exit;
}

// ✅ Fetch doctor details for navbar
$doctor_id = $_SESSION['doctor_id'];
$doctor_stmt = $conn->prepare("SELECT full_name, email FROM staff WHERE id = ?");
$doctor_stmt->bind_param("i", $doctor_id);
$doctor_stmt->execute();
$doctor = $doctor_stmt->get_result()->fetch_assoc();
$doctor_stmt->close();
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<!-- ======= Header ======= -->
<header id="header" class="header fixed-top d-flex align-items-center">

  <div class="d-flex align-items-center justify-content-between">
    <a href="dashboard.php" class="logo d-flex align-items-center">
      <img src="assets/img/preg.jpeg" alt="">
      <span class="d-none d-lg-block">SmartLab Doctor</span>
    </a>
    <i class="bi bi-list toggle-sidebar-btn"></i>
  </div>

  <nav class="header-nav ms-auto">
    <ul class="d-flex align-items-center">

      <li class="nav-item dropdown pe-3">
        <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
          <img src="../assets/img/profile-img.jpg" alt="Profile" class="rounded-circle">
          <span class="d-none d-md-block dropdown-toggle ps-2"><?= htmlspecialchars($doctor['full_name']) ?></span>
        </a>

        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile shadow">
          <li class="dropdown-header text-center">
            <h6><?= htmlspecialchars($doctor['full_name']) ?></h6>
            <span class="text-muted small">Doctor</span>
          </li>
          <li><hr class="dropdown-divider"></li>

          <li>
            <a class="dropdown-item d-flex align-items-center" href="profile.php">
              <i class="bi bi-person me-2 text-primary"></i> My Profile
            </a>
          </li>

          <li>
            <a class="dropdown-item d-flex align-items-center" href="change_password.php">
              <i class="bi bi-lock me-2 text-warning"></i> Change Password
            </a>
          </li>

          <li><hr class="dropdown-divider"></li>

          <li>
            <a class="dropdown-item d-flex align-items-center text-danger" href="logout.php">
              <i class="bi bi-box-arrow-right me-2"></i> Logout
            </a>
          </li>
        </ul>
      </li>
    </ul>
  </nav>
</header>
<!-- ======= End Header ======= -->

<main id="main" class="main">

  <div class="pagetitle">
    <h1>Patients Overview</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Patients</li>
      </ol>
    </nav>
  </div>

  <section class="section">
    <div class="card shadow-sm border-0">
      <div class="card-body pt-4">
        <h5 class="card-title text-primary mb-3">
          <i class="bi bi-people"></i> Registered Patients & Test Summaries
        </h5>

        <div class="table-responsive">
          <table class="table table-hover datatable align-middle">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Tests</th>
                <th>Technicians</th>
                <th>Results</th>
                <th>Diagnosis / Medication</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php
              // ✅ Fixed SQL: use patient_id instead of id
              $sql = "
                SELECT 
                  p.patient_id,
                  p.full_name, 
                  p.email, 
                  p.phone,
                  GROUP_CONCAT(DISTINCT b.test_name ORDER BY b.created_at DESC SEPARATOR ', ') AS test_names,
                  GROUP_CONCAT(DISTINCT t.full_name SEPARATOR ', ') AS technician_names,
                  GROUP_CONCAT(DISTINCT b.result_file SEPARATOR ', ') AS result_files,
                  GROUP_CONCAT(DISTINCT d.diagnosis_note SEPARATOR ' | ') AS diagnosis_notes,
                  GROUP_CONCAT(DISTINCT d.medication SEPARATOR ', ') AS medications
                FROM patients p
                LEFT JOIN bookings b ON p.patient_id = b.patient_id
                LEFT JOIN technicians t ON b.uploaded_by = t.technician_id
                LEFT JOIN diagnosis d ON d.booking_id = b.id
                WHERE b.status = 'Completed' OR b.status IS NULL
                GROUP BY p.patient_id, p.full_name, p.email, p.phone
                ORDER BY p.full_name ASC
              ";

              $result = $conn->query($sql);
              $count = 1;

              if ($result && $result->num_rows > 0):
                while ($row = $result->fetch_assoc()):
              ?>
                <tr>
                  <td><?= $count++ ?></td>
                  <td><strong><?= htmlspecialchars($row['full_name']) ?></strong></td>
                  <td><?= htmlspecialchars($row['email']) ?></td>
                  <td><?= htmlspecialchars($row['phone']) ?></td>

                  <td>
                    <?= $row['test_names'] ? htmlspecialchars($row['test_names']) : '<span class="badge bg-secondary">No Tests</span>' ?>
                  </td>

                  <td>
                    <?= $row['technician_names'] ? htmlspecialchars($row['technician_names']) : '<span class="badge bg-warning text-dark">Pending</span>' ?>
                  </td>

                  <td>
                    <?php if (!empty($row['result_files'])): ?>
                      <?php foreach (explode(',', $row['result_files']) as $file): ?>
                        <?php $file = trim($file); if ($file): ?>
                          <a href="../uploads/<?= htmlspecialchars($file) ?>" target="_blank" class="btn btn-sm btn-outline-success mb-1">
                            <i class="bi bi-file-earmark-arrow-down"></i> <?= htmlspecialchars(basename($file)) ?>
                          </a><br>
                        <?php endif; ?>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <span class="text-muted">Not Uploaded</span>
                    <?php endif; ?>
                  </td>

                  <td>
                    <?php if (!empty($row['diagnosis_notes'])): ?>
                      <span class="text-dark"><?= htmlspecialchars($row['diagnosis_notes']) ?></span>
                      <?php if (!empty($row['medications'])): ?>
                        <br><small class="text-muted"><strong>Medication:</strong> <?= htmlspecialchars($row['medications']) ?></small>
                      <?php endif; ?>
                    <?php else: ?>
                      <span class="text-muted">Not Added</span>
                    <?php endif; ?>
                  </td>

                  <td>
                    <a href="patient_history.php?patient_id=<?= $row['patient_id'] ?>" class="btn btn-sm btn-primary rounded-pill px-3">
                      <i class="bi bi-eye"></i> View
                    </a>
                  </td>
                </tr>
              <?php 
                endwhile;
              else:
                echo "<tr><td colspan='9' class='text-center text-muted py-4'>No patients found.</td></tr>";
              endif;
              ?>
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </section>

</main>
