<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['technician_id'])) {
    header("Location: technician_login.php");
    exit;
}

$technician_username = $_SESSION['technician_username'];

// Gender count for pie chart
$genderCounts = ['Male' => 0, 'Female' => 0, 'Other' => 0];
$genderQuery = $conn->query("SELECT gender, COUNT(*) as total FROM patients GROUP BY gender");
while ($row = $genderQuery->fetch_assoc()) {
    $gender = $row['gender'];
    $genderCounts[$gender] = (int)$row['total'];
}

// Appointment per hour for bar chart
$hours = [];
$data = [];
for ($i = 9; $i <= 17; $i++) {
    $start = sprintf('%02d:00:00', $i);
    $end = sprintf('%02d:59:59', $i);
    $label = sprintf('%02d:00', $i);

    $query = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE TIME(preferred_date) BETWEEN '$start' AND '$end'");
    $count = $query ? $query->fetch_assoc()['count'] : 0;

    $hours[] = $label;
    $data[] = (int)$count;
}

// Next 4 appointments
$sql = "
    SELECT b.*, p.full_name 
    FROM bookings b 
    JOIN patients p ON b.patient_id = p.id 
    ORDER BY b.preferred_date ASC 
    LIMIT 4
";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Technician Dashboard</title>
  <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      background: url('../assets/img/26807.jpg') no-repeat center center fixed;
      background-size: cover;
    }
    .card-title { font-weight: bold; }
    .card { backdrop-filter: blur(6px); background-color: rgba(255,255,255,0.9); }
    .list-group-item i { color: red; font-size: 1.2em; }
    .header-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
    }
  </style>
</head>
<body>
  <div class="container-fluid p-4">
    
    <!-- Header Bar -->
    <div class="header-bar">
      <h3 class="text-white">Welcome, <?= htmlspecialchars($technician_username) ?></h3>
      <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
    </div>

    <div class="row">
      <!-- Next Patient -->
      <div class="col-md-4 mb-4">
        <div class="card border-primary">
          <div class="card-header bg-primary text-white">Next Patient</div>
          <div class="card-body">
            <?php if ($result && $next = $result->fetch_assoc()): ?>
              <h5>👤 <?= $next['full_name'] ?></h5>
              <p><?= date("H:i", strtotime($next['preferred_date'])) ?> | <?= $next['test_type'] ?></p>
              <i class="bi bi-telephone-fill text-danger"></i>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Lab Test Actions -->
      <div class="col-md-4 mb-4">
        <div class="card border-primary">
          <div class="card-header bg-primary text-white">Lab Tests</div>
          <div class="card-body">
            <p><strong><?= $next['full_name'] ?? '' ?></strong></p>
            <p><?= $next['test_type'] ?? 'N/A' ?></p>
            <a href="view_details.php?id=<?= $next['id'] ?>" class="btn btn-info btn-sm">Details</a>
            <a href="contact_patient.php?id=<?= $next['patient_id'] ?>" class="btn btn-secondary btn-sm">Contact</a>
            <a href="archive_booking.php?id=<?= $next['id'] ?>" class="btn btn-outline-success btn-sm">✔ Archive</a>
          </div>
        </div>
      </div>

      <!-- Upcoming Appointments -->
      <div class="col-md-4 mb-4">
        <div class="card border-primary">
          <div class="card-header bg-primary text-white">Upcoming Appointments</div>
          <ul class="list-group list-group-flush">
            <?php mysqli_data_seek($result, 0); while ($row = $result->fetch_assoc()): ?>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                  <strong><?= $row['full_name'] ?></strong><br>
                  <small><?= $row['test_type'] ?></small><br>
                  <small><?= date("H:i", strtotime($row['preferred_date'])) ?></small>
                </div>
                <i class="bi bi-telephone-fill"></i>
              </li>
            <?php endwhile; ?>
          </ul>
        </div>
      </div>
    </div>

    <div class="row">
      <!-- Bar Chart -->
      <div class="col-md-4 mb-4">
        <div class="card">
          <div class="card-header bg-primary text-white">Appointment Overview</div>
          <div class="card-body">
            <canvas id="barChart"></canvas>
          </div>
        </div>
      </div>

      <!-- Line Chart -->
      <div class="col-md-4 mb-4">
        <div class="card">
          <div class="card-header bg-primary text-white">Patient Traffic</div>
          <div class="card-body">
            <canvas id="lineChart"></canvas>
          </div>
        </div>
      </div>

      <!-- Gender Pie Chart -->
      <div class="col-md-4 mb-4">
        <div class="card">
          <div class="card-header bg-primary text-white">Gender Overview</div>
          <div class="card-body">
            <canvas id="pieChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Chart Scripts -->
  <script>
    const ctx1 = document.getElementById('barChart').getContext('2d');
    new Chart(ctx1, {
      type: 'bar',
      data: {
        labels: <?= json_encode($hours) ?>,
        datasets: [{
          label: 'Appointments Per Hour',
          data: <?= json_encode($data) ?>,
          backgroundColor: '#007bff'
        }]
      },
      options: {
        responsive: true,
        plugins: {
          tooltip: { enabled: true },
          legend: { display: false }
        },
        scales: {
          y: {
            beginAtZero: true,
            title: { display: true, text: 'Appointments' }
          },
          x: {
            title: { display: true, text: 'Hour of Day' }
          }
        }
      }
    });

    const ctx2 = document.getElementById('lineChart').getContext('2d');
    new Chart(ctx2, {
      type: 'line',
      data: {
        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
        datasets: [
          {
            label: 'New patients',
            data: [3, 4, 3, 5, 6],
            borderColor: 'red',
            fill: false
          },
          {
            label: 'Returning patients',
            data: [2, 3, 4, 2, 3],
            borderColor: 'blue',
            fill: false
          }
        ]
      }
    });

    const ctx3 = document.getElementById('pieChart').getContext('2d');
    new Chart(ctx3, {
      type: 'doughnut',
      data: {
        labels: ['Male', 'Female', 'Other'],
        datasets: [{
          data: <?= json_encode(array_values($genderCounts)) ?>,
          backgroundColor: ['#2196f3', '#e91e63', '#ffc107']
        }]
      }
    });
  </script>
</body>
</html>