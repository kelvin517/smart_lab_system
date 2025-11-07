<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['doctor_id'])) {
    header("Location: doctor_login.php");
    exit;
}

$doctor_id = $_SESSION['doctor_id'];
$patient_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($patient_id <= 0) {
    echo "<script>alert('Invalid patient ID.'); window.location.href='patients.php';</script>";
    exit;
}

// Fetch patient details
$patient_stmt = $conn->prepare("SELECT full_name, email, phone, gender FROM patients WHERE patient_id = ?");
$patient_stmt->bind_param("i", $patient_id);
$patient_stmt->execute();
$patient_result = $patient_stmt->get_result();
$patient = $patient_result->fetch_assoc();
$patient_stmt->close();

if (!$patient) {
    echo "<script>alert('Patient not found.'); window.location.href='patients.php';</script>";
    exit;
}

// ---------------- FILTER LOGIC ----------------
$filter_test = $_GET['test_type'] ?? '';
$filter_uploader = $_GET['uploader_role'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';

$where = "b.patient_id = ?";
$params = [$patient_id];
$types = "i";

if (!empty($filter_test)) {
    $where .= " AND b.test_type LIKE ?";
    $params[] = "%$filter_test%";
    $types .= "s";
}
if (!empty($filter_uploader)) {
    $where .= " AND b.uploaded_by_role = ?";
    $params[] = $filter_uploader;
    $types .= "s";
}
if (!empty($filter_date_from) && !empty($filter_date_to)) {
    $where .= " AND DATE(b.created_at) BETWEEN ? AND ?";
    $params[] = $filter_date_from;
    $params[] = $filter_date_to;
    $types .= "ss";
}

$query = "
    SELECT 
        b.id, b.test_type, b.status, b.result_file, b.uploaded_by, b.uploaded_by_role, b.created_at,
        d.diagnosis_note
    FROM bookings b
    LEFT JOIN diagnosis d ON d.booking_id = b.id
    WHERE $where
    ORDER BY b.created_at DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<main id="main" class="main">

  <div class="pagetitle">
    <h1><i class="bi bi-person-lines-fill"></i> Patient History</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="patients.php">Patients</a></li>
        <li class="breadcrumb-item active"><?= htmlspecialchars($patient['full_name']) ?></li>
      </ol>
    </nav>
  </div>

  <section class="section profile">
    <div class="row">

      <!-- Patient Info Card -->
      <div class="col-lg-4">
        <div class="card shadow border-0">
          <div class="card-body pt-4 text-center">
            <img src="../assets/img/profile-img.jpg" alt="Profile" class="rounded-circle mb-3" width="100">
            <h5 class="card-title mb-1"><?= htmlspecialchars($patient['full_name']) ?></h5>
            <p class="text-muted"><?= htmlspecialchars($patient['gender']) ?></p>
            <hr>
            <div class="text-start small">
              <p><i class="bi bi-envelope"></i> <strong>Email:</strong> <?= htmlspecialchars($patient['email']) ?></p>
              <p><i class="bi bi-phone"></i> <strong>Phone:</strong> <?= htmlspecialchars($patient['phone']) ?></p>
            </div>
          </div>
        </div>
      </div>

      <!-- Patient History Section -->
      <div class="col-lg-8">
        <div class="card shadow border-0">
          <div class="card-body pt-4">
            <h5 class="card-title"><i class="bi bi-activity"></i> Completed Tests & Diagnosis</h5>

            <!-- Filter Bar -->
            <form class="row g-3 mb-4" method="GET">
              <input type="hidden" name="id" value="<?= $patient_id ?>">
              
              <div class="col-md-4">
                <label class="form-label">Test Type</label>
                <input type="text" name="test_type" class="form-control" placeholder="e.g. Blood Test" value="<?= htmlspecialchars($filter_test) ?>">
              </div>

              <div class="col-md-4">
                <label class="form-label">Uploaded By</label>
                <select name="uploader_role" class="form-select">
                  <option value="">-- All --</option>
                  <option value="doctor" <?= ($filter_uploader === 'doctor') ? 'selected' : '' ?>>Doctor</option>
                  <option value="technician" <?= ($filter_uploader === 'technician') ? 'selected' : '' ?>>Technician</option>
                </select>
              </div>

              <div class="col-md-4 d-flex align-items-end">
                <button class="btn btn-primary w-100" type="submit"><i class="bi bi-funnel"></i> Filter</button>
              </div>

              <div class="col-md-6">
                <label class="form-label">From</label>
                <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($filter_date_from) ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">To</label>
                <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($filter_date_to) ?>">
              </div>
            </form>

            <!-- Data Table -->
            <div class="table-responsive">
              <table class="table table-hover align-middle table-bordered">
                <thead class="table-light">
                  <tr>
                    <th>Test Type</th>
                    <th>Status</th>
                    <th>Result File</th>
                    <th>Diagnosis</th>
                    <th>Uploaded By</th>
                    <th>Date</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if ($result->num_rows === 0): ?>
                    <tr>
                      <td colspan="6" class="text-center text-muted py-4">
                        <i class="bi bi-info-circle"></i> No records found matching the criteria.
                      </td>
                    </tr>
                  <?php else: ?>
                    <?php while ($row = $result->fetch_assoc()):
                      $file_path = '../uploads/' . $row['result_file'];
                      $has_result = !empty($row['result_file']) && file_exists($file_path);

                      // Get uploader info
                      $uploader_name = 'Unknown';
                      if (!empty($row['uploaded_by'])) {
                          $q = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
                          $q->bind_param("i", $row['uploaded_by']);
                          $q->execute();
                          $res = $q->get_result()->fetch_assoc();
                          if ($res) $uploader_name = $res['full_name'];
                          $q->close();
                      }
                      $role_badge = ($row['uploaded_by_role'] === 'doctor') ? 'bg-primary' : 'bg-info';
                    ?>
                      <tr>
                        <td><strong><?= htmlspecialchars($row['test_type']) ?></strong></td>
                        <td><span class="badge bg-success"><?= htmlspecialchars($row['status']) ?></span></td>
                        <td>
                          <?php if ($has_result): ?>
                            <div class="d-flex gap-2">
                              <a href="<?= $file_path ?>" target="_blank" class="btn btn-sm btn-primary">
                                <i class="bi bi-eye"></i> View
                              </a>
                              <a href="<?= $file_path ?>" download class="btn btn-sm btn-success">
                                <i class="bi bi-download"></i> Download
                              </a>
                            </div>
                          <?php else: ?>
                            <span class="text-muted fst-italic">No File</span>
                          <?php endif; ?>
                        </td>
                        <td><?= !empty($row['diagnosis_note']) ? htmlspecialchars($row['diagnosis_note']) : '<span class="text-muted">Not added</span>' ?></td>
                        <td>
                          <span class="fw-semibold"><?= htmlspecialchars($uploader_name) ?></span><br>
                          <span class="badge <?= $role_badge ?>"><?= ucfirst($row['uploaded_by_role']) ?></span>
                        </td>
                        <td><small><?= htmlspecialchars(date('Y-m-d H:i', strtotime($row['created_at']))) ?></small></td>
                      </tr>
                    <?php endwhile; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>

            <div class="mt-4 text-end">
              <a href="patients.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left-circle"></i> Back to Patients
              </a>
            </div>

          </div>
        </div>
      </div>
    </div>
  </section>
</main>