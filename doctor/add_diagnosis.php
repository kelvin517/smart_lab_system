<?php
// doctor/prescription.php
// Create & view prescriptions (doctor-facing)

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/db.php'; // expects $conn (mysqli)

// --- Auth check ---
if (!isset($_SESSION['doctor_id'])) {
    header("Location: doctor_login.php");
    exit;
}

$doctor_id = (int) $_SESSION['doctor_id'];
$doctor_name = $_SESSION['doctor_name'] ?? 'Doctor';

$success = $error = '';

// Optional: patient_id or booking_id in URL to pre-fill form
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

// If booking_id provided and patient_id missing, try to fetch patient_id
if ($booking_id && !$patient_id) {
    $bstmt = $conn->prepare("SELECT patient_id FROM bookings WHERE id = ? LIMIT 1");
    $bstmt->bind_param("i", $booking_id);
    $bstmt->execute();
    $bres = $bstmt->get_result()->fetch_assoc();
    if ($bres && isset($bres['patient_id'])) $patient_id = (int)$bres['patient_id'];
    $bstmt->close();
}

// --- Handle POST: create prescription ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    // collect and validate
    $patient_id_post = isset($_POST['patient_id']) ? intval($_POST['patient_id']) : 0;
    $booking_id_post = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : null;
    $medication = trim($_POST['medication'] ?? '');
    $dosage = trim($_POST['dosage'] ?? '');
    $duration = trim($_POST['duration'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($patient_id_post <= 0) {
        $error = "Please select a valid patient.";
    } elseif ($medication === '') {
        $error = "Medication field cannot be empty.";
    } else {
        $ins = $conn->prepare("INSERT INTO prescriptions (booking_id, patient_id, doctor_id, medication, dosage, duration, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        if ($booking_id_post > 0) {
            $ins->bind_param("iiissss", $booking_id_post, $patient_id_post, $doctor_id, $medication, $dosage, $duration, $notes);
        } else {
            $ins->close();
            $ins = $conn->prepare("INSERT INTO prescriptions (booking_id, patient_id, doctor_id, medication, dosage, duration, notes, created_at) VALUES (NULL, ?, ?, ?, ?, ?, ?, NOW())");
            $ins->bind_param("iissss", $patient_id_post, $doctor_id, $medication, $dosage, $duration, $notes);
        }

        if ($ins->execute()) {
            $success = "Prescription created successfully.";
            $patient_id = $patient_id_post;
            $booking_id = $booking_id_post;
        } else {
            $error = "Failed to create prescription: " . htmlspecialchars($ins->error);
        }
        $ins->close();
    }
}

// --- Fetch patient list for form dropdown ---
$patients = [];
$pstmt = $conn->query("SELECT patient_id as patient_id, full_name FROM patients ORDER BY full_name ASC");
if ($pstmt) {
    while ($r = $pstmt->fetch_assoc()) $patients[] = $r;
}

// --- Fetch all diagnosis for history ---
$all_prescriptions = [];
$hst = $conn->prepare("
    SELECT pr.*, p.full_name AS patient_name, s.full_name AS doctor_name
    FROM diagnosis pr
    LEFT JOIN patients p ON pr.id = p.patient_id
    LEFT JOIN staff s ON pr.doctor_id = s.id
    WHERE pr.doctor_id = ?
    ORDER BY pr.created_at DESC
    LIMIT 100
");
$hst->bind_param("i", $doctor_id);
$hst->execute();
$res = $hst->get_result();
while ($row = $res->fetch_assoc()) $all_prescriptions[] = $row;
$hst->close();

// --- If a patient is selected, fetch recent prescriptions for that patient ---
$prescriptions = [];
if ($patient_id > 0) {
    $pst = $conn->prepare("
        SELECT pr.*, p.full_name AS patient_name, s.full_name AS doctor_name
        FROM prescriptions pr
        LEFT JOIN patients p ON pr.patient_id = p.id
        LEFT JOIN staff s ON pr.doctor_id = s.id
        WHERE pr.patient_id = ?
        ORDER BY pr.created_at DESC
        LIMIT 50
    ");
    $pst->bind_param("i", $patient_id);
    $pst->execute();
    $pres_res = $pst->get_result();
    while ($row = $pres_res->fetch_assoc()) $prescriptions[] = $row;
    $pst->close();
}

// Get stats for header
$stats_stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_prescriptions,
        COUNT(DISTINCT id) as total_patients,
        MAX(created_at) as last_prescription
    FROM diagnosis
    WHERE doctor_id = ?
");
$stats_stmt->bind_param("i", $doctor_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();
?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<style>
.prescription-container { margin: 24px; }
.card-prescription { 
    border-radius: 12px; 
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: none;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.card-prescription:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
}
.print-area { background: #fff; padding: 25px; border-radius: 10px; border: 2px solid #e9ecef; }
.prescription-title { font-weight: 700; color: #2c3e50; }
.small-muted { color: #6c757d; font-size: 0.9rem; }
.nav-tabs .nav-link { 
    border: none;
    color: #6c757d;
    font-weight: 500;
    padding: 12px 24px;
    border-radius: 8px 8px 0 0;
}
.nav-tabs .nav-link.active { 
    background: linear(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    font-weight: 600;
}
.stats-card { 
    background: linear(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
}
.stats-number { 
    font-size: 2.5rem; 
    font-weight: 700; 
    margin-bottom: 0;
}
.stats-label { 
    font-size: 0.9rem; 
    opacity: 0.9;
    margin-bottom: 0;
}
.quick-action-card {
    transition: all 0.3s ease;
    border: none;
    border-radius: 10px;
}
.quick-action-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}
.medication-preview {
    background: #f8f9fa;
    border-left: 4px solid #0d6efd;
    padding: 15px;
    border-radius: 0 8px 8px 0;
}
@media print {
  body * { visibility: hidden; }
  .print-area, .print-area * { visibility: visible; }
  .print-area { 
      position: absolute; 
      left: 0; 
      top: 0; 
      width: 100%; 
      background: white;
  }
}
.badge-prescription {
    background: linear(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
}
</style>

<main id="main" class="main">
    <!-- Enhanced Header Section -->
    <div class="pagetitle">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1>Prescriptions Management</h1>
                <nav>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">
                            <i class="bi bi-house-door"></i> Dashboard
                        </a></li>
                        <li class="breadcrumb-item active">
                            <i class="bi bi-prescription2"></i> Prescriptions
                        </li>
                    </ol>
                </nav>
            </div>
            <div class="col-md-4 text-end">
                <span class="badge-prescription">
                    <i class="bi bi-capsule-pill me-1"></i>
                    Doctor: <?= htmlspecialchars($doctor_name) ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <section class="section">
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="stats-number"><?= $stats['total_prescriptions'] ?? 0 ?></h2>
                            <p class="stats-label">Total Prescriptions</p>
                        </div>
                        <i class="bi bi-prescription2 display-4 opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h2 class="stats-number"><?= $stats['total_patients'] ?? 0 ?></h2>
                                <p class="stats-label">Patients Treated</p>
                            </div>
                            <i class="bi bi-people display-4 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h2 class="stats-number"><?= count($prescriptions) ?></h2>
                                <p class="stats-label">Current Patient Scripts</p>
                            </div>
                            <i class="bi bi-file-medical display-4 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card bg-warning text-dark">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h2 class="stats-number">
                                    <?= $stats['last_prescription'] ? date('M j', strtotime($stats['last_prescription'])) : 'N/A' ?>
                                </h2>
                                <p class="stats-label">Last Prescription</p>
                            </div>
                            <i class="bi bi-calendar-event display-4 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Navigation Tabs -->
    <section class="section">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body py-3">
                        <ul class="nav nav-tabs nav-tabs-bordered" id="prescriptionTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="create-tab" data-bs-toggle="tab" data-bs-target="#create-prescription" type="button" role="tab">
                                    <i class="bi bi-plus-circle me-2"></i>Create Prescription
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#prescription-history" type="button" role="tab">
                                    <i class="bi bi-clock-history me-2"></i>Prescription History
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="search-tab" data-bs-toggle="tab" data-bs-target="#search-prescriptions" type="button" role="tab">
                                    <i class="bi bi-search me-2"></i>Search & Analytics
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Tab Content -->
    <section class="section prescription-container">
        <div class="tab-content" id="prescriptionTabsContent">
            
            <!-- Create Prescription Tab -->
            <div class="tab-pane fade show active" id="create-prescription" role="tabpanel">
                <div class="row">
                    <!-- Form Column -->
                    <div class="col-lg-6">
                        <div class="card card-prescription">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-file-medical me-2"></i>Create New Prescription
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if ($success): ?>
                                    <div class="alert alert-success alert-dismissible fade show">
                                        <i class="bi bi-check-circle me-2"></i>
                                        <?= htmlspecialchars($success) ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>
                                <?php if ($error): ?>
                                    <div class="alert alert-danger alert-dismissible fade show">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        <?= htmlspecialchars($error) ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" action="" id="prescriptionForm">
                                    <input type="hidden" name="action" value="create">
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Select Patient</label>
                                        <select name="patient_id" class="form-select form-select-lg" required>
                                            <option value="">-- Select patient --</option>
                                            <?php foreach ($patients as $p): ?>
                                                <option value="<?= $p['patient_id'] ?>" 
                                                    <?= ($p['patient_id'] == $patient_id) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($p['full_name']) ?> (ID: <?= $p['patient_id'] ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Booking Reference (Optional)</label>
                                        <input type="number" name="booking_id" class="form-control" 
                                               placeholder="Enter booking ID if applicable" 
                                               value="<?= $booking_id ? htmlspecialchars($booking_id) : '' ?>">
                                        <div class="form-text">Link this prescription to a specific appointment</div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Medication Details</label>
                                        <textarea name="medication" class="form-control" rows="6" required 
                                                  placeholder="Enter medication name, strength, form, and specific instructions..."><?= '' ?></textarea>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Dosage Instructions</label>
                                            <input type="text" name="dosage" class="form-control" 
                                                   placeholder="e.g., 1 tablet twice daily after meals">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Treatment Duration</label>
                                            <input type="text" name="duration" class="form-control" 
                                                   placeholder="e.g., 7 days, 2 weeks, 1 month">
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">Additional Notes</label>
                                        <textarea name="notes" class="form-control" rows="3" 
                                                  placeholder="Special instructions, precautions, follow-up requirements..."></textarea>
                                    </div>

                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <button class="btn btn-outline-secondary me-md-2" type="reset">
                                            <i class="bi bi-arrow-clockwise me-1"></i>Clear Form
                                        </button>
                                        <button class="btn btn-primary" type="submit">
                                            <i class="bi bi-save me-1"></i>Save Prescription
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions & Preview -->
                    <div class="col-lg-6">
                        <!-- Quick Actions -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card quick-action-card border-primary">
                                    <div class="card-body text-center">
                                        <i class="bi bi-person-plus display-5 text-primary mb-3"></i>
                                        <h6 class="fw-bold">view Patients</h6>
                                        <p class="small text-muted">view  patient quickly</p>
                                        <a href="patients.php?action=create" class="btn btn-primary btn-sm">
                                            <i class="bi bi-plus-circle me-1"></i>view Patient
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card quick-action-card border-success">
                                    <div class="card-body text-center">
                                        <i class="bi bi-calendar-check display-5 text-success mb-3"></i>
                                        <h6 class="fw-bold">Today's Schedule</h6>
                                        <p class="small text-muted">View appointments</p>
                                        <a href="appointments.php" class="btn btn-success btn-sm">
                                            <i class="bi bi-calendar3 me-1"></i>View Schedule
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Guidelines -->
                        <div class="card card-prescription">
                            <div class="card-header bg-info text-white">
                                <h6 class="card-title mb-0">
                                    <i class="bi bi-info-circle me-2"></i>Prescription Guidelines
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                    Always verify patient identity before prescribing medications.
                                </div>
                                <ul class="list-unstyled small">
                                    <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Include clear dosage and timing instructions</li>
                                    <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Specify exact duration of treatment</li>
                                    <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Note any contraindications or warnings</li>
                                    <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Include follow-up requirements if needed</li>
                                    <li><i class="bi bi-check-circle text-success me-2"></i>Use generic names when possible</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Recent Activity -->
                        <?php if (!empty($prescriptions)): ?>
                        <div class="card card-prescription mt-4">
                            <div class="card-header bg-secondary text-white">
                                <h6 class="card-title mb-0">
                                    <i class="bi bi-clock me-2"></i>Recent for This Patient
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php foreach (array_slice($prescriptions, 0, 3) as $recent): ?>
                                    <div class="medication-preview mb-3">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <strong><?= htmlspecialchars(date('M j, Y', strtotime($recent['created_at']))) ?></strong>
                                                <div class="small"><?= nl2br(htmlspecialchars(substr($recent['medication'], 0, 100))) ?>...</div>
                                            </div>
                                            <button class="btn btn-sm btn-outline-primary" onclick="openPrint(<?= $recent['id'] ?>)">
                                                <i class="bi bi-printer"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Prescription History Tab -->
            <div class="tab-pane fade" id="prescription-history" role="tabpanel">
                <div class="card card-prescription">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-clock-history me-2"></i>Prescription History
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($all_prescriptions) === 0): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-file-medical display-1 text-muted"></i>
                                <h4 class="text-muted mt-3">No Prescriptions Found</h4>
                                <p class="text-muted">Start by creating your first prescription.</p>
                                <button class="btn btn-primary" onclick="switchToCreateTab()">
                                    <i class="bi bi-plus-circle me-1"></i>Create First Prescription
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Prescription ID</th>
                                            <th>Patient</th>
                                            <th>Medication Summary</th>
                                            <th>Dosage</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($all_prescriptions as $pres): ?>
                                            <tr>
                                                <td>
                                                    <strong>#<?= htmlspecialchars($pres['id']) ?></strong>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold"><?= htmlspecialchars($pres['patient_name']) ?></div>
                                                    <small class="text-muted">Patient ID: <?= htmlspecialchars($pres['patient_id']) ?></small>
                                                </td>
                                                <td>
                                                    <div class="text-truncate" style="max-width: 250px;" 
                                                         data-bs-toggle="tooltip" 
                                                         title="<?= htmlspecialchars($pres['medication']) ?>">
                                                        <?= htmlspecialchars($pres['medication']) ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if (!empty($pres['dosage'])): ?>
                                                        <span class="badge bg-info"><?= htmlspecialchars($pres['dosage']) ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="small">
                                                        <?= htmlspecialchars(date('M j, Y', strtotime($pres['created_at']))) ?>
                                                    </div>
                                                    <div class="text-muted smaller">
                                                        <?= htmlspecialchars(date('H:i', strtotime($pres['created_at']))) ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-primary" onclick="viewPrescriptionDetails(<?= $pres['id'] ?>)">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                        <button class="btn btn-outline-secondary" onclick="openPrint(<?= $pres['id'] ?>)">
                                                            <i class="bi bi-printer"></i>
                                                        </button>
                                                        <button class="btn btn-outline-info" onclick="copyPrescription(<?= $pres['id'] ?>)">
                                                            <i class="bi bi-copy"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            <nav aria-label="Prescription history navigation">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item disabled">
                                        <a class="page-link" href="#" tabindex="-1">Previous</a>
                                    </li>
                                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                                    <li class="page-item">
                                        <a class="page-link" href="#">Next</a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Search & Analytics Tab -->
            <div class="tab-pane fade" id="search-prescriptions" role="tabpanel">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card card-prescription">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-search me-2"></i>Search Prescriptions
                                </h5>
                            </div>
                            <div class="card-body">
                                <form class="row g-3 mb-4" id="searchForm">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Patient Name</label>
                                        <input type="text" class="form-control" placeholder="Enter patient name">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Date From</label>
                                        <input type="date" class="form-control">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Date To</label>
                                        <input type="date" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Medication Type</label>
                                        <select class="form-select">
                                            <option value="">All Medications</option>
                                            <option value="antibiotic">Antibiotics</option>
                                            <option value="analgesic">Pain Relief</option>
                                            <option value="chronic">Chronic Condition</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Status</label>
                                        <select class="form-select">
                                            <option value="">All Status</option>
                                            <option value="active">Active</option>
                                            <option value="completed">Completed</option>
                                            <option value="cancelled">Cancelled</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-warning">
                                            <i class="bi bi-search me-1"></i>Search Prescriptions
                                        </button>
                                        <button type="reset" class="btn btn-outline-secondary ms-2">
                                            <i class="bi bi-arrow-clockwise me-1"></i>Reset Filters
                                        </button>
                                    </div>
                                </form>
                                
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Advanced search and analytics features are coming in the next update.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card card-prescription">
                            <div class="card-header bg-info text-white">
                                <h6 class="card-title mb-0">
                                    <i class="bi bi-graph-up me-2"></i>Quick Stats
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="text-center mb-4">
                                    <div class="display-6 fw-bold text-primary"><?= count($all_prescriptions) ?></div>
                                    <div class="text-muted">Total Prescriptions</div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>This Month</span>
                                        <span><?= count($all_prescriptions) ?></span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-success" style="width: 75%"></div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Last Month</span>
                                        <span>24</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-info" style="width: 60%"></div>
                                    </div>
                                </div>
                                
                                <div class="text-center mt-4">
                                    <button class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-download me-1"></i>Export Report
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- Hidden print areas -->
<?php foreach ($all_prescriptions as $pres): ?>
    <div id="print-area-<?= $pres['id'] ?>" class="print-area" style="display:none;">
        <div style="max-width:800px;margin:0 auto;padding:30px;font-family:'Segoe UI',Arial,sans-serif;line-height:1.6;">
            <!-- Header -->
            <div style="border-bottom:3px double #333;padding-bottom:20px;margin-bottom:30px;">
                <table style="width:100%;">
                    <tr>
                        <td style="width:70%;">
                            <h1 style="margin:0;color:#2c3e50;font-size:28px;">Smart Medical Laboratory</h1>
                            <p style="margin:5px 0 0 0;color:#7f8c8d;font-size:14px;">
                                123 Healthcare Street, Medical City, MC 12345<br>
                                Phone: (555) 123-4567 | Email: info@smartlab.com
                            </p>
                        </td>
                        <td style="text-align:right;vertical-align:top;">
                            <div style="background:#2c3e50;color:white;padding:15px;border-radius:8px;display:inline-block;">
                                <strong style="display:block;font-size:16px;">PRESCRIPTION</strong>
                                <span style="font-size:12px;">#<?= htmlspecialchars($pres['id']) ?></span>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Patient and Doctor Info -->
            <div style="margin-bottom:30px;">
                <div style="display:inline-block;width:48%;vertical-align:top;">
                    <h3 style="color:#3498db;margin-bottom:10px;font-size:16px;">PATIENT INFORMATION</h3>
                    <p style="margin:2px 0;"><strong>Name:</strong> <?= htmlspecialchars($pres['patient_name']) ?></p>
                    <p style="margin:2px 0;"><strong>Patient ID:</strong> <?= htmlspecialchars($pres['patient_id']) ?></p>
                </div>
                <div style="display:inline-block;width:48%;vertical-align:top;text-align:right;">
                    <h3 style="color:#3498db;margin-bottom:10px;font-size:16px;">PRESCRIBING DOCTOR</h3>
                    <p style="margin:2px 0;"><strong>Dr. <?= htmlspecialchars($pres['doctor_name']) ?></strong></p>
                    <p style="margin:2px 0;"><strong>Date:</strong> <?= htmlspecialchars(date('F j, Y', strtotime($pres['created_at']))) ?></p>
                    <p style="margin:2px 0;"><strong>Time:</strong> <?= htmlspecialchars(date('h:i A', strtotime($pres['created_at']))) ?></p>
                </div>
            </div>

            <!-- Prescription Details -->
            <div style="margin-bottom:30px;">
                <h3 style="color:#e74c3c;border-bottom:2px solid #e74c3c;padding-bottom:5px;font-size:18px;">
                    MEDICATION PRESCRIPTION
                </h3>
                <div style="background:#f8f9fa;padding:20px;border-radius:5px;margin-top:15px;">
                    <div style="white-space: pre-line;font-size:14px;"><?= htmlspecialchars($pres['medication']) ?></div>
                </div>
            </div>

            <!-- Dosage and Duration -->
            <div style="margin-bottom:30px;">
                <div style="display:inline-block;width:48%;vertical-align:top;">
                    <?php if (!empty($pres['dosage'])): ?>
                    <h3 style="color:#27ae60;font-size:16px;margin-bottom:5px;">DOSAGE INSTRUCTIONS</h3>
                    <p style="margin:0;font-size:14px;"><?= htmlspecialchars($pres['dosage']) ?></p>
                    <?php endif; ?>
                </div>
                <div style="display:inline-block;width:48%;vertical-align:top;">
                    <?php if (!empty($pres['duration'])): ?>
                    <h3 style="color:#f39c12;font-size:16px;margin-bottom:5px;">TREATMENT DURATION</h3>
                    <p style="margin:0;font-size:14px;"><?= htmlspecialchars($pres['duration']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Additional Notes -->
            <?php if (!empty($pres['notes'])): ?>
            <div style="margin-bottom:30px;">
                <h3 style="color:#9b59b6;border-bottom:1px solid #9b59b6;padding-bottom:5px;font-size:16px;">
                    ADDITIONAL NOTES & INSTRUCTIONS
                </h3>
                <div style="padding:15px;background:#f8f9fa;border-radius:5px;margin-top:10px;">
                    <div style="white-space: pre-line;font-size:14px;"><?= htmlspecialchars($pres['notes']) ?></div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Footer -->
            <div style="border-top:3px double #333;padding-top:20px;margin-top:40px;text-align:center;">
                <table style="width:100%;">
                    <tr>
                        <td style="width:60%;text-align:left;vertical-align:top;">
                            <div style="border:1px solid #bdc3c7;padding:10px;border-radius:5px;display:inline-block;">
                                <strong style="display:block;font-size:12px;">DOCTOR'S SIGNATURE</strong>
                                <div style="height:40px;margin-top:5px;"></div>
                                <hr style="margin:5px 0;border-top:1px solid #bdc3c7;">
                                <span style="font-size:11px;">Dr. <?= htmlspecialchars($pres['doctor_name']) ?></span>
                            </div>
                        </td>
                        <td style="text-align:right;vertical-align:top;">
                            <div style="font-size:11px;color:#7f8c8d;">
                                <p style="margin:2px 0;">This is a computer-generated prescription</p>
                                <p style="margin:2px 0;">Valid without signature</p>
                                <p style="margin:2px 0;">Smart Laboratory Management System</p>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Watermark -->
            <div style="position:fixed;top:40%;left:20%;transform:rotate(-45deg);font-size:80px;color:rgba(0,0,0,0.03);font-weight:bold;pointer-events:none;">
                PRESCRIPTION
            </div>
        </div>
    </div>
<?php endforeach; ?>

<script>
function openPrint(id){
    var html = document.getElementById('print-area-' + id).innerHTML;
    var w = window.open('', '_blank', 'width=1000,height=800,scrollbars=yes');
    w.document.write('<html><head><title>Prescription #' + id + '</title>');
    w.document.write('<link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">');
    w.document.write('<style>body { margin: 0; padding: 20px; }</style>');
    w.document.write('</head><body>');
    w.document.write(html);
    w.document.write('</body></html>');
    w.document.close();
    w.focus();
    setTimeout(function(){ w.print(); }, 500);
}

function viewPrescriptionDetails(id) {
    // Simple modal implementation - you can enhance this with a proper modal
    alert('Viewing detailed prescription #' + id + '\nThis would open a modal with full prescription details.');
}

function copyPrescription(id) {
    alert('Copy functionality for prescription #' + id + ' would be implemented here.');
}

function switchToCreateTab() {
    const createTab = new bootstrap.Tab(document.getElementById('create-tab'));
    createTab.show();
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});

// Form validation
document.getElementById('prescriptionForm').addEventListener('submit', function(e) {
    const patientSelect = this.querySelector('select[name="patient_id"]');
    const medicationTextarea = this.querySelector('textarea[name="medication"]');
    
    if (patientSelect.value === '') {
        e.preventDefault();
        alert('Please select a patient.');
        patientSelect.focus();
        return;
    }
    
    if (medicationTextarea.value.trim() === '') {
        e.preventDefault();
        alert('Please enter medication details.');
        medicationTextarea.focus();
        return;
    }
});
</script>