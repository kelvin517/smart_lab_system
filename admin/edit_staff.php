<?php
session_start();
include '../config/db.php';

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../admin_login.php");
    exit();
}

// Validate and fetch staff ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: view_staff.php");
    exit();
}

$id = intval($_GET['id']);

// Fetch staff record
$stmt = $conn->prepare("SELECT * FROM staff WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    echo "<div class='alert alert-danger m-3'>Error: Staff not found.</div>";
    exit();
}

$success = $error = "";

// Handle update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $role = trim($_POST['role']);

    if (empty($name) || empty($email) || empty($phone) || empty($role)) {
        $error = "All fields are required.";
    } else {
        $update = $conn->prepare("UPDATE staff SET full_name=?, email=?, phone=?, role=? WHERE id=?");
        $update->bind_param("ssssi", $name, $email, $phone, $role, $id);

        if ($update->execute()) {
            $success = "Staff details updated successfully.";
            // Refresh data
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
        } else {
            $error = "Error updating record. Please try again.";
        }
    }
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<main id="main" class="main">
  <div class="pagetitle">
    <h1>Edit Staff</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="view_staff.php">Manage Staff</a></li>
        <li class="breadcrumb-item active">Edit Staff</li>
      </ol>
    </nav>
  </div>

  <section class="section">
    <div class="card col-lg-8 mx-auto">
      <div class="card-body pt-4">
        <h5 class="card-title">Update Staff Information</h5>

        <?php if ($success): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $success ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <?php if ($error): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <form method="POST" class="row g-3">
          <div class="col-md-6">
            <label for="full_name" class="form-label">Full Name</label>
            <input type="text" name="full_name" id="full_name" class="form-control" 
                   value="<?= htmlspecialchars($row['full_name']) ?>" required>
          </div>

          <div class="col-md-6">
            <label for="email" class="form-label">Email</label>
            <input type="email" name="email" id="email" class="form-control" 
                   value="<?= htmlspecialchars($row['email']) ?>" required>
          </div>

          <div class="col-md-6">
            <label for="phone" class="form-label">Phone</label>
            <input type="text" name="phone" id="phone" class="form-control" 
                   value="<?= htmlspecialchars($row['phone']) ?>" required>
          </div>

          <div class="col-md-6">
            <label for="role" class="form-label">Role</label>
            <select name="role" id="role" class="form-select" required>
              <option value="">-- Select Role --</option>
              <option value="doctor" <?= $row['role'] === 'doctor' ? 'selected' : '' ?>>Doctor</option>
              <option value="technician" <?= $row['role'] === 'technician' ? 'selected' : '' ?>>Technician</option>
            </select>
          </div>

          <div class="text-center mt-3">
            <button type="submit" class="btn btn-primary">Update Staff</button>
            <a href="view_staff.php" class="btn btn-secondary">Cancel</a>
          </div>
        </form>
      </div>
    </div>
  </section>
</main>

<?php include 'includes/footer.php'; ?>
