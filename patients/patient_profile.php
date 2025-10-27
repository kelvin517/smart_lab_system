<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit;
}

$patient_id = $_SESSION['patient_id'];
$success = $error = '';

// Fetch patient data
$stmt = $conn->prepare("SELECT full_name, email, phone, sex, profile_image FROM patients WHERE id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$stmt->bind_result($full_name, $email, $phone, $gender, $profile_image);
$stmt->fetch();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_name = trim($_POST['full_name']);
    $new_email = trim($_POST['email']);
    $new_phone = trim($_POST['phone']);
    $new_gender = trim($_POST['gender']);

    // Profile image upload
    if (!empty($_FILES['profile_image']['name'])) {
        $target_dir = "../uploads/";
        $file_name = basename($_FILES["profile_image"]["name"]);
        $target_file = $target_dir . time() . "_" . $file_name;
        move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file);
        $profile_image = $target_file;
    }

    $update = $conn->prepare("UPDATE patients SET full_name=?, email=?, phone=?, gender=?, profile_image=? WHERE id=?");
    $update->bind_param("sssssi", $new_name, $new_email, $new_phone, $new_gender, $profile_image, $patient_id);
    if ($update->execute()) {
        $success = "Profile updated successfully.";
    } else {
        $error = "Update failed.";
    }
    $update->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Patient Profile - Smart Laboratory</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card shadow">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
          <h5 class="mb-0">My Profile</h5>
          <a href="dashboard.php" class="btn btn-light btn-sm">Back to Dashboard</a>
        </div>
        <div class="card-body">
          <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
          <?php elseif ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
          <?php endif; ?>

          <form method="POST" enctype="multipart/form-data">
            <div class="mb-3 text-center">
              <?php if (!empty($profile_image)): ?>
                <img src="<?= $profile_image ?>" width="120" height="120" class="rounded-circle mb-2" alt="Profile">
              <?php else: ?>
                <i class="bi bi-person-circle" style="font-size: 4rem;"></i>
              <?php endif; ?>
              <div>
                <input type="file" name="profile_image" class="form-control mt-2" accept="image/*">
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label">Full Name</label>
              <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($full_name) ?>" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Phone</label>
              <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($phone) ?>">
            </div>

            <div class="mb-3">
              <label class="form-label">Gender</label>
              <select name="gender" class="form-select">
                <option value="Male" <?= ($gender === 'Male') ? 'selected' : '' ?>>Male</option>
                <option value="Female" <?= ($gender === 'Female') ? 'selected' : '' ?>>Female</option>
                <option value="Other" <?= ($gender === 'Other') ? 'selected' : '' ?>>Other</option>
              </select>
            </div>

            <div class="d-grid">
              <button class="btn btn-primary">Update Profile</button>
            </div>
          </form>

          <div class="mt-4 text-center">
            <a href="change_password.php" class="btn btn-outline-secondary btn-sm">Change Password</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
