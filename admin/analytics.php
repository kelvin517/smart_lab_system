<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];

// ✅ Fetch admin details
$stmt = $conn->prepare("SELECT full_name FROM admins WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ✅ Fetch analytics data
// Monthly bookings
$sql = "
    SELECT 
        DATE(created_at) AS day,
        COUNT(*) AS total
    FROM bookings
    GROUP BY DATE(created_at)
    ORDER BY day ASC
";
$result = $conn->query($sql);


// Test types distribution
$testTypes = $conn->query("
    SELECT test_name, COUNT(*) as count 
    FROM bookings 
    GROUP BY test_name 
    ORDER BY count DESC 
    LIMIT 8
");

// Billing status
$billingStats = $conn->query("
    SELECT status, COUNT(*) as count 
    FROM billing 
    GROUP BY status
");

// Revenue by month
$sql = "
    SELECT 
        YEAR(created_at) AS year,
        MONTH(created_at) AS month,
        SUM(amount) AS total_revenue
    FROM billing
    GROUP BY YEAR(created_at), MONTH(created_at)
    ORDER BY year, month
";
$result = $conn->query($sql);


// Patient demographics
$patientGender = $conn->query("
    SELECT gender, COUNT(*) as count 
    FROM patients 
    WHERE gender IS NOT NULL AND gender != ''
    GROUP BY gender
");

// Daily appointments
$dailyAppointments = $conn->query("
    SELECT DAYNAME(preferred_date) as day, COUNT(*) as count
    FROM bookings 
    WHERE preferred_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DAYOFWEEK(preferred_date), DAYNAME(preferred_date)
    ORDER BY DAYOFWEEK(preferred_date)
");

// Technician performance
$technicianPerformance = $conn->query("
    SELECT s.full_name, COUNT(b.id) as test_count
    FROM staff s
    LEFT JOIN bookings b ON s.id = b.handled_by 
    WHERE s.role = 'technician' AND b.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY s.id, s.full_name
    ORDER BY test_count DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - Smart Lab System</title>
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
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
        }
        
        .navbar-custom {
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }
        
        .main-content {
            margin-top: 80px;
            padding: 20px;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
            transition: transform 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            background: white;
            border-bottom: 1px solid #e9ecef;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px 25px;
        }
        
        .stats-card {
            text-align: center;
            padding: 25px 20px;
            border-radius: 12px;
            color: white;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .stats-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
            opacity: 0.9;
        }
        
        .stats-card .number {
            font-size: 2.2rem;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .stats-card .label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .total-bookings { background: linear-gradient(135deg, #3498db, #2980b9); }
        .total-revenue { background: linear-gradient(135deg, #27ae60, #229954); }
        .total-patients { background: linear-gradient(135deg, #9b59b6, #8e44ad); }
        .active-technicians { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        
        .chart-container {
            position: relative;
            height: 300px;
            padding: 20px;
        }
        
        .analytics-header {
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-item {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }
        
        .stat-item .value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 5px;
        }
        
        .stat-item .label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .chart-container {
                height: 250px;
            }
            
            .stats-card .number {
                font-size: 1.8rem;
            }
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
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_patients.php">
                            <i class="fas fa-users"></i>Patients
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="analytics.php">
                            <i class="fas fa-chart-bar"></i>Analytics
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-shield"></i>
                            <?= htmlspecialchars($admin['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="logout.php">
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
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="analytics-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="h2 mb-2">Analytics Dashboard</h1>
                        <p class="mb-0 opacity-75">Comprehensive insights and performance metrics for your laboratory</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="btn-group">
                            <button class="btn btn-outline-light" onclick="updateCharts('week')">Week</button>
                            <button class="btn btn-outline-light" onclick="updateCharts('month')">Month</button>
                            <button class="btn btn-light active" onclick="updateCharts('year')">Year</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="quick-stats">
                <?php
                $total_bookings = $conn->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'];
                $total_revenue = $conn->query("SELECT SUM(amount) as total FROM billing WHERE status = 'paid'")->fetch_assoc()['total'] ?? 0;
                $total_patients = $conn->query("SELECT COUNT(*) as count FROM patients")->fetch_assoc()['count'];
                $active_tech = $conn->query("SELECT COUNT(*) as count FROM staff WHERE role = 'technician' AND status = 'active'")->fetch_assoc()['count'];
                ?>
                <div class="stat-item">
                    <div class="value"><?= number_format($total_bookings) ?></div>
                    <div class="label">Total Bookings</div>
                </div>
                <div class="stat-item">
                    <div class="value">KES <?= number_format($total_revenue, 0) ?></div>
                    <div class="label">Total Revenue</div>
                </div>
                <div class="stat-item">
                    <div class="value"><?= number_format($total_patients) ?></div>
                    <div class="label">Registered Patients</div>
                </div>
                <div class="stat-item">
                    <div class="value"><?= number_format($active_tech) ?></div>
                    <div class="label">Active Technicians</div>
                </div>
            </div>

            <div class="row">
                <!-- Monthly Bookings Chart -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-calendar-alt me-2 text-primary"></i>Monthly Bookings Trend</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="bookingsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Revenue Chart -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-money-bill-wave me-2 text-success"></i>Monthly Revenue</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="revenueChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Test Types Distribution -->
                <div class="col-lg-5">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-vial me-2 text-info"></i>Test Type Distribution</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="testTypeChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Billing Status -->
                <div class="col-lg-3">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-receipt me-2 text-warning"></i>Billing Status</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="billingChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Patient Demographics -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-users me-2 text-danger"></i>Patient Demographics</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="genderChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Daily Appointments -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-chart-line me-2 text-primary"></i>Appointments by Day</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="dailyAppointmentsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Technician Performance -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-user-cog me-2 text-success"></i>Technician Performance</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="technicianChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <script>
        // Chart colors
        const colors = {
            primary: '#3498db',
            success: '#27ae60',
            warning: '#f39c12',
            danger: '#e74c3c',
            info: '#17a2b8',
            purple: '#9b59b6',
            pink: '#e91e63'
        };

        // Chart 1: Monthly Bookings
        <?php
        $months = []; $totals = [];
        while ($row = $bookingData->fetch_assoc()) {
            $months[] = $row['month'];
            $totals[] = $row['total'];
        }
        ?>
        new Chart(document.getElementById('bookingsChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode($months) ?>,
                datasets: [{
                    label: 'Bookings',
                    data: <?= json_encode($totals) ?>,
                    borderColor: colors.primary,
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
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
                            color: 'rgba(0,0,0,0.05)'
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

        // Chart 2: Revenue Chart
        <?php
        $rev_months = []; $revenues = [];
        while ($row = $revenueData->fetch_assoc()) {
            $rev_months[] = $row['month'];
            $revenues[] = $row['revenue'];
        }
        ?>
        new Chart(document.getElementById('revenueChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($rev_months) ?>,
                datasets: [{
                    label: 'Revenue (KES)',
                    data: <?= json_encode($revenues) ?>,
                    backgroundColor: colors.success,
                    borderRadius: 8
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
                            color: 'rgba(0,0,0,0.05)'
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

        // Chart 3: Test Types Distribution
        <?php
        $labels = []; $counts = [];
        while ($row = $testTypes->fetch_assoc()) {
            $labels[] = $row['test_name'];
            $counts[] = $row['count'];
        }
        ?>
        new Chart(document.getElementById('testTypeChart'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    data: <?= json_encode($counts) ?>,
                    backgroundColor: [
                        colors.primary, colors.success, colors.warning, 
                        colors.danger, colors.info, colors.purple, colors.pink
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });

        // Chart 4: Billing Status
        <?php
        $billingLabels = []; $billingCounts = [];
        while ($row = $billingStats->fetch_assoc()) {
            $billingLabels[] = ucfirst($row['status']);
            $billingCounts[] = $row['count'];
        }
        ?>
        new Chart(document.getElementById('billingChart'), {
            type: 'pie',
            data: {
                labels: <?= json_encode($billingLabels) ?>,
                datasets: [{
                    data: <?= json_encode($billingCounts) ?>,
                    backgroundColor: [colors.success, colors.warning, colors.danger],
                    borderWidth: 2,
                    borderColor: '#fff'
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

        // Chart 5: Patient Demographics
        <?php
        $genders = []; $genderCounts = [];
        while ($row = $patientGender->fetch_assoc()) {
            $genders[] = $row['gender'];
            $genderCounts[] = $row['count'];
        }
        ?>
        new Chart(document.getElementById('genderChart'), {
            type: 'polarArea',
            data: {
                labels: <?= json_encode($genders) ?>,
                datasets: [{
                    data: <?= json_encode($genderCounts) ?>,
                    backgroundColor: [colors.primary, colors.pink, colors.info],
                    borderWidth: 2,
                    borderColor: '#fff'
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

        // Chart 6: Daily Appointments
        <?php
        $days = []; $dayCounts = [];
        while ($row = $dailyAppointments->fetch_assoc()) {
            $days[] = $row['day'];
            $dayCounts[] = $row['count'];
        }
        ?>
        new Chart(document.getElementById('dailyAppointmentsChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($days) ?>,
                datasets: [{
                    label: 'Appointments',
                    data: <?= json_encode($dayCounts) ?>,
                    backgroundColor: colors.info,
                    borderRadius: 8
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
                            color: 'rgba(0,0,0,0.05)'
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

        // Chart 7: Technician Performance
        <?php
        $techNames = []; $techCounts = [];
        while ($row = $technicianPerformance->fetch_assoc()) {
            $techNames[] = $row['full_name'];
            $techCounts[] = $row['test_count'];
        }
        ?>
        new Chart(document.getElementById('technicianChart'), {
            type: 'horizontalBar',
            data: {
                labels: <?= json_encode($techNames) ?>,
                datasets: [{
                    label: 'Tests Handled',
                    data: <?= json_encode($techCounts) ?>,
                    backgroundColor: colors.success,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Time period filter function
        function updateCharts(period) {
            // Remove active class from all buttons
            document.querySelectorAll('.btn-group .btn').forEach(btn => {
                btn.classList.remove('active');
                btn.classList.remove('btn-light');
                btn.classList.add('btn-outline-light');
            });
            
            // Add active class to clicked button
            event.target.classList.add('active');
            event.target.classList.add('btn-light');
            event.target.classList.remove('btn-outline-light');
            
            // Here you would typically reload charts with new data based on period
            console.log('Updating charts for period:', period);
            // In a real implementation, you would make an AJAX call to fetch new data
        }
    </script>
</body>
</html>