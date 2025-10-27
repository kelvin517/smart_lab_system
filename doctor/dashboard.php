<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['doctor_id'])) {
    header("Location: doctor_login.php");
    exit;
}

$doctor_name = $_SESSION['doctor_name'];
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

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
        <div class="card info-card">
          <div class="card-body text-center">
            <a href="view_results.php" class="stretched-link text-decoration-none">
              <h5 class="card-title">View Test Results</h5>
              <p class="card-text text-muted">Access patient lab reports</p>
            </a>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-4">
        <div class="card info-card">
          <div class="card-body text-center">
            <a href="add_diagnosis.php" class="stretched-link text-decoration-none">
              <h5 class="card-title">Add Diagnosis</h5>
              <p class="card-text text-muted">Record diagnosis notes for patients</p>
            </a>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-4">
        <div class="card info-card">
          <div class="card-body text-center">
            <a href="patient_history.php" class="stretched-link text-decoration-none">
              <h5 class="card-title">Patient History</h5>
              <p class="card-text text-muted">Review patient's medical test history</p>
            </a>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-4">
        <div class="card info-card">
          <div class="card-body text-center">
            <a href="send_message.php" class="stretched-link text-decoration-none">
              <h5 class="card-title">Send Message</h5>
              <p class="card-text text-muted">Communicate with your patients</p>
            </a>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-4">
        <div class="card info-card">
          <div class="card-body text-center">
            <a href="profile.php" class="stretched-link text-decoration-none">
              <h5 class="card-title">My Profile</h5>
              <p class="card-text text-muted">View and update your profile</p>
            </a>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-4">
        <div class="card info-card">
          <div class="card-body text-center">
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