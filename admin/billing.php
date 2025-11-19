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

// ✅ Check and create billing table if it doesn't exist
$table_check = $conn->query("SHOW TABLES LIKE 'billing'");
if ($table_check->num_rows == 0) {
    $create_billing_table = $conn->query("
        CREATE TABLE billing (
            id INT AUTO_INCREMENT PRIMARY KEY,
            booking_id INT NOT NULL,
            patient_id INT NOT NULL,
            test_name VARCHAR(255) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
            FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE
        )
    ");
    
    // Insert sample billing data if table was just created
    $conn->query("
        INSERT INTO billing (booking_id, patient_id, test_name, amount, status) 
        SELECT b.id, b.patient_id, b.test_name, 
               CASE 
                 WHEN b.test_name LIKE '%blood%' THEN 1500.00
                 WHEN b.test_name LIKE '%urine%' THEN 800.00
                 WHEN b.test_name LIKE '%x-ray%' THEN 2500.00
                 ELSE 1200.00 
               END as amount,
               'pending'
        FROM bookings b 
        WHERE NOT EXISTS (SELECT 1 FROM billing bl WHERE bl.booking_id = b.id)
        LIMIT 10
    ");
}

// ✅ Handle billing actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $bill_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    switch ($action) {
        case 'mark_paid':
            $update_stmt = $conn->prepare("UPDATE billing SET status = 'paid' WHERE id = ?");
            $update_stmt->bind_param("i", $bill_id);
            if ($update_stmt->execute()) {
                $_SESSION['success_msg'] = "Bill marked as paid successfully!";
            } else {
                $_SESSION['error_msg'] = "Error updating bill status: " . $conn->error;
            }
            $update_stmt->close();
            break;
            
        case 'cancel':
            $update_stmt = $conn->prepare("UPDATE billing SET status = 'cancelled' WHERE id = ?");
            $update_stmt->bind_param("i", $bill_id);
            if ($update_stmt->execute()) {
                $_SESSION['success_msg'] = "Bill cancelled successfully!";
            } else {
                $_SESSION['error_msg'] = "Error cancelling bill: " . $conn->error;
            }
            $update_stmt->close();
            break;
            
        case 'delete':
            $delete_stmt = $conn->prepare("DELETE FROM billing WHERE id = ?");
            $delete_stmt->bind_param("i", $bill_id);
            if ($delete_stmt->execute()) {
                $_SESSION['success_msg'] = "Bill deleted successfully!";
            } else {
                $_SESSION['error_msg'] = "Error deleting bill: " . $conn->error;
            }
            $delete_stmt->close();
            break;
    }
    
    header("Location: billing.php");
    exit();
}

// ✅ Search and filter functionality
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// ✅ Build query with filters
$where_conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(p.full_name LIKE ? OR b.test_name LIKE ? OR bil.id = ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search]);
    $types .= 'sss';
}

if (!empty($status_filter)) {
    $where_conditions[] = "bil.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(bil.created_at) >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(bil.created_at) <= ?";
    $params[] = $date_to;
    $types .= 's';
}

$where_sql = '';
if (!empty($where_conditions)) {
    $where_sql = "WHERE " . implode(" AND ", $where_conditions);
}

// ✅ Get total billing count for pagination
$count_sql = "SELECT COUNT(*) as total FROM billing 
              JOIN patients p ON patient_id = p.patient_id 
              JOIN bookings b ON booking_id = b.id 
              $where_sql";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_bills = $count_stmt->get_result()->fetch_assoc()['total'];
$count_stmt->close();

// ✅ Pagination
$per_page = 10;
$total_pages = ceil($total_bills / $per_page);
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $per_page;

// ✅ Fetch billing records with pagination and filters
$billing_sql = "SELECT billing.*, p.full_name, p.phone, p.email, b.test_name, b.preferred_date 
                FROM billing  
                JOIN patients p ON patient_id = p.patient_id 
                JOIN bookings b ON booking_id = b.id 
                $where_sql 
                ORDER BY billing.created_at DESC 
                LIMIT ? OFFSET ?";
$billing_stmt = $conn->prepare($billing_sql);

$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

if (!empty($params)) {
    $billing_stmt->bind_param($types, ...$params);
}

$billing_stmt->execute();
$billing_result = $billing_stmt->get_result();
$bills = [];
while ($row = $billing_result->fetch_assoc()) {
    $bills[] = $row;
}
$billing_stmt->close();

// ✅ Get billing statistics
$total_revenue = $conn->query("SELECT SUM(amount) as total FROM billing WHERE status = 'paid'")->fetch_assoc()['total'] ?? 0;
$pending_bills = $conn->query("SELECT COUNT(*) as count FROM billing WHERE status = 'pending'")->fetch_assoc()['count'] ?? 0;
$paid_bills = $conn->query("SELECT COUNT(*) as count FROM billing WHERE status = 'paid'")->fetch_assoc()['count'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing Management - Smart Lab System</title>
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
        }
        
        .card-header {
            background: white;
            border-bottom: 1px solid #e9ecef;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px 25px;
        }
        
        .stats-card {
            text-align: center;
            padding: 20px;
            border-radius: 12px;
            color: white;
            margin-bottom: 20px;
        }
        
        .stats-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
            opacity: 0.9;
        }
        
        .stats-card .number {
            font-size: 2rem;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .total-revenue { background: linear-gradient(45deg, #27ae60, #229954); }
        .pending-bills { background: linear-gradient(45deg, #f39c12, #e67e22); }
        .paid-bills { background: linear-gradient(45deg, #3498db, #2980b9); }
        
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
        
        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .btn-action {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            margin: 2px;
        }
        
        .amount-cell {
            font-weight: 600;
            font-size: 1.1rem;
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
                        <a class="nav-link active" href="billing.php">
                            <i class="fas fa-receipt"></i>Billing
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
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="h3 mb-1">Billing Management</h2>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                    <li class="breadcrumb-item active">Billing</li>
                                </ol>
                            </nav>
                        </div>
                        <a href="create_bill.php" class="btn btn-primary">
                            <i class="fas fa-plus-circle me-2"></i>Create New Bill
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="stats-card total-revenue">
                        <i class="fas fa-money-bill-wave"></i>
                        <div class="number">KES <?= number_format($total_revenue, 2) ?></div>
                        <div class="label">Total Revenue</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card pending-bills">
                        <i class="fas fa-clock"></i>
                        <div class="number"><?= $pending_bills ?></div>
                        <div class="label">Pending Bills</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card paid-bills">
                        <i class="fas fa-check-circle"></i>
                        <div class="number"><?= $paid_bills ?></div>
                        <div class="label">Paid Bills</div>
                    </div>
                </div>
            </div>

            <!-- Search and Filter Section -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-search me-2"></i>Search & Filter Bills</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Search by patient name, test, or bill ID..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="paid" <?= $status_filter == 'paid' ? 'selected' : '' ?>>Paid</option>
                                <option value="cancelled" <?= $status_filter == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date From</label>
                            <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date To</label>
                            <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid gap-2 d-md-flex">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter me-1"></i>Filter
                                </button>
                                <a href="billing.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-refresh me-1"></i>Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Billing Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="fas fa-list me-2"></i>Billing Records</h5>
                    <div class="text-muted">
                        Showing <?= count($bills) ?> of <?= $total_bills ?> bills
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['success_msg'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?= $_SESSION['success_msg'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['success_msg']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error_msg'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?= $_SESSION['error_msg'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['error_msg']); ?>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Bill ID</th>
                                    <th>Patient</th>
                                    <th>Test Type</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Appointment Date</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($bills)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fas fa-receipt fa-2x text-muted mb-3"></i>
                                            <p class="text-muted mb-0">No billing records found.</p>
                                            <?php if (!empty($search) || !empty($status_filter) || !empty($date_from) || !empty($date_to)): ?>
                                                <small>Try adjusting your search filters.</small>
                                            <?php else: ?>
                                                <small>No billing records have been created yet.</small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($bills as $bill): ?>
                                        <tr>
                                            <td>
                                                <strong>#<?= $bill['id'] ?></strong>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?= htmlspecialchars($bill['full_name']) ?></strong>
                                                    <div class="text-muted small"><?= htmlspecialchars($bill['phone']) ?></div>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($bill['test_name']) ?></td>
                                            <td class="amount-cell">
                                                KES <?= number_format($bill['amount'], 2) ?>
                                            </td>
                                            <td>
                                                <?php
                                                $status_class = '';
                                                switch ($bill['status']) {
                                                    case 'paid':
                                                        $status_class = 'bg-success';
                                                        break;
                                                    case 'pending':
                                                        $status_class = 'bg-warning';
                                                        break;
                                                    case 'cancelled':
                                                        $status_class = 'bg-danger';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge badge-status <?= $status_class ?>">
                                                    <?= ucfirst($bill['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?= date('M j, Y', strtotime($bill['preferred_date'])) ?>
                                                <div class="text-muted small">
                                                    <?= date('g:i A', strtotime($bill['preferred_date'])) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?= date('M j, Y', strtotime($bill['created_at'])) ?>
                                                <div class="text-muted small">
                                                    <?= date('g:i A', strtotime($bill['created_at'])) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="generate_invoice.php?id=<?= $bill['id'] ?>" class="btn btn-action btn-info" title="Generate Invoice">
                                                        <i class="fas fa-file-invoice"></i>
                                                    </a>
                                                    <?php if ($bill['status'] == 'pending'): ?>
                                                        <a href="?action=mark_paid&id=<?= $bill['id'] ?>" class="btn btn-action btn-success" title="Mark as Paid">
                                                            <i class="fas fa-check"></i>
                                                        </a>
                                                        <a href="?action=cancel&id=<?= $bill['id'] ?>" class="btn btn-action btn-warning" title="Cancel Bill" onclick="return confirm('Are you sure you want to cancel this bill?')">
                                                            <i class="fas fa-times"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="?action=delete&id=<?= $bill['id'] ?>" class="btn btn-action btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this billing record? This action cannot be undone.')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <?php if ($current_page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $current_page - 1])) ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($current_page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $current_page + 1])) ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>