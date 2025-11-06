<?php
// Enable full error visibility
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/db.php';

// Session check
if (!isset($_SESSION['doctor_id']) || empty($_SESSION['doctor_id'])) {
    header("Location: doctor_login.php");
    exit;
}

$doctor_id = $_SESSION['doctor_id'];
$doctor_name = $_SESSION['doctor_name'] ?? 'Doctor';

// Fetch Dashboard Statistics
$stats = [
    'pending_tests' => 0,
    'completed_reports' => 0,
    'unread_messages' => 0,
    'total_patients' => 0
];

try {
    // Pending tests
    $result = $conn->query("SELECT COUNT(*) AS count FROM bookings WHERE status='Pending' AND assigned_doctor='$doctor_id'");
    $stats['pending_tests'] = $result->fetch_assoc()['count'] ?? 0;

    // Completed reports
    $result = $conn->query("SELECT COUNT(*) AS count FROM bookings WHERE status='Completed' AND assigned_doctor='$doctor_id'");
    $stats['completed_reports'] = $result->fetch_assoc()['count'] ?? 0;

    // Unread messages
    $result = $conn->query("SELECT COUNT(*) AS count FROM messages WHERE receiver_id='$doctor_id' AND receiver_role='doctor' AND is_read=0");
    $stats['unread_messages'] = $result->fetch_assoc()['count'] ?? 0;

    // Total patients
    $result = $conn->query("SELECT COUNT(DISTINCT patient_id) AS count FROM bookings WHERE assigned_doctor='$doctor_id'");
    $stats['total_patients'] = $result->fetch_assoc()['count'] ?? 0;
} catch (Exception $e) {
    error_log("Dashboard query error: " . $e->getMessage());
}

// Include reusable files
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<style>
  body {
    font-family: 'Poppins', sans-serif;
    background: #f5f7fa;
  }

  /* Navbar */
  .top-navbar {
    position: sticky;
    top: 0;
    z-index: 1020;
    background: linear-gradient(90deg, #0062E6, #33AEFF);
    color: #fff;
    padding: 0.75rem 1.5rem;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
  }

  .top-navbar .navbar-brand {
    font-weight: 700;
    font-size: 1.4rem;
    color: #fff;
    text-decoration: none;
  }

  .top-navbar .nav-link {
    color: #fff !important;
    font-weight: 500;
    margin-right: 1rem;
    transition: 0.3s;
  }

  .top-navbar .nav-link:hover {
    text-decoration: underline;
    color: #eaf4ff !important;
  }

  .top-navbar .dropdown-menu {
    min-width: 200px;
  }

  .dashboard-wrapper {
    margin-left: 300px;
    padding: 40px;
    background: #f5f7fa;
    min-height: 100vh;
  }

  .dashboard-title {
    font-size: 2.1rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 25px;
  }

  /* Stat Cards */
  .stat-card {
    border-radius: 18px;
    padding: 30px 20px;
    color: #fff;
    transition: all 0.3s ease;
    box-shadow: 0 6px 18px rgba(0,0,0,0.1);
  }

  .stat-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
  }

  .stat-card h5 {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 5px;
  }

  .stat-card h2 {
    font-size: 2.2rem;
    font-weight: 700;
  }

  .bg-blue { background: linear-gradient(135deg, #007bff, #00c6ff); }
  .bg-green { background: linear-gradient(135deg, #28a745, #8fd19e); }
  .bg-orange { background: linear-gradient(135deg, #f39c12, #f7b733); }
  .bg-purple { background: linear-gradient(135deg, #6f42c1, #a770ef); }

  /* Info Cards */
  .info-card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    padding: 25px;
    text-align: center;
    transition: 0.3s;
  }

  .info-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.12);
  }

  .info-card h5 {
    color: #2c3e50;
    font-size: 1.25rem;
    font-weight: 600;
  }

  .info-card p {
    color: #7f8c8d;
  }

  .info-card a {
    color: #007bff;
    font-weight: 500;
  }

  @media (max-width: 992px) {
    .dashboard-wrapper { margin-left: 0; padding: 25px; }
  }
</style>

<!-- ✅ NAVBAR -->
<nav class="navbar navbar-expand-lg top-navbar">
  <a class="navbar-brand" href="dashboard.php">Smart Laboratory System</a>

  <button class="navbar-toggler text-white" type="button" data-bs-toggle="collapse" data-bs-target="#doctorNavbar">
    <i class="bi bi-list" style="font-size: 1.5rem;"></i>
  </button>

  <div class="collapse navbar-collapse justify-content-end" id="doctorNavbar">
    <ul class="navbar-nav align-items-center">
      <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
      <li class="nav-item"><a class="nav-link" href="send_message.php"><i class="bi bi-envelope"></i> Messages</a></li>
      <li class="nav-item"><a class="nav-link" href="reports.php"><i class="bi bi-bar-chart-line"></i> Reports</a></li>

      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown">
          <img src="../assets/img/doctor-avatar.png" alt="Profile" class="rounded-circle me-2" width="35" height="35">
          <?= htmlspecialchars($doctor_name) ?>
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person"></i> My Profile</a></li>
          <li><a class="dropdown-item" href="change_password.php"><i class="bi bi-lock"></i> Change Password</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
      </li>
    </ul>
  </div>
</nav>

<!-- ✅ DASHBOARD CONTENT -->
<div class="dashboard-wrapper">
  <div class="container-fluid">
    <h1 class="dashboard-title">Welcome, Dr. <?= htmlspecialchars($doctor_name) ?></h1>

    <!-- Stats -->
    <div class="row g-4 mb-4">
      <div class="col-lg-3 col-md-6"><div class="stat-card bg-blue"><h5>Pending Tests</h5><h2><?= $stats['pending_tests'] ?></h2></div></div>
      <div class="col-lg-3 col-md-6"><div class="stat-card bg-green"><h5>Completed Reports</h5><h2><?= $stats['completed_reports'] ?></h2></div></div>
      <div class="col-lg-3 col-md-6"><div class="stat-card bg-orange"><h5>Unread Messages</h5><h2><?= $stats['unread_messages'] ?></h2></div></div>
      <div class="col-lg-3 col-md-6"><div class="stat-card bg-purple"><h5>Total Patients</h5><h2><?= $stats['total_patients'] ?></h2></div></div>
    </div>

    <!-- Function Cards -->
    <div class="row g-4">
      <div class="col-lg-4 col-md-6"><div class="info-card"><a href="view_results.php"><h5>View Test Results</h5><p>Access lab results uploaded by technicians</p></a></div></div>
      <div class="col-lg-4 col-md-6"><div class="info-card"><a href="add_diagnosis.php"><h5>Add Diagnosis</h5><p>Record and attach medical notes</p></a></div></div>
      <div class="col-lg-4 col-md-6"><div class="info-card"><a href="patient_history.php"><h5>Patient History</h5><p>View patient medical test records</p></a></div></div>
      <div class="col-lg-4 col-md-6"><div class="info-card"><a href="send_message.php"><h5>Message Center</h5><p>Communicate securely with patients</p></a></div></div>
      <div class="col-lg-4 col-md-6"><div class="info-card"><a href="profile.php"><h5>My Profile</h5><p>View and update professional info</p></a></div></div>
      <div class="col-lg-4 col-md-6"><div class="info-card"><a href="change_password.php"><h5>Change Password</h5><p>Secure your account credentials</p></a></div></div>

      <!-- AI Suggested Tests -->
      <div class="col-lg-12">
        <div class="info-card bg-light">
          <h5>AI Suggested Tests</h5>
          <p>Automatically generated recommendations based on patient symptoms.</p>
          <a href="ai_suggestions.php" class="btn btn-primary btn-sm mt-2">View AI Suggestions</a>
        </div>
      </div>
    </div>
  </div>
</div>
