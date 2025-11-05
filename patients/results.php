<?php
// Enable full error visibility (for debugging)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include_once '../config/db.php';

// Ensure the user is logged in
if (!isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit();
}

$patient_id = $_SESSION['patient_id'];
$limit = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Handle filters
$filter_test = isset($_GET['test_type']) ? trim($_GET['test_type']) : '';
$filter_date = isset($_GET['preferred_date']) ? trim($_GET['preferred_date']) : '';

$where = "b.patient_id = ? AND b.status = 'Completed' AND b.result_file IS NOT NULL";
$params = [$patient_id];
$types = "i";

if ($filter_test !== '') {
    $where .= " AND t.test_name LIKE ?";
    $params[] = "%$filter_test%";
    $types .= "s";
}

if ($filter_date !== '') {
    $where .= " AND DATE(b.preferred_date) = ?";
    $params[] = $filter_date;
    $types .= "s";
}

// Ensure connection
if (!$conn) {
    die("<div style='color:red;text-align:center;'>❌ Database connection failed.</div>");
}

// Query results
$query = "
    SELECT 
        b.id,
        t.test_name AS test_type,
        b.status,
        b.preferred_date,
        b.result_file,
        b.created_at
    FROM bookings b
    JOIN tests t ON b.test_id = t.id
    WHERE $where
    ORDER BY b.created_at DESC
    LIMIT $limit OFFSET $offset
";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("<div style='color:red;text-align:center;'>SQL Error: " . htmlspecialchars($conn->error) . "</div>");
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

// Count total for pagination
$count_sql = "
    SELECT COUNT(*) 
    FROM bookings b
    JOIN tests t ON b.test_id = t.id
    WHERE $where
";
$count_stmt = $conn->prepare($count_sql);
if (!$count_stmt) {
    die("<div style='color:red;text-align:center;'>Count Query Error: " . htmlspecialchars($conn->error) . "</div>");
}
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$count_stmt->bind_result($total);
$count_stmt->fetch();
$count_stmt->close();

$total_pages = max(1, ceil($total / $limit));

include_once 'includes/header.php';
include_once 'includes/sidebar.php';
?>

<main id="main" class="main">
  <div class="pagetitle">
    <h1>My Lab Results</h1>
  </div>

  <section class="section">
    <div class="card">
      <div class="card-body pt-4">
        <h5 class="card-title">Completed Test Results</h5>

        <!-- Filter Form -->
        <form method="GET" class="row g-3 mb-3">
          <div class="col-md-4">
            <input type="text" name="test_type" class="form-control"
                   placeholder="Filter by Test Type"
                   value="<?= htmlspecialchars($filter_test) ?>">
          </div>
          <div class="col-md-4">
            <input type="date" name="preferred_date" class="form-control"
                   value="<?= htmlspecialchars($filter_date) ?>">
          </div>
          <div class="col-md-4 d-flex">
            <button type="submit" class="btn btn-primary me-2">Filter</button>
            <a href="results.php" class="btn btn-secondary">Clear</a>
          </div>
        </form>

        <?php if ($res && $res->num_rows > 0): ?>
        <div class="table-responsive">
          <table class="table table-bordered table-striped align-middle">
            <thead class="table-light">
              <tr>
                <th>Test Type</th>
                <th>Status</th>
                <th>Preferred Date</th>
                <th>Preview</th>
                <th>Download</th>
                <th>Uploaded On</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = $res->fetch_assoc()):
                $file_path = "../uploads/" . $row['result_file'];
                $is_image = preg_match('/\.(jpg|jpeg|png)$/i', $row['result_file']);
                $is_pdf = preg_match('/\.pdf$/i', $row['result_file']);
              ?>
              <tr>
                <td><?= htmlspecialchars($row['test_type']) ?></td>
                <td><span class="badge bg-success"><?= htmlspecialchars($row['status']) ?></span></td>
                <td><?= htmlspecialchars($row['preferred_date']) ?></td>
                <td>
                  <?php if (file_exists($file_path)): ?>
                    <?php if ($is_pdf): ?>
                      <a href="<?= $file_path ?>" target="_blank" class="btn btn-sm btn-outline-info">View PDF</a>
                    <?php elseif ($is_image): ?>
                      <img src="<?= $file_path ?>" width="80" height="60" alt="Result Preview" class="rounded shadow-sm">
                    <?php else: ?>
                      <span class="text-muted">No Preview</span>
                    <?php endif; ?>
                  <?php else: ?>
                    <span class="text-danger">Missing File</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if (file_exists($file_path)): ?>
                    <a href="download.php?file=<?= urlencode($row['result_file']) ?>&type=<?= urlencode($row['test_type']) ?>" class="btn btn-sm btn-primary">
                      Download
                    </a>
                  <?php else: ?>
                    <span class="text-danger">Unavailable</span>
                  <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($row['created_at']) ?></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <nav aria-label="Results Pagination">
          <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
              <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                <a class="page-link"
                   href="?page=<?= $i ?>&test_type=<?= urlencode($filter_test) ?>&preferred_date=<?= urlencode($filter_date) ?>">
                   <?= $i ?>
                </a>
              </li>
            <?php endfor; ?>
          </ul>
        </nav>

        <?php else: ?>
          <div class="alert alert-info mt-3">No completed results found for your filters.</div>
        <?php endif; $stmt->close(); ?>
      </div>
    </div>
  </section>
</main>