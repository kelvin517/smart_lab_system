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

// ✅ Check and create settings table if it doesn't exist
$table_check = $conn->query("SHOW TABLES LIKE 'system_settings'");
if ($table_check->num_rows == 0) {
    $create_table = $conn->query("
        CREATE TABLE system_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            setting_type ENUM('text', 'number', 'boolean', 'email', 'url') DEFAULT 'text',
            setting_group VARCHAR(50) DEFAULT 'general',
            description TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            updated_by INT,
            FOREIGN KEY (updated_by) REFERENCES admins(id)
        )
    ");
    
    // Insert default settings
    $default_settings = [
        // General Settings
        ['lab_name', 'Smart Laboratory System', 'text', 'general', 'Laboratory Name'],
        ['lab_email', 'info@smartlab.co.ke', 'email', 'general', 'Laboratory Email'],
        ['lab_phone', '+254700123456', 'text', 'general', 'Laboratory Phone'],
        ['lab_address', '123 Healthcare Avenue, Nairobi, Kenya', 'text', 'general', 'Laboratory Address'],
        ['currency', 'KES', 'text', 'general', 'Default Currency'],
        
        // Billing Settings
        ['tax_rate', '16', 'number', 'billing', 'Tax Rate (%)'],
        ['invoice_prefix', 'INV', 'text', 'billing', 'Invoice Number Prefix'],
        ['payment_due_days', '7', 'number', 'billing', 'Payment Due Days'],
        ['late_fee_percentage', '5', 'number', 'billing', 'Late Fee Percentage'],
        
        // Notification Settings
        ['email_notifications', '1', 'boolean', 'notifications', 'Enable Email Notifications'],
        ['sms_notifications', '1', 'boolean', 'notifications', 'Enable SMS Notifications'],
        ['appointment_reminder', '1', 'boolean', 'notifications', 'Send Appointment Reminders'],
        ['result_notifications', '1', 'boolean', 'notifications', 'Send Result Notifications'],
        
        // Security Settings
        ['session_timeout', '30', 'number', 'security', 'Session Timeout (minutes)'],
        ['login_attempts', '5', 'number', 'security', 'Max Login Attempts'],
        ['password_expiry', '90', 'number', 'security', 'Password Expiry Days'],
        ['two_factor_auth', '0', 'boolean', 'security', 'Enable Two-Factor Authentication'],
        
        // System Settings
        ['records_per_page', '10', 'number', 'system', 'Records Per Page'],
        ['auto_backup', '1', 'boolean', 'system', 'Enable Auto Backup'],
        ['backup_frequency', 'daily', 'text', 'system', 'Backup Frequency'],
        ['maintenance_mode', '0', 'boolean', 'system', 'Maintenance Mode'],
    ];
    
    $insert_stmt = $conn->prepare("
        INSERT INTO system_settings (setting_key, setting_value, setting_type, setting_group, description) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    foreach ($default_settings as $setting) {
        $insert_stmt->bind_param("sssss", $setting[0], $setting[1], $setting[2], $setting[3], $setting[4]);
        $insert_stmt->execute();
    }
    $insert_stmt->close();
}

// ✅ Handle form submissions
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_settings'])) {
        // Update multiple settings
        foreach ($_POST['settings'] as $key => $value) {
            $update_stmt = $conn->prepare("
                UPDATE system_settings 
                SET setting_value = ?, updated_by = ?, updated_at = NOW() 
                WHERE setting_key = ?
            ");
            $update_stmt->bind_param("sis", $value, $admin_id, $key);
            $update_stmt->execute();
            $update_stmt->close();
        }
        $success_msg = "System settings updated successfully!";
        
    } elseif (isset($_POST['test_email'])) {
        // Test email configuration
        $to = $_POST['test_email_address'];
        $subject = "Test Email - Smart Laboratory System";
        $message = "This is a test email from your Smart Laboratory System.\n\nIf you received this email, your email configuration is working correctly.";
        $headers = "From: " . get_setting('lab_email') . "\r\n";
        
        if (mail($to, $subject, $message, $headers)) {
            $success_msg = "Test email sent successfully to $to";
        } else {
            $error_msg = "Failed to send test email. Please check your email configuration.";
        }
        
    } elseif (isset($_POST['backup_database'])) {
        // Create database backup
        $backup_file = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $backup_path = '../backups/' . $backup_file;
        
        // Ensure backups directory exists
        if (!is_dir('../backups')) {
            mkdir('../backups', 0755, true);
        }
        
        // Simple backup (in production, use mysqldump command)
        $tables = $conn->query("SHOW TABLES");
        $backup_content = "-- Smart Laboratory System Database Backup\n";
        $backup_content .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
        
        while ($table_row = $tables->fetch_array()) {
            $table = $table_row[0];
            $backup_content .= "-- Table: $table\n";
            
            $create_table = $conn->query("SHOW CREATE TABLE $table")->fetch_array();
            $backup_content .= $create_table[1] . ";\n\n";
            
            $data = $conn->query("SELECT * FROM $table");
            while ($row = $data->fetch_assoc()) {
                $columns = implode("`, `", array_keys($row));
                $values = implode("', '", array_map([$conn, 'real_escape_string'], array_values($row)));
                $backup_content .= "INSERT INTO `$table` (`$columns`) VALUES ('$values');\n";
            }
            $backup_content .= "\n";
        }
        
        if (file_put_contents($backup_path, $backup_content)) {
            $success_msg = "Database backup created successfully: $backup_file";
        } else {
            $error_msg = "Failed to create database backup";
        }
    }
}

// ✅ Function to get setting value
function get_setting($key, $default = '') {
    global $conn;
    $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    $setting = $result->fetch_assoc();
    $stmt->close();
    
    return $setting ? $setting['setting_value'] : $default;
}

// ✅ Get all settings grouped by category
$settings_groups = [];
$settings_query = $conn->query("
    SELECT setting_key, setting_value, setting_type, setting_group, description 
    FROM system_settings 
    ORDER BY setting_group, setting_key
");

while ($setting = $settings_query->fetch_assoc()) {
    $settings_groups[$setting['setting_group']][] = $setting;
}

// ✅ Get system information
$system_info = [
    'php_version' => PHP_VERSION,
    'mysql_version' => $conn->server_info,
    'server_software' => $_SERVER['SERVER_SOFTWARE'],
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'max_execution_time' => ini_get('max_execution_time'),
];

// ✅ Get database size
$db_size_query = $conn->query("
    SELECT table_schema AS database_name,
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
    FROM information_schema.tables 
    WHERE table_schema = DATABASE()
    GROUP BY table_schema
");
$db_size = $db_size_query->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Smart Lab System</title>
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
        
        .settings-group {
            margin-bottom: 30px;
        }
        
        .setting-item {
            display: flex;
            justify-content: between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f1f1f1;
        }
        
        .setting-item:last-child {
            border-bottom: none;
        }
        
        .setting-info {
            flex: 1;
        }
        
        .setting-control {
            flex: 0 0 300px;
        }
        
        .system-info-card {
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .info-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        
        .info-item .value {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .info-item .label {
            font-size: 0.85rem;
            opacity: 0.9;
        }
        
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 30px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .toggle-slider {
            background-color: var(--success);
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(30px);
        }
        
        @media (max-width: 768px) {
            .setting-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .setting-control {
                flex: 1;
                width: 100%;
                margin-top: 10px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
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
                        <a class="nav-link" href="system_settings.php">
                            <i class="fas fa-cog"></i>Settings
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
                            <h2 class="h3 mb-1">System Settings</h2>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                    <li class="breadcrumb-item active">System Settings</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Information -->
            <div class="system-info-card">
                <h5 class="text-white mb-3"><i class="fas fa-info-circle me-2"></i>System Information</h5>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="value"><?= $system_info['php_version'] ?></div>
                        <div class="label">PHP Version</div>
                    </div>
                    <div class="info-item">
                        <div class="value"><?= $db_size['size_mb'] ?? '0' ?> MB</div>
                        <div class="label">Database Size</div>
                    </div>
                    <div class="info-item">
                        <div class="value"><?= $system_info['upload_max_filesize'] ?></div>
                        <div class="label">Max Upload Size</div>
                    </div>
                    <div class="info-item">
                        <div class="value"><?= $system_info['max_execution_time'] ?>s</div>
                        <div class="label">Max Execution Time</div>
                    </div>
                </div>
            </div>

            <?php if ($success_msg): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= $success_msg ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error_msg): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= $error_msg ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST">
                <!-- General Settings -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-building me-2"></i>General Settings</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($settings_groups['general'] ?? [] as $setting): ?>
                        <div class="setting-item">
                            <div class="setting-info">
                                <label class="form-label fw-bold"><?= htmlspecialchars($setting['description']) ?></label>
                                <div class="text-muted small">Key: <?= $setting['setting_key'] ?></div>
                            </div>
                            <div class="setting-control">
                                <input type="text" name="settings[<?= $setting['setting_key'] ?>]" 
                                       value="<?= htmlspecialchars($setting['setting_value']) ?>" 
                                       class="form-control">
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Billing Settings -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-money-bill-wave me-2"></i>Billing Settings</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($settings_groups['billing'] ?? [] as $setting): ?>
                        <div class="setting-item">
                            <div class="setting-info">
                                <label class="form-label fw-bold"><?= htmlspecialchars($setting['description']) ?></label>
                                <div class="text-muted small">Key: <?= $setting['setting_key'] ?></div>
                            </div>
                            <div class="setting-control">
                                <input type="<?= $setting['setting_type'] == 'number' ? 'number' : 'text' ?>" 
                                       name="settings[<?= $setting['setting_key'] ?>]" 
                                       value="<?= htmlspecialchars($setting['setting_value']) ?>" 
                                       class="form-control" step="0.01">
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Notification Settings -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-bell me-2"></i>Notification Settings</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($settings_groups['notifications'] ?? [] as $setting): ?>
                        <div class="setting-item">
                            <div class="setting-info">
                                <label class="form-label fw-bold"><?= htmlspecialchars($setting['description']) ?></label>
                                <div class="text-muted small">Key: <?= $setting['setting_key'] ?></div>
                            </div>
                            <div class="setting-control">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="settings[<?= $setting['setting_key'] ?>]" 
                                           value="1" <?= $setting['setting_value'] == '1' ? 'checked' : '' ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Security Settings -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-shield-alt me-2"></i>Security Settings</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($settings_groups['security'] ?? [] as $setting): ?>
                        <div class="setting-item">
                            <div class="setting-info">
                                <label class="form-label fw-bold"><?= htmlspecialchars($setting['description']) ?></label>
                                <div class="text-muted small">Key: <?= $setting['setting_key'] ?></div>
                            </div>
                            <div class="setting-control">
                                <?php if ($setting['setting_type'] == 'boolean'): ?>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="settings[<?= $setting['setting_key'] ?>]" 
                                           value="1" <?= $setting['setting_value'] == '1' ? 'checked' : '' ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                                <?php else: ?>
                                <input type="<?= $setting['setting_type'] == 'number' ? 'number' : 'text' ?>" 
                                       name="settings[<?= $setting['setting_key'] ?>]" 
                                       value="<?= htmlspecialchars($setting['setting_value']) ?>" 
                                       class="form-control">
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- System Settings -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-cogs me-2"></i>System Settings</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($settings_groups['system'] ?? [] as $setting): ?>
                        <div class="setting-item">
                            <div class="setting-info">
                                <label class="form-label fw-bold"><?= htmlspecialchars($setting['description']) ?></label>
                                <div class="text-muted small">Key: <?= $setting['setting_key'] ?></div>
                            </div>
                            <div class="setting-control">
                                <?php if ($setting['setting_type'] == 'boolean'): ?>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="settings[<?= $setting['setting_key'] ?>]" 
                                           value="1" <?= $setting['setting_value'] == '1' ? 'checked' : '' ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                                <?php else: ?>
                                <input type="<?= $setting['setting_type'] == 'number' ? 'number' : 'text' ?>" 
                                       name="settings[<?= $setting['setting_key'] ?>]" 
                                       value="<?= htmlspecialchars($setting['setting_value']) ?>" 
                                       class="form-control">
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <button type="submit" name="update_settings" class="btn btn-primary w-100">
                                    <i class="fas fa-save me-2"></i>Save All Settings
                                </button>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" name="backup_database" class="btn btn-warning w-100">
                                    <i class="fas fa-database me-2"></i>Backup Database
                                </button>
                            </div>
                            <div class="col-md-4">
                                <div class="input-group">
                                    <input type="email" name="test_email_address" class="form-control" 
                                           placeholder="Email address for test" required>
                                    <button type="submit" name="test_email" class="btn btn-info">
                                        <i class="fas fa-paper-plane me-1"></i>Test Email
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
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

        // Handle toggle switches for checkboxes
        document.querySelectorAll('.toggle-switch input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                this.value = this.checked ? '1' : '0';
            });
        });
    </script>
</body>
</html>