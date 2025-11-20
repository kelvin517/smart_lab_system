<?php
// ... PHP code remains the same ...
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #8b5cf6;
            --secondary-color: #06d6a0;
            --accent-color: #f59e0b;
            --dark-color: #1e293b;
            --light-color: #f8fafc;
            --gray-color: #64748b;
            --border-color: #e2e8f0;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --shadow-lg: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-secondary: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --gradient-success: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: var(--gradient-primary);
            min-height: 100vh;
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
            line-height: 1.6;
            color: var(--dark-color);
            padding: 20px 0;
        }

        .settings-container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .settings-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            position: relative;
        }

        .settings-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
            z-index: 1;
        }
        
        /* Sidebar Styling */
        .sidebar {
            background: linear-gradient(180deg, var(--light-color) 0%, #f1f5f9 100%);
            border-right: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }

        .sidebar::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 1px;
            height: 100%;
            background: linear-gradient(180deg, transparent, var(--border-color), transparent);
        }

        .profile-section {
            padding: 2rem 1.5rem;
            text-align: center;
            border-bottom: 1px solid var(--border-color);
            background: white;
            margin: -1px -1px 0 -1px;
        }
        
        .profile-picture {
            width: 140px;
            height: 140px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid white;
            box-shadow: var(--shadow);
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            position: relative;
        }

        .profile-picture:hover {
            transform: scale(1.05);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }

        .profile-picture::after {
            content: '';
            position: absolute;
            top: -4px;
            left: -4px;
            right: -4px;
            bottom: -4px;
            border-radius: 50%;
            background: var(--gradient-primary);
            z-index: -1;
        }
        
        .nav-pills {
            padding: 1.5rem;
        }
        
        .nav-pills .nav-link {
            color: var(--gray-color);
            border-radius: 16px;
            margin: 8px 0;
            padding: 16px 20px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid transparent;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }
        
        .nav-pills .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: var(--gradient-primary);
            transition: left 0.3s ease;
            z-index: -1;
        }
        
        .nav-pills .nav-link.active {
            background: white;
            color: var(--primary-color);
            border-color: var(--primary-color);
            box-shadow: var(--shadow);
            transform: translateX(8px);
        }

        .nav-pills .nav-link.active::before {
            left: 0;
        }
        
        .nav-pills .nav-link:hover:not(.active) {
            background: rgba(99, 102, 241, 0.05);
            color: var(--primary-color);
            border-color: rgba(99, 102, 241, 0.2);
            transform: translateX(4px);
        }
        
        .nav-pills .nav-link i {
            width: 20px;
            text-align: center;
            margin-right: 12px;
            font-size: 1.1em;
        }
        
        /* Stats Cards */
        .stats-section {
            padding: 1.5rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--gradient-primary);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        
        .stat-card i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Content Area */
        .content-area {
            padding: 2rem;
            background: white;
        }
        
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid var(--border-color);
        }
        
        .section-title {
            color: var(--dark-color);
            font-weight: 700;
            font-size: 1.75rem;
            margin-bottom: 0;
            position: relative;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -12px;
            left: 0;
            width: 60px;
            height: 4px;
            background: var(--gradient-primary);
            border-radius: 2px;
        }
        
        /* Form Styling */
        .form-control {
            border-radius: 16px;
            border: 2px solid var(--border-color);
            padding: 14px 20px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--light-color);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            background: white;
            transform: translateY(-2px);
        }
        
        .form-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        
        /* Buttons */
        .btn {
            border-radius: 16px;
            padding: 14px 32px;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn-primary {
            background: var(--gradient-primary);
            border: none;
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
        }
        
        .btn-outline-primary {
            border-color: var(--primary-color);
            color: var(--primary-color);
            background: transparent;
        }
        
        .btn-outline-primary:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }
        
        /* Upload Area */
        .upload-area {
            border: 3px dashed var(--border-color);
            border-radius: 20px;
            padding: 3rem 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            background: var(--light-color);
            position: relative;
        }
        
        .upload-area:hover {
            border-color: var(--primary-color);
            background: rgba(99, 102, 241, 0.02);
            transform: scale(1.02);
        }
        
        .upload-area.dragover {
            border-color: var(--primary-color);
            background: rgba(99, 102, 241, 0.05);
            transform: scale(1.02);
        }
        
        .upload-area i {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Preference Items */
        .preference-item {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
        }
        
        .preference-item:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }
        
        .preference-item h6 {
            color: var(--dark-color);
            font-weight: 700;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }
        
        /* Switch Toggle */
        .form-check-input {
            width: 3rem;
            height: 1.5rem;
            border-radius: 2rem;
            border: 2px solid var(--border-color);
            background-color: var(--border-color);
        }
        
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .form-check-input:focus {
            box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.25);
        }
        
        /* Alert Styling */
        .alert {
            border-radius: 16px;
            border: none;
            padding: 1.25rem 1.5rem;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }
        
        .alert::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: currentColor;
            opacity: 0.3;
        }
        
        /* Tab Content */
        .tab-content {
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Progress Bar for File Upload */
        .upload-progress {
            height: 6px;
            background: var(--border-color);
            border-radius: 3px;
            overflow: hidden;
            margin-top: 10px;
            display: none;
        }
        
        .upload-progress-bar {
            height: 100%;
            background: var(--gradient-primary);
            border-radius: 3px;
            transition: width 0.3s ease;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .settings-card {
                border-radius: 16px;
                margin: 10px;
            }
            
            .sidebar {
                border-right: none;
                border-bottom: 1px solid var(--border-color);
            }
            
            .profile-picture {
                width: 100px;
                height: 100px;
            }
            
            .header-section {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
            
            .btn {
                padding: 12px 24px;
            }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }

        /* Loading Animation */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
            margin-right: 8px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Badge Styling */
        .badge-premium {
            background: var(--gradient-secondary);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="settings-container">
    <div class="row g-0">
        <div class="col-12">
            <div class="settings-card">
                <div class="row g-0">
                    <!-- Enhanced Sidebar -->
                    <div class="col-md-3 sidebar">
                        <div class="profile-section">
                            <img src="<?php echo !empty($result['profile_picture']) ? htmlspecialchars($result['profile_picture']) : 'assets/images/default-avatar.png'; ?>" 
                                 alt="Profile Picture" class="profile-picture">
                            <h5 class="mt-3 mb-1 fw-bold"><?php echo htmlspecialchars($result['full_name']); ?></h5>
                            <p class="text-muted mb-2"><?php echo htmlspecialchars($result['role'] ?? 'Administrator'); ?></p>
                            <span class="badge-premium">
                                <i class="fas fa-crown me-1"></i>Premium Admin
                            </span>
                        </div>
                        
                        <nav class="nav nav-pills flex-column">
                            <a class="nav-link active" href="#profile" data-bs-toggle="tab">
                                <i class="fas fa-user-circle"></i>Profile Settings
                            </a>
                            <a class="nav-link" href="#security" data-bs-toggle="tab">
                                <i class="fas fa-shield-alt"></i>Security & Privacy
                            </a>
                            <a class="nav-link" href="#preferences" data-bs-toggle="tab">
                                <i class="fas fa-sliders-h"></i>Preferences
                            </a>
                            <a class="nav-link" href="#appearance" data-bs-toggle="tab">
                                <i class="fas fa-palette"></i>Appearance
                            </a>
                            <a class="nav-link" href="#notifications" data-bs-toggle="tab">
                                <i class="fas fa-bell"></i>Notifications
                            </a>
                        </nav>
                        
                        <!-- Enhanced Stats -->
                        <div class="stats-section">
                            <div class="stat-card">
                                <i class="fas fa-calendar-check"></i>
                                <h6 class="fw-bold">Member Since</h6>
                                <p class="mb-0 text-dark"><?php echo date('M j, Y', strtotime($result['created_at'])); ?></p>
                            </div>
                            <?php if ($result['last_login']): ?>
                            <div class="stat-card">
                                <i class="fas fa-clock"></i>
                                <h6 class="fw-bold">Last Active</h6>
                                <p class="mb-0 text-dark"><?php echo date('M j, Y g:i A', strtotime($result['last_login'])); ?></p>
                            </div>
                            <?php endif; ?>
                            <div class="stat-card">
                                <i class="fas fa-user-shield"></i>
                                <h6 class="fw-bold">Account Status</h6>
                                <p class="mb-0 text-success">Verified & Active</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Enhanced Content Area -->
                    <div class="col-md-9">
                        <div class="content-area">
                            <div class="header-section">
                                <div>
                                    <h3 class="section-title">Account Settings</h3>
                                    <p class="text-muted mb-0">Manage your account preferences and security settings</p>
                                </div>
                                <a href="dashboard.php" class="btn btn-outline-primary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                </a>
                            </div>
                            
                            <?php if ($message): ?>
                                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2 fa-lg"></i>
                                        <div>
                                            <strong><?php echo ucfirst($message_type); ?>!</strong> <?= $message ?>
                                        </div>
                                    </div>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            
                            <div class="tab-content">
                                <!-- Enhanced Profile Tab -->
                                <div class="tab-pane fade show active" id="profile">
                                    <h4 class="fw-bold mb-4">Profile Information</h4>
                                    
                                    <form method="POST" enctype="multipart/form-data" class="row g-4">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Full Name</label>
                                                <input type="text" class="form-control" name="full_name" 
                                                       value="<?= htmlspecialchars($result['full_name']) ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Phone Number</label>
                                                <input type="text" class="form-control" name="phone" 
                                                       value="<?= htmlspecialchars($result['phone']) ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="col-12">
                                            <div class="mb-3">
                                                <label class="form-label">Email Address</label>
                                                <input type="email" class="form-control" name="email" 
                                                       value="<?= htmlspecialchars($result['email']) ?>" required>
                                            </div>
                                        </div>
                                        
                                        <div class="col-12">
                                            <div class="mb-4">
                                                <label class="form-label">Profile Picture</label>
                                                <div class="upload-area" id="uploadArea" onclick="document.getElementById('profile_picture').click()">
                                                    <i class="fas fa-cloud-upload-alt"></i>
                                                    <h5 class="mt-3">Drag & drop or click to upload</h5>
                                                    <p class="text-muted mb-2">Supports JPG, PNG, GIF, WebP - Max 2MB</p>
                                                    <small class="text-muted">Recommended: 500x500 pixels</small>
                                                    <input type="file" id="profile_picture" name="profile_picture" 
                                                           accept="image/*" style="display: none;" 
                                                           onchange="handleFileSelect(this)">
                                                </div>
                                                <div class="upload-progress" id="uploadProgress">
                                                    <div class="upload-progress-bar" id="uploadProgressBar"></div>
                                                </div>
                                                <div class="d-flex gap-2 mt-3">
                                                    <button type="submit" name="update_profile_picture" id="upload-btn" 
                                                            class="btn btn-success" style="display: none;">
                                                        <i class="fas fa-upload me-2"></i>Upload Photo
                                                    </button>
                                                    <?php if (!empty($result['profile_picture'])): ?>
                                                        <button type="submit" name="remove_profile_picture" 
                                                                class="btn btn-danger" 
                                                                onclick="return confirm('Are you sure you want to remove your profile picture?')">
                                                            <i class="fas fa-trash me-2"></i>Remove Photo
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-12">
                                            <button type="submit" name="update_profile" class="btn btn-primary px-5">
                                                <i class="fas fa-save me-2"></i>Save Changes
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                
                                <!-- Other tabs remain similar but with enhanced styling -->
                                <!-- Security Tab -->
                                <div class="tab-pane fade" id="security">
                                    <h4 class="fw-bold mb-4">Security Settings</h4>
                                    <!-- Security content with enhanced styling -->
                                </div>
                                
                                <!-- Preferences Tab -->
                                <div class="tab-pane fade" id="preferences">
                                    <h4 class="fw-bold mb-4">System Preferences</h4>
                                    <!-- Preferences content with enhanced styling -->
                                </div>
                                
                                <!-- Appearance Tab -->
                                <div class="tab-pane fade" id="appearance">
                                    <h4 class="fw-bold mb-4">Appearance Settings</h4>
                                    <!-- Appearance content with enhanced styling -->
                                </div>

                                <!-- New Notifications Tab -->
                                <div class="tab-pane fade" id="notifications">
                                    <h4 class="fw-bold mb-4">Notification Preferences</h4>
                                    <div class="preference-item">
                                        <h6>Email Notifications</h6>
                                        <p class="text-muted mb-3">Choose which email notifications you want to receive</p>
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="securityAlerts" checked>
                                            <label class="form-check-label" for="securityAlerts">
                                                Security alerts
                                            </label>
                                        </div>
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="productUpdates" checked>
                                            <label class="form-check-label" for="productUpdates">
                                                Product updates
                                            </label>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="marketingEmails">
                                            <label class="form-check-label" for="marketingEmails">
                                                Marketing emails
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
    // Enhanced JavaScript with animations and interactions
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-dismiss alerts with animation
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            setTimeout(function() {
                if (alert) {
                    alert.style.transform = 'translateX(100%)';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }
            }, 5000);
        });
        
        // Tab activation with smooth scrolling
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        if (tab) {
            const triggerEl = document.querySelector(`[href="#${tab}"]`);
            if (triggerEl) {
                bootstrap.Tab.getOrCreateInstance(triggerEl).show();
            }
        }

        // Add active state to nav items with animation
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                navLinks.forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            });
        });
    });

    // Enhanced file upload handling
    function handleFileSelect(input) {
        if (input.files.length > 0) {
            const file = input.files[0];
            const uploadArea = document.getElementById('uploadArea');
            const uploadBtn = document.getElementById('upload-btn');
            const progressBar = document.getElementById('uploadProgressBar');
            const progressContainer = document.getElementById('uploadProgress');
            
            // Update upload area text
            uploadArea.querySelector('h5').textContent = file.name;
            uploadArea.querySelector('p').textContent = `Size: ${(file.size / 1024 / 1024).toFixed(2)} MB`;
            
            // Show upload button
            uploadBtn.style.display = 'inline-block';
            
            // Simulate upload progress
            progressContainer.style.display = 'block';
            let progress = 0;
            const interval = setInterval(() => {
                progress += Math.random() * 10;
                if (progress >= 100) {
                    progress = 100;
                    clearInterval(interval);
                }
                progressBar.style.width = progress + '%';
            }, 200);
        }
    }

    // Drag and drop functionality
    const uploadArea = document.getElementById('uploadArea');
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        uploadArea.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, unhighlight, false);
    });

    function highlight() {
        uploadArea.classList.add('dragover');
    }

    function unhighlight() {
        uploadArea.classList.remove('dragover');
    }

    uploadArea.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        document.getElementById('profile_picture').files = files;
        handleFileSelect(document.getElementById('profile_picture'));
    }

    // Add loading states to buttons
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<span class="loading-spinner"></span>Processing...';
                submitBtn.disabled = true;
            }
        });
    });
</script>
</body>
</html>