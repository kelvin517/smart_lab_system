<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// start_test.php - Technician starts a test for a patient
session_start();
include '../config/db.php';

if (!isset($_SESSION['technician_id'])) {
    header('Location: login.php');
    exit();
}

$technician_id = $_SESSION['technician_id'];

// Validate booking ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid Test ID');
}

$booking_id = intval($_GET['id']);

// Fetch booking details
$stmt = $conn->prepare("SELECT b.id, b.patient_id, b.test_name, b.status, p.full_name, p.phone, p.email, p.gender, p.date_of_birth FROM bookings b JOIN patients p ON b.patient_id = p.patient_id WHERE b.id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
    die('Test record not found');
}

// Calculate patient age
$age = '';
if (!empty($booking['date_of_birth'])) {
    $dob = new DateTime($booking['date_of_birth']);
    $now = new DateTime();
    $age = $now->diff($dob)->y;
}

// Check the actual status column length
$column_info = $conn->query("SHOW COLUMNS FROM bookings LIKE 'status'");
$status_column = $column_info->fetch_assoc();
$status_type = $status_column['Type'];

// Determine appropriate status value based on column type
if (strpos($status_type, 'enum') !== false) {
    // It's an ENUM column - we need to use one of the allowed values
    preg_match("/enum\('(.*)'\)/", $status_type, $matches);
    $allowed_values = explode("','", $matches[1]);
    
    // Use appropriate status based on available values
    if (in_array('in_progress', $allowed_values)) {
        $new_status = 'in_progress';
    } elseif (in_array('progress', $allowed_values)) {
        $new_status = 'progress';
    } elseif (in_array('active', $allowed_values)) {
        $new_status = 'active';
    } else {
        $new_status = $allowed_values[1] ?? 'pending'; // Use second value or fallback
    }
} else {
    // It's a VARCHAR column - use shorter status
    $new_status = 'Progress';
}

// Start test action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_time = date('Y-m-d H:i:s');

    $update = $conn->prepare("UPDATE bookings SET status = ?, started_at = ?, handled_by = ? WHERE id = ?");
    $update->bind_param("ssii", $new_status, $start_time, $technician_id, $booking_id);

    if ($update->execute()) {
        header('Location: manage_tests.php?start=success');
        exit();
    } else {
        $error = "Failed to start test. Please try again. Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Start Test - Smart Lab System</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .test-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
        }
        
        .main-card {
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
            border: none;
            overflow: hidden;
            background: white;
        }
        
        .card-header-custom {
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            color: white;
            padding: 25px 30px;
            border-bottom: none;
        }
        
        .card-header-custom h1 {
            margin: 0;
            font-weight: 600;
            font-size: 1.8rem;
        }
        
        .card-header-custom .subtitle {
            opacity: 0.9;
            font-size: 0.95rem;
            margin-top: 5px;
        }
        
        .patient-profile {
            display: flex;
            align-items: center;
            padding: 25px 30px;
            background: linear-gradient(to right, #f8f9fa, #e9ecef);
            border-bottom: 1px solid #dee2e6;
        }
        
        .patient-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(45deg, var(--primary), var(--info));
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            border: 4px solid white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .patient-avatar i {
            font-size: 2rem;
            color: white;
        }
        
        .patient-info h3 {
            margin: 0 0 5px 0;
            color: var(--secondary);
            font-weight: 600;
        }
        
        .patient-info p {
            margin: 2px 0;
            color: #6c757d;
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            padding: 30px;
        }
        
        .detail-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            border-left: 4px solid var(--primary);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .detail-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .detail-card h5 {
            color: var(--secondary);
            margin-bottom: 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .detail-card h5 i {
            margin-right: 10px;
            color: var(--primary);
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 500;
            color: #495057;
        }
        
        .detail-value {
            color: var(--dark);
            font-weight: 500;
        }
        
        .status-badge {
            padding: 6px 15px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.85rem;
        }
        
        .action-section {
            padding: 25px 30px;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
        }
        
        .btn-start {
            background: linear-gradient(135deg, var(--success), #2ecc71);
            border: none;
            border-radius: 12px;
            padding: 14px 35px;
            font-weight: 600;
            font-size: 1.1rem;
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 5px 20px rgba(46, 204, 113, 0.3);
        }
        
        .btn-start:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(46, 204, 113, 0.4);
            background: linear-gradient(135deg, #27ae60, #229954);
        }
        
        .btn-cancel {
            background: white;
            border: 2px solid #6c757d;
            border-radius: 12px;
            padding: 14px 35px;
            font-weight: 600;
            font-size: 1.1rem;
            color: #6c757d;
            transition: all 0.3s ease;
        }
        
        .btn-cancel:hover {
            background: #6c757d;
            color: white;
            transform: translateY(-2px);
        }
        
        .alert-custom {
            border-radius: 12px;
            border: none;
            padding: 15px 20px;
            margin: 20px 30px;
        }
        
        .test-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: rgba(52, 152, 219, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        
        .test-icon i {
            font-size: 1.5rem;
            color: var(--primary);
        }
        
        .debug-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 30px;
            font-size: 0.85rem;
            color: #6c757d;
            border-left: 4px solid var(--warning);
        }
        
        @media (max-width: 768px) {
            .test-container {
                margin: 20px auto;
                padding: 10px;
            }
            
            .patient-profile {
                flex-direction: column;
                text-align: center;
            }
            
            .patient-avatar {
                margin-right: 0;
                margin-bottom: 15px;
            }
            
            .details-grid {
                grid-template-columns: 1fr;
                padding: 20px;
            }
            
            .action-section .d-flex {
                flex-direction: column;
                gap: 15px;
            }
            
            .btn-start, .btn-cancel {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="test-container">
        <div class="main-card">
            <!-- Header -->
            <div class="card-header-custom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1><i class="fas fa-vial me-2"></i>Start Laboratory Test</h1>
                        <div class="subtitle">Initiate diagnostic testing procedure</div>
                    </div>
                    <div class="text-end">
                        <div class="text-light opacity-75">Test ID: #<?= $booking_id ?></div>
                        <div class="text-warning">
                            <i class="fas fa-clock me-1"></i>
                            <?= date('M j, Y g:i A') ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Patient Profile -->
            <div class="patient-profile">
                <div class="patient-avatar">
                    <i class="fas fa-user-injured"></i>
                </div>
                <div class="patient-info">
                    <h3><?= htmlspecialchars($booking['full_name']) ?></h3>
                    <p class="mb-1">
                        <i class="fas fa-id-card me-1 text-primary"></i>
                        Patient ID: #<?= $booking['patient_id'] ?>
                    </p>
                    <p class="mb-0">
                        <i class="fas fa-phone me-1 text-success"></i>
                        <?= htmlspecialchars($booking['phone']) ?>
                    </p>
                </div>
            </div>
            
            <!-- Debug Information (remove in production) -->
            <div class="debug-info">
                <strong>Status Info:</strong> Column type: <?= htmlspecialchars($status_type) ?> | 
                Using status: <strong><?= $new_status ?></strong>
            </div>
            
            <!-- Error Alert -->
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-custom d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle me-3 fa-lg"></i>
                    <div><?= $error ?></div>
                </div>
            <?php endif; ?>
            
            <!-- Test Details -->
            <div class="details-grid">
                <!-- Test Information -->
                <div class="detail-card">
                    <h5><i class="fas fa-vial"></i> Test Information</h5>
                    <div class="detail-item">
                        <span class="detail-label">Test Type:</span>
                        <span class="detail-value"><?= htmlspecialchars($booking['test_name']) ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Current Status:</span>
                        <span class="badge status-badge bg-warning"><?= htmlspecialchars($booking['status']) ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">New Status:</span>
                        <span class="badge status-badge bg-success"><?= $new_status ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Booking ID:</span>
                        <span class="detail-value">#<?= $booking_id ?></span>
                    </div>
                </div>
                
                <!-- Patient Details -->
                <div class="detail-card">
                    <h5><i class="fas fa-user-circle"></i> Patient Details</h5>
                    <div class="detail-item">
                        <span class="detail-label">Full Name:</span>
                        <span class="detail-value"><?= htmlspecialchars($booking['full_name']) ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Gender:</span>
                        <span class="detail-value"><?= htmlspecialchars($booking['gender'] ?? 'Not specified') ?></span>
                    </div>
                    <?php if ($age): ?>
                    <div class="detail-item">
                        <span class="detail-label">Age:</span>
                        <span class="detail-value"><?= $age ?> years</span>
                    </div>
                    <?php endif; ?>
                    <div class="detail-item">
                        <span class="detail-label">Email:</span>
                        <span class="detail-value"><?= htmlspecialchars($booking['email'] ?? 'Not provided') ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Action Section -->
            <div class="action-section">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <div class="test-icon">
                            <i class="fas fa-vial"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Ready to Begin Test</h5>
                            <p class="mb-0 text-muted">Confirm to start the laboratory procedure</p>
                        </div>
                    </div>
                    <div class="d-flex gap-3">
                        <a href="manage_tests.php" class="btn btn-cancel">
                            <i class="fas fa-arrow-left me-2"></i>Cancel
                        </a>
                        <form method="POST" class="mb-0">
                            <button type="submit" class="btn btn-start">
                                <i class="fas fa-play-circle me-2"></i>Start Test
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add some interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            // Add confirmation dialog for starting test
            const startForm = document.querySelector('form');
            if (startForm) {
                startForm.addEventListener('submit', function(e) {
                    const confirmed = confirm('Are you sure you want to start this test? This action will change the status to "<?= $new_status ?>".');
                    if (!confirmed) {
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
</body>
</html>