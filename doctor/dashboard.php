<?php
// doctor/dashboard.php

// Debug mode (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/db.php';

// Auth check
if (!isset($_SESSION['doctor_id']) || empty($_SESSION['doctor_id'])) {
  header("Location: doctor_login.php");
  exit;
}

$doctor_name = isset($_SESSION['doctor_name']) ? $_SESSION['doctor_name'] : 'Doctor';

// Include shared layout files
$header_path = __DIR__ . '/includes/header.php';
$sidebar_path = __DIR__ . '/includes/sidebar.php';

if (!file_exists($header_path) || !file_exists($sidebar_path)) {
  die("<h3 style='color:red;text-align:center;'>Missing include files (header.php or sidebar.php)</h3>");
}

include $header_path;
include $sidebar_path;
?>

<!-- 🌟 Enhanced Styling -->
<style>
  body {
    background: #f8faff;
    font-family: 'Poppins', sans-serif;
    color: #343a40;
  }

  main.dashboard-main {
    margin-left: 300px;
    padding: 100px 35px 60px;
    min-height: 100vh;
    background: linear-gradient(180deg, #f8faff 0%, #eef3fb 100%);
  }

  /* Navbar Styling */
  .top-navbar {
    background: linear-gradient(90deg, #007bff, #0056d2);
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.1);
  }
  .top-navbar .navbar-brand {
    font-weight: 600;
    color: #fff;
    letter-spacing: 0.5px;
  }
  .top-navbar .nav-link {
    color: rgba(255, 255, 255, 0.9);
    transition: color 0.2s;
  }
  .top-navbar .nav-link:hover {
    color: #fff;
  }
  .dropdown-menu {
    box-shadow: 0 6px 20px rgba(0,0,0,0.08);
    border-radius: 12px;
  }

  /* Welcome Banner */
  .welcome-banner {
    background: linear-gradient(90deg, #0062ff, #00bfff);
    color: #fff;
    border-radius: 16px;
    padding: 40px 50px;
    margin-bottom: 40px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    position: relative;
    overflow: hidden;
  }

  .welcome-banner h1 {
    font-size: 2rem;
    font-weight: 600;
    animation: fadeInDown 1s ease;
  }

  .welcome-banner p {
    font-size: 1rem;
    opacity: 0.9;
    animation: fadeInUp 1.2s ease;
  }

  .welcome-banner::before {
    content: "";
    position: absolute;
    right: -40px;
    bottom: -40px;
    width: 220px;
    height: 220px;
    background: rgba(255,255,255,0.15);
    border-radius: 50%;
    animation: pulse 4s infinite;
  }

  @keyframes pulse {
    0% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.2); opacity: 0.3; }
    100% { transform: scale(1); opacity: 0.5; }
  }

  @keyframes fadeInDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
  }

  @keyframes fadeInUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
  }

  /* Dashboard Grid */
  .dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 30px;
  }

  .dash-card {
    background: #fff;
    border-radius: 14px;
    padding: 35px 25px;
    text-align: center;
    box-shadow: 0 10px 35px rgba(13, 110, 253, 0.05);
    transition: all 0.25s ease;
  }
  .dash-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 16px 45px rgba(13,110,253,0.15);
  }

  .dash-card .icon {
    font-size: 48px;
    margin-bottom: 14px;
  }
  .dash-card h5 {
    font-weight: 600;
    margin-bottom: 6px;
  }
  .dash-card p {
    font-size: 0.9rem;
    color: #6c757d;
  }
  .dash-card a.btn {
    border-radius: 8px;
  }

  /* Footer */
  footer {
    margin-left: 300px;
    padding: 18px 0;
    text-align: center;
    font-size: 0.85rem;
    color: #777;
  }
</style>

<!-- ✅ Navbar -->
<nav class="navbar navbar-expand-lg top-navbar fixed-top">
  <div class="container-fluid" style="padding-left:calc(300px + 24px);padding-right:24px;">
    <a class="navbar-brand" href="#">Smart Laboratory</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#doctorNavbar" aria-controls="doctorNavbar" aria-expanded="false">
      <span class="navbar-toggler-icon" style="filter:invert(1)"></span>
    </button>

    <div class="collapse navbar-collapse justify-content-end" id="doctorNavbar">
      <ul class="navbar-nav align-items-center">
        <li class="nav-item">
          <a class="nav-link" href="messages.php"><i class="bi bi-envelope me-1"></i> Messages</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="reports.php"><i class="bi bi-bar-chart-line me-1"></i> Reports</a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <img src="../assets/img/preg.jpeg" alt="Avatar" class="rounded-circle me-2" width="36" height="36">
            <span class="text-white"><?= htmlspecialchars($doctor_name) ?></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i> My Profile</a></li>
            <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear me-2"></i> Settings</a></li>
            <li><a class="dropdown-item" href="change_password.php"><i class="bi bi-lock me-2"></i> Change Password</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- ✅ Main Dashboard -->
<main class="dashboard-main">

  <!-- Welcome Section -->
  <div class="welcome-banner">
    <h1>Welcome, Dr. <?= htmlspecialchars($doctor_name) ?> 👋</h1>
    <p>Here’s your personalized dashboard — manage diagnostics, communicate with patients, and monitor reports efficiently.</p>
  </div>

  <!-- Dashboard Cards -->
  <div class="dashboard-grid">

    <div class="dash-card">
      <div class="icon text-primary"><i class="bi bi-file-earmark-medical"></i></div>
      <h5>View Test Results</h5>
      <p>Access patient lab reports uploaded by technicians.</p>
      <a href="view_results.php" class="btn btn-outline-primary btn-sm mt-2">Open</a>
    </div>

    <div class="dash-card">
      <div class="icon text-success"><i class="bi bi-clipboard-plus"></i></div>
      <h5>Add Diagnosis</h5>
      <p>Record diagnosis notes for patients efficiently.</p>
      <a href="add_diagnosis.php" class="btn btn-outline-success btn-sm mt-2">Add</a>
    </div>

    <div class="dash-card">
      <div class="icon text-info"><i class="bi bi-people"></i></div>
      <h5>Patient History</h5>
      <p>View a patient’s medical and test history.</p>
      <a href="patient_history.php" class="btn btn-outline-info btn-sm mt-2">View</a>
    </div>

    <div class="dash-card">
      <div class="icon text-warning"><i class="bi bi-chat-dots"></i></div>
      <h5>Messages</h5>
      <p>Chat with patients or respond to messages.</p>
      <a href="messages.php" class="btn btn-outline-warning btn-sm mt-2">Open</a>
    </div>

    <div class="dash-card">
      <div class="icon text-secondary"><i class="bi bi-person-circle"></i></div>
      <h5>My Profile</h5>
      <p>Update your personal and professional details.</p>
      <a href="profile.php" class="btn btn-outline-secondary btn-sm mt-2">Edit</a>
    </div>

    <div class="dash-card">
      <div class="icon text-danger"><i class="bi bi-lock"></i></div>
      <h5>Change Password</h5>
      <p>Change your password regularly for better security.</p>
      <a href="change_password.php" class="btn btn-outline-danger btn-sm mt-2">Change</a>
    </div>
  </div>
</main>

<!-- Footer -->
<footer>
  © <?= date('Y') ?> Smart Laboratory System — Doctor Portal
</footer>

<!-- Ensure dropdown works -->
<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('.dropdown-toggle').forEach(el => {
  new bootstrap.Dropdown(el);
});
</script>
</body>
</html>
