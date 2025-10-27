<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../admin_login.php");
    exit();
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<main id="main" class="main">
  <div class="pagetitle">
    <h1>Analytics Dashboard</h1>
  </div>

  <section class="section dashboard">
    <div class="row">

      <!-- Bookings per Month -->
      <div class="col-lg-6">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Monthly Bookings</h5>
            <canvas id="bookingsChart"></canvas>
          </div>
        </div>
      </div>

      <!-- Test Types Distribution -->
      <div class="col-lg-6">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Test Type Distribution</h5>
            <canvas id="testTypeChart"></canvas>
          </div>
        </div>
      </div>

    </div>

    <!-- Billing Status -->
    <div class="row">
      <div class="col-lg-6">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Billing Summary</h5>
            <canvas id="billingChart"></canvas>
          </div>
        </div>
      </div>
    </div>

  </section>
</main>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
  // Chart 1: Bookings per Month
  <?php
    $bookingData = mysqli_query($conn, "
      SELECT DATE_FORMAT(created_at, '%b') AS month, COUNT(*) AS total 
      FROM bookings 
      GROUP BY MONTH(created_at)
    ");
    $months = [];
    $totals = [];
    while ($row = mysqli_fetch_assoc($bookingData)) {
      $months[] = $row['month'];
      $totals[] = $row['total'];
    }
  ?>
  new Chart(document.getElementById('bookingsChart'), {
    type: 'bar',
    data: {
      labels: <?= json_encode($months) ?>,
      datasets: [{
        label: 'Bookings',
        data: <?= json_encode($totals) ?>,
        backgroundColor: '#4CAF50'
      }]
    }
  });

  // Chart 2: Test Types
  <?php
    $testTypes = mysqli_query($conn, "
      SELECT test_type, COUNT(*) as count 
      FROM bookings 
      GROUP BY test_type
    ");
    $labels = [];
    $counts = [];
    while ($row = mysqli_fetch_assoc($testTypes)) {
      $labels[] = $row['test_type'];
      $counts[] = $row['count'];
    }
  ?>
  new Chart(document.getElementById('testTypeChart'), {
    type: 'pie',
    data: {
      labels: <?= json_encode($labels) ?>,
      datasets: [{
        data: <?= json_encode($counts) ?>,
        backgroundColor: ['#2196F3', '#FF9800', '#E91E63', '#4CAF50', '#9C27B0']
      }]
    }
  });

  // Chart 3: Billing Summary
  <?php
    $paid = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM billing WHERE status = 'Paid'"))['count'];
    $unpaid = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM billing WHERE status = 'Unpaid'"))['count'];
  ?>
  new Chart(document.getElementById('billingChart'), {
    type: 'doughnut',
    data: {
      labels: ['Paid', 'Unpaid'],
      datasets: [{
        data: [<?= $paid ?>, <?= $unpaid ?>],
        backgroundColor: ['#4CAF50', '#F44336']
      }]
    }
  });
</script>