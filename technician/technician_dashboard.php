<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['technician_id'])) {
    header("Location: technician_login.php");
    exit;
}

$technician_username = $_SESSION['technician_username'] ?? 'Technician';
$technician_id = $_SESSION['technician_id'];

// Gender count for pie chart
$genderCounts = ['Male' => 0, 'Female' => 0, 'Other' => 0];
$genderQuery = $conn->query("SELECT gender, COUNT(*) as total FROM patients GROUP BY gender");
if ($genderQuery) {
    while ($row = $genderQuery->fetch_assoc()) {
        $gender = $row['gender'];
        $genderCounts[$gender] = (int)$row['total'];
    }
}

// Appointment per hour for bar chart
$hours = [];
$data = [];
for ($i = 9; $i <= 17; $i++) {
    $start = sprintf('%02d:00:00', $i);
    $end = sprintf('%02d:59:59', $i);
    $label = sprintf('%02d:00', $i);

    $query = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE TIME(preferred_date) BETWEEN '$start' AND '$end'");
    $count = ($query && $row = $query->fetch_assoc()) ? $row['count'] : 0;

    $hours[] = $label;
    $data[] = (int)$count;
}

// Next 4 appointments
$sql = "
    SELECT b.*, p.full_name, p.patient_id, p.phone, p.email
    FROM bookings b 
    JOIN patients p ON b.patient_id = p.patient_id 
    WHERE b.status = 'pending'
    ORDER BY b.preferred_date ASC 
    LIMIT 4
";
$result = $conn->query($sql);
$appointments = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }
}
$next = $appointments[0] ?? null;

// Pending tests count
$pendingTestsQuery = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'");
$pendingTestsCount = $pendingTestsQuery ? $pendingTestsQuery->fetch_assoc()['count'] : 0;

// Completed tests today
$today = date('Y-m-d');
$completedTodayQuery = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'completed' AND DATE(completed_date) = '$today'");
$completedTodayCount = $completedTodayQuery ? $completedTodayQuery->fetch_assoc()['count'] : 0;

// Urgent tests (high priority)
$urgentTestsQuery = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE priority = 'high' AND status = 'pending'");
$urgentTestsCount = $urgentTestsQuery ? $urgentTestsQuery->fetch_assoc()['count'] : 0;

// Recent activities
$activitiesQuery = $conn->query("
    SELECT action, timestamp, full_name 
    FROM technician_activities 
    WHERE technician_id = $technician_id 
    ORDER BY timestamp DESC 
    LIMIT 5
");
$recentActivities = [];
if ($activitiesQuery) {
    while ($row = $activitiesQuery->fetch_assoc()) {
        $recentActivities[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Technician Dashboard - Smart Lab System</title>
  <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    :root {
      --primary: #2c3e50;
      --secondary: #3498db;
      --success: #27ae60;
      --warning: #f39c12;
      --danger: #e74c3c;
      --light: #ecf0f1;
      --dark: #34495e;
    }
    
    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      background-attachment: fixed;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      min-height: 100vh;
    }
    
    .dashboard-card {
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      border: none;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      overflow: hidden;
      margin-bottom: 25px;
    }
    
    .dashboard-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
    }
    
    .card-header {
      background: linear-gradient(to right, var(--primary), var(--dark));
      color: white;
      border-bottom: none;
      padding: 15px 20px;
      font-weight: 600;
    }
    
    .stat-card {
      text-align: center;
      padding: 20px 15px;
      border-radius: 12px;
      color: white;
      margin-bottom: 20px;
    }
    
    .stat-card i {
      font-size: 2.5rem;
      margin-bottom: 15px;
      opacity: 0.9;
    }
    
    .stat-card .number {
      font-size: 2rem;
      font-weight: bold;
      margin: 10px 0;
    }
    
    .stat-card .label {
      font-size: 0.9rem;
      opacity: 0.9;
    }
    
    .pending-tests { background: linear-gradient(45deg, #ff9a9e, #fad0c4); }
    .completed-today { background: linear-gradient(45deg, #a1c4fd, #c2e9fb); }
    .urgent-tests { background: linear-gradient(45deg, #ffecd2, #fcb69f); }
    
    .patient-card {
      border-left: 5px solid var(--secondary);
    }
    
    .action-btn {
      border-radius: 8px;
      padding: 8px 15px;
      font-weight: 500;
      transition: all 0.3s;
    }
    
    .appointment-item {
      border-left: 4px solid var(--secondary);
      margin-bottom: 12px;
      padding: 12px 15px;
      border-radius: 8px;
      background: rgba(255, 255, 255, 0.8);
      transition: all 0.3s;
    }
    
    .appointment-item:hover {
      background: white;
      transform: translateX(5px);
    }
    
    .activity-item {
      padding: 10px 15px;
      border-left: 3px solid var(--secondary);
      margin-bottom: 10px;
      background: rgba(255, 255, 255, 0.7);
      border-radius: 8px;
    }
    
    .navbar-custom {
      background: rgba(44, 62, 80, 0.95);
      backdrop-filter: blur(10px);
      border-radius: 15px;
      margin-bottom: 30px;
      padding: 15px 25px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .welcome-text {
      color: white;
      font-weight: 600;
      font-size: 1.4rem;
    }
    
    .chart-container {
      position: relative;
      height: 250px;
      padding: 15px;
    }
    
    .quick-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 15px;
    }
    
    .quick-action-btn {
      flex: 1;
      min-width: 120px;
      text-align: center;
      padding: 12px 10px;
      border-radius: 10px;
      background: white;
      border: 1px solid #e0e0e0;
      transition: all 0.3s;
      text-decoration: none;
      color: var(--dark);
    }
    
    .quick-action-btn:hover {
      background: var(--secondary);
      color: white;
      transform: translateY(-3px);
      text-decoration: none;
    }
    
    .badge-urgent {
      background: var(--danger);
      color: white;
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 0.8rem;
    }
  </style>
</head>
<body>
  <div class="container-fluid py-4">
    
    <!-- Header Bar -->
    <div class="navbar-custom d-flex justify-content-between align-items-center">
      <div>
        <h3 class="welcome-text mb-0">Welcome, <?= htmlspecialchars($technician_username) ?></h3>
        <small class="text-light">Lab Technician Dashboard</small>
      </div>
      <div class="d-flex align-items-center">
        <a href="manage_tests.php" class="btn btn-outline-light btn-sm me-2">
          <i class="bi bi-clipboard-data"></i> Manage Tests
        </a>
        <a href="patient_search.php" class="btn btn-outline-light btn-sm me-2">
          <i class="bi bi-search"></i> Find Patient
        </a>
        <a href="logout.php" class="btn btn-danger btn-sm">
          <i class="bi bi-box-arrow-right"></i> Logout
        </a>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="row">
      <div class="col-md-4">
        <div class="stat-card pending-tests">
          <i class="fas fa-vial"></i>
          <div class="number"><?= $pendingTestsCount ?></div>
          <div class="label">Pending Tests</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-card completed-today">
          <i class="fas fa-check-circle"></i>
          <div class="number"><?= $completedTodayCount ?></div>
          <div class="label">Completed Today</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-card urgent-tests">
          <i class="fas fa-exclamation-triangle"></i>
          <div class="number"><?= $urgentTestsCount ?></div>
          <div class="label">Urgent Tests</div>
        </div>
      </div>
    </div>

    <div class="row">
      <!-- Next Patient -->
      <div class="col-lg-4 col-md-6">
        <div class="card dashboard-card patient-card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span>Next Patient</span>
            <?php if ($next): ?>
              <span class="badge-urgent">UPCOMING</span>
            <?php endif; ?>
          </div>
          <div class="card-body">
            <?php if ($next): ?>
              <div class="d-flex align-items-center mb-3">
                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                  <i class="fas fa-user text-white"></i>
                </div>
                <div>
                  <h5 class="mb-1"><?= htmlspecialchars($next['full_name']) ?></h5>
                  <p class="mb-1 text-muted"><?= htmlspecialchars($next['test_name']) ?></p>
                  <small class="text-primary">
                    <i class="bi bi-clock"></i> <?= date("H:i", strtotime($next['preferred_date'])) ?>
                  </small>
                </div>
              </div>
              
              <div class="quick-actions">
                <a href="view_details.php?id=<?= $next['id'] ?>" class="quick-action-btn">
                  <i class="bi bi-eye"></i> Details
                </a>
                <a href="contact_patient.php?id=<?= $next['patient_id'] ?>" class="quick-action-btn">
                  <i class="bi bi-telephone"></i> Contact
                </a>
                <a href="start_test.php?id=<?= $next['id'] ?>" class="quick-action-btn">
                  <i class="bi bi-play-circle"></i> Start Test
                </a>
              </div>
            <?php else: ?>
              <div class="text-center py-4">
                <i class="fas fa-user-clock fa-3x text-muted mb-3"></i>
                <p class="text-muted">No upcoming patients scheduled.</p>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Upcoming Appointments -->
      <div class="col-lg-4 col-md-6">
        <div class="card dashboard-card">
          <div class="card-header">Upcoming Appointments</div>
          <div class="card-body">
            <?php if (!empty($appointments)): ?>
              <?php foreach ($appointments as $row): ?>
                <div class="appointment-item">
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <strong><?= htmlspecialchars($row['full_name']) ?></strong>
                      <div class="small text-muted"><?= htmlspecialchars($row['test_name']) ?></div>
                      <div class="small text-primary">
                        <i class="bi bi-clock"></i> <?= date("H:i", strtotime($row['preferred_date'])) ?>
                      </div>
                    </div>
                    <div class="dropdown">
                      <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-three-dots"></i>
                      </button>
                      <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="view_details.php?id=<?= $row['id'] ?>">View Details</a></li>
                        <li><a class="dropdown-item" href="contact_patient.php?id=<?= $row['patient_id'] ?>">Contact</a></li>
                        <li><a class="dropdown-item" href="start_test.php?id=<?= $row['id'] ?>">Start Test</a></li>
                      </ul>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="text-center py-4">
                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                <p class="text-muted">No appointments found.</p>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Recent Activities -->
      <div class="col-lg-4 col-md-12">
        <div class="card dashboard-card">
          <div class="card-header">Recent Activities</div>
          <div class="card-body">
            <?php if (!empty($recentActivities)): ?>
              <?php foreach ($recentActivities as $activity): ?>
                <div class="activity-item">
                  <div class="d-flex justify-content-between">
                    <div>
                      <strong><?= htmlspecialchars($activity['action']) ?></strong>
                      <div class="small text-muted"><?= htmlspecialchars($activity['patient_name'] ?? 'N/A') ?></div>
                    </div>
                    <div class="small text-muted">
                      <?= date("H:i", strtotime($activity['timestamp'])) ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="text-center py-4">
                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                <p class="text-muted">No recent activities.</p>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts Section -->
    <div class="row mt-4">
      <!-- Bar Chart -->
      <div class="col-lg-4 col-md-6">
        <div class="card dashboard-card">
          <div class="card-header">Appointments Per Hour</div>
          <div class="card-body">
            <div class="chart-container">
              <canvas id="barChart"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- Line Chart -->
      <div class="col-lg-4 col-md-6">
        <div class="card dashboard-card">
          <div class="card-header">Patient Traffic</div>
          <div class="card-body">
            <div class="chart-container">
              <canvas id="lineChart"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- Gender Pie Chart -->
      <div class="col-lg-4 col-md-12">
        <div class="card dashboard-card">
          <div class="card-header">Gender Overview</div>
          <div class="card-body">
            <div class="chart-container">
              <canvas id="pieChart"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap & Chart Scripts -->
  <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script>
    // Bar Chart
    const ctx1 = document.getElementById('barChart').getContext('2d');
    new Chart(ctx1, {
      type: 'bar',
      data: {
        labels: <?= json_encode($hours) ?>,
        datasets: [{
          label: 'Appointments Per Hour',
          data: <?= json_encode($data) ?>,
          backgroundColor: '#3498db',
          borderColor: '#2980b9',
          borderWidth: 1,
          borderRadius: 5
        }]
      },
      options: { 
        responsive: true, 
        maintainAspectRatio: false,
        scales: { 
          y: { 
            beginAtZero: true,
            grid: { color: 'rgba(0,0,0,0.05)' }
          },
          x: {
            grid: { display: false }
          }
        },
        plugins: {
          legend: { display: false }
        }
      }
    });

    // Line Chart
    const ctx2 = document.getElementById('lineChart').getContext('2d');
    new Chart(ctx2, {
      type: 'line',
      data: {
        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
        datasets: [
          { 
            label: 'New patients', 
            data: [3, 4, 3, 5, 6], 
            borderColor: '#e74c3c', 
            backgroundColor: 'rgba(231, 76, 60, 0.1)',
            borderWidth: 2,
            tension: 0.3,
            fill: true
          },
          { 
            label: 'Returning', 
            data: [2, 3, 4, 2, 3], 
            borderColor: '#3498db', 
            backgroundColor: 'rgba(52, 152, 219, 0.1)',
            borderWidth: 2,
            tension: 0.3,
            fill: true
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            grid: { color: 'rgba(0,0,0,0.05)' }
          },
          x: {
            grid: { display: false }
          }
        }
      }
    });

    // Pie Chart
    const ctx3 = document.getElementById('pieChart').getContext('2d');
    new Chart(ctx3, {
      type: 'doughnut',
      data: {
        labels: ['Male', 'Female', 'Other'],
        datasets: [{
          data: <?= json_encode(array_values($genderCounts)) ?>,
          backgroundColor: ['#3498db', '#e91e63', '#ffc107'],
          borderWidth: 0,
          hoverOffset: 15
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom'
          }
        }
      }
    });
  </script>
</body>
</html>