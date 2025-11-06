<?php
// =============================================
// SMART LABORATORY SYSTEM - ADMIN DASHBOARD
// =============================================
session_start();
include '../config/db.php';

// ✅ Enable error reporting (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ✅ Redirect if not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];

// ✅ Fetch admin details securely
$stmt = $conn->prepare("SELECT full_name, email FROM admins WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$admin) {
    $admin = ['full_name' => 'Administrator', 'email' => ''];
}

// ✅ Fetch system statistics (with fallbacks)
function fetch_count($conn, $table) {
    $query = $conn->query("SELECT COUNT(*) AS total FROM $table");
    return ($query && $row = $query->fetch_assoc()) ? $row['total'] : 0;
}

$total_patients     = fetch_count($conn, 'patients');
$total_doctors      = fetch_count($conn, 'doctors');
$total_technicians  = fetch_count($conn, 'technicians');
$total_tests        = fetch_count($conn, 'test_results');

?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<main id="main" class="main">

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

      <!-- Welcome Banner -->
      <div class="col-12">
        <div class="alert alert-primary shadow-sm fade show">
          <i class="bi bi-person-check-fill me-2"></i>
          <strong>Welcome back, <?= htmlspecialchars($admin['full_name']); ?>!</strong> 
          Manage users, results, and operations from this dashboard.
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="col-lg-3 col-md-6 mb-3">
        <div class="card info-card">
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

      <div class="col-lg-3 col-md-6 mb-3">
        <div class="card info-card">
          <div class="card-body">
            <h5 class="card-title">Doctors <span>| Active</span></h5>
            <div class="d-flex align-items-center">
              <div class="card-icon rounded-circle d-flex align-items-center justify-content-center bg-success text-white">
                <i class="bi bi-person-badge"></i>
              </div>
              <div class="ps-3">
                <h6><?= $total_doctors; ?></h6>
                <span class="text-muted small">Registered Doctors</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-md-6 mb-3">
        <div class="card info-card">
          <div class="card-body">
            <h5 class="card-title">Technicians <span>| Lab Staff</span></h5>
            <div class="d-flex align-items-center">
              <div class="card-icon rounded-circle d-flex align-items-center justify-content-center bg-warning text-white">
                <i class="bi bi-gear"></i>
              </div>
              <div class="ps-3">
                <h6><?= $total_technicians; ?></h6>
                <span class="text-muted small">Lab Technicians</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-md-6 mb-3">
        <div class="card info-card">
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

    <!-- Recent Logins -->
    <div class="row mt-4">
      <div class="col-lg-12">
        <div class="card recent-sales overflow-auto">
          <div class="card-body">
            <h5 class="card-title">Recent Logins <span>| Last 10</span></h5>

            <table class="table table-borderless datatable align-middle">
              <thead>
                <tr>
                  <th scope="col">#</th>
                  <th scope="col">Username</th>
                  <th scope="col">Role</th>
                  <th scope="col">Email</th>
                  <th scope="col">Login Time</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $i = 1;
                $logs = $conn->query("SELECT username, role, email, login_time FROM login_logs ORDER BY login_time DESC LIMIT 10");
                if ($logs && $logs->num_rows > 0) {
                    while ($row = $logs->fetch_assoc()) {
                        echo "<tr>
                            <th scope='row'>{$i}</th>
                            <td>" . htmlspecialchars($row['username']) . "</td>
                            <td><span class='badge bg-info text-dark'>" . htmlspecialchars($row['role']) . "</span></td>
                            <td>" . htmlspecialchars($row['email']) . "</td>
                            <td>" . htmlspecialchars($row['login_time']) . "</td>
                        </tr>";
                        $i++;
                    }
                } else {
                    echo "<tr><td colspan='5' class='text-center text-muted'>No recent login activity.</td></tr>";
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