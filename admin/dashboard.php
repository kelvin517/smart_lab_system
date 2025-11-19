<?php
// =============================================
// SMART LABORATORY SYSTEM - ADMIN DASHBOARD
// =============================================
session_start();
include '../config/db.php';

// ✅ Enable error reporting (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ✅ Redirect if not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];

// ✅ Fetch admin details securely
$stmt = $conn->prepare("SELECT full_name, email FROM admins WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$admin) {
    $admin = ['full_name' => 'Administrator', 'email' => ''];
}

// ✅ Fetch system statistics (with fallbacks)
function fetch_count($conn, $table) {
    $query = $conn->query("SELECT COUNT(*) AS total FROM $table");
    return ($query && $row = $query->fetch_assoc()) ? $row['total'] : 0;
}

$total_patients     = fetch_count($conn, 'patients');
$total_doctors      = fetch_count($conn, 'doctors');
$total_technicians  = fetch_count($conn, 'technicians');
$total_tests        = fetch_count($conn, 'test_results');

// Fetch recent appointments
$recent_appointments = $conn->query("SELECT p.full_name, b.test_name, b.preferred_date, b.status 
                                   FROM bookings b 
                                   JOIN patients p ON b.patient_id = p.patient_id 
                                   ORDER BY b.preferred_date DESC 
                                   LIMIT 5");

// Fetch system alerts
$system_alerts = $conn->query("SELECT message, level, created_at FROM system_alerts 
                             WHERE resolved = 0 
                             ORDER BY created_at DESC 
                             LIMIT 3");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Smart Lab System</title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #3498db;
            --secondary: #2c3e50;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
            --info: #17a2b8;
            --light: #ecf0f1;
            --dark: #34495e;
            --sidebar-width: 250px;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            overflow-x: hidden;
        }
        
        /* Navbar Styles */
        .navbar-custom {
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            padding: 15px 0;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: white !important;
        }
        
        .navbar-brand i {
            margin-right: 10px;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
            padding: 8px 15px;
            margin: 0 5px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: white !important;
            transform: translateY(-1px);
        }
        
        .nav-link i {
            margin-right: 8px;
            width: 20px;
            text-align: center;
        }
        
        .user-dropdown .dropdown-toggle {
            color: white;
            font-weight: 500;
        }
        
        .user-dropdown .dropdown-menu {
            border: none;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15);
            border-radius: 10px;
        }
        
        /* Main Content */
        .main-content {
            margin-top: 80px;
            padding: 20px;
        }
        
        .pagetitle {
            margin-bottom: 30px;
        }
        
        .pagetitle h1 {
            color: var(--secondary);
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .breadcrumb {
            background: transparent;
            padding: 0;
        }
        
        /* Welcome Banner */
        .welcome-banner {
            background: linear-gradient(135deg, var(--primary), #2980b9);
            border: none;
            border-radius: 15px;
            color: white;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(52, 152, 219, 0.3);
        }
        
        .welcome-banner i {
            font-size: 2rem;
            margin-right: 15px;
            opacity: 0.9;
        }
        
        /* Stats Cards */
        .info-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }
        
        .card-icon {
            width: 60px;
            height: 60px;
            transition: all 0.3s ease;
        }
        
        .info-card:hover .card-icon {
            transform: scale(1.1);
        }
        
        .card-title {
            color: var(--secondary);
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        
        .card-title span {
            color: #6c757d;
            font-weight: 400;
        }
        
        .info-card h6 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--secondary);
        }
        
        /* Recent Tables */
        .recent-sales {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }
        
        .table th {
            background: var(--light);
            color: var(--secondary);
            font-weight: 600;
            border: none;
            padding: 15px;
        }
        
        .table td {
            padding: 15px;
            vertical-align: middle;
            border-color: #f1f1f1;
        }
        
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        /* Alert Styles */
        .alert-custom {
            border: none;
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 20px;
        }
        
        .alert-warning {
            background: rgba(243, 156, 18, 0.1);
            color: #e67e22;
            border-left: 4px solid var(--warning);
        }
        
        .alert-danger {
            background: rgba(231, 76, 60, 0.1);
            color: #c0392b;
            border-left: 4px solid var(--danger);
        }
        
        .alert-info {
            background: rgba(23, 162, 184, 0.1);
            color: #2980b9;
            border-left: 4px solid var(--info);
        }
        
        /* Quick Actions */
        .quick-actions {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }
        
        .action-btn {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            background: #f8f9fa;
            border: 2px solid transparent;
            border-radius: 10px;
            color: var(--secondary);
            text-decoration: none;
            transition: all 0.3s ease;
            margin-bottom: 15px;
        }
        
        .action-btn:hover {
            background: white;
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-2px);
            text-decoration: none;
        }
        
        .action-btn i {
            font-size: 1.5rem;
            margin-right: 15px;
            width: 30px;
            text-align: center;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .navbar-nav {
                text-align: center;
            }
            
            .nav-link {
                margin: 5px 0;
            }
            
            .main-content {
                margin-top: 60px;
                padding: 15px;
            }
            
            .welcome-banner {
                padding: 20px;
            }
        }
        
        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-custom fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-vial"></i>Smart Lab System
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon">
                    <i class="fas fa-bars text-white"></i>
                </span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-home"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_patients.php">
                            <i class="fas fa-users"></i>Patients
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_doctors.php">
                            <i class="fas fa-user-md"></i>Doctors
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_technicians.php">
                            <i class="fas fa-user-cog"></i>Technicians
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar"></i>Reports
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown user-dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-shield"></i>
                            <?= htmlspecialchars($admin['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php">
                                <i class="fas fa-user me-2"></i>Profile
                            </a></li>
                            <li><a class="dropdown-item" href="settings.php">
                                <i class="fas fa-cog me-2"></i>Settings
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="pagetitle fade-in">
            <h1>Admin Dashboard</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                </ol>
            </nav>
        </div>

        <section class="section dashboard">
            <!-- Welcome Banner -->
            <div class="row">
                <div class="col-12">
                    <div class="alert welcome-banner fade-in">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-user-shield"></i>
                            <div>
                                <h4 class="mb-1">Welcome back, <?= htmlspecialchars($admin['full_name']); ?>!</h4>
                                <p class="mb-0">Manage users, results, and operations from this dashboard.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Alerts -->
            <?php if ($system_alerts && $system_alerts->num_rows > 0): ?>
            <div class="row">
                <div class="col-12">
                    <?php while ($alert = $system_alerts->fetch_assoc()): ?>
                    <div class="alert alert-<?= $alert['level'] ?> alert-custom fade-in">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?= htmlspecialchars($alert['message']) ?>
                        <small class="float-end"><?= date('M j, g:i A', strtotime($alert['created_at'])) ?></small>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="row mb-4">
                <div class="col-lg-12">
                    <div class="quick-actions fade-in">
                        <h5 class="mb-3"><i class="fas fa-bolt text-warning me-2"></i>Quick Actions</h5>
                        <div class="row">
                            <div class="col-md-3 col-6">
                                <a href="billing.php" class="action-btn">
                                    <i class="fas fa-user-plus text-primary"></i>
                                    <div>
                                        <strong>Billing</strong>
                                        <small class="d-block text-muted">View Billing</small>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-3 col-6">
                                <a href="analytics.php" class="action-btn">
                                    <i class="fas fa-cogs text-success"></i>
                                    <div>
                                        <strong>Analytics</strong>
                                        <small class="d-block text-muted">view analysis</small>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-3 col-6">
                                <a href="reports.php" class="action-btn">
                                    <i class="fas fa-chart-pie text-info"></i>
                                    <div>
                                        <strong>Reports</strong>
                                        <small class="d-block text-muted">view reports</small>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-3 col-6">
                                <a href="system_settings.php" class="action-btn">
                                    <i class="fas fa-sliders-h text-warning"></i>
                                    <div>
                                        <strong>Settings</strong>
                                        <small class="d-block text-muted">System config</small>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4 fade-in">
                    <div class="card info-card">
                        <div class="card-body">
                            <h5 class="card-title">Patients <span>| Registered</span></h5>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center bg-primary text-white">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="ps-3">
                                    <h6><?= $total_patients; ?></h6>
                                    <span class="text-muted small">Total Patients</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4 fade-in">
                    <div class="card info-card">
                        <div class="card-body">
                            <h5 class="card-title">Doctors <span>| Active</span></h5>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center bg-success text-white">
                                    <i class="fas fa-user-md"></i>
                                </div>
                                <div class="ps-3">
                                    <h6><?= $total_doctors; ?></h6>
                                    <span class="text-muted small">Registered Doctors</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4 fade-in">
                    <div class="card info-card">
                        <div class="card-body">
                            <h5 class="card-title">Technicians <span>| Lab Staff</span></h5>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center bg-warning text-white">
                                    <i class="fas fa-user-cog"></i>
                                </div>
                                <div class="ps-3">
                                    <h6><?= $total_technicians; ?></h6>
                                    <span class="text-muted small">Lab Technicians</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4 fade-in">
                    <div class="card info-card">
                        <div class="card-body">
                            <h5 class="card-title">Test Results <span>| Completed</span></h5>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center bg-danger text-white">
                                    <i class="fas fa-file-medical"></i>
                                </div>
                                <div class="ps-3">
                                    <h6><?= $total_tests; ?></h6>
                                    <span class="text-muted small">Results Uploaded</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Row -->
            <div class="row">
                <!-- Recent Logins -->
                <div class="col-lg-8">
                    <div class="card recent-sales fade-in">
                        <div class="card-body">
                            <h5 class="card-title">Recent Logins <span>| Last 10 Activities</span></h5>

                            <div class="table-responsive">
                                <table class="table table-borderless align-middle">
                                    <thead>
                                        <tr>
                                            <th scope="col">#</th>
                                            <th scope="col">Username</th>
                                            <th scope="col">Role</th>
                                            <th scope="col">Email</th>
                                            <th scope="col">Login Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $i = 1;
                                        $logs = $conn->query("SELECT username, role, email, login_time FROM login_logs ORDER BY login_time DESC LIMIT 10");
                                        if ($logs && $logs->num_rows > 0) {
                                            while ($row = $logs->fetch_assoc()) {
                                                echo "<tr>
                                                    <th scope='row'>{$i}</th>
                                                    <td>" . htmlspecialchars($row['username']) . "</td>
                                                    <td><span class='badge bg-info'>" . htmlspecialchars($row['role']) . "</span></td>
                                                    <td>" . htmlspecialchars($row['email']) . "</td>
                                                    <td>" . date('M j, g:i A', strtotime($row['login_time'])) . "</td>
                                                </tr>";
                                                $i++;
                                            }
                                        } else {
                                            echo "<tr><td colspan='5' class='text-center text-muted py-4'>No recent login activity.</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Appointments -->
                <div class="col-lg-4">
                    <div class="card recent-sales fade-in">
                        <div class="card-body">
                            <h5 class="card-title">Recent Appointments <span>| Today</span></h5>
                            
                            <?php if ($recent_appointments && $recent_appointments->num_rows > 0): ?>
                                <div class="list-group list-group-flush">
                                    <?php while ($appointment = $recent_appointments->fetch_assoc()): ?>
                                    <div class="list-group-item px-0">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?= htmlspecialchars($appointment['full_name']) ?></h6>
                                            <small class="text-muted"><?= date('H:i', strtotime($appointment['preferred_date'])) ?></small>
                                        </div>
                                        <p class="mb-1"><?= htmlspecialchars($appointment['test_name']) ?></p>
                                        <small class="text-<?= $appointment['status'] == 'completed' ? 'success' : 'warning' ?>">
                                            <?= ucfirst($appointment['status']) ?>
                                        </small>
                                    </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-center text-muted py-3">No recent appointments.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add active class to current nav item
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = window.location.pathname.split('/').pop();
            const navLinks = document.querySelectorAll('.nav-link');
            
            navLinks.forEach(link => {
                if (link.getAttribute('href') === currentPage) {
                    link.classList.add('active');
                }
            });
            
            // Add loading animation to cards
            const cards = document.querySelectorAll('.fade-in');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });
        });
    </script>
</body>
</html>