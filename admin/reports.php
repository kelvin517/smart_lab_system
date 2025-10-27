
<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../admin_login.php");
    exit();
}

$selected_month = isset($_GET['month']) ? $_GET['month'] : date('m');
$selected_year  = isset($_GET['year']) ? $_GET['year'] : date('Y');
$whereClause = "WHERE MONTH(created_at) = $selected_month AND YEAR(created_at) = $selected_year";

// Fetch test bookings data
$datesArray = $countArray = [];
$q = $conn->query("SELECT DATE(created_at) as day, COUNT(*) as count FROM bookings $whereClause GROUP BY day");
while ($r = $q->fetch_assoc()) {
    $datesArray[] = $r['day'];
    $countArray[] = $r['count'];
}

// Fetch revenue data
$revenueDates = $revenueAmounts = [];
$rq = $conn->query("SELECT DATE(created_at) as day, SUM(amount) as revenue FROM billing $whereClause GROUP BY day");
while ($r = $rq->fetch_assoc()) {
    $revenueDates[] = $r['day'];
    $revenueAmounts[] = $r['revenue'];
}

// Fetch feedback trends
$feedbackDates = $feedbackCounts = [];
$fq = $conn->query("SELECT DATE(created_at) as day, COUNT(*) as count FROM feedback $whereClause GROUP BY day");
while ($r = $fq->fetch_assoc()) {
    $feedbackDates[] = $r['day'];
    $feedbackCounts[] = $r['count'];
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<main id="main" class="main">
  <div class="pagetitle">
    <h1>Reports</h1>
  </div>

  <!-- Filter Form -->
  <form method="GET" class="row g-2 mb-4">
    <div class="col-md-3">
      <label class="form-label">Select Month</label>
      <select name="month" class="form-select" required>
        <?php for ($m = 1; $m <= 12; $m++):
          $value = str_pad($m, 2, '0', STR_PAD_LEFT);
          $monthName = date("F", mktime(0, 0, 0, $m, 10)); ?>
          <option value="<?= $value ?>" <?= ($value == $selected_month ? "selected" : "") ?>><?= $monthName ?></option>
        <?php endfor; ?>
      </select>
    </div>

    <div class="col-md-3">
      <label class="form-label">Select Year</label>
      <select name="year" class="form-select" required>
        <?php $current_year = date('Y');
        for ($y = $current_year; $y >= $current_year - 5; $y--): ?>
          <option value="<?= $y ?>" <?= ($y == $selected_year ? "selected" : "") ?>><?= $y ?></option>
        <?php endfor; ?>
      </select>
    </div>

    <div class="col-md-2 align-self-end">
      <button class="btn btn-primary">Apply Filters</button>
    </div>
  </form>

  <!-- Charts -->
  <section class="section">
    <div class="card mb-4">
      <div class="card-body">
        <h5 class="card-title">Tests Booked per Day</h5>
        <canvas id="testsChart" height="100"></canvas>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-body">
        <h5 class="card-title">Revenue per Day (KES)</h5>
        <canvas id="revenueChart" height="100"></canvas>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <h5 class="card-title">Feedback Trends</h5>
        <canvas id="feedbackChart" height="100"></canvas>
      </div>
    </div>
  </section>
</main>

<!-- Chart JS Libraries -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const testsChart = new Chart(document.getElementById('testsChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode($datesArray) ?>,
    datasets: [{
      label: 'Tests',
      data: <?= json_encode($countArray) ?>,
      borderColor: 'blue',
      fill: true,
      tension: 0.3
    }]
  }
});

const revenueChart = new Chart(document.getElementById('revenueChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($revenueDates) ?>,
    datasets: [{
      label: 'KES',
      data: <?= json_encode($revenueAmounts) ?>,
      backgroundColor: 'green'
    }]
  }
});

const feedbackChart = new Chart(document.getElementById('feedbackChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode($feedbackDates) ?>,
    datasets: [{
      label: 'Feedback Count',
      data: <?= json_encode($feedbackCounts) ?>,
      borderColor: 'purple',
      fill: true,
      tension: 0.3
    }]
  }
});
</script>