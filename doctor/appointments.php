<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/db.php';

// Redirect if doctor not logged in
if (!isset($_SESSION['doctor_id'])) {
    header("Location: doctor_login.php");
    exit;
}

$doctor_id = $_SESSION['doctor_id'];

try {
    // Fetch doctor details for navbar
    $doctor_stmt = $conn->prepare("SELECT full_name, email, profile_picture FROM staff WHERE id = ?");
    $doctor_stmt->bind_param("i", $doctor_id);
    $doctor_stmt->execute();
    $doctor = $doctor_stmt->get_result()->fetch_assoc();
    $doctor_stmt->close();

    if (!$doctor) {
        throw new Exception("Doctor not found in database.");
    }

    // Get filter parameters
    $status_filter = isset($_GET['status']) ? $_GET['status'] : 'upcoming';
    $date_filter = isset($_GET['date']) ? $_GET['date'] : '';

    // Build where conditions safely
    $where_conditions = ["b.id = ?"];
    $params = [$doctor_id];
    $types = 'i';

    // Validate and set status filter
    $valid_statuses = ['upcoming', 'today', 'completed', 'cancelled', 'all'];
    if (!in_array($status_filter, $valid_statuses)) {
        $status_filter = 'upcoming';
    }

    if ($status_filter === 'upcoming') {
        $where_conditions[] = "b.appointment_date >= CURDATE() AND b.status IN ('Scheduled', 'Confirmed')";
    } elseif ($status_filter === 'today') {
        $where_conditions[] = "b.appointment_date = CURDATE() AND b.status IN ('Scheduled', 'Confirmed')";
    } elseif ($status_filter === 'completed') {
        $where_conditions[] = "b.status = 'Completed'";
    } elseif ($status_filter === 'cancelled') {
        $where_conditions[] = "b.status = 'Cancelled'";
    }
    // 'all' needs no additional condition

    // Validate date filter
    if (!empty($date_filter)) {
        if (DateTime::createFromFormat('Y-m-d', $date_filter) !== false) {
            $where_conditions[] = "b.appointment_date = ?";
            $params[] = $date_filter;
            $types .= 's';
        } else {
            $date_filter = ''; // Invalid date format
        }
    }

    $where_clause = "WHERE " . implode(" AND ", $where_conditions);

    // Get appointment statistics with error handling
    $stats_sql = "
        SELECT 
            COUNT(CASE WHEN b.appointment_date = CURDATE() AND b.status IN ('Scheduled', 'Confirmed') THEN 1 END) as today_appointments,
            COUNT(CASE WHEN b.appointment_date > CURDATE() AND b.status IN ('Scheduled', 'Confirmed') THEN 1 END) as upcoming_appointments,
            COUNT(CASE WHEN b.status = 'Completed' THEN 1 END) as completed_appointments,
            COUNT(CASE WHEN b.status = 'Cancelled' THEN 1 END) as cancelled_appointments
        FROM bookings b
        WHERE b.id = ?
    ";

    $stats_stmt = $conn->prepare($stats_sql);
    if (!$stats_stmt) {
        throw new Exception("Failed to prepare statistics query: " . $conn->error);
    }
    
    $stats_stmt->bind_param("i", $doctor_id);
    $stats_stmt->execute();
    $stats_result = $stats_stmt->get_result();
    $stats = $stats_result->fetch_assoc();
    $stats_stmt->close();

    // Main query for appointments with safe column selection
    $sql = "
        SELECT 
            b.id as booking_id,
            b.patient_id,
            b.test_name,
            b.appointment_date,
            b.appointment_time,
            b.status,
            b.created_at as booking_date,
            p.full_name as patient_name,
            p.email as patient_email,
            p.phone as patient_phone,
            p.gender as patient_gender,
            p.date_of_birth as patient_dob,
            t.full_name as technician_name,
            d.diagnosis_note,
            d.medication
        FROM bookings b
        INNER JOIN patients p ON b.patient_id = p.patient_id
        LEFT JOIN technicians t ON b.uploaded_by = t.technician_id
        LEFT JOIN diagnosis d ON d.booking_id = b.id
        $where_clause
        ORDER BY 
            b.appointment_date ASC,
            b.appointment_time ASC,
            b.created_at DESC
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Failed to prepare main query: " . $conn->error);
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();

} catch (Exception $e) {
    $error_message = "Database Error: " . $e->getMessage();
    error_log($error_message);
}

// Handle status updates
if (isset($_POST['update_status'])) {
    try {
        $booking_id = intval($_POST['booking_id']);
        $new_status = $conn->real_escape_string($_POST['status']);
        $notes = isset($_POST['notes']) ? $conn->real_escape_string($_POST['notes']) : '';
        
        // Validate status
        $valid_statuses = ['Scheduled', 'Confirmed', 'Completed', 'Cancelled'];
        if (!in_array($new_status, $valid_statuses)) {
            throw new Exception("Invalid status value.");
        }
        
        $update_sql = "UPDATE bookings SET status = ? WHERE id = ? AND id = ?";
        $update_stmt = $conn->prepare($update_sql);
        
        if (!$update_stmt) {
            throw new Exception("Failed to prepare update query: " . $conn->error);
        }
        
        $update_stmt->bind_param("sii", $new_status, $booking_id, $doctor_id);
        
        if ($update_stmt->execute()) {
            $_SESSION['success'] = "Appointment status updated successfully!";
            
            // Log the status change if notes provided
            if (!empty($notes)) {
                // Check if appointment_notes table exists
                $table_check = $conn->query("SHOW TABLES LIKE 'appointment_notes'");
                if ($table_check->num_rows > 0) {
                    $log_sql = "INSERT INTO appointment_notes (booking_id, doctor_id, notes, created_at) VALUES (?, ?, ?, NOW())";
                    $log_stmt = $conn->prepare($log_sql);
                    if ($log_stmt) {
                        $log_stmt->bind_param("iis", $booking_id, $doctor_id, $notes);
                        $log_stmt->execute();
                        $log_stmt->close();
                    }
                }
            }
        } else {
            throw new Exception("Failed to execute update query.");
        }
        $update_stmt->close();
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to update appointment status: " . $e->getMessage();
    }
    
    header("Location: appointments.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Management - SmartLab</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .info-card .card-icon {
            font-size: 1.5rem;
            width: 60px;
            height: 60px;
        }
        .today-card .card-icon { background-color: #fff3e0; color: #ef6c00; }
        .upcoming-card .card-icon { background-color: #e3f2fd; color: #1976d2; }
        .completed-card .card-icon { background-color: #e8f5e8; color: #2e7d32; }
        .cancelled-card .card-icon { background-color: #ffebee; color: #c62828; }
        .avatar-initial { font-weight: 600; font-size: 1rem; }
        .table td { vertical-align: middle; }
        .btn-group-vertical .btn { width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; }
        .datetime-info { min-width: 120px; }
        .table-warning { background-color: #fff3cd; }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-heart-pulse"></i> SmartLab Doctor
            </a>
            <div class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                        <?= htmlspecialchars($doctor['full_name']) ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                    </ul>
                </li>
            </div>
        </div>
    </nav>

    <main class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <h1>Appointment Management</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Appointments</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Display any PHP errors -->
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <strong>Error:</strong> <?= htmlspecialchars($error_message) ?>
                <br><small>Please check your database configuration and table structure.</small>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card info-card today-card">
                    <div class="card-body">
                        <h5 class="card-title">Today's Appointments</h5>
                        <div class="d-flex align-items-center">
                            <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                <i class="bi bi-calendar-day"></i>
                            </div>
                            <div class="ps-3">
                                <h6><?= $stats['today_appointments'] ?? 0 ?></h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card info-card upcoming-card">
                    <div class="card-body">
                        <h5 class="card-title">Upcoming</h5>
                        <div class="d-flex align-items-center">
                            <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                <i class="bi bi-calendar-week"></i>
                            </div>
                            <div class="ps-3">
                                <h6><?= $stats['upcoming_appointments'] ?? 0 ?></h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card info-card completed-card">
                    <div class="card-body">
                        <h5 class="card-title">Completed</h5>
                        <div class="d-flex align-items-center">
                            <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <div class="ps-3">
                                <h6><?= $stats['completed_appointments'] ?? 0 ?></h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card info-card cancelled-card">
                    <div class="card-body">
                        <h5 class="card-title">Cancelled</h5>
                        <div class="d-flex align-items-center">
                            <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                <i class="bi bi-x-circle"></i>
                            </div>
                            <div class="ps-3">
                                <h6><?= $stats['cancelled_appointments'] ?? 0 ?></h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters and Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-4">
                                <form method="GET" class="filter-form">
                                    <div class="input-group">
                                        <select name="status" class="form-select" onchange="this.form.submit()">
                                            <option value="upcoming" <?= $status_filter === 'upcoming' ? 'selected' : '' ?>>Upcoming Appointments</option>
                                            <option value="today" <?= $status_filter === 'today' ? 'selected' : '' ?>>Today's Appointments</option>
                                            <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                                            <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                            <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Appointments</option>
                                        </select>
                                    </div>
                                </form>
                            </div>
                            <div class="col-md-4">
                                <form method="GET" class="date-form">
                                    <input type="hidden" name="status" value="<?= $status_filter ?>">
                                    <div class="input-group">
                                        <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($date_filter) ?>">
                                        <button class="btn btn-outline-primary" type="submit">
                                            <i class="bi bi-filter"></i> Filter
                                        </button>
                                        <?php if (!empty($date_filter)): ?>
                                            <a href="appointments.php?status=<?= $status_filter ?>" class="btn btn-outline-secondary">
                                                <i class="bi bi-x"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="btn-group">
                                    <a href="add_appointment.php" class="btn btn-primary">
                                        <i class="bi bi-plus-circle me-1"></i> New Appointment
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Appointments Table -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="card-title text-primary mb-0">
                                <i class="bi bi-calendar-check me-2"></i> 
                                <?= ucfirst($status_filter) ?> Appointments
                            </h5>
                            <span class="badge bg-primary"><?= isset($result) ? $result->num_rows : 0 ?> appointments found</span>
                        </div>

                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?= $_SESSION['success'] ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['success']); ?>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?= $_SESSION['error'] ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['error']); ?>
                        <?php endif; ?>

                        <?php if (isset($result) && $result->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Patient Details</th>
                                            <th>Test Information</th>
                                            <th>Appointment Date & Time</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $count = 1; while ($row = $result->fetch_assoc()): 
                                            $is_past = strtotime($row['appointment_date']) < strtotime(date('Y-m-d'));
                                            $is_today = $row['appointment_date'] == date('Y-m-d');
                                        ?>
                                            <tr class="<?= $is_today ? 'table-warning' : '' ?>">
                                                <td><?= $count++ ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar me-3">
                                                            <div class="avatar-initial bg-primary rounded-circle text-white d-flex align-items-center justify-content-center" 
                                                                 style="width: 40px; height: 40px;">
                                                                <?= strtoupper(substr($row['patient_name'], 0, 1)) ?>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <strong class="text-dark"><?= htmlspecialchars($row['patient_name']) ?></strong>
                                                            <br>
                                                            <small class="text-muted">
                                                                <?= htmlspecialchars($row['patient_email']) ?>
                                                            </small>
                                                            <br>
                                                            <small class="text-muted">
                                                                <?= htmlspecialchars($row['patient_phone']) ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <strong><?= htmlspecialchars($row['test_name']) ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        Booking ID: #<?= $row['booking_id'] ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="datetime-info">
                                                        <strong class="d-block">
                                                            <i class="bi bi-calendar me-1"></i>
                                                            <?= date('M j, Y', strtotime($row['appointment_date'])) ?>
                                                        </strong>
                                                        <span class="badge bg-primary">
                                                            <i class="bi bi-clock me-1"></i>
                                                            <?= date('h:i A', strtotime($row['appointment_time'])) ?>
                                                        </span>
                                                        <?php if ($is_today): ?>
                                                            <br>
                                                            <small class="text-warning">
                                                                <i class="bi bi-exclamation-triangle me-1"></i>
                                                                Today
                                                            </small>
                                                        <?php elseif ($is_past && $row['status'] !== 'Completed'): ?>
                                                            <br>
                                                            <small class="text-danger">
                                                                <i class="bi bi-clock-history me-1"></i>
                                                                Past Due
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status_badges = [
                                                        'Scheduled' => 'bg-primary',
                                                        'Confirmed' => 'bg-info',
                                                        'Completed' => 'bg-success',
                                                        'Cancelled' => 'bg-danger',
                                                        'Pending' => 'bg-warning text-dark'
                                                    ];
                                                    $status_class = $status_badges[$row['status']] ?? 'bg-secondary';
                                                    ?>
                                                    <span class="badge <?= $status_class ?>"><?= $row['status'] ?></span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="patient_history.php?patient_id=<?= $row['patient_id'] ?>" 
                                                           class="btn btn-outline-primary"
                                                           title="View Patient History">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <?php if (in_array($row['status'], ['Scheduled', 'Confirmed'])): ?>
                                                            <button class="btn btn-outline-success update-status" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#statusModal"
                                                                    data-booking-id="<?= $row['booking_id'] ?>"
                                                                    data-current-status="<?= $row['status'] ?>">
                                                                <i class="bi bi-pencil"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-calendar-x display-1 text-muted"></i>
                                <h4 class="text-muted mt-3">No Appointments Found</h4>
                                <p class="text-muted">
                                    <?php if (isset($error_message)): ?>
                                        There was an error loading appointments. Please check the database connection.
                                    <?php else: ?>
                                        No appointments match your current filter criteria.
                                    <?php endif; ?>
                                </p>
                                <?php if (!empty($date_filter) || $status_filter !== 'upcoming'): ?>
                                    <a href="appointments.php" class="btn btn-primary">View Upcoming Appointments</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Status Update Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Appointment Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="booking_id" id="statusBookingId">
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select" required>
                                <option value="Scheduled">Scheduled</option>
                                <option value="Confirmed">Confirmed</option>
                                <option value="Completed">Completed</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes (Optional)</label>
                            <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="Add any notes about this status change..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Status update modal
        document.querySelectorAll('.update-status').forEach(button => {
            button.addEventListener('click', function() {
                const bookingId = this.getAttribute('data-booking-id');
                const currentStatus = this.getAttribute('data-current-status');
                
                document.getElementById('statusBookingId').value = bookingId;
                document.getElementById('status').value = currentStatus;
            });
        });
    </script>
</body>
</html>