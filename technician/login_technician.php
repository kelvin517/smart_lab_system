<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include '../config/db.php';

// Redirect if already logged in
if (isset($_SESSION['technician_id'])) {
    header("Location: technician_dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Ensure correct role and email match
    $stmt = $conn->prepare("SELECT id, full_name, password FROM staff WHERE email = ? AND role = 'technician'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $full_name, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $_SESSION['technician_id'] = $id;
            $_SESSION['technician_username'] = $full_name;
            header("Location: technician_dashboard.php");
            exit;
        } else {
            $error = "Invalid password. Please try again.";
        }
    } else {
        $error = "Technician account not found.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Login - Smart Lab System</title>
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
            --light: #ecf0f1;
            --dark: #34495e;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .login-container {
            max-width: 420px;
            width: 100%;
            padding: 20px;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.2);
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .login-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 20px 20px;
            animation: float 20s linear infinite;
        }
        
        @keyframes float {
            0% { transform: translate(0, 0) rotate(0deg); }
            100% { transform: translate(-20px, -20px) rotate(360deg); }
        }
        
        .login-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
        }
        
        .login-icon i {
            font-size: 2.5rem;
            color: white;
        }
        
        .login-header h2 {
            margin: 0;
            font-weight: 600;
            font-size: 1.8rem;
        }
        
        .login-header p {
            margin: 8px 0 0;
            opacity: 0.9;
            font-size: 0.95rem;
        }
        
        .login-body {
            padding: 40px;
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 8px;
            color: var(--secondary);
            display: flex;
            align-items: center;
        }
        
        .form-label i {
            margin-right: 10px;
            color: var(--primary);
            width: 20px;
            text-align: center;
        }
        
        .form-control {
            border: 2px solid #e1e5eb;
            border-radius: 12px;
            padding: 14px 20px;
            font-size: 15px;
            transition: all 0.3s;
            background: #f8f9fa;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
            transform: translateY(-2px);
        }
        
        .input-group {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #95a5a6;
            cursor: pointer;
            transition: color 0.3s;
        }
        
        .password-toggle:hover {
            color: var(--primary);
        }
        
        .btn-login {
            background: linear-gradient(135deg, var(--primary), #2980b9);
            border: none;
            border-radius: 12px;
            padding: 15px;
            font-weight: 600;
            font-size: 16px;
            color: white;
            transition: all 0.3s;
            box-shadow: 0 5px 20px rgba(52, 152, 219, 0.3);
            margin-top: 10px;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-login:hover::before {
            left: 100%;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .alert-custom {
            border-radius: 12px;
            padding: 15px 20px;
            border: none;
            background-color: rgba(231, 76, 60, 0.1);
            color: #c0392b;
            font-weight: 500;
            margin-bottom: 25px;
            border-left: 4px solid var(--danger);
        }
        
        .alert-custom i {
            margin-right: 10px;
        }
        
        .forgot-password {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .forgot-password a {
            color: var(--primary);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.2s;
            display: inline-flex;
            align-items: center;
        }
        
        .forgot-password a:hover {
            color: var(--secondary);
            text-decoration: underline;
        }
        
        .forgot-password i {
            margin-right: 5px;
        }
        
        .back-home {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-home a {
            color: var(--secondary);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: color 0.2s;
            font-size: 14px;
        }
        
        .back-home a:hover {
            color: var(--primary);
        }
        
        .back-home i {
            margin-right: 5px;
        }
        
        .features-list {
            background: rgba(52, 152, 219, 0.05);
            border-radius: 12px;
            padding: 20px;
            margin-top: 25px;
        }
        
        .features-list h6 {
            color: var(--secondary);
            margin-bottom: 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .features-list h6 i {
            margin-right: 8px;
            color: var(--primary);
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            font-size: 14px;
            color: #555;
        }
        
        .feature-item i {
            margin-right: 10px;
            color: var(--success);
            font-size: 12px;
        }
        
        @media (max-width: 576px) {
            .login-container {
                padding: 15px;
            }
            
            .login-body {
                padding: 30px 25px;
            }
            
            .login-header {
                padding: 25px 20px;
            }
        }
        
        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }
        
        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float-shapes 20s infinite linear;
        }
        
        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .shape:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 70%;
            left: 80%;
            animation-delay: -5s;
        }
        
        .shape:nth-child(3) {
            width: 60px;
            height: 60px;
            top: 50%;
            left: 5%;
            animation-delay: -10s;
        }
        
        @keyframes float-shapes {
            0% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(30px, -50px) rotate(120deg); }
            66% { transform: translate(-20px, 20px) rotate(240deg); }
            100% { transform: translate(0, 0) rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Floating Background Shapes -->
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
    
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-icon">
                    <i class="fas fa-vial"></i>
                </div>
                <h2>Welcome Back</h2>
                <p>Lab Technician Portal - Smart Lab System</p>
            </div>
            
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-custom">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="loginForm">
                    <div class="form-group">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope"></i> Email Address
                        </label>
                        <input type="email" name="email" id="email" class="form-control" placeholder="Enter your email address" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i> Password
                        </label>
                        <div class="input-group">
                            <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
                            <button type="button" class="password-toggle" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-login">
                            <i class="fas fa-sign-in-alt me-2"></i> Login to Dashboard
                        </button>
                    </div>
                </form>

                <div class="forgot-password">
                    <a href="#">
                        <i class="fas fa-key"></i> Forgot Password?
                    </a>
                </div>
                
                <div class="back-home">
                    <a href="../index.php">
                        <i class="fas fa-arrow-left"></i> Back to Homepage
                    </a>
                </div>
                
                <div class="features-list">
                    <h6><i class="fas fa-star"></i> Technician Features</h6>
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i> Manage laboratory tests
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i> Process patient samples
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i> Update test results
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i> View patient history
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Form validation and enhancements
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                // Add shake animation to empty fields
                const inputs = document.querySelectorAll('input[required]');
                inputs.forEach(input => {
                    if (!input.value) {
                        input.style.borderColor = 'var(--danger)';
                        input.classList.add('animate__animated', 'animate__headShake');
                        setTimeout(() => {
                            input.classList.remove('animate__animated', 'animate__headShake');
                        }, 1000);
                    }
                });
            }
        });
        
        // Add focus effects
        const inputs = document.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                if (!this.value) {
                    this.parentElement.classList.remove('focused');
                }
            });
        });
        
        // Add loading state to submit button
        const submitButton = document.querySelector('.btn-login');
        document.getElementById('loginForm').addEventListener('submit', function() {
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Signing In...';
            submitButton.disabled = true;
        });
    </script>
</body>
</html>