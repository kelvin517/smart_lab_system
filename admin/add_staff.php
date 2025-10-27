<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include '../config/db.php';

// Redirect if admin not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../admin_login.php");
    exit();
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Handle image upload
    $profile_image = NULL;
    if (!empty($_FILES['profile_image']['name'])) {
        $targetDir = "../uploads/staff/";
        $fileName = time() . "_" . basename($_FILES["profile_image"]["name"]);
        $targetFile = $targetDir . $fileName;
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        // Validate image type
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($imageFileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $targetFile)) {
                $profile_image = "uploads/staff/" . $fileName;
            } else {
                $message = "<div class='alert alert-warning text-center mt-3'>⚠️ Image upload failed, using default avatar.</div>";
            }
        } else {
            $message = "<div class='alert alert-danger text-center mt-3'>❌ Invalid image type. Allowed: JPG, PNG, GIF.</div>";
        }
    }

    // Insert into DB
    $stmt = $conn->prepare("INSERT INTO staff (full_name, email, profile_image, role, password) VALUES (?, ?, ?, ?, ?)");
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }

    $stmt->bind_param("sssss", $name, $email, $profile_image, $role, $password);

    if ($stmt->execute()) {
        $message = "<div class='alert alert-success text-center mt-3'>✅ Staff member added successfully!</div>";
        header("Refresh:2; url=view_staff.php");
    } else {
        $message = "<div class='alert alert-danger text-center mt-3'>❌ Failed to add staff. Error: " . htmlspecialchars($stmt->error) . "</div>";
    }

    $stmt->close();
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<style>
    .profile-upload {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        background-color: #f1f1f1;
        position: relative;
        overflow: hidden;
        margin: 0 auto;
        border: 3px dashed #ccc;
    }
    .profile-upload img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .upload-btn {
        position: absolute;
        bottom: 0;
        width: 100%;
        text-align: center;
        background: rgba(0,0,0,0.5);
        color: white;
        padding: 8px 0;
        cursor: pointer;
        font-size: 14px;
    }
    .form-control, .form-select {
        border-radius: 8px;
        padding: 10px;
    }
    .card-custom {
        border-radius: 15px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .toggle-switch {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 15px;
    }
</style>

<main id="main" class="main">

  <div class="pagetitle">
    <h1>Add New Staff</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
        <li class="breadcrumb-item">Staff</li>
        <li class="breadcrumb-item active">Add New Staff</li>
      </ol>
    </nav>
  </div>

  <section class="section">
    <div class="container mt-4">
      <div class="card card-custom p-5">

        <div class="text-center mb-4">
          <div class="profile-upload">
            <img id="previewImage" src="https://via.placeholder.com/150" alt="Profile Preview">
            <label class="upload-btn">
              <input type="file" name="profile_image" id="profileImage" accept="image/*" style="display:none;" form="staffForm">
              Change Picture
            </label>
          </div>
          <h4 class="mt-3">Add New Staff Member</h4>
        </div>

        <?php if ($message) echo $message; ?>

        <form id="staffForm" method="POST" enctype="multipart/form-data" class="row g-3 px-5">

          <div class="col-md-6">
            <label class="form-label fw-semibold">Full Name</label>
            <input type="text" name="full_name" class="form-control" placeholder="John Doe" required>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">Email</label>
            <input type="email" name="email" class="form-control" placeholder="example@domain.com" required>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">Role</label>
            <select name="role" class="form-select" required>
              <option value="">-- Select Role --</option>
              <option value="doctor">Doctor</option>
              <option value="technician">Technician</option>
              <option value="receptionist">Receptionist</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">Password</label>
            <input type="password" name="password" class="form-control" placeholder="Enter password" required>
          </div>

          <div class="toggle-switch">
            <label class="form-label fw-semibold mb-0">Email Notification</label>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="emailNotification" checked>
            </div>
          </div>

          <div class="col-12 text-end mt-4">
            <button type="submit" class="btn btn-primary px-4 py-2">
              <i class="bi bi-person-plus"></i> Create Profile
            </button>
            <a href="view_staff.php" class="btn btn-secondary px-4 py-2">
              <i class="bi bi-arrow-left-circle"></i> Back
            </a>
          </div>
        </form>

      </div>
    </div>
  </section>

</main>

<script>
document.getElementById("profileImage").addEventListener("change", function(event) {
  const [file] = event.target.files;
  if (file) {
    document.getElementById("previewImage").src = URL.createObjectURL(file);
  }
});
</script>