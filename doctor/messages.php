<?php
session_start();
include '../config/db.php';

// Ensure the doctor is logged in
if (!isset($_SESSION['doctor_id'])) {
    header("Location: ../login.php");
    exit;
}

$doctor_id = $_SESSION['doctor_id'];

// Enable error reporting for debugging (you can disable later)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle delete message
if (isset($_GET['delete'])) {
    $msg_id = intval($_GET['delete']);
    $conn->query("DELETE FROM messages WHERE id = $msg_id AND (sender_id = $doctor_id OR receiver_id = $doctor_id)");
    echo "<script>alert('Message deleted successfully'); window.location.href='messages.php';</script>";
    exit;
}

// Handle mark as read
if (isset($_GET['mark_read'])) {
    $msg_id = intval($_GET['mark_read']);
    $conn->query("UPDATE messages SET is_read = 1 WHERE id = $msg_id AND receiver_id = $doctor_id AND receiver_role = 'doctor'");
    header("Location: messages.php");
    exit;
}

// Handle new message send
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['receiver_id'], $_POST['receiver_role'], $_POST['subject'], $_POST['message'])) {
    $receiver_id = intval($_POST['receiver_id']);
    $receiver_role = $conn->real_escape_string($_POST['receiver_role']);
    $subject = $conn->real_escape_string(trim($_POST['subject']));
    $message = $conn->real_escape_string(trim($_POST['message']));
    $reply_to = isset($_POST['reply_to']) ? intval($_POST['reply_to']) : NULL;

    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, sender_role, receiver_role, subject, message, reply_to, created_at) VALUES (?, ?, 'doctor', ?, ?, ?, ?, NOW())");
    $stmt->bind_param("iisssi", $doctor_id, $receiver_id, $receiver_role, $subject, $message, $reply_to);

    if ($stmt->execute()) {
        echo "<script>alert('Message sent successfully'); window.location.href='messages.php';</script>";
        exit;
    } else {
        echo "<script>alert('Failed to send message.');</script>";
    }
}

// Fetch inbox messages (received by doctor)
$inbox = $conn->query("
    SELECT m.id, m.subject, m.message, m.is_read, m.created_at, m.reply_to,
           CASE 
               WHEN m.sender_role = 'patient' THEN p.full_name
               WHEN m.sender_role = 'doctor' THEN dr.full_name
               WHEN m.sender_role = 'admin' THEN a.full_name
               ELSE 'Unknown'
           END AS sender_name,
           m.sender_role
    FROM messages m
    LEFT JOIN patients p ON m.sender_id = p.patient_id AND m.sender_role = 'patient'
    LEFT JOIN doctors dr ON m.sender_id = dr.doctor_id AND m.sender_role = 'doctor'
    LEFT JOIN admins a ON m.sender_id = a.id AND m.sender_role = 'admin'
    WHERE m.receiver_id = $doctor_id AND m.receiver_role = 'doctor'
    ORDER BY m.created_at DESC
");

// Fetch outbox messages (sent by doctor)
$outbox = $conn->query("
    SELECT m.id, m.subject, m.message, m.is_read, m.created_at, m.reply_to,
           CASE 
               WHEN m.receiver_role = 'patient' THEN p.full_name
               WHEN m.receiver_role = 'doctor' THEN dr.full_name
               WHEN m.receiver_role = 'admin' THEN a.full_name
               ELSE 'Unknown'
           END AS receiver_name,
           m.receiver_role
    FROM messages m
    LEFT JOIN patients p ON m.receiver_id = p.patient_id AND m.receiver_role = 'patient'
    LEFT JOIN doctors dr ON m.receiver_id = dr.doctor_id AND m.receiver_role = 'doctor'
    LEFT JOIN admins a ON m.receiver_id = a.id AND m.receiver_role = 'admin'
    WHERE m.sender_id = $doctor_id AND m.sender_role = 'doctor'
    ORDER BY m.created_at DESC
");

// Fetch assigned patients (for sending messages)
$patients = $conn->query("SELECT DISTINCT p.patient_id as id, p.full_name FROM patients p
    JOIN bookings b ON b.patient_id = p.patient_id
    WHERE b.id = $doctor_id
");

// Fetch other doctors for messaging
$doctors = $conn->query("SELECT id as id, full_name FROM staff WHERE id != $doctor_id");

// Fetch admins for messaging
$admins = $conn->query("SELECT id as id, full_name FROM admins");

// Get unread count
$unread_result = $conn->query("SELECT COUNT(*) as count FROM messages WHERE receiver_id = $doctor_id AND receiver_role = 'doctor' AND is_read = 0");
$unread_count = $unread_result ? $unread_result->fetch_assoc()['count'] : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Messages | Doctor Dashboard - Smart Laboratory System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .unread { font-weight: bold; background-color: #f8f9fa; }
        .read { color: #6c757d; }
        .badge { font-size: 0.7em; }
        .navbar-brand { font-weight: 600; }
        .nav-link:hover { background-color: rgba(255,255,255,0.1); }
        .dropdown-menu { background-color: #343a40; }
        .dropdown-item { color: #fff; }
        .dropdown-item:hover { background-color: #495057; color: #fff; }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-heart-pulse"></i> Smart Laboratory System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="appointments.php">
                            <i class="bi bi-calendar-check"></i> Appointments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="patients.php">
                            <i class="bi bi-people"></i> Patients
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="messages.php">
                            <i class="bi bi-chat-dots"></i> Messages
                            <?php if ($unread_count > 0): ?>
                                <span class="badge bg-danger"><?php echo $unread_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="bi bi-file-earmark-medical"></i> Reports
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> Doctor
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php">
                                <i class="bi bi-person"></i> My Profile
                            </a></li>
                            <li><a class="dropdown-item" href="settings.php">
                                <i class="bi bi-gear"></i> Settings
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="appointments.php">
                                <i class="bi bi-calendar-check"></i> Appointments
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="patients.php">
                                <i class="bi bi-people"></i> Patients
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="messages.php">
                                <i class="bi bi-chat-dots"></i> Messages
                                <?php if ($unread_count > 0): ?>
                                    <span class="badge bg-danger float-end"><?php echo $unread_count; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reports.php">
                                <i class="bi bi-file-earmark-medical"></i> Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="view_results.php">
                                <i class="bi bi-clipboard-data"></i> Lab Results
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="add_diagnosis.php">
                                <i class="bi bi-prescription"></i> Prescriptions
                            </a>
                        </li>
                    </ul>

                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                        <span>Account</span>
                    </h6>
                    <ul class="nav flex-column mb-2">
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">
                                <i class="bi bi-person"></i> Profile
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="settings.php">
                                <i class="bi bi-gear"></i> Settings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content Area -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="bi bi-chat-dots text-primary"></i> Messages
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                                <i class="bi bi-plus-circle"></i> New Message
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        Message sent successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Inbox and Outbox -->
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <ul class="nav nav-tabs" id="messageTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="inbox-tab" data-bs-toggle="tab" data-bs-target="#inbox" type="button" role="tab">
                                            <i class="bi bi-inbox"></i> Inbox 
                                            <?php if ($unread_count > 0): ?>
                                                <span class="badge bg-danger"><?php echo $unread_count; ?></span>
                                            <?php endif; ?>
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="outbox-tab" data-bs-toggle="tab" data-bs-target="#outbox" type="button" role="tab">
                                            <i class="bi bi-send"></i> Outbox
                                        </button>
                                    </li>
                                </ul>

                                <div class="tab-content pt-3" id="messageTabsContent">
                                    <!-- Inbox Tab -->
                                    <div class="tab-pane fade show active" id="inbox" role="tabpanel">
                                        <?php if ($inbox && $inbox->num_rows > 0): ?>
                                            <div class="table-responsive">
                                                <table class="table table-hover">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>From</th>
                                                            <th>Role</th>
                                                            <th>Subject</th>
                                                            <th>Date</th>
                                                            <th>Status</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php while ($msg = $inbox->fetch_assoc()) { 
                                                            $row_class = $msg['is_read'] ? 'read' : 'unread';
                                                        ?>
                                                            <tr class="<?php echo $row_class; ?>">
                                                                <td>
                                                                    <div class="d-flex align-items-center">
                                                                        <div class="flex-shrink-0">
                                                                            <i class="bi bi-person-circle me-2"></i>
                                                                        </div>
                                                                        <div class="flex-grow-1">
                                                                            <?= htmlspecialchars($msg['sender_name']); ?>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                                <td><span class="badge bg-info"><?= ucfirst($msg['sender_role']); ?></span></td>
                                                                <td><?= htmlspecialchars($msg['subject']); ?></td>
                                                                <td><?= date('d M Y, h:i A', strtotime($msg['created_at'])); ?></td>
                                                                <td>
                                                                    <?php if ($msg['is_read']): ?>
                                                                        <span class="badge bg-success">Read</span>
                                                                    <?php else: ?>
                                                                        <span class="badge bg-warning">Unread</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td>
                                                                    <div class="btn-group btn-group-sm">
                                                                        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewModal<?= $msg['id']; ?>" onclick="markAsRead(<?= $msg['id']; ?>)">
                                                                            <i class="bi bi-eye"></i>
                                                                        </button>
                                                                        <?php if ($msg['sender_role'] != 'admin'): ?>
                                                                            <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#replyModal<?= $msg['id']; ?>">
                                                                                <i class="bi bi-reply"></i>
                                                                            </button>
                                                                        <?php endif; ?>
                                                                        <a href="?delete=<?= $msg['id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Delete this message?')">
                                                                            <i class="bi bi-trash"></i>
                                                                        </a>
                                                                    </div>
                                                                </td>
                                                            </tr>

                                                            <!-- View Modal -->
                                                            <div class="modal fade" id="viewModal<?= $msg['id']; ?>" tabindex="-1">
                                                                <div class="modal-dialog modal-dialog-centered">
                                                                    <div class="modal-content">
                                                                        <div class="modal-header">
                                                                            <h5 class="modal-title"><?= htmlspecialchars($msg['subject']); ?></h5>
                                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                        </div>
                                                                        <div class="modal-body">
                                                                            <div class="mb-3">
                                                                                <strong>From:</strong> <?= htmlspecialchars($msg['sender_name']); ?> (<?= ucfirst($msg['sender_role']); ?>)<br>
                                                                                <strong>Date:</strong> <?= date('d M Y, h:i A', strtotime($msg['created_at'])); ?>
                                                                            </div>
                                                                            <hr>
                                                                            <p class="mb-0"><?= nl2br(htmlspecialchars($msg['message'])); ?></p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- Reply Modal -->
                                                            <div class="modal fade" id="replyModal<?= $msg['id']; ?>" tabindex="-1">
                                                                <div class="modal-dialog modal-dialog-centered">
                                                                    <div class="modal-content">
                                                                        <div class="modal-header">
                                                                            <h5 class="modal-title">Reply to <?= htmlspecialchars($msg['sender_name']); ?></h5>
                                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                        </div>
                                                                        <form method="POST" action="">
                                                                            <div class="modal-body">
                                                                                <input type="hidden" name="receiver_id" value="<?= $msg['sender_id']; ?>">
                                                                                <input type="hidden" name="receiver_role" value="<?= $msg['sender_role']; ?>">
                                                                                <input type="hidden" name="reply_to" value="<?= $msg['id']; ?>">
                                                                                
                                                                                <div class="mb-3">
                                                                                    <label class="form-label">Subject</label>
                                                                                    <input type="text" name="subject" class="form-control" value="Re: <?= htmlspecialchars($msg['subject']); ?>" required>
                                                                                </div>

                                                                                <div class="mb-3">
                                                                                    <label class="form-label">Message</label>
                                                                                    <textarea name="message" class="form-control" rows="6" placeholder="Type your reply here..." required></textarea>
                                                                                </div>
                                                                            </div>
                                                                            <div class="modal-footer">
                                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                                <button type="submit" class="btn btn-primary">Send Reply</button>
                                                                            </div>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php } ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-center py-5">
                                                <i class="bi bi-inbox display-1 text-muted"></i>
                                                <h4 class="text-muted mt-3">No messages in inbox</h4>
                                                <p class="text-muted">Your inbox is empty. When you receive messages, they will appear here.</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Outbox Tab -->
                                    <div class="tab-pane fade" id="outbox" role="tabpanel">
                                        <?php if ($outbox && $outbox->num_rows > 0): ?>
                                            <div class="table-responsive">
                                                <table class="table table-hover">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>To</th>
                                                            <th>Role</th>
                                                            <th>Subject</th>
                                                            <th>Date</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php while ($msg = $outbox->fetch_assoc()) { ?>
                                                            <tr>
                                                                <td>
                                                                    <div class="d-flex align-items-center">
                                                                        <div class="flex-shrink-0">
                                                                            <i class="bi bi-person-circle me-2"></i>
                                                                        </div>
                                                                        <div class="flex-grow-1">
                                                                            <?= htmlspecialchars($msg['receiver_name']); ?>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                                <td><span class="badge bg-info"><?= ucfirst($msg['receiver_role']); ?></span></td>
                                                                <td><?= htmlspecialchars($msg['subject']); ?></td>
                                                                <td><?= date('d M Y, h:i A', strtotime($msg['created_at'])); ?></td>
                                                                <td>
                                                                    <div class="btn-group btn-group-sm">
                                                                        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewModalOut<?= $msg['id']; ?>">
                                                                            <i class="bi bi-eye"></i>
                                                                        </button>
                                                                        <a href="?delete=<?= $msg['id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Delete this message?')">
                                                                            <i class="bi bi-trash"></i>
                                                                        </a>
                                                                    </div>
                                                                </td>
                                                            </tr>

                                                            <!-- Outbox View Modal -->
                                                            <div class="modal fade" id="viewModalOut<?= $msg['id']; ?>" tabindex="-1">
                                                                <div class="modal-dialog modal-dialog-centered">
                                                                    <div class="modal-content">
                                                                        <div class="modal-header">
                                                                            <h5 class="modal-title"><?= htmlspecialchars($msg['subject']); ?></h5>
                                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                        </div>
                                                                        <div class="modal-body">
                                                                            <div class="mb-3">
                                                                                <strong>To:</strong> <?= htmlspecialchars($msg['receiver_name']); ?> (<?= ucfirst($msg['receiver_role']); ?>)<br>
                                                                                <strong>Date:</strong> <?= date('d M Y, h:i A', strtotime($msg['created_at'])); ?>
                                                                            </div>
                                                                            <hr>
                                                                            <p class="mb-0"><?= nl2br(htmlspecialchars($msg['message'])); ?></p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php } ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-center py-5">
                                                <i class="bi bi-send display-1 text-muted"></i>
                                                <h4 class="text-muted mt-3">No messages in outbox</h4>
                                                <p class="text-muted">Messages you send will appear here.</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- New Message Modal -->
    <div class="modal fade" id="newMessageModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Compose New Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Send To</label>
                                <select name="receiver_role" class="form-control" id="receiver_role" required onchange="updateReceiverOptions()">
                                    <option value="">-- Choose Recipient Type --</option>
                                    <option value="patient">Patient</option>
                                    <option value="doctor">Doctor</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Select Recipient</label>
                                <select name="receiver_id" class="form-control" id="receiver_id" required>
                                    <option value="">-- Choose Recipient --</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <input type="text" name="subject" class="form-control" placeholder="Enter message subject" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message</label>
                            <textarea name="message" class="form-control" rows="8" placeholder="Type your message here..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    function updateReceiverOptions() {
        const receiverRole = document.getElementById('receiver_role').value;
        const receiverSelect = document.getElementById('receiver_id');
        
        // Clear existing options
        receiverSelect.innerHTML = '<option value="">-- Choose Recipient --</option>';
        
        if (receiverRole === 'patient') {
            <?php
                $patients_data = [];
                if ($patients) {
                    while ($row = $patients->fetch_assoc()) {
                        $patients_data[] = $row;
                    }
                }
                echo 'const patients = ' . json_encode($patients_data) . ';';
            ?>
            patients.forEach(patient => {
                const option = document.createElement('option');
                option.value = patient.id;
                option.textContent = patient.full_name;
                receiverSelect.appendChild(option);
            });
        } else if (receiverRole === 'doctor') {
            <?php
                $doctors_data = [];
                if ($doctors) {
                    while ($row = $doctors->fetch_assoc()) {
                        $doctors_data[] = $row;
                    }
                }
                echo 'const doctors = ' . json_encode($doctors_data) . ';';
            ?>
            doctors.forEach(doctor => {
                const option = document.createElement('option');
                option.value = doctor.id;
                option.textContent = doctor.full_name;
                receiverSelect.appendChild(option);
            });
        } else if (receiverRole === 'admin') {
            <?php
                $admins_data = [];
                if ($admins) {
                    while ($row = $admins->fetch_assoc()) {
                        $admins_data[] = $row;
                    }
                }
                echo 'const admins = ' . json_encode($admins_data) . ';';
            ?>
            admins.forEach(admin => {
                const option = document.createElement('option');
                option.value = admin.id;
                option.textContent = admin.full_name;
                receiverSelect.appendChild(option);
            });
        }
    }

    function markAsRead(messageId) {
        // AJAX call to mark message as read
        fetch(`messages.php?mark_read=${messageId}`)
            .then(response => {
                // Remove unread styling
                const row = document.querySelector(`button[data-bs-target="#viewModal${messageId}"]`).closest('tr');
                if (row) {
                    row.classList.remove('unread');
                    row.classList.add('read');
                    
                    // Update status badge
                    const statusCell = row.querySelector('td:nth-child(5)');
                    if (statusCell) {
                        statusCell.innerHTML = '<span class="badge bg-success">Read</span>';
                    }
                }
            })
            .catch(error => console.error('Error:', error));
    }

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
    </script>
</body>
</html>