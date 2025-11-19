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

// ✅ Handle patient actions (delete, update status)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $patient_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    switch ($action) {
        case 'delete':
            $delete_stmt = $conn->prepare("DELETE FROM patients WHERE patient_id = ?");
            $delete_stmt->bind_param("i", $patient_id);
            if ($delete_stmt->execute()) {
                $_SESSION['success_msg'] = "Patient deleted successfully!";
            } else {
                $_SESSION['error_msg'] = "Error deleting patient: " . $conn->error;
            }
            $delete_stmt->close();
            break;
            
        case 'toggle_status':
            $status_stmt = $conn->prepare("UPDATE patients SET status = IF(status = 'active', 'inactive', 'active') WHERE patient_id = ?");
            $status_stmt->bind_param("i", $patient_id);
            if ($status_stmt->execute()) {
                $_SESSION['success_msg'] = "Patient status updated successfully!";
            } else {
                $_SESSION['error_msg'] = "Error updating patient status: " . $conn->error;
            }
            $status_stmt->close();
            break;
    }
    
    header("Location: manage_patients.php");
    exit();
}

// ✅ Search and filter functionality
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$gender_filter = $_GET['gender'] ?? '';

// ✅ Build query with filters
$where_conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
    $types .= 'sss';
}

if (!empty($status_filter)) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if (!empty($gender_filter)) {
    $where_conditions[] = "gender = ?";
    $params[] = $gender_filter;
    $types .= 's';
}

$where_sql = '';
if (!empty($where_conditions)) {
    $where_sql = "WHERE " . implode(" AND ", $where_conditions);
}

// ✅ Get total patients count for pagination
$count_sql = "SELECT COUNT(*) as total FROM patients $where_sql";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_patients = $count_stmt->get_result()->fetch_assoc()['total'];
$count_stmt->close();

// ✅ Pagination
$per_page = 10;
$total_pages = ceil($total_patients / $per_page);
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $per_page;

// ✅ Fetch patients with pagination and filters
$patients_sql = "SELECT * FROM patients $where_sql ORDER BY created_at DESC LIMIT ? OFFSET ?";
$patients_stmt = $conn->prepare($patients_sql);

$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

if (!empty($params)) {
    $patients_stmt->bind_param($types, ...$params);
}

$patients_stmt->execute();
$patients_result = $patients_stmt->get_result();
$patients = [];
while ($row = $patients_result->fetch_assoc()) {
    $patients[] = $row;
}
$patients_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Patients - Smart Lab System</title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
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
        
        .patient-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(45deg, var(--primary), var(--info));
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
        
        .total-patients { background: linear-gradient(45deg, #3498db, #2980b9); }
        .active-patients { background: linear-gradient(45deg, #27ae60, #229954); }
        .new-today { background: linear-gradient(45deg, #e74c3c, #c0392b); }
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
                        <a class="nav-link active" href="manage_patients.php">
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
                            <h2 class="h3 mb-1">Patient Management</h2>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                    <li class="breadcrumb-item active">Manage Patients</li>
                                </ol>
                            </nav>
                        </div>
                        <a href="add_patient.php" class="btn btn-primary">
                            <i class="fas fa-user-plus me-2"></i>Add New Patient
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="stats-card total-patients">
                        <i class="fas fa-users"></i>
                        <div class="number"><?= $total_patients ?></div>
                        <div class="label">Total Patients</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <?php
                    $active_count = $conn->query("SELECT COUNT(*) as count FROM patients WHERE status = 'active'")->fetch_assoc()['count'];
                    ?>
                    <div class="stats-card active-patients">
                        <i class="fas fa-user-check"></i>
                        <div class="number"><?= $active_count ?></div>
                        <div class="label">Active Patients</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <?php
                    $today = date('Y-m-d');
                    $new_today = $conn->query("SELECT COUNT(*) as count FROM patients WHERE DATE(created_at) = '$today'")->fetch_assoc()['count'];
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
                    <h5 class="card-title mb-0"><i class="fas fa-search me-2"></i>Search & Filter Patients</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Search by name, email, or phone..." value="<?= htmlspecialchars($search) ?>">
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
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="">All Genders</option>
                                <option value="Male" <?= $gender_filter == 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= $gender_filter == 'Female' ? 'selected' : '' ?>>Female</option>
                                <option value="Other" <?= $gender_filter == 'Other' ? 'selected' : '' ?>>Other</option>
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

            <!-- Patients Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="fas fa-list me-2"></i>Patients List</h5>
                    <div class="text-muted">
                        Showing <?= count($patients) ?> of <?= $total_patients ?> patients
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
                                    <th>Patient</th>
                                    <th>Contact Info</th>
                                    <th>Gender</th>
                                    <th>Date of Birth</th>
                                    <th>Status</th>
                                    <th>Registered</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($patients)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <i class="fas fa-users fa-2x text-muted mb-3"></i>
                                            <p class="text-muted mb-0">No patients found.</p>
                                            <?php if (!empty($search) || !empty($status_filter) || !empty($gender_filter)): ?>
                                                <small>Try adjusting your search filters.</small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($patients as $patient): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="patient-avatar me-3">
                                                        <?= strtoupper(substr($patient['full_name'], 0, 1)) ?>
                                                    </div>
                                                    <div>
                                                        <strong><?= htmlspecialchars($patient['full_name']) ?></strong>
                                                        <div class="text-muted small">ID: #<?= $patient['patient_id'] ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div><?= htmlspecialchars($patient['email']) ?></div>
                                                <div class="text-muted small"><?= htmlspecialchars($patient['phone']) ?></div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark">
                                                    <i class="fas fa-<?= strtolower($patient['gender']) == 'male' ? 'mars' : (strtolower($patient['gender']) == 'female' ? 'venus' : 'genderless') ?> me-1"></i>
                                                    <?= htmlspecialchars($patient['gender']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?= $patient['date_of_birth'] ? date('M j, Y', strtotime($patient['date_of_birth'])) : '<span class="text-muted">Not set</span>' ?>
                                                <?php if ($patient['date_of_birth']): ?>
                                                    <div class="text-muted small">
                                                        <?php
                                                        $dob = new DateTime($patient['date_of_birth']);
                                                        $now = new DateTime();
                                                        $age = $now->diff($dob)->y;
                                                        echo "($age years)";
                                                        ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-status <?= $patient['status'] == 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                                    <?= ucfirst($patient['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?= date('M j, Y', strtotime($patient['created_at'])) ?>
                                                <div class="text-muted small">
                                                    <?= date('g:i A', strtotime($patient['created_at'])) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="view_patient.php?id=<?= $patient['patient_id'] ?>" class="btn btn-action btn-info" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit_patient.php?id=<?= $patient['patient_id'] ?>" class="btn btn-action btn-warning" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?action=toggle_status&id=<?= $patient['patient_id'] ?>" class="btn btn-action <?= $patient['status'] == 'active' ? 'btn-secondary' : 'btn-success' ?>" title="<?= $patient['status'] == 'active' ? 'Deactivate' : 'Activate' ?>">
                                                        <i class="fas fa-<?= $patient['status'] == 'active' ? 'pause' : 'play' ?>"></i>
                                                    </a>
                                                    <a href="?action=delete&id=<?= $patient['patient_id'] ?>" class="btn btn-action btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this patient? This action cannot be undone.')">
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