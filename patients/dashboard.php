<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../config/db.php';

// ✅ Check login
if (!isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit;
}

$patient_id = $_SESSION['patient_id'];

// ✅ Fetch patient info
$stmt = $conn->prepare("SELECT full_name, email, phone FROM patients WHERE patient_id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ✅ Fetch bookings & test results
$b = $conn->prepare("
    SELECT booking_id, test_name, preferred_date, status, result_file 
    FROM bookings 
    WHERE patient_id = ? 
    ORDER BY preferred_date DESC
");
$b->bind_param("i", $patient_id);
$b->execute();
$bookings = $b->get_result();
$b->close();

// ✅ Fetch feedback history
$f = $conn->prepare("SELECT rating, comments, created_at FROM feedback WHERE patient_id = ? ORDER BY created_at DESC");
$f->bind_param("i", $patient_id);
$f->execute();
$feedbacks = $f->get_result();
$f->close();

// ✅ AI Suggestions (placeholder)
$ai_recs = [
    "Based on your lab results, you might consider a follow-up glucose test.",
    "Your last CBC indicates low hemoglobin. Ensure adequate iron intake.",
    "Hydrate regularly and get 7–8 hours of sleep for better immunity."
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Patient Dashboard | Smart Laboratory</title>
  <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
  <style>
    body { background: #f8fafc; font-family: 'Segoe UI', sans-serif; }
    .sidebar {
      position: fixed; top: 0; left: 0; width: 230px; height: 100%;
      background: #002b5c; color: #fff; padding-top: 60px;
    }
    .sidebar a {
      display: block; color: #dce3f2; padding: 12px 20px; text-decoration: none;
    }
    .sidebar a:hover, .sidebar a.active {
      background: #014f86; color: #fff; border-left: 4px solid #ffc107;
    }
    .topbar {
      position: fixed; top: 0; left: 230px; right: 0;
      height: 60px; background: #fff; border-bottom: 1px solid #ddd;
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 20px; z-index: 1000;
    }
    .main-content {
      margin-left: 230px; margin-top: 80px; padding: 20px;
    }
    .card { border: none; border-radius: 10px; box-shadow: 0 3px 8px rgba(0,0,0,0.05); }
    .card-header { font-weight: 600; }
    .profile-img {
      width: 70px; height: 70px; border-radius: 50%;
      background: #ddd url('../assets/img/profile.png') center/cover no-repeat;
    }
  </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
  <div class="text-center mb-4">
    <h5 class="text-white">SmartLab System</h5>
  </div>
  <a href="dashboard.php" class="active"><i class="bi bi-speedometer2"></i> Dashboard</a>
  <a href="book_test.php"><i class="bi bi-journal-plus"></i> Book Test</a>
  <a href="view_results.php"><i class="bi bi-file-earmark-medical"></i> View Results</a>
  <a href="messages.php"><i class="bi bi-chat-dots"></i> Messages</a>
  <a href="feedback.php"><i class="bi bi-star"></i> Feedback</a>
  <a href="patient_profile.php"><i class="bi bi-person"></i> Profile</a>
  <a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right"></i> Logout</a>
</div>

<!-- Top Navbar -->
<div class="topbar">
  <div><h5 class="mb-0">Welcome, <?= htmlspecialchars($patient['full_name']) ?></h5></div>
  <div class="d-flex align-items-center gap-3">
    <i class="bi bi-bell fs-5"></i>
    <div class="dropdown">
      <a href="#" class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
        <div class="profile-img me-2"></div>
        <span><?= htmlspecialchars($patient['full_name']) ?></span>
      </a>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="patient_profile.php"><i class="bi bi-person"></i> My Profile</a></li>
        <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
      </ul>
    </div>
  </div>
</div>

<!-- Main Content -->
<div class="main-content">
  <div class="row g-4">

    <!-- Profile Summary -->
    <div class="col-lg-4">
      <div class="card p-3">
        <div class="card-header bg-primary text-white"><i class="bi bi-person-circle"></i> My Profile</div>
        <div class="card-body">
          <p><strong>Email:</strong> <?= htmlspecialchars($patient['email']) ?></p>
          <p><strong>Phone:</strong> <?= htmlspecialchars($patient['phone']) ?></p>
          <a href="patient_profile.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil"></i> Edit</a>
        </div>
      </div>

      <!-- AI Health Recommendations -->
      <div class="card mt-4 p-3">
        <div class="card-header bg-warning text-white"><i class="bi bi-lightbulb"></i> AI Health Suggestions</div>
        <div class="card-body">
          <ul>
            <?php foreach ($ai_recs as $rec): ?>
              <li><?= htmlspecialchars($rec) ?></li>
            <?php endforeach; ?>
          </ul>
          <form action="ai_recommendation.php" method="POST">
            <label class="form-label mt-2">Describe your symptoms:</label>
            <textarea class="form-control" name="symptoms" rows="2" required></textarea>
            <button class="btn btn-warning btn-sm mt-2"><i class="bi bi-robot"></i> Get Suggestion</button>
          </form>
        </div>
      </div>
    </div>

    <!-- Test Bookings and Feedback -->
    <div class="col-lg-8">
      <div class="card p-3 mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between">
          <span><i class="bi bi-journal-medical"></i> Test Bookings & Results</span>
          <a href="book_test.php" class="btn btn-light btn-sm"><i class="bi bi-plus-circle"></i> New Booking</a>
        </div>
        <div class="card-body">
          <?php if ($bookings->num_rows > 0): ?>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Test</th>
                  <th>Date</th>
                  <th>Status</th>
                  <th>Result</th>
                </tr>
              </thead>
              <tbody>
              <?php $i=1; while ($b = $bookings->fetch_assoc()): ?>
                <tr>
                  <td><?= $i++ ?></td>
                  <td><?= htmlspecialchars($b['test_name']) ?></td>
                  <td><?= date('d M Y', strtotime($b['preferred_date'])) ?></td>
                  <td>
                    <span class="badge bg-<?= $b['status']=='Completed'?'success':($b['status']=='Pending'?'warning text-dark':'secondary') ?>">
                      <?= htmlspecialchars($b['status']) ?>
                    </span>
                  </td>
                  <td>
                    <?php 
                      $path = "../uploads/results/" . $b['result_file'];
                      if (!empty($b['result_file']) && file_exists($path)): ?>
                        <a href="<?= $path ?>" target="_blank" class="btn btn-success btn-sm"><i class="bi bi-download"></i> View</a>
                      <?php else: ?>
                        <span class="text-muted">Not Available</span>
                      <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
              </tbody>
            </table>
          </div>
          <?php else: ?>
            <p class="text-muted">You have no test bookings yet. <a href="book_test.php">Book one now</a>.</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Feedback -->
      <div class="card p-3">
        <div class="card-header bg-success text-white"><i class="bi bi-chat-dots"></i> Feedback</div>
        <div class="card-body">
          <form method="POST" action="submit_feedback.php">
            <div class="row g-2 align-items-center">
              <div class="col-md-3">
                <label>Rating:</label>
                <select name="rating" class="form-select form-select-sm" required>
                  <option value="">Choose</option>
                  <?php for ($i=1; $i<=5; $i++): ?>
                    <option value="<?= $i ?>"><?= $i ?> ★</option>
                  <?php endfor; ?>
                </select>
              </div>
              <div class="col-md-7">
                <textarea name="comments" rows="1" class="form-control form-control-sm" placeholder="Write feedback..."></textarea>
              </div>
              <div class="col-md-2">
                <button type="submit" class="btn btn-success btn-sm w-100"><i class="bi bi-send"></i></button>
              </div>
            </div>
          </form>
          <hr>
          <h6 class="fw-bold">Previous Feedback:</h6>
          <?php if ($feedbacks->num_rows > 0): ?>
            <?php while ($fb = $feedbacks->fetch_assoc()): ?>
              <p><strong><?= $fb['rating'] ?>★</strong> - <?= htmlspecialchars($fb['comments']) ?> 
              <em>(<?= date('d M Y', strtotime($fb['created_at'])) ?>)</em></p>
            <?php endwhile; ?>
          <?php else: ?>
            <p class="text-muted">No feedback yet.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
