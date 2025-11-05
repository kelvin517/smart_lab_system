<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit;
}

$patient_id = $_SESSION['patient_id'];

// Fetch patient profile
$query = "SELECT full_name, dob, gender, email, phone, address, blood_group, avatar, registered_on 
          FROM patients WHERE patient_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();

if (!$patient) {
    die("Patient not found.");
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $dob = $_POST['dob'] ?: null;
    $gender = $_POST['gender'];
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $blood_group = trim($_POST['blood_group']);

    $avatar = $patient['avatar']; // Default

    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        $target_file = $target_dir . basename($_FILES['avatar']['name']);
        $ext = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($ext, $allowed)) {
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_file)) {
                $avatar = $target_file;
            }
        }
    }

    $update = "UPDATE patients 
               SET full_name=?, dob=?, gender=?, email=?, phone=?, address=?, blood_group=?, avatar=? 
               WHERE patient_id=?";
    $stmt = $conn->prepare($update);
    $stmt->bind_param("ssssssssi", $full_name, $dob, $gender, $email, $phone, $address, $blood_group, $avatar, $patient_id);

    if ($stmt->execute()) {
        $success = "Profile updated successfully.";
        $patient = [
            'full_name' => $full_name,
            'dob' => $dob,
            'gender' => $gender,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'blood_group' => $blood_group,
            'avatar' => $avatar,
            'registered_on' => $patient['registered_on']
        ];
    } else {
        $error = "Failed to update profile.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>My Profile - Smart Lab Patient Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
  <!-- NiceAdmin CSS -->
  <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="../assets/vendor/quill/quill.snow.css" rel="stylesheet">
  <link href="../assets/vendor/quill/quill.bubble.css" rel="stylesheet">
  <link href="../assets/vendor/remixicon/remixicon.css" rel="stylesheet">
  <link href="../assets/vendor/simple-datatables/style.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
</head>

<body>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<main id="main" class="main">

  <div class="pagetitle">
    <h1>My Profile</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item active">Profile</li>
      </ol>
    </nav>
  </div><!-- End Page Title -->

  <section class="section profile">
    <div class="row">

      <div class="col-xl-4">
        <div class="card">
          <div class="card-body profile-card pt-4 d-flex flex-column align-items-center">
            <img src="<?= htmlspecialchars($patient['avatar'] ?: '../assets/img/profile-img.jpg') ?>" alt="Profile" class="rounded-circle" width="120" height="120">
            <h2><?= htmlspecialchars($patient['full_name']) ?></h2>
            <h3>Patient</h3>
            <p><small>Member since <?= date("d M Y", strtotime($patient['registered_on'])) ?></small></p>
          </div>
        </div>
      </div>

      <div class="col-xl-8">
        <div class="card">
          <div class="card-body pt-3">
            <h5 class="card-title">Edit Profile</h5>

            <?php if (!empty($success)): ?>
              <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>
            <?php elseif (!empty($error)): ?>
              <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
              <div class="row mb-3">
                <label class="col-md-4 col-lg-3 col-form-label">Full Name</label>
                <div class="col-md-8 col-lg-9">
                  <input name="full_name" type="text" class="form-control" value="<?= htmlspecialchars($patient['full_name'] ?? '') ?>" required>
                </div>
              </div>

              <div class="row mb-3">
                <label class="col-md-4 col-lg-3 col-form-label">Date of Birth</label>
                <div class="col-md-8 col-lg-9">
                  <input name="dob" type="date" class="form-control" value="<?= htmlspecialchars($patient['dob'] ?? '') ?>">
                </div>
              </div>

              <div class="row mb-3">
                <label class="col-md-4 col-lg-3 col-form-label">Gender</label>
                <div class="col-md-8 col-lg-9">
                  <select name="gender" class="form-control" required>
                    <option value="">-- Select Gender --</option>
                    <option value="Male" <?= (($patient['gender'] ?? '') == 'Male') ? 'selected' : '' ?>>Male</option>
                    <option value="Female" <?= (($patient['gender'] ?? '') == 'Female') ? 'selected' : '' ?>>Female</option>
                    <option value="Other" <?= (($patient['gender'] ?? '') == 'Other') ? 'selected' : '' ?>>Other</option>
                  </select>
                </div>
              </div>

              <div class="row mb-3">
                <label class="col-md-4 col-lg-3 col-form-label">Email</label>
                <div class="col-md-8 col-lg-9">
                  <input name="email" type="email" class="form-control" value="<?= htmlspecialchars($patient['email'] ?? '') ?>" required>
                </div>
              </div>

              <div class="row mb-3">
                <label class="col-md-4 col-lg-3 col-form-label">Phone</label>
                <div class="col-md-8 col-lg-9">
                  <input name="phone" type="text" class="form-control" value="<?= htmlspecialchars($patient['phone'] ?? '') ?>">
                </div>
              </div>

              <div class="row mb-3">
                <label class="col-md-4 col-lg-3 col-form-label">Address</label>
                <div class="col-md-8 col-lg-9">
                  <textarea name="address" class="form-control"><?= htmlspecialchars($patient['address'] ?? '') ?></textarea>
                </div>
              </div>

              <div class="row mb-3">
                <label class="col-md-4 col-lg-3 col-form-label">Blood Group</label>
                <div class="col-md-8 col-lg-9">
                  <input name="blood_group" type="text" class="form-control" value="<?= htmlspecialchars($patient['blood_group'] ?? '') ?>">
                </div>
              </div>

              <div class="row mb-3">
                <label class="col-md-4 col-lg-3 col-form-label">Profile Picture</label>
                <div class="col-md-8 col-lg-9">
                  <input class="form-control" type="file" name="avatar" accept="image/*">
                </div>
              </div>

              <div class="text-center">
                <button type="submit" class="btn btn-primary">Update Profile</button>
              </div>
            </form>
          </div>
        </div>
      </div>

    </div>
  </section>

</main><!-- End #main -->

<?php include 'includes/footer.php'; ?>

<!-- Vendor JS Files -->
<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/vendor/simple-datatables/simple-datatables.js"></script>
<script src="../assets/vendor/tinymce/tinymce.min.js"></script>
<script src="../assets/js/main.js"></script>

</body>
</html>
