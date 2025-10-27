<?php
// ==============================
// Smart Laboratory Admin Dashboard
// ==============================
session_start();
include '../config/db.php';

// ✅ Redirect if not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// ✅ Fetch admin details
$admin_id = $_SESSION['admin_id'];
$admin_query = $conn->query("SELECT full_name, email FROM admins WHERE id = '$admin_id' LIMIT 1");
$admin = $admin_query ? $admin_query->fetch_assoc() : ['full_name' => 'Administrator', 'email' => ''];

// ✅ Fetch statistics
$total_patients = $conn->query("SELECT COUNT(*) AS total FROM patients")->fetch_assoc()['total'] ?? 0;
$total_doctors = $conn->query("SELECT COUNT(*) AS total FROM doctors")->fetch_assoc()['total'] ?? 0;
$total_technicians = $conn->query("SELECT COUNT(*) AS total FROM technicians")->fetch_assoc()['total'] ?? 0;
$total_tests = $conn->query("SELECT COUNT(*) AS total FROM test_results")->fetch_assoc()['total'] ?? 0;
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<main id="main" class="main">

  <!-- Page Title -->
  <div class="pagetitle">
    <h1>Admin Dashboard</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
        <li class="breadcrumb-item active">Dashboard</li>
      </ol>
    </nav>
  </div><!-- End Page Title -->

  <section class="section dashboard">
    <div class="row">

      <!-- Welcome Card -->
      <div class="col-12">
        <div class="alert alert-primary shadow-sm">
          <strong>Welcome, <?= htmlspecialchars($admin['full_name']); ?>!</strong>  
          Manage your Smart Laboratory operations from this dashboard.
        </div>
      </div>

      <!-- Patients Card -->
      <div class="col-lg-3 col-md-6">
        <div class="card info-card customers-card">
          <div class="card-body">
            <h5 class="card-title">Patients <span>| Registered</span></h5>
            <div class="d-flex align-items-center">
              <div class="card-icon rounded-circle d-flex align-items-center justify-content-center bg-primary text-white">
                <i class="bi bi-people"></i>
              </div>
              <div class="ps-3">
                <h6><?= $total_patients; ?></h6>
                <span class="text-muted small">Total Patients</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Doctors Card -->
      <div class="col-lg-3 col-md-6">
        <div class="card info-card sales-card">
          <div class="card-body">
            <h5 class="card-title">Doctors <span>| Active</span></h5>
            <div class="d-flex align-items-center">
              <div class="card-icon rounded-circle d-flex align-items-center justify-content-center bg-success text-white">
                <i class="bi bi-person-badge"></i>
              </div>
              <div class="ps-3">
                <h6><?= $total_doctors; ?></h6>
                <span class="text-muted small">Available Doctors</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Technicians Card -->
      <div class="col-lg-3 col-md-6">
        <div class="card info-card revenue-card">
          <div class="card-body">
            <h5 class="card-title">Technicians <span>| Lab Staff</span></h5>
            <div class="d-flex align-items-center">
              <div class="card-icon rounded-circle d-flex align-items-center justify-content-center bg-warning text-white">
                <i class="bi bi-gear"></i>
              </div>
              <div class="ps-3">
                <h6><?= $total_technicians; ?></h6>
                <span class="text-muted small">Registered Technicians</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Test Results Card -->
      <div class="col-lg-3 col-md-6">
        <div class="card info-card customers-card">
          <div class="card-body">
            <h5 class="card-title">Test Results <span>| Completed</span></h5>
            <div class="d-flex align-items-center">
              <div class="card-icon rounded-circle d-flex align-items-center justify-content-center bg-danger text-white">
                <i class="bi bi-file-earmark-text"></i>
              </div>
              <div class="ps-3">
                <h6><?= $total_tests; ?></h6>
                <span class="text-muted small">Results Uploaded</span>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div><!-- End Stats Row -->

    <!-- Recent Login Activity -->
    <div class="row mt-4">
      <div class="col-lg-12">
        <div class="card recent-sales overflow-auto">
          <div class="card-body">
            <h5 class="card-title">Recent Logins <span>| Last 10 Records</span></h5>

            <table class="table table-borderless datatable">
              <thead>
                <tr>
                  <th scope="col">#</th>
                  <th scope="col">User</th>
                  <th scope="col">Role</th>
                  <th scope="col">Email</th>
                  <th scope="col">Login Time</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $i = 1;
                $logs = $conn->query("SELECT * FROM login_logs ORDER BY login_time DESC LIMIT 10");
                if ($logs && $logs->num_rows > 0) {
                  while ($row = $logs->fetch_assoc()) {
                    echo "
                      <tr>
                        <th scope='row'>{$i}</th>
                        <td>{$row['username']}</td>
                        <td>{$row['role']}</td>
                        <td>{$row['email']}</td>
                        <td>{$row['login_time']}</td>
                      </tr>
                    ";
                    $i++;
                  }
                } else {
                  echo "<tr><td colspan='5'>No recent login records found.</td></tr>";
                }
                ?>
              </tbody>
            </table>

          </div>
        </div>
      </div>
    </div>

  </section>

</main>

<?php include 'includes/footer.php'; ?>
