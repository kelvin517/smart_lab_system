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
        // Check if test already exists for this technician
        $check_stmt = $conn->prepare("SELECT test_id FROM tests WHERE test_name = ? AND created_by = ?");
        $check_stmt->bind_param("si", $test_name, $technician_id);
        $check_stmt->execute();
        $check_stmt->store_result();
        
        if ($check_stmt->num_rows > 0) {
            $status = "<div class='alert alert-warning'>A test with this name already exists.</div>";
        } else {
            $stmt = $conn->prepare("INSERT INTO tests (test_name, description, created_by) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $test_name, $description, $technician_id);
            if ($stmt->execute()) {
                $status = "<div class='alert alert-success'>New test added successfully!</div>";
            } else {
                $status = "<div class='alert alert-danger'>Error adding test: " . htmlspecialchars($conn->error) . "</div>";
            }
            $stmt->close();
        }
        $check_stmt->close();
    } else {
        $status = "<div class='alert alert-warning'>Test name is required.</div>";
    }
}

// ✅ Handle Delete
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    
    // Check if test exists and belongs to this technician
    $check_stmt = $conn->prepare("SELECT test_id FROM tests WHERE test_id = ? AND created_by = ?");
    $check_stmt->bind_param("ii", $delete_id, $technician_id);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows > 0) {
        // Check if test has any assigned bookings
        $booking_check = $conn->prepare("SELECT booking_id FROM bookings WHERE test_id = ? LIMIT 1");
        $booking_check->bind_param("i", $delete_id);
        $booking_check->execute();
        $booking_check->store_result();
        
        if ($booking_check->num_rows > 0) {
            $status = "<div class='alert alert-danger'>Cannot delete test. It has assigned bookings.</div>";
        } else {
            $stmt = $conn->prepare("DELETE FROM tests WHERE test_id = ? AND created_by = ?");
            $stmt->bind_param("ii", $delete_id, $technician_id);
            if ($stmt->execute()) {
                $status = "<div class='alert alert-success'>Test deleted successfully!</div>";
            } else {
                $status = "<div class='alert alert-danger'>Error deleting test: " . htmlspecialchars($conn->error) . "</div>";
            }
            $stmt->close();
        }
        $booking_check->close();
    } else {
        $status = "<div class='alert alert-danger'>Test not found or you don't have permission to delete it.</div>";
    }
    $check_stmt->close();
}

// ✅ Fetch all tests with corrected column names
$query = $conn->prepare("
    SELECT t.id, t.test_name, t.description, t.created_at, 
           COUNT(b.id) AS assigned_count
    FROM tests t
    LEFT JOIN bookings b ON b.id = t.id
    WHERE t.created_by = ?
    GROUP BY t.id
    ORDER BY t.created_at DESC
");

if ($query) {
    $query->bind_param("i", $technician_id);
    if ($query->execute()) {
        $tests = $query->get_result();
    } else {
        $status .= "<div class='alert alert-danger'>Error fetching tests: " . htmlspecialchars($conn->error) . "</div>";
        $tests = false;
    }
} else {
    $status .= "<div class='alert alert-danger'>Error preparing query: " . htmlspecialchars($conn->error) . "</div>";
    $tests = false;
}
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
  
  <style>
    .action-buttons {
        display: flex;
        gap: 5px;
    }
    .table-responsive {
        overflow-x: auto;
    }
    .badge {
        font-size: 0.75em;
    }
  </style>
</head>
<body>

<?php include('includes/header.php'); ?>
<?php include('includes/sidebar.php'); ?>

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
        <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addTestModal">
          <i class="bi bi-plus-circle"></i> Add New Test
        </button>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered table-striped table-hover">
            <thead class="table-dark">
              <tr>
                <th>#</th>
                <th>Test Name</th>
                <th>Description</th>
                <th>Assigned Bookings</th>
                <th>Date Created</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($tests && $tests->num_rows > 0): ?>
                <?php $i = 1; while ($row = $tests->fetch_assoc()): ?>
                  <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($row['test_name']) ?></td>
                    <td><?= !empty($row['description']) ? htmlspecialchars($row['description']) : '<span class="text-muted">No description</span>' ?></td>
                    <td>
                      <span class="badge bg-<?= $row['assigned_count'] > 0 ? 'info' : 'secondary' ?>">
                        <?= $row['assigned_count'] ?> booking<?= $row['assigned_count'] != 1 ? 's' : '' ?>
                      </span>
                    </td>
                    <td><?= date("M d, Y h:i A", strtotime($row['created_at'])) ?></td>
                    <td>
                      <div class="action-buttons">
                        <a href="edit_test.php?id=<?= $row['test_id'] ?>" class="btn btn-sm btn-warning" title="Edit Test">
                          <i class="bi bi-pencil"></i>
                        </a>
                        <a href="?delete=<?= $row['test_id'] ?>" class="btn btn-sm btn-danger" 
                           onclick="return confirm('Are you sure you want to delete this test? This action cannot be undone.');"
                           title="Delete Test">
                          <i class="bi bi-trash"></i>
                        </a>
                      </div>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6" class="text-center text-muted py-4">
                    <i class="bi bi-flask" style="font-size: 2rem;"></i><br>
                    No tests added yet. Click "Add New Test" to get started.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
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
          <label for="test_name" class="form-label">Test Name <span class="text-danger">*</span></label>
          <input type="text" class="form-control" name="test_name" required maxlength="255" 
                 placeholder="Enter test name">
        </div>
        <div class="mb-3">
          <label for="description" class="form-label">Description</label>
          <textarea class="form-control" name="description" rows="3" 
                    placeholder="Enter test description (optional)" maxlength="500"></textarea>
          <div class="form-text">Maximum 500 characters</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="add_test" class="btn btn-success">
          <i class="bi bi-check-circle"></i> Save Test
        </button>
      </div>
    </form>
  </div>
</div>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
// Auto-hide status messages after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
    
    // Clear form after successful submission
    const addTestForm = document.querySelector('form[method="POST"]');
    const successAlert = document.querySelector('.alert-success');
    if (successAlert && addTestForm) {
        addTestForm.reset();
    }
});
</script>
</body>
</html>