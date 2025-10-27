<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['doctor_id'])) {
    header("Location: doctor_login.php");
    exit;
}

$doctor_id = $_SESSION['doctor_id'];
$success = $error = '';

// Fetch doctor info
$stmt = $conn->prepare("SELECT full_name, email, phone, specialization FROM doctors WHERE id = ?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$stmt->bind_result($full_name, $email, $phone, $specialization);
$stmt->fetch();
$stmt->close();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updated_name = trim($_POST['full_name']);
    $updated_phone = trim($_POST['phone']);
    $updated_spec = trim($_POST['specialization']);

    if (!empty($updated_name) && !empty($updated_phone)) {
        $update_stmt = $conn->prepare("UPDATE doctors SET full_name = ?, phone = ?, specialization = ? WHERE id = ?");
        $update_stmt->bind_param("sssi", $updated_name, $updated_phone, $updated_spec, $doctor_id);
        if ($update_stmt->execute()) {
            $success = "Profile updated successfully.";
            $full_name = $updated_name;
            $phone = $updated_phone;
            $specialization = $updated_spec;
            $_SESSION['doctor_name'] = $updated_name;
        } else {
            $error = "Failed to update profile.";
        }
        $update_stmt->close();
    } else {
        $error = "Name and phone are required.";
    }
}
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<main id="main" class="main">

  <div class="pagetitle">
    <h1>My Profile</h1>
  </div>

  <section class="section profile">
    <div class="row">
      <div class="col-xl-4">
        <div class="card">
          <div class="card-body profile-card pt-4 d-flex flex-column align-items-center">
            <img src="../assets/img/avatar-doctor.png" alt="Profile" class="rounded-circle" width="100">
            <h2><?= htmlspecialchars($full_name) ?></h2>
            <h3>Doctor</h3>
          </div>
        </div>
      </div>

      <div class="col-xl-8">
        <div class="card">
          <div class="card-body pt-3">
            <h5 class="card-title">Edit Profile</h5>

            <?php if ($success): ?>
              <div class="alert alert-success"><?= $success ?></div>
            <?php elseif ($error): ?>
              <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
              <div class="row mb-3">
                <label class="col-md-4 col-form-label">Full Name</label>
                <div class="col-md-8">
                  <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($full_name) ?>" required>
                </div>
              </div>

              <div class="row mb-3">
                <label class="col-md-4 col-form-label">Email (readonly)</label>
                <div class="col-md-8">
                  <input type="email" class="form-control" value="<?= htmlspecialchars($email) ?>" readonly>
                </div>
              </div>

              <div class="row mb-3">
                <label class="col-md-4 col-form-label">Phone</label>
                <div class="col-md-8">
                  <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($phone) ?>" required>
                </div>
              </div>

              <div class="row mb-3">
                <label class="col-md-4 col-form-label">Specialization</label>
                <div class="col-md-8">
                  <input type="text" name="specialization" class="form-control" value="<?= htmlspecialchars($specialization) ?>">
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

</main>