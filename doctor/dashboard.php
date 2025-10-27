<?php
// Enable full error visibility
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/db.php'; // Ensure this file exists and has correct DB credentials

// ✅ Check session variable properly
if (!isset($_SESSION['doctor_id']) || empty($_SESSION['doctor_id'])) {
    header("Location: doctor_login.php");
    exit;
}

// ✅ Ensure doctor_name is set
$doctor_name = isset($_SESSION['doctor_name']) ? $_SESSION['doctor_name'] : 'Doctor';

// ✅ Include header and sidebar safely
$header_path = __DIR__ . '/includes/header.php';
$sidebar_path = __DIR__ . '/includes/sidebar.php';

if (!file_exists($header_path) || !file_exists($sidebar_path)) {
    die("<h3 style='color:red;'>Error: Missing include files (header.php or sidebar.php)</h3>");
}

include $header_path;
include $sidebar_path;
?>

<main id="main" class="main">
  <div class="pagetitle">
    <h1>Doctor Dashboard</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item active">Dashboard</li>
      </ol>
    </nav>
  </div>

  <section class="section dashboard">
    <div class="row">

      <!-- Welcome Card -->
      <div class="col-12">
        <div class="card info-card">
          <div class="card-body">
            <h5 class="card-title">Welcome, Dr. <?= htmlspecialchars($doctor_name) ?></h5>
            <p class="text-muted">Use the features below to manage your patients and lab results.</p>
          </div>
        </div>
      </div>

      <!-- Features Grid -->
      <div class="col-md-6 col-lg-4">
        <div class="card info-card text-center">
          <div class="card-body">
            <a href="view_results.php" class="stretched-link text-decoration-none">
              <h5 class="card-title">View Test Results</h5>
              <p class="card-text text-muted">Access patient lab reports</p>
            </a>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-4">
        <div class="card info-card text-center">
          <div class="card-body">
            <a href="add_diagnosis.php" class="stretched-link text-decoration-none">
              <h5 class="card-title">Add Diagnosis</h5>
              <p class="card-text text-muted">Record diagnosis notes for patients</p>
            </a>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-4">
        <div class="card info-card text-center">
          <div class="card-body">
            <a href="patient_history.php" class="stretched-link text-decoration-none">
              <h5 class="card-title">Patient History</h5>
              <p class="card-text text-muted">Review patient's medical test history</p>
            </a>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-4">
        <div class="card info-card text-center">
          <div class="card-body">
            <a href="send_message.php" class="stretched-link text-decoration-none">
              <h5 class="card-title">Send Message</h5>
              <p class="card-text text-muted">Communicate with your patients</p>
            </a>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-4">
        <div class="card info-card text-center">
          <div class="card-body">
            <a href="profile.php" class="stretched-link text-decoration-none">
              <h5 class="card-title">My Profile</h5>
              <p class="card-text text-muted">View and update your profile</p>
            </a>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-4">
        <div class="card info-card text-center">
          <div class="card-body">
            <a href="change_password.php" class="stretched-link text-decoration-none">
              <h5 class="card-title">Change Password</h5>
              <p class="card-text text-muted">Secure your account</p>
            </a>
          </div>
        </div>
      </div>

    </div>
  </section>
</main>
