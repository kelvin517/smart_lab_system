<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/db.php';

// Ensure technician is logged in
if (!isset($_SESSION['technician_id'])) {
    header("Location: technician_login.php");
    exit();
}

$tech_id = intval($_SESSION['technician_id']);
$current_date = date('Y-m-d');
$status_message = '';

// Handle status updates
if (isset($_POST['update_status'])) {
    $booking_id = intval($_POST['booking_id']);
    $new_status = $_POST['status'];
    
    $update_stmt = $conn->prepare("UPDATE bookings SET status = ?, updated_at = NOW() WHERE id = ? AND assigned_technician = ?");
    $update_stmt->bind_param("sii", $new_status, $booking_id, $tech_id);
    
    if ($update_stmt->execute()) {
        $status_message = "<div class='alert alert-success'>Appointment status updated successfully!</div>";
    } else {
        $status_message = "<div class='alert alert-danger'>Error updating status: " . $conn->error . "</div>";
    }
    $update_stmt->close();
}

// Handle quick actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $booking_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    switch ($action) {
        case 'start_test':
            $update_stmt = $conn->prepare("UPDATE bookings SET status = 'in_progress', started_at = NOW() WHERE id = ? AND assigned_technician = ?");
            $update_stmt->bind_param("ii", $booking_id, $tech_id);
            if ($update_stmt->execute()) {
                $status_message = "<div class='alert alert-success'>Test marked as in progress!</div>";
            }
            $update_stmt->close();
            break;
            
        case 'complete_test':
            $update_stmt = $conn->prepare("UPDATE bookings SET status = 'completed', completed_at = NOW() WHERE id = ? AND assigned_technician = ?");
            $update_stmt->bind_param("ii", $booking_id, $tech_id);
            if ($update_stmt->execute()) {
                $status_message = "<div class='alert alert-success'>Test marked as completed!</div>";
            }
            $update_stmt->close();
            break;
            
        case 'cancel_test':
            $update_stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled', cancelled_at = NOW() WHERE id = ? AND assigned_technician = ?");
            $update_stmt->bind_param("ii", $booking_id, $tech_id);
            if ($update_stmt->execute()) {
                $status_message = "<div class='alert alert-warning'>Appointment cancelled!</div>";
            }
            $update_stmt->close();
            break;
    }
}

// Fetch schedule with additional fields
$sql = "
    SELECT b.id, b.test_name, b.preferred_date, b.appointment_time, b.status, b.priority,
           b.sample_collected, b.notes, b.created_at,
           p.patient_id, p.full_name, p.phone, p.email, p.date_of_birth, p.gender
    FROM bookings b
    JOIN patients p ON b.patient_id = p.patient_id
    WHERE b.assigned_technician = ? 
    ORDER BY 
        CASE 
            WHEN b.preferred_date = ? AND b.status NOT IN ('completed', 'cancelled') THEN 1
            WHEN b.preferred_date < ? AND b.status NOT IN ('completed', 'cancelled') THEN 2
            WHEN b.status = 'in_progress' THEN 3
            ELSE 4
        END,
        b.preferred_date ASC, 
        b.appointment_time ASC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("iss", $tech_id, $current_date, $current_date);
$stmt->execute();
$res = $stmt->get_result();

// Group results
$today = [];
$upcoming = [];
$completed = [];
$overdue = [];
$in_progress = [];

while ($row = $res->fetch_assoc()) {
    switch ($row['status']) {
        case 'completed':
            $completed[] = $row;
            break;
        case 'cancelled':
            continue 2; // Skip cancelled appointments
        case 'in_progress':
            $in_progress[] = $row;
            break;
        default:
            if ($row['preferred_date'] === $current_date) {
                $today[] = $row;
            } elseif ($row['preferred_date'] < $current_date) {
                $overdue[] = $row;
            } else {
                $upcoming[] = $row;
            }
    }
}

$stmt->close();
?>

<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold text-primary mb-1">Technician Schedule</h2>
            <p class="text-muted mb-0">Welcome back, <?= htmlspecialchars($_SESSION['technician_username'] ?? 'Technician') ?>! Manage your appointments efficiently.</p>
        </div>
        <div class="text-end">
            <span class="badge bg-primary fs-6"><?= date('l, F j, Y') ?></span>
            <div class="mt-1">
                <small class="text-muted">Last updated: <?= date('g:i A') ?></small>
            </div>
        </div>
    </div>

    <?= $status_message ?>

    <!-- Quick Actions Bar -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-body py-3">
                    <div class="row g-2 text-center">
                        <div class="col-md-3 col-6">
                            <a href="start_test.php" class="btn btn-success btn-sm w-100">
                                <i class="fas fa-plus-circle me-1"></i>New Test
                            </a>
                        </div>
                        <div class="col-md-3 col-6">
                            <a href="test_reports.php" class="btn btn-info btn-sm w-100">
                                <i class="fas fa-file-alt me-1"></i>Reports
                            </a>
                        </div>
                        <div class="col-md-3 col-6">
                            <a href="inventory.php" class="btn btn-warning btn-sm w-100">
                                <i class="fas fa-boxes me-1"></i>Inventory
                            </a>
                        </div>
                        <div class="col-md-3 col-6">
                            <button onclick="window.location.reload()" class="btn btn-secondary btn-sm w-100">
                                <i class="fas fa-sync-alt me-1"></i>Refresh
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card border-start border-warning border-4 hover-shadow">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h4 class="card-title text-warning fw-bold"><?= count($today) ?></h4>
                            <p class="card-text text-muted small">Today</p>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="fas fa-calendar-day fa-2x text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card border-start border-primary border-4 hover-shadow">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h4 class="card-title text-primary fw-bold"><?= count($in_progress) ?></h4>
                            <p class="card-text text-muted small">In Progress</p>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="fas fa-spinner fa-2x text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card border-start border-danger border-4 hover-shadow">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h4 class="card-title text-danger fw-bold"><?= count($overdue) ?></h4>
                            <p class="card-text text-muted small">Overdue</p>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle fa-2x text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card border-start border-info border-4 hover-shadow">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h4 class="card-title text-info fw-bold"><?= count($upcoming) ?></h4>
                            <p class="card-text text-muted small">Upcoming</p>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="fas fa-calendar-alt fa-2x text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card border-start border-success border-4 hover-shadow">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h4 class="card-title text-success fw-bold"><?= count($completed) ?></h4>
                            <p class="card-text text-muted small">Completed</p>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card bg-primary text-white hover-shadow">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h5 class="card-title">Total</h5>
                            <h4 class="mb-0 fw-bold"><?= count($today) + count($upcoming) + count($overdue) + count($completed) + count($in_progress) ?></h4>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="fas fa-clipboard-list fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- IN PROGRESS TESTS -->
    <?php if (!empty($in_progress)): ?>
    <div class="card shadow-sm mb-4 border-primary">
        <div class="card-header bg-primary bg-opacity-10 border-bottom border-primary">
            <h4 class="card-title mb-0 text-primary">
                <i class="fas fa-spinner me-2"></i>
                Tests In Progress
                <span class="badge bg-primary ms-2"><?= count($in_progress) ?></span>
            </h4>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <?php foreach ($in_progress as $ip): ?>
                    <div class="col-lg-6 col-xl-4">
                        <div class="card h-100 border-primary border-2 hover-shadow">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title text-primary"><?= htmlspecialchars($ip['full_name']) ?></h5>
                                    <span class="badge bg-primary">
                                        <i class="fas fa-spinner me-1"></i>In Progress
                                    </span>
                                </div>
                                
                                <div class="mb-3">
                                    <span class="badge bg-info mb-2"><?= htmlspecialchars($ip['test_name']) ?></span>
                                    <div class="d-flex align-items-center text-muted mb-1">
                                        <i class="fas fa-user me-2"></i>
                                        <span><?= htmlspecialchars($ip['gender']) ?>, <?= calculateAge($ip['date_of_birth']) ?> years</span>
                                    </div>
                                    <div class="d-flex align-items-center text-muted mb-1">
                                        <i class="fas fa-phone me-2"></i>
                                        <span><?= htmlspecialchars($ip['phone']) ?></span>
                                    </div>
                                    <?php if ($ip['sample_collected']): ?>
                                        <div class="d-flex align-items-center text-success mb-1">
                                            <i class="fas fa-check-circle me-2"></i>
                                            <span>Sample Collected</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="d-flex gap-2">
                                    <a href="complete_test.php?id=<?= $ip['id'] ?>" class="btn btn-success btn-sm flex-fill">
                                        <i class="fas fa-check-circle me-1"></i>Complete
                                    </a>
                                    <a href="test_details.php?id=<?= $ip['id'] ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-edit me-1"></i>Update
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- OVERDUE APPOINTMENTS -->
    <?php if (!empty($overdue)): ?>
    <div class="card shadow-sm mb-4 border-danger">
        <div class="card-header bg-danger bg-opacity-10 border-bottom border-danger">
            <h4 class="card-title mb-0 text-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Overdue Appointments
                <span class="badge bg-danger ms-2"><?= count($overdue) ?></span>
            </h4>
        </div>
        <div class="card-body">
            <div class="alert alert-warning d-flex align-items-center mb-3">
                <i class="fas fa-exclamation-circle me-2 fs-5"></i>
                <span class="fw-semibold">You have <?= count($overdue) ?> overdue appointment(s) that require immediate attention.</span>
            </div>
            <div class="row g-3">
                <?php foreach ($overdue as $o): ?>
                    <div class="col-lg-6 col-xl-4">
                        <div class="card h-100 border-danger border-2 hover-shadow">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title text-danger"><?= htmlspecialchars($o['full_name']) ?></h5>
                                    <span class="badge bg-danger">
                                        Overdue
                                    </span>
                                </div>
                                
                                <div class="mb-3">
                                    <span class="badge bg-secondary mb-2"><?= htmlspecialchars($o['test_name']) ?></span>
                                    <div class="d-flex align-items-center text-muted mb-1">
                                        <i class="fas fa-calendar me-2"></i>
                                        <span class="text-danger fw-semibold"><?= date('M j, Y', strtotime($o['preferred_date'])) ?></span>
                                    </div>
                                    <div class="d-flex align-items-center text-muted mb-1">
                                        <i class="fas fa-clock me-2"></i>
                                        <span><?= date('g:i A', strtotime($o['appointment_time'])) ?></span>
                                    </div>
                                    <div class="d-flex align-items-center text-muted">
                                        <i class="fas fa-phone me-2"></i>
                                        <span><?= htmlspecialchars($o['phone']) ?></span>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <a href="start_test.php?id=<?= $o['id'] ?>" class="btn btn-danger btn-sm flex-fill">
                                        <i class="fas fa-play-circle me-1"></i>Start Now
                                    </a>
                                    <a href="view_patient.php?id=<?= $o['patient_id'] ?>" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-eye me-1"></i>Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- TODAY'S APPOINTMENTS -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-warning bg-opacity-10 border-bottom">
            <h4 class="card-title mb-0">
                <i class="fas fa-sun text-warning me-2"></i>
                Today's Appointments
                <span class="badge bg-warning ms-2"><?= count($today) ?></span>
            </h4>
        </div>
        <div class="card-body">
            <?php if (empty($today)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-0">No appointments scheduled for today.</p>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($today as $t): ?>
                        <div class="col-lg-6 col-xl-4">
                            <div class="card h-100 border-warning border-2 hover-shadow">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="card-title text-primary"><?= htmlspecialchars($t['full_name']) ?></h5>
                                        <span class="badge 
                                            <?= $t['priority'] === 'high' ? 'bg-danger' : 
                                               ($t['priority'] === 'medium' ? 'bg-warning' : 'bg-info') ?>">
                                            <?= ucfirst($t['priority']) ?>
                                        </span>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <span class="badge bg-primary mb-2"><?= htmlspecialchars($t['test_name']) ?></span>
                                        <div class="d-flex align-items-center text-muted mb-1">
                                            <i class="fas fa-clock me-2"></i>
                                            <span class="fw-semibold"><?= date('g:i A', strtotime($t['appointment_time'])) ?></span>
                                        </div>
                                        <div class="d-flex align-items-center text-muted">
                                            <i class="fas fa-phone me-2"></i>
                                            <span><?= htmlspecialchars($t['phone']) ?></span>
                                        </div>
                                        <?php if (!empty($t['notes'])): ?>
                                            <div class="mt-2 p-2 bg-light rounded small">
                                                <strong>Notes:</strong> <?= htmlspecialchars($t['notes']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <a href="start_test.php?id=<?= $t['id'] ?>" class="btn btn-success btn-sm flex-fill">
                                            <i class="fas fa-play-circle me-1"></i>Start Test
                                        </a>
                                        <div class="dropdown">
                                            <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-cog"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="view_patient.php?id=<?= $t['patient_id'] ?>"><i class="fas fa-eye me-2"></i>View Details</a></li>
                                                <li><a class="dropdown-item" href="reschedule.php?id=<?= $t['id'] ?>"><i class="fas fa-calendar-alt me-2"></i>Reschedule</a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item text-danger" href="?action=cancel_test&id=<?= $t['id'] ?>" onclick="return confirm('Cancel this appointment?')"><i class="fas fa-times me-2"></i>Cancel</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- UPCOMING APPOINTMENTS -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-info bg-opacity-10 border-bottom">
            <h4 class="card-title mb-0">
                <i class="fas fa-calendar-alt text-info me-2"></i>
                Upcoming Appointments
                <span class="badge bg-info ms-2"><?= count($upcoming) ?></span>
            </h4>
        </div>
        <div class="card-body">
            <?php if (empty($upcoming)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-calendar-plus fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-0">No upcoming appointments.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Patient</th>
                                <th>Test</th>
                                <th>Date & Time</th>
                                <th>Priority</th>
                                <th>Contact</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcoming as $u): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-2">
                                                <i class="fas fa-user text-primary"></i>
                                            </div>
                                            <div>
                                                <strong><?= htmlspecialchars($u['full_name']) ?></strong>
                                                <br><small class="text-muted"><?= htmlspecialchars($u['email']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($u['test_name']) ?></span>
                                    </td>
                                    <td>
                                        <div>
                                            <i class="fas fa-calendar me-1 text-muted"></i>
                                            <?= date('M j, Y', strtotime($u['preferred_date'])) ?>
                                        </div>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            <?= date('g:i A', strtotime($u['appointment_time'])) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge 
                                            <?= $u['priority'] === 'high' ? 'bg-danger' : 
                                               ($u['priority'] === 'medium' ? 'bg-warning' : 'bg-info') ?>">
                                            <?= ucfirst($u['priority']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?= htmlspecialchars($u['phone']) ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="view_patient.php?id=<?= $u['patient_id'] ?>" class="btn btn-outline-primary" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="reschedule.php?id=<?= $u['id'] ?>" class="btn btn-outline-warning" title="Reschedule">
                                                <i class="fas fa-calendar-alt"></i>
                                            </a>
                                            <a href="?action=cancel_test&id=<?= $u['id'] ?>" class="btn btn-outline-danger" title="Cancel" onclick="return confirm('Cancel this appointment?')">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- COMPLETED TESTS -->
    <div class="card shadow-sm">
        <div class="card-header bg-success bg-opacity-10 border-bottom">
            <h4 class="card-title mb-0">
                <i class="fas fa-check-circle text-success me-2"></i>
                Completed Tests
                <span class="badge bg-success ms-2"><?= count($completed) ?></span>
            </h4>
        </div>
        <div class="card-body">
            <?php if (empty($completed)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-clipboard-check fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-0">No completed tests yet.</p>
                </div>
            <?php else: ?>
                <div class="row g-2">
                    <?php foreach ($completed as $c): ?>
                        <div class="col-md-6 col-lg-4 col-xl-3">
                            <div class="card border-success border-start border-3 hover-shadow">
                                <div class="card-body py-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="flex-grow-1">
                                            <h6 class="card-title mb-1 text-truncate"><?= htmlspecialchars($c['full_name']) ?></h6>
                                            <p class="card-text text-muted small mb-1"><?= htmlspecialchars($c['test_name']) ?></p>
                                        </div>
                                        <span class="badge bg-success flex-shrink-0">
                                            <i class="fas fa-check me-1"></i>Done
                                        </span>
                                    </div>
                                    <div class="text-muted small">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?= date('M j, Y', strtotime($c['preferred_date'])) ?>
                                        <br>
                                        <i class="fas fa-clock me-1"></i>
                                        <?= date('g:i A', strtotime($c['appointment_time'])) ?>
                                    </div>
                                    <div class="mt-2">
                                        <a href="test_report.php?id=<?= $c['id'] ?>" class="btn btn-outline-success btn-sm w-100">
                                            <i class="fas fa-file-alt me-1"></i>View Report
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Helper function to calculate age
function calculateAge($birthdate) {
    if (empty($birthdate)) return 'N/A';
    $birthDate = new DateTime($birthdate);
    $today = new DateTime();
    $age = $today->diff($birthDate);
    return $age->y;
}
?>

<style>
.avatar-sm {
    width: 32px;
    height: 32px;
    font-size: 0.875rem;
}
.card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}
.hover-shadow:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
}
.border-start {
    border-left-width: 4px !important;
}
.table th {
    border-top: none;
    font-weight: 600;
    color: #6c757d;
}
.text-truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.dropdown-toggle::after {
    margin-left: 0.25em;
}
@media (max-width: 768px) {
    .container-fluid {
        padding-left: 10px;
        padding-right: 10px;
    }
    .card-body {
        padding: 1rem;
    }
    .btn-group {
        display: flex;
    }
    .table-responsive {
        font-size: 0.875rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh page every 3 minutes
    setTimeout(() => {
        window.location.reload();
    }, 180000);
    
    // Add smooth animations
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        card.addEventListener('click', function(e) {
            if (e.target.tagName === 'A' || e.target.closest('a') || e.target.closest('.dropdown')) return;
            this.style.transform = 'scale(0.98)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    });
    
    // Auto-hide status messages
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});
</script>