<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit;
}

$patient_id = $_SESSION['patient_id'];

// Handle Feedback Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $category = $_POST['category'];
    $rating = !empty($_POST['rating']) ? intval($_POST['rating']) : NULL;
    $comments = trim($_POST['comments']);

    if (!empty($category) && !empty($comments)) {
        $stmt = $conn->prepare("INSERT INTO feedback (patient_id, category, rating, comments) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isis", $patient_id, $category, $rating, $comments);
        if ($stmt->execute()) {
            $success = "Thank you! Your feedback has been submitted successfully.";
        } else {
            $error = "Something went wrong. Please try again.";
        }
        $stmt->close();
    } else {
        $error = "Please fill in all required fields.";
    }
}

// Fetch Feedback History with Pagination
$limit = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

$count_sql = "SELECT COUNT(*) FROM feedback WHERE patient_id = ?";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param("i", $patient_id);
$count_stmt->execute();
$count_stmt->bind_result($total);
$count_stmt->fetch();
$count_stmt->close();
$total_pages = ceil($total / $limit);

$sql = "SELECT * FROM feedback WHERE patient_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $patient_id, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<main id="main" class="main">
  <div class="pagetitle">
    <h1>Feedback</h1>
  </div>

  <section class="section">
    <div class="card">
      <div class="card-body pt-4">
        <h5 class="card-title">Submit Feedback</h5>

        <?php if (isset($success)): ?>
          <div class="alert alert-success"><?= $success ?></div>
        <?php elseif (isset($error)): ?>
          <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" class="row g-3 mb-4">
          <div class="col-md-4">
            <label class="form-label">Feedback Category</label>
            <select name="category" class="form-select" required>
              <option value="">-- Select Category --</option>
              <option value="System">System</option>
              <option value="Doctor">Doctor</option>
              <option value="Technician">Technician</option>
              <option value="Service">Service</option>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Rating (Optional)</label>
            <select name="rating" class="form-select">
              <option value="">-- Select Rating --</option>
              <option value="5">Excellent (5)</option>
              <option value="4">Good (4)</option>
              <option value="3">Average (3)</option>
              <option value="2">Poor (2)</option>
              <option value="1">Very Poor (1)</option>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label">Comments</label>
            <textarea name="comments" class="form-control" rows="4" placeholder="Share your experience or suggestions..." required></textarea>
          </div>

          <div class="col-12 text-end">
            <button type="submit" name="submit_feedback" class="btn btn-success">
              <i class="bi bi-send"></i> Submit Feedback
            </button>
          </div>
        </form>

        <h5 class="card-title">My Previous Feedback</h5>

        <?php if ($result->num_rows > 0): ?>
        <table class="table table-bordered table-striped">
          <thead>
            <tr>
              <th>#</th>
              <th>Category</th>
              <th>Rating</th>
              <th>Comments</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php $i = 1; while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($row['category']) ?></td>
                <td><?= $row['rating'] ? $row['rating'] . " / 5" : '<span class="text-muted">N/A</span>' ?></td>
                <td><?= nl2br(htmlspecialchars($row['comments'])) ?></td>
                <td><?= htmlspecialchars($row['created_at']) ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>

        <!-- Pagination -->
        <nav>
          <ul class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
              <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>
          </ul>
        </nav>

        <?php else: ?>
          <div class="alert alert-info">You have not submitted any feedback yet.</div>
        <?php endif; ?>
      </div>
    </div>
  </section>
</main>