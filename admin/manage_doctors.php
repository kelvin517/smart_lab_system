<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

include '../config/db.php';

// ✅ Redirect if not logged in
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

// ✅ Handle doctor actions (delete, update status)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $doctor_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    switch ($action) {
        case 'delete':
            $delete_stmt = $conn->prepare("DELETE FROM staff WHERE id = ? AND role = 'doctor'");
            $delete_stmt->bind_param("i", $doctor_id);
            if ($delete_stmt->execute()) {
                $_SESSION['success_msg'] = "Doctor deleted successfully!";
            } else {
                $_SESSION['error_msg'] = "Error deleting doctor: " . $conn->error;
            }
            $delete_stmt->close();
            break;
            
        case 'toggle_status':
            $status_stmt = $conn->prepare("UPDATE staff SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ? AND role = 'doctor'");
            $status_stmt->bind_param("i", $doctor_id);
            if ($status_stmt->execute()) {
                $_SESSION['success_msg'] = "Doctor status updated successfully!";
            } else {
                $_SESSION['error_msg'] = "Error updating doctor status: " . $conn->error;
            }
            $status_stmt->close();
            break;
    }
    
    header("Location: manage_doctors.php");
    exit();
}

// ✅ Search and filter functionality
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$specialization_filter = $_GET['specialization'] ?? '';

// ✅ Build query with filters
$where_conditions = ["role = 'doctor'"];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(full_name LIKE ? OR email LIKE ? OR phone LIKE ? OR specialization LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
    $types .= 'ssss';
}

if (!empty($status_filter)) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if (!empty($specialization_filter)) {
    $where_conditions[] = "specialization = ?";
    $params[] = $specialization_filter;
    $types .= 's';
}

$where_sql = implode(" AND ", $where_conditions);

// ✅ Get total doctors count for pagination
$count_sql = "SELECT COUNT(*) as total FROM staff WHERE $where_sql";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_doctors = $count_stmt->get_result()->fetch_assoc()['total'];
$count_stmt->close();

// ✅ Pagination
$per_page = 10;
$total_pages = ceil($total_doctors / $per_page);
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $per_page;

// ✅ Fetch doctors with pagination and filters
$doctors_sql = "SELECT * FROM staff WHERE $where_sql ORDER BY created_at DESC LIMIT ? OFFSET ?";
$doctors_stmt = $conn->prepare($doctors_sql);

$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

if (!empty($params)) {
    $doctors_stmt->bind_param($types, ...$params);
}

$doctors_stmt->execute();
$doctors_result = $doctors_stmt->get_result();
$doctors = [];
while ($row = $doctors_result->fetch_assoc()) {
    $doctors[] = $row;
}
$doctors_stmt->close();

// ✅ Get specializations for filter dropdown
$specializations_query = $conn->query("SELECT DISTINCT specialization FROM staff WHERE role = 'doctor' AND specialization IS NOT NULL AND specialization != '' ORDER BY specialization");
$specializations = [];
while ($row = $specializations_query->fetch_assoc()) {
    $specializations[] = $row['specialization'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Doctors - Smart Lab System</title>
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
        
        .doctor-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
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
        
        .search-box {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
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
        
        .pagination .page-link {
            border: none;
            border-radius: 8px;
            margin: 0 3px;
            color: var(--secondary);
        }
        
        .pagination .page-item.active .page-link {
            background: var(--primary);
            color: white;
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
        
        .total-doctors { background: linear-gradient(45deg, #e74c3c, #c0392b); }
        .active-doctors { background: linear-gradient(45deg, #27ae60, #229954); }
        .new-today { background: linear-gradient(45deg, #3498db, #2980b9); }
        
        .specialization-badge {
            background: linear-gradient(45deg, #9b59b6, #8e44ad);
            color: white;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
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
                        <a class="nav-link active" href="manage_doctors.php">
                            <i class="fas fa-user-md"></i>Doctors
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_technicians.php">
                            <i class="fas fa-user-cog"></i>Technicians
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
                            <h2 class="h3 mb-1">Doctor Management</h2>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                    <li class="breadcrumb-item active">Manage Doctors</li>
                                </ol>
                            </nav>
                        </div>
                        <a href="add_doctor.php" class="btn btn-primary">
                            <i class="fas fa-user-plus me-2"></i>Add New Doctor
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="stats-card total-doctors">
                        <i class="fas fa-user-md"></i>
                        <div class="number"><?= $total_doctors ?></div>
                        <div class="label">Total Doctors</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <?php
                    $active_count = $conn->query("SELECT COUNT(*) as count FROM staff WHERE role = 'doctor' AND status = 'active'")->fetch_assoc()['count'];
                    ?>
                    <div class="stats-card active-doctors">
                        <i class="fas fa-user-check"></i>
                        <div class="number"><?= $active_count ?></div>
                        <div class="label">Active Doctors</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <?php
                    $today = date('Y-m-d');
                    $new_today = $conn->query("SELECT COUNT(*) as count FROM staff WHERE role = 'doctor' AND DATE(created_at) = '$today'")->fetch_assoc()['count'];
                    ?>
                    <div class="stats-card new-today">
                        <i class="fas fa-user-plus"></i>
                        <div class="number"><?= $new_today ?></div>
                        <div class="label">New Today</div>
                    </div>
                </div>
            </div>

            <!-- Search and Filter Section -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-search me-2"></i>Search & Filter Doctors</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Search by name, email, phone, or specialization..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="active" <?= $status_filter == 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $status_filter == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Specialization</label>
                            <select name="specialization" class="form-select">
                                <option value="">All Specializations</option>
                                <?php foreach ($specializations as $spec): ?>
                                    <option value="<?= htmlspecialchars($spec) ?>" <?= $specialization_filter == $spec ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($spec) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter me-1"></i>Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Doctors Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="fas fa-list me-2"></i>Doctors List</h5>
                    <div class="text-muted">
                        Showing <?= count($doctors) ?> of <?= $total_doctors ?> doctors
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
                                    <th>Doctor</th>
                                    <th>Contact Info</th>
                                    <th>Specialization</th>
                                    <th>Status</th>
                                    <th>Last Login</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($doctors)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <i class="fas fa-user-md fa-2x text-muted mb-3"></i>
                                            <p class="text-muted mb-0">No doctors found.</p>
                                            <?php if (!empty($search) || !empty($status_filter) || !empty($specialization_filter)): ?>
                                                <small>Try adjusting your search filters.</small>
                                            <?php else: ?>
                                                <small>No doctors have been registered yet.</small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($doctors as $doctor): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="doctor-avatar me-3">
                                                        <?= strtoupper(substr($doctor['full_name'], 0, 1)) ?>
                                                    </div>
                                                    <div>
                                                        <strong><?= htmlspecialchars($doctor['full_name']) ?></strong>
                                                        <div class="text-muted small">ID: #<?= $doctor['id'] ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div><?= htmlspecialchars($doctor['email']) ?></div>
                                                <div class="text-muted small"><?= htmlspecialchars($doctor['phone'] ?? 'Not provided') ?></div>
                                            </td>
                                            <td>
                                                <?php if ($doctor['specialization']): ?>
                                                    <span class="specialization-badge">
                                                        <i class="fas fa-stethoscope me-1"></i>
                                                        <?= htmlspecialchars($doctor['specialization']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">Not specified</span>
                                                <?php endif; ?>
                                            </td>
                                
                                            <td>
                                                <span class="badge badge-status <?= $doctor['status'] == 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                                    <?= ucfirst($doctor['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                $last_login = $conn->query("SELECT login_time FROM login_logs WHERE email = '{$doctor['email']}' ORDER BY login_time DESC LIMIT 1")->fetch_assoc();
                                                if ($last_login) {
                                                    echo date('M j, g:i A', strtotime($last_login['login_time']));
                                                } else {
                                                    echo '<span class="text-muted">Never</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="view_doctor.php?id=<?= $doctor['id'] ?>" class="btn btn-action btn-info" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit_doctor.php?id=<?= $doctor['id'] ?>" class="btn btn-action btn-warning" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?action=toggle_status&id=<?= $doctor['id'] ?>" class="btn btn-action <?= $doctor['status'] == 'active' ? 'btn-secondary' : 'btn-success' ?>" title="<?= $doctor['status'] == 'active' ? 'Deactivate' : 'Activate' ?>">
                                                        <i class="fas fa-<?= $doctor['status'] == 'active' ? 'pause' : 'play' ?>"></i>
                                                    </a>
                                                    <a href="?action=delete&id=<?= $doctor['id'] ?>" class="btn btn-action btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this doctor? This action cannot be undone.')">
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