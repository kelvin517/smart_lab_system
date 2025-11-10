<?php
// Enable error reporting
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
$error = null;
$doctor = null;
$stats = [];

try {
    // First, try to get basic staff info
    $stmt = $conn->prepare("SELECT * FROM staff WHERE id = ?");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $doctor = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$doctor) {
        throw new Exception("Doctor not found in database.");
    }

    // Try to get doctor-specific info if doctors table exists
    $doctors_table_exists = $conn->query("SHOW TABLES LIKE 'doctors'")->num_rows > 0;
    if ($doctors_table_exists) {
        $stmt = $conn->prepare("SELECT * FROM doctors WHERE doctor_id = ?");
        $stmt->bind_param("i", $doctor_id);
        $stmt->execute();
        $doctor_details = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        // Merge doctor details
        if ($doctor_details) {
            $doctor = array_merge($doctor, $doctor_details);
        }
    }

    // Try to get statistics if bookings table exists and has doctor_id
    $bookings_table_exists = $conn->query("SHOW TABLES LIKE 'bookings'")->num_rows > 0;
    if ($bookings_table_exists) {
        $has_doctor_id = $conn->query("SHOW COLUMNS FROM bookings LIKE 'doctor_id'")->num_rows > 0;
        
        if ($has_doctor_id) {
            $stats_sql = "
                SELECT 
                    COUNT(DISTINCT b.id) as total_appointments,
                    COUNT(DISTINCT CASE WHEN b.status = 'Completed' THEN b.id END) as completed_appointments,
                    COUNT(DISTINCT p.patient_id) as total_patients,
                    COUNT(DISTINCT CASE WHEN b.status = 'Pending' AND b.preferred_date >= CURDATE() THEN b.id END) as upcoming_appointments
                FROM bookings b
                INNER JOIN patients p ON b.patient_id = p.patient_id
                WHERE b.doctor_id = ?
            ";
            
            $stats_stmt = $conn->prepare($stats_sql);
            $stats_stmt->bind_param("i", $doctor_id);
            $stats_stmt->execute();
            $stats = $stats_stmt->get_result()->fetch_assoc();
            $stats_stmt->close();
        }
    }

} catch (Exception $e) {
    $error = $e->getMessage();
    error_log("Profile Error: " . $error);
}
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>My Profile</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Profile</li>
            </ol>
        </nav>
    </div>

    <!-- Display Errors -->
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>Database Error:</strong> <?= htmlspecialchars($error) ?>
            <br><small>Some features may not work properly. Please contact administrator.</small>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Success Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?= $_SESSION['success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <section class="section profile">
        <div class="row">
            <div class="col-xl-4">
                <!-- Profile Card -->
                <div class="card profile-card">
                    <div class="card-body profile-overview">
                        <div class="profile-header text-center">
                            <div class="profile-avatar">
                                <?php if (!empty($doctor['profile_picture'])): ?>
                                    <img src="../uploads/profiles/<?= htmlspecialchars($doctor['profile_picture']) ?>" 
                                         alt="Profile" class="rounded-circle">
                                <?php else: ?>
                                    <div class="avatar-placeholder rounded-circle">
                                        <?= strtoupper(substr($doctor['full_name'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="online-status online"></div>
                            </div>
                            <h2 class="profile-name"><?= htmlspecialchars($doctor['full_name']) ?></h2>
                            <p class="profile-title text-muted">
                                <i class="bi bi-briefcase me-1"></i>Medical Doctor
                            </p>
                            
                            <!-- Quick Actions -->
                            <div class="profile-actions mt-3">
                                <a href="edit_profile.php" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-camera me-1"></i> Update Photo
                                </a>
                                <a href="messages.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-chat me-1"></i> Message
                                </a>
                            </div>
                        </div>

                        <div class="profile-info mt-4">
                            <div class="info-item">
                                <i class="bi bi-envelope"></i>
                                <div>
                                    <span class="info-label">Email</span>
                                    <span class="info-value"><?= htmlspecialchars($doctor['email']) ?></span>
                                </div>
                            </div>
                            <?php if (!empty($doctor['phone'])): ?>
                            <div class="info-item">
                                <i class="bi bi-telephone"></i>
                                <div>
                                    <span class="info-label">Phone</span>
                                    <span class="info-value"><?= htmlspecialchars($doctor['phone']) ?></span>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($doctor['specialization'])): ?>
                            <div class="info-item">
                                <i class="bi bi-heart-pulse"></i>
                                <div>
                                    <span class="info-label">Specialization</span>
                                    <span class="info-value"><?= htmlspecialchars($doctor['specialization']) ?></span>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Statistics Card -->
                <div class="card stats-card">
                    <div class="card-body">
                        <h5 class="card-title stats-title">
                            <i class="bi bi-graph-up me-2"></i>Practice Overview
                        </h5>
                        <div class="stats-grid">
                            <div class="stat-card patients">
                                <div class="stat-icon">
                                    <i class="bi bi-people"></i>
                                </div>
                                <div class="stat-content">
                                    <span class="stat-number"><?= $stats['total_patients'] ?? 0 ?></span>
                                    <span class="stat-label">Patients</span>
                                </div>
                            </div>
                            <div class="stat-card appointments">
                                <div class="stat-icon">
                                    <i class="bi bi-calendar-check"></i>
                                </div>
                                <div class="stat-content">
                                    <span class="stat-number"><?= $stats['total_appointments'] ?? 0 ?></span>
                                    <span class="stat-label">Appointments</span>
                                </div>
                            </div>
                            <div class="stat-card completed">
                                <div class="stat-icon">
                                    <i class="bi bi-check-circle"></i>
                                </div>
                                <div class="stat-content">
                                    <span class="stat-number"><?= $stats['completed_appointments'] ?? 0 ?></span>
                                    <span class="stat-label">Completed</span>
                                </div>
                            </div>
                            <div class="stat-card upcoming">
                                <div class="stat-icon">
                                    <i class="bi bi-clock"></i>
                                </div>
                                <div class="stat-content">
                                    <span class="stat-number"><?= $stats['upcoming_appointments'] ?? 0 ?></span>
                                    <span class="stat-label">Upcoming</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <!-- Profile Details Card -->
                <div class="card profile-details-card">
                    <div class="card-body">
                        <!-- Enhanced Tabs -->
                        <ul class="nav nav-tabs nav-tabs-custom" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#profile-overview" type="button" role="tab">
                                    <i class="bi bi-person-vcard me-2"></i>Overview
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#profile-edit" type="button" role="tab">
                                    <i class="bi bi-pencil-square me-2"></i>Edit Profile
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#profile-change-password" type="button" role="tab">
                                    <i class="bi bi-shield-lock me-2"></i>Security
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content pt-4">
                            <!-- Overview Tab -->
                            <div class="tab-pane fade show active" id="profile-overview">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="card-title mb-0">Profile Information</h5>
                                    <span class="badge bg-success">
                                        <i class="bi bi-patch-check-fill me-1"></i>Verified
                                    </span>
                                </div>

                                <div class="row g-4">
                                    <!-- Personal Information -->
                                    <div class="col-md-6">
                                        <div class="info-section">
                                            <h6 class="section-title">
                                                <i class="bi bi-person-gear me-2"></i>Personal Information
                                            </h6>
                                            <div class="info-list">
                                                <div class="info-row">
                                                    <span class="info-label">Full Name</span>
                                                    <span class="info-value"><?= htmlspecialchars($doctor['full_name']) ?></span>
                                                </div>
                                                <div class="info-row">
                                                    <span class="info-label">Email</span>
                                                    <span class="info-value"><?= htmlspecialchars($doctor['email']) ?></span>
                                                </div>
                                                <?php if (!empty($doctor['phone'])): ?>
                                                <div class="info-row">
                                                    <span class="info-label">Phone</span>
                                                    <span class="info-value"><?= htmlspecialchars($doctor['phone']) ?></span>
                                                </div>
                                                <?php endif; ?>
                                                <div class="info-row">
                                                    <span class="info-label">Member Since</span>
                                                    <span class="info-value"><?= date('F j, Y', strtotime($doctor['created_at'])) ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Professional Information -->
                                    <div class="col-md-6">
                                        <div class="info-section">
                                            <h6 class="section-title">
                                                <i class="bi bi-briefcase me-2"></i>Professional Information
                                            </h6>
                                            <div class="info-list">
                                                <?php if (!empty($doctor['specialization'])): ?>
                                                <div class="info-row">
                                                    <span class="info-label">Specialization</span>
                                                    <span class="info-value"><?= htmlspecialchars($doctor['specialization']) ?></span>
                                                </div>
                                                <?php endif; ?>
                                                <?php if (!empty($doctor['license_number'])): ?>
                                                <div class="info-row">
                                                    <span class="info-label">License Number</span>
                                                    <span class="info-value"><?= htmlspecialchars($doctor['license_number']) ?></span>
                                                </div>
                                                <?php endif; ?>
                                                <?php if (!empty($doctor['years_of_experience'])): ?>
                                                <div class="info-row">
                                                    <span class="info-label">Experience</span>
                                                    <span class="info-value"><?= htmlspecialchars($doctor['years_of_experience']) ?> years</span>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Qualifications & Address -->
                                    <div class="col-12">
                                        <?php if (!empty($doctor['qualifications'])): ?>
                                        <div class="info-section">
                                            <h6 class="section-title">
                                                <i class="bi bi-award me-2"></i>Qualifications
                                            </h6>
                                            <div class="qualifications">
                                                <?= nl2br(htmlspecialchars($doctor['qualifications'])) ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <?php if (!empty($doctor['address'])): ?>
                                        <div class="info-section">
                                            <h6 class="section-title">
                                                <i class="bi bi-geo-alt me-2"></i>Address
                                            </h6>
                                            <div class="address">
                                                <?= htmlspecialchars($doctor['address']) ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Edit Profile Tab -->
                            <div class="tab-pane fade" id="profile-edit">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="card-title mb-0">Edit Profile Information</h5>
                                    <span class="text-muted small">Last updated: <?= date('M j, Y g:i A') ?></span>
                                </div>

                                <?php if ($error): ?>
                                    <div class="alert alert-warning">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        Profile editing is currently unavailable due to database issues.
                                    </div>
                                <?php else: ?>
                                <form method="POST" action="update_profile.php" class="needs-validation" novalidate>
                                    <div class="row g-3">
                                        <!-- Personal Information -->
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="full_name" class="form-label">
                                                    <i class="bi bi-person me-1"></i>Full Name
                                                </label>
                                                <input name="full_name" type="text" class="form-control" id="full_name" 
                                                       value="<?= htmlspecialchars($doctor['full_name']) ?>" required>
                                                <div class="invalid-feedback">Please enter your full name.</div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="email" class="form-label">
                                                    <i class="bi bi-envelope me-1"></i>Email Address
                                                </label>
                                                <input name="email" type="email" class="form-control" id="email" 
                                                       value="<?= htmlspecialchars($doctor['email']) ?>" required>
                                                <div class="invalid-feedback">Please enter a valid email address.</div>
                                            </div>
                                        </div>

                                        <?php if (isset($doctor['phone'])): ?>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="phone" class="form-label">
                                                    <i class="bi bi-telephone me-1"></i>Phone Number
                                                </label>
                                                <input name="phone" type="text" class="form-control" id="phone" 
                                                       value="<?= !empty($doctor['phone']) ? htmlspecialchars($doctor['phone']) : '' ?>">
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <?php if (isset($doctor['specialization'])): ?>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="specialization" class="form-label">
                                                    <i class="bi bi-heart-pulse me-1"></i>Specialization
                                                </label>
                                                <input name="specialization" type="text" class="form-control" id="specialization" 
                                                       value="<?= !empty($doctor['specialization']) ? htmlspecialchars($doctor['specialization']) : '' ?>">
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <?php if (isset($doctor['license_number'])): ?>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="license_number" class="form-label">
                                                    <i class="bi bi-file-text me-1"></i>License Number
                                                </label>
                                                <input name="license_number" type="text" class="form-control" id="license_number" 
                                                       value="<?= !empty($doctor['license_number']) ? htmlspecialchars($doctor['license_number']) : '' ?>">
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <?php if (isset($doctor['years_of_experience'])): ?>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="years_of_experience" class="form-label">
                                                    <i class="bi bi-clock-history me-1"></i>Years of Experience
                                                </label>
                                                <input name="years_of_experience" type="number" class="form-control" id="years_of_experience" 
                                                       value="<?= !empty($doctor['years_of_experience']) ? htmlspecialchars($doctor['years_of_experience']) : '' ?>">
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <?php if (isset($doctor['qualifications'])): ?>
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="qualifications" class="form-label">
                                                    <i class="bi bi-award me-1"></i>Qualifications
                                                </label>
                                                <textarea name="qualifications" class="form-control" id="qualifications" 
                                                          rows="4" placeholder="List your qualifications, degrees, certifications..."><?= !empty($doctor['qualifications']) ? htmlspecialchars($doctor['qualifications']) : '' ?></textarea>
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <?php if (isset($doctor['address'])): ?>
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="address" class="form-label">
                                                    <i class="bi bi-geo-alt me-1"></i>Address
                                                </label>
                                                <textarea name="address" class="form-control" id="address" 
                                                          rows="3"><?= !empty($doctor['address']) ? htmlspecialchars($doctor['address']) : '' ?></textarea>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="form-actions mt-4 pt-3 border-top">
                                        <button type="submit" class="btn btn-primary px-4">
                                            <i class="bi bi-check-circle me-2"></i>Save Changes
                                        </button>
                                        <button type="reset" class="btn btn-outline-secondary ms-2">
                                            <i class="bi bi-arrow-clockwise me-2"></i>Reset
                                        </button>
                                    </div>
                                </form>
                                <?php endif; ?>
                            </div>

                            <!-- Change Password Tab -->
                            <div class="tab-pane fade" id="profile-change-password">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="card-title mb-0">Change Password</h5>
                                    <span class="text-muted small">Last changed: Recently</span>
                                </div>

                                <form method="POST" action="change_password.php" class="needs-validation" novalidate>
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="current_password" class="form-label">
                                                    <i class="bi bi-lock me-1"></i>Current Password
                                                </label>
                                                <input name="current_password" type="password" class="form-control" id="current_password" required>
                                                <div class="invalid-feedback">Please enter your current password.</div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="new_password" class="form-label">
                                                    <i class="bi bi-key me-1"></i>New Password
                                                </label>
                                                <input name="new_password" type="password" class="form-control" id="new_password" required minlength="8">
                                                <div class="form-text">
                                                    <i class="bi bi-info-circle me-1"></i>Password must be at least 8 characters long.
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="confirm_password" class="form-label">
                                                    <i class="bi bi-key-fill me-1"></i>Confirm New Password
                                                </label>
                                                <input name="confirm_password" type="password" class="form-control" id="confirm_password" required>
                                                <div class="invalid-feedback">Passwords must match.</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="password-strength mt-3">
                                        <div class="progress" style="height: 4px;">
                                            <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                        </div>
                                        <small class="text-muted">Password strength: <span id="password-strength-text">None</span></small>
                                    </div>

                                    <div class="form-actions mt-4 pt-3 border-top">
                                        <button type="submit" class="btn btn-primary px-4">
                                            <i class="bi bi-shield-check me-2"></i>Update Password
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>


<style>
/* Profile Card Styles */
.profile-card {
    border: none;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    border-radius: 1rem;
    overflow: hidden;
}

.profile-header {
    padding: 2rem 1.5rem 1.5rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    margin: -1.5rem -1.5rem 1.5rem;
}

.profile-avatar {
    position: relative;
    margin-bottom: 1rem;
}

.profile-avatar img,
.avatar-placeholder {
    width: 120px;
    height: 120px;
    border: 4px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.avatar-placeholder {
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    font-weight: 600;
    color: white;
}

.online-status {
    position: absolute;
    bottom: 10px;
    right: 10px;
    width: 20px;
    height: 20px;
    border: 3px solid white;
    border-radius: 50%;
}

.online-status.online {
    background: #28a745;
}

.profile-name {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.profile-title {
    font-size: 0.9rem;
    opacity: 0.9;
}

.profile-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
}

.profile-info {
    padding: 0 0.5rem;
}

.info-item {
    display: flex;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.info-item:last-child {
    border-bottom: none;
}

.info-item i {
    font-size: 1.25rem;
    color: #667eea;
    width: 30px;
    text-align: center;
    margin-right: 1rem;
}

.info-label {
    font-size: 0.8rem;
    color: #6c757d;
    display: block;
}

.info-value {
    font-weight: 500;
    color: #495057;
}

/* Statistics Card */
.stats-card {
    border: none;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    border-radius: 1rem;
    margin-top: 1.5rem;
}

.stats-title {
    color: #495057;
    font-weight: 600;
    margin-bottom: 1.5rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.stat-card {
    background: #f8f9fa;
    border-radius: 0.75rem;
    padding: 1.25rem;
    text-align: center;
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.stat-card.patients { border-left: 4px solid #007bff; }
.stat-card.appointments { border-left: 4px solid #28a745; }
.stat-card.completed { border-left: 4px solid #17a2b8; }
.stat-card.upcoming { border-left: 4px solid #ffc107; }

.stat-icon {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

.stat-card.patients .stat-icon { color: #007bff; }
.stat-card.appointments .stat-icon { color: #28a745; }
.stat-card.completed .stat-icon { color: #17a2b8; }
.stat-card.upcoming .stat-icon { color: #ffc107; }

.stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    display: block;
    color: #495057;
}

.stat-label {
    font-size: 0.8rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Profile Details Card */
.profile-details-card {
    border: none;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    border-radius: 1rem;
}

/* Custom Tabs */
.nav-tabs-custom {
    border-bottom: 2px solid #e9ecef;
    padding: 0 1rem;
}

.nav-tabs-custom .nav-link {
    border: none;
    font-weight: 500;
    color: #6c757d;
    padding: 1rem 1.5rem;
    margin-bottom: -2px;
    transition: all 0.3s ease;
}

.nav-tabs-custom .nav-link:hover {
    color: #495057;
    background: #f8f9fa;
}

.nav-tabs-custom .nav-link.active {
    color: #007bff;
    background: none;
    border-bottom: 3px solid #007bff;
}

/* Info Sections */
.info-section {
    background: #f8f9fa;
    border-radius: 0.75rem;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    border: 1px solid #e9ecef;
}

.section-title {
    font-size: 1rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #007bff;
}

.info-list {
    space-y: 0.75rem;
}

.info-row {
    display: flex;
    justify-content: between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #e9ecef;
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 500;
    color: #495057;
    min-width: 140px;
}

.info-value {
    color: #6c757d;
    text-align: right;
    flex: 1;
}

.qualifications,
.address {
    background: white;
    padding: 1rem;
    border-radius: 0.5rem;
    border: 1px solid #e9ecef;
    line-height: 1.6;
}

/* Form Styles */
.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    font-weight: 500;
    color: #495057;
    margin-bottom: 0.5rem;
}

.form-control {
    border: 2px solid #e9ecef;
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-start;
}

/* Password Strength */
.password-strength {
    margin-top: 1rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .profile-actions {
        flex-direction: column;
    }
    
    .info-row {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .info-value {
        text-align: left;
        margin-top: 0.25rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
}

/* Animation for tab transitions */
.tab-pane.fade {
    transition: opacity 0.3s ease-in-out;
}

/* Custom scrollbar for qualifications */
.qualifications {
    max-height: 200px;
    overflow-y: auto;
}

.qualifications::-webkit-scrollbar {
    width: 6px;
}

.qualifications::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.qualifications::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.qualifications::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>

<script>
// Form validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();

// Password strength indicator
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('new_password');
    const strengthBar = document.querySelector('.password-strength .progress-bar');
    const strengthText = document.getElementById('password-strength-text');
    
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const strength = calculatePasswordStrength(password);
            
            strengthBar.style.width = strength.percentage + '%';
            strengthBar.className = 'progress-bar ' + strength.class;
            strengthText.textContent = strength.text;
            strengthText.className = strength.textClass;
        });
    }
    
    function calculatePasswordStrength(password) {
        let score = 0;
        
        if (password.length >= 8) score += 25;
        if (password.match(/[a-z]/) && password.match(/[A-Z]/)) score += 25;
        if (password.match(/\d/)) score += 25;
        if (password.match(/[^a-zA-Z\d]/)) score += 25;
        
        if (score >= 75) {
            return { percentage: 100, class: 'bg-success', text: 'Strong', textClass: 'text-success' };
        } else if (score >= 50) {
            return { percentage: 75, class: 'bg-info', text: 'Good', textClass: 'text-info' };
        } else if (score >= 25) {
            return { percentage: 50, class: 'bg-warning', text: 'Fair', textClass: 'text-warning' };
        } else {
            return { percentage: 25, class: 'bg-danger', text: 'Weak', textClass: 'text-danger' };
        }
    }
});
</script>