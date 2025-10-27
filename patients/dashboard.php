<?php
session_start();
if (!isset($_SESSION['patient_id'])) {
  header("Location: login.php"); exit;
}

require_once '../config/db.php';
$p = $_SESSION['patient_id'];

// Patient info
$res = $conn->prepare("SELECT full_name,email,phone FROM patients WHERE id=?");
$res->bind_param("i",$p); $res->execute();
$res->bind_result($fullName,$email,$phone);
$res->fetch(); $res->close();

// Appointments
$appts = $conn->prepare("SELECT id,test_type,preferred_date,status,result_file FROM bookings WHERE patient_id=? ORDER BY preferred_date DESC");
$appts->bind_param("i",$p);
$appts->execute();
$apptsRes = $appts->get_result();
$appts->close();

// Feedback
$feedbacks = $conn->prepare("SELECT rating,comments FROM feedback WHERE patient_id=?");
$feedbacks->bind_param("i",$p);
$feedbacks->execute();
$feedbackRes = $feedbacks->get_result();
$feedbacks->close();

// AI static recs (optional)
$ai_recs = ["Consider follow-up Blood Sugar test.", "Based on history, an X-ray may help."];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Patient Dashboard</title>
  <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
  <style>
    body { background: #f5f7fa; }
    .header-bar { display: flex; justify-content: space-between; align-items: center; margin: 20px 0; }
    .card { margin-bottom: 20px; }
  </style>
</head>
<body>
<div class="container-fluid py-4">
  <div class="header-bar">
    <h3>Welcome, <?= htmlspecialchars($fullName) ?></h3>
    <a href="logout.php" class="btn btn-danger"><i class="bi bi-box-arrow-right"></i> Logout</a>
  </div>

  <!-- Alerts -->
  <?php if (isset($_SESSION['feedback_success'])): ?>
    <div class="alert alert-success"><?= $_SESSION['feedback_success']; unset($_SESSION['feedback_success']); ?></div>
  <?php elseif (isset($_SESSION['feedback_error'])): ?>
    <div class="alert alert-danger"><?= $_SESSION['feedback_error']; unset($_SESSION['feedback_error']); ?></div>
  <?php endif; ?>

  <?php if (isset($_SESSION['ai_result'])): ?>
    <div class="alert alert-success">
      <strong>AI Suggestion:</strong> <?= $_SESSION['ai_result']; unset($_SESSION['ai_result']); ?>
    </div>
  <?php elseif (isset($_SESSION['ai_error'])): ?>
    <div class="alert alert-danger"><?= $_SESSION['ai_error']; unset($_SESSION['ai_error']); ?></div>
  <?php endif; ?>

  <div class="row">
    <!-- Left Column -->
    <div class="col-md-4">
      <!-- Profile -->
      <div class="card border-info">
        <div class="card-header bg-info text-white">Your Profile</div>
        <div class="card-body">
          <p><strong>Email:</strong> <?= htmlspecialchars($email) ?></p>
          <p><strong>Phone:</strong> <?= htmlspecialchars($phone) ?></p>
          <a href="patient_profile.php" class="btn btn-secondary me-2">
  <i class="bi bi-person"></i> My Profile
</a>
        </div>
      </div>

      <!-- AI Recommendations -->
      <div class="card border-warning">
        <div class="card-header bg-warning text-white">AI Recommendations</div>
        <div class="card-body">
          <ul>
            <?php foreach ($ai_recs as $r): ?>
              <li><?= htmlspecialchars($r) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>

      <!-- AI Symptom Form -->
      <div class="card border-secondary">
        <div class="card-header bg-secondary text-white">🧠 Get AI-Based Test Suggestion</div>
        <div class="card-body">
          <form action="ai_recommendation.php" method="POST">
            <div class="mb-3">
              <label for="symptoms" class="form-label">Describe your symptoms:</label>
              <textarea name="symptoms" id="symptoms" rows="3" class="form-control" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Get Recommendation</button>
          </form>
        </div>
      </div>
    </div>

    <!-- Right Column -->
    <div class="col-md-8">
      <!-- Bookings -->
      <div class="card border-primary">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
          <span>Book a New Test / Reschedule</span>
          <a href="book_test.php" class="btn btn-light btn-sm">+ Book / Reschedule</a>
        </div>
        <div class="card-body">
          <table class="table">
            <thead><tr><th>#</th><th>Test</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
              <?php while ($row = $apptsRes->fetch_assoc()): ?>
                <tr>
                  <td><?= $row['id'] ?></td>
                  <td><?= htmlspecialchars($row['test_type']) ?></td>
                  <td><?= date('d-M H:i', strtotime($row['preferred_date'])) ?></td>
                  <td><?= htmlspecialchars($row['status']) ?></td>
                  <td>
                    <?php if ($row['status'] !== 'Completed'): ?>
                      <a href="reschedule.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Reschedule</a>
                    <?php endif; ?>
                    <?php if ($row['result_file']): ?>
                      <a href="download.php?file=<?= urlencode($row['result_file']) ?>" class="btn btn-sm btn-success">Download Report</a>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Feedback -->
      <div class="card border-success">
        <div class="card-header bg-success text-white">Your Feedback & Rating</div>
        <div class="card-body">
          <form method="post" action="submit_feedback.php">
            <div class="mb-3">
              <label>Rating:</label>
              <select name="rating" class="form-select" required>
                <option value="">--Select--</option>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                  <option value="<?= $i ?>"><?= $i ?> Star<?= $i > 1 ? 's' : '' ?></option>
                <?php endfor; ?>
              </select>
            </div>
            <div class="mb-3">
              <label>Comments:</label>
              <textarea name="comments" class="form-control" rows="2"></textarea>
            </div>
            <button type="submit" class="btn btn-success btn-sm">Submit Feedback</button>
          </form>
          <hr>
          <h6>Your Past Feedback:</h6>
          <?php while ($f = $feedbackRes->fetch_assoc()): ?>
            <p><strong><?= $f['rating'] ?>★</strong> – <?= htmlspecialchars($f['comments']) ?> <em>(<?= $f['created_at'] ?>)</em></p>
          <?php endwhile; ?>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>