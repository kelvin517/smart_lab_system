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

// ✅ Date filters
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('m');
$selected_year  = isset($_GET['year']) ? $_GET['year'] : date('Y');
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'monthly';

// ✅ Build WHERE clause based on report type
if ($report_type === 'monthly') {
    $whereClause = "WHERE MONTH(created_at) = $selected_month AND YEAR(created_at) = $selected_year";
    $groupBy = "DAY(created_at)";
    $dateFormat = "DATE(created_at)";
} else {
    $whereClause = "WHERE YEAR(created_at) = $selected_year";
    $groupBy = "MONTH(created_at)";
    $dateFormat = "DATE_FORMAT(created_at, '%b %Y')";
}

// ✅ Fetch test bookings data
$datesArray = $countArray = [];

$q = $conn->query("
    SELECT 
        $dateFormat AS period,
        COUNT(*) AS count
    FROM bookings
    $whereClause
    GROUP BY period
    ORDER BY period ASC
");


// ✅ Fetch revenue data
$revenueDates = $revenueAmounts = [];

// Ensure $whereClause always starts with AND (never with WHERE)
if (!empty($whereClause)) {
    if (str_starts_with(trim($whereClause), "WHERE")) {
        $whereClause = preg_replace('/^WHERE/i', 'AND', $whereClause);
    }
}

$sql = "
    SELECT 
        $dateFormat AS period,
        SUM(amount) AS revenue
    FROM billing
    WHERE status = 'paid'
    $whereClause
    GROUP BY period
    ORDER BY period ASC
";

$rq = $conn->query($sql);

while ($r = $rq->fetch_assoc()) {
    $revenueDates[] = $r['period'];
    $revenueAmounts[] = $r['revenue'];
}


// ✅ Fetch test type distribution
$whereClause = []; // array to hold conditions

if (!empty($month)) {
    $whereClause[] = "MONTH(created_at) = $month";
}

if (!empty($year)) {
    $whereClause[] = "YEAR(created_at) = $year";
}

if (!empty($whereClause)) {
    $whereClause = "WHERE " . implode(" AND ", $whereClause);
} else {
    $whereClause = ""; // no filtering
}


// ✅ Fetch technician performance
$technicians = $techCounts = [];

// Ensure $start and $end are defined
$start = $start ?? date('Y-m-d 00:00:00', strtotime('-30 days')); // default 30 days ago
$end   = $end ?? date('Y-m-d 23:59:59');                           // default today

// Correct SQL assignment
$sql = "SELECT t.full_name, COUNT(*) as test_count
        FROM bookings b
        JOIN technicians t ON t.technician_id = b.handled_by
        WHERE b.created_at BETWEEN '$start' AND '$end'
        GROUP BY t.technician_id
        ORDER BY test_count DESC";

// Execute the query
$techq = $conn->query($sql);

$technicians = $techCounts = [];
if ($techq) {
    while ($row = $techq->fetch_assoc()) {
        $technicians[] = $row['full_name'];
        $techCounts[] = $row['test_count'];
    }
} else {
    echo "SQL Error: " . $conn->error;
}

// Fetch results
while ($r = $techq->fetch_assoc()) {
    $technicians[] = $r['full_name'];
    $techCounts[] = $r['test_count'];
}

while ($r = $techq->fetch_assoc()) {
    $technicians[] = $r['full_name'];
    $techCounts[] = $r['test_count'];
}

// ✅ Fetch billing status summary
$billingSummary = $conn->query("
    SELECT status, COUNT(*) as count, SUM(amount) as total 
    FROM billing 
    $whereClause 
    GROUP BY status
")->fetch_all(MYSQLI_ASSOC);

// ✅ Fetch patient demographics
$patientStats = $conn->query("
    SELECT 
        COUNT(*) as total_patients,
        COUNT(CASE WHEN gender = 'Male' THEN 1 END) as male,
        COUNT(CASE WHEN gender = 'Female' THEN 1 END) as female,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active,
        COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as new_today
    FROM patients
")->fetch_assoc();

// ✅ Calculate summary statistics
$totalBookings = array_sum($countArray);
$totalRevenue = array_sum($revenueAmounts);
$avgTestsPerDay = $totalBookings > 0 ? round($totalBookings / count($countArray), 1) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Smart Lab System</title>
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
            transform: translateY(-2px);
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
        .avg-tests { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        
        .chart-container {
            position: relative;
            height: 300px;
            padding: 20px;
        }
        
        .report-header {
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .filter-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .summary-item {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }
        
        .summary-item .value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 5px;
        }
        
        .summary-item .label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .export-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .chart-container {
                height: 250px;
            }
            
            .export-buttons {
                justify-content: stretch;
            }
            
            .export-buttons .btn {
                flex: 1;
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
                        <a class="nav-link" href="analytics.php">
                            <i class="fas fa-chart-bar"></i>Analytics
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="reports.php">
                            <i class="fas fa-file-alt"></i>Reports
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
            <div class="report-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="h2 mb-2">Comprehensive Reports</h1>
                        <p class="mb-0 opacity-75">Detailed analytics and performance insights for your laboratory</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="export-buttons">
                            <button class="btn btn-light" onclick="exportToPDF()">
                                <i class="fas fa-file-pdf me-2"></i>Export PDF
                            </button>
                            <button class="btn btn-light" onclick="exportToExcel()">
                                <i class="fas fa-file-excel me-2"></i>Export Excel
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Report Type</label>
                        <select name="report_type" class="form-select" onchange="this.form.submit()">
                            <option value="monthly" <?= $report_type === 'monthly' ? 'selected' : '' ?>>Monthly Report</option>
                            <option value="yearly" <?= $report_type === 'yearly' ? 'selected' : '' ?>>Yearly Report</option>
                        </select>
                    </div>

                    <?php if ($report_type === 'monthly'): ?>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Select Month</label>
                        <select name="month" class="form-select" required>
                            <?php for ($m = 1; $m <= 12; $m++):
                                $value = str_pad($m, 2, '0', STR_PAD_LEFT);
                                $monthName = date("F", mktime(0, 0, 0, $m, 10)); ?>
                                <option value="<?= $value ?>" <?= ($value == $selected_month ? "selected" : "") ?>>
                                    <?= $monthName ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div class="col-md-3">
                        <label class="form-label fw-bold">Select Year</label>
                        <select name="year" class="form-select" required>
                            <?php $current_year = date('Y');
                            for ($y = $current_year; $y >= $current_year - 5; $y--): ?>
                                <option value="<?= $y ?>" <?= ($y == $selected_year ? "selected" : "") ?>>
                                    <?= $y ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-2"></i>Generate Report
                        </button>
                    </div>
                </form>
            </div>

            <!-- Summary Statistics -->
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="value"><?= number_format($totalBookings) ?></div>
                    <div class="label">Total Bookings</div>
                </div>
                <div class="summary-item">
                    <div class="value">KES <?= number_format($totalRevenue, 0) ?></div>
                    <div class="label">Total Revenue</div>
                </div>
                <div class="summary-item">
                    <div class="value"><?= number_format($patientStats['total_patients']) ?></div>
                    <div class="label">Total Patients</div>
                </div>
                <div class="summary-item">
                    <div class="value"><?= $avgTestsPerDay ?></div>
                    <div class="label">Avg Tests/Day</div>
                </div>
            </div>

            <div class="row">
                <!-- Tests Booked Chart -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-line me-2 text-primary"></i>
                                Tests Booked Trend
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="testsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Revenue Chart -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-money-bill-wave me-2 text-success"></i>
                                Revenue Trend (KES)
                            </h5>
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
                <!-- Test Type Distribution -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-vial me-2 text-info"></i>
                                Test Type Distribution
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="testTypeChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Technician Performance -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-user-cog me-2 text-warning"></i>
                                Technician Performance
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="technicianChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Billing Summary -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-receipt me-2 text-danger"></i>
                                Billing Summary
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Status</th>
                                            <th>Count</th>
                                            <th>Total Amount</th>
                                            <th>Percentage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($billingSummary as $bill): ?>
                                        <tr>
                                            <td>
                                                <span class="badge 
                                                    <?= $bill['status'] == 'paid' ? 'bg-success' : 
                                                       ($bill['status'] == 'pending' ? 'bg-warning' : 'bg-danger') ?>">
                                                    <?= ucfirst($bill['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= $bill['count'] ?></td>
                                            <td>KES <?= number_format($bill['total'] ?? 0, 2) ?></td>
                                            <td>
                                                <?php 
                                                $percentage = $totalRevenue > 0 ? ($bill['total'] / $totalRevenue) * 100 : 0;
                                                echo number_format($percentage, 1) . '%';
                                                ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
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

        // Tests Booked Chart
        new Chart(document.getElementById('testsChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode($datesArray) ?>,
                datasets: [{
                    label: 'Tests Booked',
                    data: <?= json_encode($countArray) ?>,
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

        // Revenue Chart
        new Chart(document.getElementById('revenueChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($revenueDates) ?>,
                datasets: [{
                    label: 'Revenue (KES)',
                    data: <?= json_encode($revenueAmounts) ?>,
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

        // Test Type Distribution
        new Chart(document.getElementById('testTypeChart'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($testTypes) ?>,
                datasets: [{
                    data: <?= json_encode($testCounts) ?>,
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

        // Technician Performance
        new Chart(document.getElementById('technicianChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($technicians) ?>,
                datasets: [{
                    label: 'Tests Handled',
                    data: <?= json_encode($techCounts) ?>,
                    backgroundColor: colors.warning,
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

        // Export functions
        function exportToPDF() {
            alert('PDF export functionality would be implemented here. This would generate a comprehensive report in PDF format.');
            // In a real implementation, this would make an AJAX call to generate PDF
        }

        function exportToExcel() {
            alert('Excel export functionality would be implemented here. This would generate an Excel spreadsheet with all report data.');
            // In a real implementation, this would make an AJAX call to generate Excel
        }
    </script>
</body>
</html>