<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include '../config/db.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Fetch user
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        session_destroy();
        header('Location: login.php');
        exit();
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Handle profile picture upload first
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $file_type = $_FILES['profile_picture']['type'];
    $file_size = $_FILES['profile_picture']['size'];

    if (!in_array($file_type, $allowed_types)) {
        $error = "Only JPG, PNG, and GIF images are allowed.";
    } elseif ($file_size > 2 * 1024 * 1024) {
        $error = "Image size must be less than 2MB.";
    } else {
        $upload_dir = 'uploads/profile_pictures/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        $filename = 'profile_' . $user_id . '_' . time() . '.' . $ext;
        $path = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $path)) {

            // Delete old image
            if (!empty($user['profile_picture']) && file_exists($user['profile_picture'])) {
                unlink($user['profile_picture']);
            }

            $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
            $stmt->execute([$path, $user_id]);
            $user['profile_picture'] = $path;

            $success = "Profile picture updated successfully!";
        } else {
            $error = "Failed to upload image.";
        }
    }
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_FILES['profile_picture'])) {
    $first = trim($_POST['first_name']);
    $last = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');
    $bio = trim($_POST['bio'] ?? '');

    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($first) || empty($last) || empty($email)) {
        $error = "First name, last name, and email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        try {
            // Check duplicate email
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);

            if ($stmt->fetch()) {
                $error = "Email already in use by another account.";
            } else {
                // Password change needed?
                if (!empty($current_password) || !empty($new_password) || !empty($confirm)) {

                    if (empty($current_password)) {
                        $error = "Current password is required.";
                    } elseif (!password_verify($current_password, $user['password'])) {
                        $error = "Current password is incorrect.";
                    } elseif (empty($new_password)) {
                        $error = "New password is required.";
                    } elseif (strlen($new_password) < 6) {
                        $error = "New password must be at least 6 characters.";
                    } elseif ($new_password !== $confirm) {
                        $error = "New passwords do not match.";
                    } else {
                        // Update with password
                        $hashed = password_hash($new_password, PASSWORD_DEFAULT);

                        $stmt = $pdo->prepare("
                            UPDATE users 
                            SET first_name=?, last_name=?, email=?, phone=?, bio=?, password=?, updated_at=NOW()
                            WHERE id=?
                        ");
                        $stmt->execute([$first, $last, $email, $phone, $bio, $hashed, $user_id]);

                        $success = "Profile and password updated!";
                    }

                } else {
                    // Update without password
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET first_name=?, last_name=?, email=?, phone=?, bio=?, updated_at=NOW()
                        WHERE id=?
                    ");
                    $stmt->execute([$first, $last, $email, $phone, $bio, $user_id]);

                    $success = "Profile updated successfully!";
                }

                // Refresh user in session
                if (empty($error)) {
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);

                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_name'] = $user['first_name'] . " " . $user['last_name'];
                }
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>My Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        .profile-picture {
            width: 150px; height: 150px;
            border-radius: 50%; border: 3px solid #007bff;
            object-fit: cover;
        }
    </style>
</head>

<body>
<?php include 'includes/header.php'; ?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">

            <div class="card mb-4">
                <div class="card-header"><h3>My Profile</h3></div>
                <div class="card-body">

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>

                    <!-- Profile Picture -->
                    <div class="text-center mb-4">
                        <img src="<?= !empty($user['profile_picture']) ? htmlspecialchars($user['profile_picture']) : 'assets/images/default-avatar.png' ?>"
                             class="profile-picture mb-3">

                        <form method="post" enctype="multipart/form-data">
                            <input type="file" name="profile_picture" accept="image/*"
                                   class="form-control mb-2" onchange="this.form.submit()">
                        </form>
                    </div>

                    <!-- Main Profile Update Form -->
                    <form method="post">

                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">First Name *</label>
                                <input type="text" name="first_name" class="form-control"
                                       value="<?= htmlspecialchars($user['first_name']) ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Last Name *</label>
                                <input type="text" name="last_name" class="form-control"
                                       value="<?= htmlspecialchars($user['last_name']) ?>" required>
                            </div>
                        </div>

                        <label class="form-label mt-3">Email *</label>
                        <input type="email" name="email" class="form-control"
                               value="<?= htmlspecialchars($user['email']) ?>" required>

                        <label class="form-label mt-3">Phone</label>
                        <input type="text" name="phone" class="form-control"
                               value="<?= htmlspecialchars($user['phone'] ?? '') ?>">

                        <label class="form-label mt-3">Bio</label>
                        <textarea name="bio" class="form-control" rows="3"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>

                        <hr class="my-4">

                        <h5>Change Password</h5>
                        <p class="text-muted small">Leave blank to keep your current password.</p>

                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control">

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control">
                            </div>
                        </div>

                        <div class="mt-4 text-end">
                            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Account Info -->
            <div class="card">
                <div class="card-header"><h5>Account Information</h5></div>
                <div class="card-body">
                    <p><strong>Member since:</strong> <?= date('F j, Y', strtotime($user['created_at'])) ?></p>
                    <p><strong>Last updated:</strong> <?= date('F j, Y g:i A', strtotime($user['updated_at'])) ?></p>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
