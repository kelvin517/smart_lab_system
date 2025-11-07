<?php
require_once '../config/db.php';

$query = isset($_POST['query']) ? trim($_POST['query']) : '';
$status = isset($_POST['status']) ? trim($_POST['status']) : '';
$technician = isset($_POST['technician']) ? trim($_POST['technician']) : '';
$page = isset($_POST['page']) ? intval($_POST['page']) : 1;

$limit = 8; // rows per page
$offset = ($page - 1) * $limit;

// Base query
$sql = "
  FROM patients p
  LEFT JOIN bookings b ON p.id = b.patient_id
  LEFT JOIN technicians t ON b.uploaded_by = t.id
  LEFT JOIN diagnosis d ON b.id = d.booking_id
  WHERE 1
";

$params = [];
$types = '';

if ($query !== '') {
  $q = "%{$query}%";
  $sql .= " AND (p.full_name LIKE ? OR b.test_type LIKE ? OR t.full_name LIKE ?)";
  $params = array_merge($params, [$q, $q, $q]);
  $types .= 'sss';
}

if ($status !== '') {
  $sql .= " AND b.status = ?";
  $params[] = $status;
  $types .= 's';
}

if ($technician !== '') {
  $sql .= " AND t.full_name = ?";
  $params[] = $technician;
  $types .= 's';
}

// Count total records
$count_query = "SELECT COUNT(*) as total " . $sql;
$stmt = $conn->prepare($count_query);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$total_result = $stmt->get_result()->fetch_assoc();
$total_rows = $total_result['total'];
$total_pages = ceil($total_rows / $limit);
$stmt->close();

// Main fetch query
$fetch_query = "
  SELECT 
    p.id AS patient_id, p.full_name AS patient_name, p.email, p.phone, p.gender,
    b.test_type, b.status, b.result_file, b.created_at,
    t.full_name AS technician_name,
    d.diagnosis_note AS diagnosis
  " . $sql . " ORDER BY p.full_name ASC, b.created_at DESC LIMIT ?, ?";

$stmt = $conn->prepare($fetch_query);
if (!empty($params)) {
  $types_with_limit = $types . 'ii';
  $params_with_limit = array_merge($params, [$offset, $limit]);
  $stmt->bind_param($types_with_limit, ...$params_with_limit);
} else {
  $stmt->bind_param('ii', $offset, $limit);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  echo "<div class='alert alert-warning text-center mb-0'>No patients or tests match your filters.</div>";
  exit;
}
?>

<div class="table-responsive">
  <table class="table table-hover table-striped align-middle">
    <thead class="table-light">
      <tr>
        <th>#</th>
        <th>Patient Name</th>
        <th>Email</th>
        <th>Phone</th>
        <th>Gender</th>
        <th>Test Type</th>
        <th>Technician</th>
        <th>Status</th>
        <th>Result File</th>
        <th>Diagnosis / Medication</th>
        <th>Date</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $count = $offset + 1;
      while ($row = $result->fetch_assoc()):
      ?>
      <tr>
        <td><?= $count++ ?></td>
        <td><a href="patient_history.php?id=<?= $row['patient_id'] ?>" class="fw-bold text-primary"><?= htmlspecialchars($row['patient_name']) ?></a></td>
        <td><?= htmlspecialchars($row['email']) ?></td>
        <td><?= htmlspecialchars($row['phone']) ?></td>
        <td><?= htmlspecialchars($row['gender']) ?></td>
        <td><?= htmlspecialchars($row['test_type'] ?? '—') ?></td>
        <td><?= htmlspecialchars($row['technician_name'] ?? '—') ?></td>
        <td>
          <?php if ($row['status'] === 'Completed'): ?>
            <span class="badge bg-success">Completed</span>
          <?php elseif ($row['status'] === 'Pending'): ?>
            <span class="badge bg-warning text-dark">Pending</span>
          <?php else: ?>
            <span class="badge bg-secondary">—</span>
          <?php endif; ?>
        </td>
        <td>
          <?php if (!empty($row['result_file'])): ?>
            <a href="../uploads/<?= htmlspecialchars($row['result_file']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
              <i class="bi bi-file-earmark-text"></i> View
            </a>
          <?php else: ?>
            <span class="text-muted">No File</span>
          <?php endif; ?>
        </td>
        <td><?= !empty($row['diagnosis']) ? htmlspecialchars($row['diagnosis']) : '<span class="text-muted">Not added</span>' ?></td>
        <td><?= htmlspecialchars($row['created_at'] ?? '—') ?></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
<nav>
  <ul class="pagination justify-content-center mt-3">
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
      <li class="page-item <?= $i == $page ? 'active' : '' ?>">
        <a href="#" class="page-link" data-page="<?= $i ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>
