<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/db.php';

// ✅ Ensure technician is logged in
if (!isset($_SESSION['technician_id'])) {
    header("Location: technician_login.php");
    exit;
}

$technician_id = intval($_SESSION['technician_id']);
$technician_name = $_SESSION['technician_username'] ?? 'Technician';
$status = '';

// ✅ Handle Add New Test
if (isset($_POST['add_test'])) {
    $test_name = trim($_POST['test_name']);
    $description = trim($_POST['description']);

    if (!empty($test_name)) {
        $stmt = $conn->prepare("INSERT INTO tests (test_name, description, created_by) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $test_name, $description, $technician_id);
        if ($stmt->execute()) {
            $status = "<div class='alert alert-success'>New test added successfully!</div>";
        } else {
            $status = "<div class='alert alert-danger'>Error adding test: " . htmlspecialchars($conn->error) . "</div>";
        }
    } else {
        $status = "<div class='alert alert-warning'>Test name is required.</div>";
    }
}

// ✅ Handle Delete
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM tests WHERE test_id = ? AND created_by = ?");
    $stmt->bind_param("ii", $delete_id, $technician_id);
    if ($stmt->execute()) {
        $status = "<div class='alert alert-success'>Test deleted successfully!</div>";
    } else {
        $status = "<div class='alert alert-danger'>Error deleting test.</div>";
    }
}

// ✅ Fetch all tests
$query = $conn->prepare("
    SELECT t.id, t.test_name, t.description, t.created_at, 
           COUNT(b.id) AS assigned_count
    FROM tests t
    LEFT JOIN bookings b ON b.id = t.id
    WHERE t.created_by = ?
    GROUP BY t.id
    ORDER BY t.created_at DESC
");
$query->bind_param("i", $technician_id);
$query->execute();
$tests = $query->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Manage Tests - Smart Laboratory System</title>
  <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>

<?php include('includes/header.php'); ?>
<?php include ('includes/sidebar.php'); ?>

<main id="main" class="main">
  <div class="pagetitle">
    <h1>Manage Tests</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="technician_dashboard.php">Home</a></li>
        <li class="breadcrumb-item active">Manage Tests</li>
      </ol>
    </nav>
  </div>

  <section class="section dashboard">
    <?= $status ?>

    <div class="card">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <span><i class="bi bi-flask"></i> All Tests</span>
        <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addTestModal"><i class="bi bi-plus-circle"></i> Add New Test</button>
      </div>
      <div class="card-body">
        <table class="table table-bordered table-striped">
          <thead>
            <tr>
              <th>#</th>
              <th>Test Name</th>
              <th>Description</th>
              <th>Assigned Bookings</th>
              <th>Date Created</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($tests->num_rows > 0): ?>
              <?php $i = 1; while ($row = $tests->fetch_assoc()): ?>
                <tr>
                  <td><?= $i++ ?></td>
                  <td><?= htmlspecialchars($row['test_name']) ?></td>
                  <td><?= htmlspecialchars($row['description']) ?></td>
                  <td><span class="badge bg-info"><?= $row['assigned_count'] ?></span></td>
                  <td><?= date("M d, Y h:i A", strtotime($row['created_at'])) ?></td>
                  <td>
                    <a href="?delete=<?= $row['test_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this test?');">
                      <i class="bi bi-trash"></i>
                    </a>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="6" class="text-center text-muted">No tests added yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</main>

<!-- Add Test Modal -->
<div class="modal fade" id="addTestModal" tabindex="-1" aria-labelledby="addTestModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="addTestModalLabel"><i class="bi bi-plus-circle"></i> Add New Test</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label for="test_name" class="form-label">Test Name</label>
          <input type="text" class="form-control" name="test_name" required>
        </div>
        <div class="mb-3">
          <label for="description" class="form-label">Description</label>
          <textarea class="form-control" name="description" rows="3"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="add_test" class="btn btn-success">Save Test</button>
      </div>
    </form>
  </div>
</div>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
