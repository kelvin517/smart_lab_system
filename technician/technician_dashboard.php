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
    ORDER BY b.preferred_date ASC, b.appointment_time ASC
    LIMIT 4
";
$result = $conn->query($sql);

$appointments = [];
if ($result && $result->num_rows > 0) {
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

// Weekly stats for line chart
$weeklyData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dayName = date('D', strtotime($date));
    
    $query = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE DATE(preferred_date) = '$date'");
    $count = $query ? $query->fetch_assoc()['count'] : 0;
    
    $weeklyData['labels'][] = $dayName;
    $weeklyData['data'][] = $count;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Lab Technician Dashboard - Hospital Management System</title>
  <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    :root {
      --primary: #1a73e8;
      --primary-dark: #0d47a1;
      --primary-light: #4285f4;
      --secondary: #34a853;
      --accent: #fbbc05;
      --danger: #ea4335;
      --warning: #f29900;
      --info: #4285f4;
      --dark: #202124;
      --light: #f8f9fa;
      --gray: #5f6368;
      --border: #e8eaed;
      --sidebar: #1e293b;
      --sidebar-hover: #334155;
      --card-bg: rgba(255, 255, 255, 0.95);
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      background-attachment: fixed;
      min-height: 100vh;
      color: var(--dark);
      line-height: 1.6;
    }

    .dashboard-container {
      display: flex;
      min-height: 100vh;
    }

    /* Sidebar */
    .sidebar {
      width: 260px;
      background: var(--sidebar);
      color: white;
      position: fixed;
      height: 100vh;
      overflow-y: auto;
      transition: all 0.3s ease;
      z-index: 1000;
    }

    .sidebar-header {
      padding: 25px 20px;
      border-bottom: 1px solid rgba(255,255,255,0.1);
      background: rgba(0,0,0,0.2);
    }

    .sidebar-brand {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .sidebar-brand i {
      font-size: 1.8rem;
      color: var(--primary-light);
    }

    .sidebar-brand h3 {
      font-size: 1.3rem;
      font-weight: 600;
      margin: 0;
    }

    .sidebar-menu {
      padding: 20px 0;
    }

    .nav-item {
      margin-bottom: 5px;
    }

    .nav-link {
      color: rgba(255,255,255,0.8);
      padding: 12px 25px;
      display: flex;
      align-items: center;
      gap: 12px;
      transition: all 0.3s ease;
      border-left: 3px solid transparent;
    }

    .nav-link:hover, .nav-link.active {
      background: var(--sidebar-hover);
      color: white;
      border-left-color: var(--primary-light);
    }

    .nav-link i {
      font-size: 1.1rem;
      width: 20px;
      text-align: center;
    }

    /* Main Content */
    .main-content {
      flex: 1;
      margin-left: 260px;
      padding: 20px;
      background: #f5f7fa;
    }

    /* Top Bar */
    .top-bar {
      background: white;
      border-radius: 15px;
      padding: 15px 25px;
      margin-bottom: 25px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
      display: flex;
      justify-content: between;
      align-items: center;
    }

    .welcome-section h1 {
      font-size: 1.8rem;
      font-weight: 700;
      color: var(--dark);
      margin-bottom: 5px;
    }

    .welcome-section p {
      color: var(--gray);
      margin: 0;
    }

    .user-menu {
      display: flex;
      align-items: center;
      gap: 900px;
    }

    .notification-badge {
      position: relative;
    }

    .badge-count {
      position: absolute;
      top: -5px;
      right: -5px;
      background: var(--danger);
      color: white;
      border-radius: 50%;
      width: 18px;
      height: 18px;
      font-size: 0.7rem;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    /* Stats Grid */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 20px;
      margin-bottom: 25px;
    }

    .stat-card {
      background: var(--card-bg);
      border-radius: 20px;
      padding: 25px;
      box-shadow: 0 8px 30px rgba(0,0,0,0.08);
      border: 1px solid rgba(255,255,255,0.2);
      backdrop-filter: blur(10px);
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(to right, var(--primary), var(--secondary));
    }

    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 15px 40px rgba(0,0,0,0.15);
    }

    .stat-icon {
      width: 60px;
      height: 60px;
      border-radius: 15px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      margin-bottom: 15px;
    }

    .pending .stat-icon { background: rgba(234, 67, 53, 0.1); color: var(--danger); }
    .completed .stat-icon { background: rgba(52, 168, 83, 0.1); color: var(--secondary); }
    .urgent .stat-icon { background: rgba(251, 188, 5, 0.1); color: var(--warning); }

    .stat-content h3 {
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 5px;
      color: var(--dark);
    }

    .stat-content p {
      color: var(--gray);
      margin: 0;
      font-weight: 500;
    }

    .trend {
      display: flex;
      align-items: center;
      gap: 5px;
      font-size: 0.85rem;
      margin-top: 8px;
    }

    .trend.up { color: var(--secondary); }
    .trend.down { color: var(--danger); }

    /* Dashboard Grid */
    .dashboard-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      grid-template-rows: auto auto;
      gap: 25px;
      margin-bottom: 25px;
    }

    .grid-card {
      background: var(--card-bg);
      border-radius: 20px;
      padding: 25px;
      box-shadow: 0 8px 30px rgba(0,0,0,0.08);
      border: 1px solid rgba(255,255,255,0.2);
    }

    .card-header {
      display: flex;
      justify-content: between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 1px solid var(--border);
    }

    .card-header h3 {
      font-size: 1.3rem;
      font-weight: 600;
      color: var(--dark);
      margin: 0;
    }

    .view-all {
      color: var(--primary);
      text-decoration: none;
      font-weight: 500;
      font-size: 0.9rem;
    }

    /* Next Patient Card */
    .next-patient {
      grid-column: 1;
      grid-row: 1;
    }

    .patient-info {
      display: flex;
      align-items: center;
      gap: 20px;
      margin-bottom: 20px;
    }

    .patient-avatar {
      width: 80px;
      height: 80px;
      border-radius: 20px;
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 2rem;
    }

    .patient-details h4 {
      font-size: 1.4rem;
      font-weight: 600;
      margin-bottom: 5px;
    }

    .patient-meta {
      display: flex;
      gap: 15px;
      margin-bottom: 10px;
    }

    .meta-item {
      display: flex;
      align-items: center;
      gap: 5px;
      color: var(--gray);
      font-size: 0.9rem;
    }

    .priority-badge {
      background: var(--danger);
      color: white;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
    }

    .patient-actions {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
      margin-top: 20px;
    }

    .action-btn {
      padding: 12px;
      border-radius: 12px;
      border: none;
      font-weight: 500;
      transition: all 0.3s ease;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .primary-btn {
      background: var(--primary);
      color: white;
    }

    .outline-btn {
      background: transparent;
      border: 2px solid var(--border);
      color: var(--gray);
    }

    .action-btn:hover {
      transform: translateY(-2px);
    }

    .primary-btn:hover {
      background: var(--primary-dark);
    }

    .outline-btn:hover {
      border-color: var(--primary);
      color: var(--primary);
    }

    /* Appointments List */
    .appointments-list {
      grid-column: 2;
      grid-row: 1;
    }

    .appointment-item {
      display: flex;
      align-items: center;
      gap: 15px;
      padding: 15px;
      border-radius: 15px;
      margin-bottom: 12px;
      background: rgba(248, 249, 250, 0.8);
      transition: all 0.3s ease;
      border-left: 4px solid var(--primary);
    }

    .appointment-item:hover {
      background: white;
      transform: translateX(5px);
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .appointment-time {
      background: var(--primary);
      color: white;
      padding: 8px 12px;
      border-radius: 10px;
      font-weight: 600;
      min-width: 70px;
      text-align: center;
    }

    .appointment-details {
      flex: 1;
    }

    .appointment-details h5 {
      font-weight: 600;
      margin-bottom: 5px;
    }

    .appointment-details p {
      color: var(--gray);
      font-size: 0.9rem;
      margin: 0;
    }

    /* Charts Section */
    .charts-grid {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: 25px;
      margin-bottom: 25px;
    }

    .chart-container {
      position: relative;
      height: 300px;
    }

    /* Recent Activities */
    .activities-list {
      grid-column: 1 / -1;
    }

    .activity-item {
      display: flex;
      align-items: center;
      gap: 15px;
      padding: 15px;
      border-radius: 15px;
      margin-bottom: 10px;
      background: rgba(248, 249, 250, 0.8);
      transition: all 0.3s ease;
    }

    .activity-item:hover {
      background: white;
      transform: translateX(5px);
    }

    .activity-icon {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1rem;
    }

    .activity-success { background: var(--secondary); }
    .activity-warning { background: var(--warning); }
    .activity-info { background: var(--info); }

    .activity-content {
      flex: 1;
    }

    .activity-content p {
      margin: 0;
      font-weight: 500;
    }

    .activity-time {
      color: var(--gray);
      font-size: 0.85rem;
    }

    /* Responsive */
    @media (max-width: 1200px) {
      .dashboard-grid {
        grid-template-columns: 1fr;
      }
      
      .charts-grid {
        grid-template-columns: 1fr 1fr;
      }
    }

    @media (max-width: 768px) {
      .sidebar {
        transform: translateX(-100%);
      }
      
      .main-content {
        margin-left: 0;
      }
      
      .stats-grid {
        grid-template-columns: 1fr;
      }
      
      .charts-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <div class="dashboard-container">
    
    <!-- Sidebar -->
    <div class="sidebar">
      <div class="sidebar-header">
        <div class="sidebar-brand">
          <i class="fas fa-hospital-alt"></i>
          <h3>MediLab Pro</h3>
        </div>
      </div>
      
      <div class="sidebar-menu">
        <ul class="nav flex-column">
          <li class="nav-item">
            <a class="nav-link active" href="#">
              <i class="fas fa-tachometer-alt"></i>
              <span>Dashboard</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="manage_tests.php">
              <i class="fas fa-vial"></i>
              <span>Test Management</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="patient_search.php">
              <i class="fas fa-search"></i>
              <span>Patient Search</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="reports.php">
              <i class="fas fa-chart-bar"></i>
              <span>Reports & Analytics</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="schedule.php">
              <i class="fas fa-calendar-alt"></i>
              <span>Schedule</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="inventory.php">
              <i class="fas fa-boxes"></i>
              <span>Inventory</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="settings.php">
              <i class="fas fa-cog"></i>
              <span>Settings</span>
            </a>
          </li>
        </ul>
      </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
      
      <!-- Top Bar -->
      <div class="top-bar">
        <div class="welcome-section">
          <h1>Welcome back, <?= htmlspecialchars($technician_username) ?>! 👋</h1>
          <p>Here's what's happening in your lab today</p>
        </div>
        
        <div class="user-menu">
          <div class="notification-badge">
            <i class="fas fa-bell fa-lg" style="color: var(--gray);"></i>
            <span class="badge-count">3</span>
          </div>
          <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
              <i class="fas fa-user-circle me-2"></i>
              <?= htmlspecialchars($technician_username) ?>
            </button>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
              <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="stats-grid">
        <div class="stat-card pending">
          <div class="stat-icon">
            <i class="fas fa-vial"></i>
          </div>
          <div class="stat-content">
            <h3><?= $pendingTestsCount ?></h3>
            <p>Pending Tests</p>
            <div class="trend up">
              <i class="fas fa-arrow-up"></i>
              <span>12% from yesterday</span>
            </div>
          </div>
        </div>
        
        <div class="stat-card completed">
          <div class="stat-icon">
            <i class="fas fa-check-circle"></i>
          </div>
          <div class="stat-content">
            <h3><?= $completedTodayCount ?></h3>
            <p>Completed Today</p>
            <div class="trend up">
              <i class="fas fa-arrow-up"></i>
              <span>8% from yesterday</span>
            </div>
          </div>
        </div>
        
        <div class="stat-card urgent">
          <div class="stat-icon">
            <i class="fas fa-exclamation-triangle"></i>
          </div>
          <div class="stat-content">
            <h3><?= $urgentTestsCount ?></h3>
            <p>Urgent Tests</p>
            <div class="trend down">
              <i class="fas fa-arrow-down"></i>
              <span>5% from yesterday</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Dashboard Grid -->
      <div class="dashboard-grid">
        
        <!-- Next Patient -->
        <div class="grid-card next-patient">
          <div class="card-header">
            <h3>Next Patient</h3>
            <?php if ($next): ?>
              <span class="priority-badge">UPCOMING</span>
            <?php endif; ?>
          </div>
          
          <?php if ($next): ?>
            <div class="patient-info">
              <div class="patient-avatar">
                <i class="fas fa-user"></i>
              </div>
              <div class="patient-details">
                <h4><?= htmlspecialchars($next['full_name']) ?></h4>
                <div class="patient-meta">
                  <div class="meta-item">
                    <i class="fas fa-id-card"></i>
                    <span>ID: <?= htmlspecialchars($next['patient_id']) ?></span>
                  </div>
                  <div class="meta-item">
                    <i class="fas fa-phone"></i>
                    <span><?= htmlspecialchars($next['phone']) ?></span>
                  </div>
                </div>
                <p class="text-muted"><?= htmlspecialchars($next['test_name']) ?></p>
              </div>
            </div>
            
            <div class="patient-actions">
              <button class="action-btn primary-btn">
                <i class="fas fa-play"></i>
                Start Test
              </button>
              <button class="action-btn outline-btn">
                <i class="fas fa-eye"></i>
                View Details
              </button>
            </div>
          <?php else: ?>
            <div class="text-center py-5">
              <i class="fas fa-user-clock fa-3x text-muted mb-3"></i>
              <h5 class="text-muted">No upcoming patients</h5>
              <p class="text-muted">All tests are completed for now</p>
            </div>
          <?php endif; ?>
        </div>

        <!-- Upcoming Appointments -->
        <div class="grid-card appointments-list">
          <div class="card-header">
            <h3>Upcoming Appointments</h3>
            <a href="schedule.php" class="view-all">View All</a>
          </div>
          
          <?php if (!empty($appointments)): ?>
            <?php foreach ($appointments as $appointment): ?>
              <div class="appointment-item">
                <div class="appointment-time">
                  <?= date("H:i", strtotime($appointment['preferred_date'])) ?>
                </div>
                <div class="appointment-details">
                  <h5><?= htmlspecialchars($appointment['full_name']) ?></h5>
                  <p><?= htmlspecialchars($appointment['test_name']) ?></p>
                </div>
                <div class="dropdown">
                  <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-ellipsis-v"></i>
                  </button>
                  <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="view_details.php?id=<?= $appointment['id'] ?>">View Details</a></li>
                    <li><a class="dropdown-item" href="contact_patient.php?id=<?= $appointment['patient_id'] ?>">Contact</a></li>
                    <li><a class="dropdown-item" href="start_test.php?id=<?= $appointment['id'] ?>">Start Test</a></li>
                  </ul>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="text-center py-4">
              <i class="fas fa-calendar-times fa-2x text-muted mb-3"></i>
              <p class="text-muted">No appointments scheduled</p>
            </div>
          <?php endif; ?>
        </div>

        <!-- Recent Activities -->
        <div class="grid-card activities-list">
          <div class="card-header">
            <h3>Recent Activities</h3>
            <a href="activities.php" class="view-all">View All</a>
          </div>
          
          <?php if (!empty($recentActivities)): ?>
            <?php foreach ($recentActivities as $activity): ?>
              <div class="activity-item">
                <div class="activity-icon activity-success">
                  <i class="fas fa-vial"></i>
                </div>
                <div class="activity-content">
                  <p><?= htmlspecialchars($activity['action']) ?></p>
                  <div class="activity-time">
                    <?= date("M j, H:i", strtotime($activity['timestamp'])) ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="text-center py-4">
              <i class="fas fa-history fa-2x text-muted mb-3"></i>
              <p class="text-muted">No recent activities</p>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Charts Section -->
      <div class="charts-grid">
        
        <!-- Appointments Chart -->
        <div class="grid-card">
          <div class="card-header">
            <h3>Appointments Today</h3>
          </div>
          <div class="chart-container">
            <canvas id="barChart"></canvas>
          </div>
        </div>

        <!-- Weekly Trend -->
        <div class="grid-card">
          <div class="card-header">
            <h3>Weekly Trend</h3>
          </div>
          <div class="chart-container">
            <canvas id="lineChart"></canvas>
          </div>
        </div>

        <!-- Gender Distribution -->
        <div class="grid-card">
          <div class="card-header">
            <h3>Gender Distribution</h3>
          </div>
          <div class="chart-container">
            <canvas id="pieChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap & Chart Scripts -->
  <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script>
    // Bar Chart - Appointments per hour
    const ctx1 = document.getElementById('barChart').getContext('2d');
    new Chart(ctx1, {
      type: 'bar',
      data: {
        labels: <?= json_encode($hours) ?>,
        datasets: [{
          label: 'Appointments',
          data: <?= json_encode($data) ?>,
          backgroundColor: 'rgba(26, 115, 232, 0.8)',
          borderColor: 'rgba(26, 115, 232, 1)',
          borderWidth: 2,
          borderRadius: 8,
          borderSkipped: false,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            titleFont: { size: 14 },
            bodyFont: { size: 13 },
            padding: 12,
            cornerRadius: 8
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: {
              color: 'rgba(0, 0, 0, 0.05)'
            },
            ticks: {
              font: { size: 11 }
            }
          },
          x: {
            grid: {
              display: false
            },
            ticks: {
              font: { size: 11 }
            }
          }
        }
      }
    });

    // Line Chart - Weekly trend
    const ctx2 = document.getElementById('lineChart').getContext('2d');
    new Chart(ctx2, {
      type: 'line',
      data: {
        labels: <?= json_encode($weeklyData['labels']) ?>,
        datasets: [{
          label: 'Appointments',
          data: <?= json_encode($weeklyData['data']) ?>,
          borderColor: 'rgba(52, 168, 83, 1)',
          backgroundColor: 'rgba(52, 168, 83, 0.1)',
          borderWidth: 3,
          tension: 0.4,
          fill: true,
          pointBackgroundColor: 'rgba(52, 168, 83, 1)',
          pointBorderColor: '#fff',
          pointBorderWidth: 2,
          pointRadius: 6,
          pointHoverRadius: 8
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: {
              color: 'rgba(0, 0, 0, 0.05)'
            }
          },
          x: {
            grid: {
              display: false
            }
          }
        }
      }
    });

    // Pie Chart - Gender distribution
    const ctx3 = document.getElementById('pieChart').getContext('2d');
    new Chart(ctx3, {
      type: 'doughnut',
      data: {
        labels: ['Male', 'Female', 'Other'],
        datasets: [{
          data: <?= json_encode(array_values($genderCounts)) ?>,
          backgroundColor: [
            'rgba(26, 115, 232, 0.8)',
            'rgba(234, 67, 53, 0.8)',
            'rgba(251, 188, 5, 0.8)'
          ],
          borderColor: [
            'rgba(26, 115, 232, 1)',
            'rgba(234, 67, 53, 1)',
            'rgba(251, 188, 5, 1)'
          ],
          borderWidth: 2,
          hoverOffset: 15
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              padding: 20,
              usePointStyle: true,
              pointStyle: 'circle'
            }
          }
        },
        cutout: '65%'
      }
    });
  </script>
</body>
</html>