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
$patient_stmt = $conn->prepare("SELECT full_name, email, phone, gender FROM patients WHERE id = ?");
$patient_stmt->bind_param("i", $patient_id);
$patient_stmt->execute();
$patient_result = $patient_stmt->get_result();
$patient = $patient_result->fetch_assoc();
$patient_stmt->close();

if (!$patient) {
    echo "<script>alert('Patient not found.'); window.location.href='patients.php';</script>";
    exit;
}
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<main id="main" class="main">

  <div class="pagetitle">
    <h1>Patient History</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="patients.php">Patients</a></li>
        <li class="breadcrumb-item active"><?= htmlspecialchars($patient['full_name']) ?></li>
      </ol>
    </nav>
  </div>

  <section class="section">
    <div class="card">
      <div class="card-body pt-4">

        <h5 class="card-title">Patient Details</h5>
        <ul class="list-group mb-4">
          <li class="list-group-item"><strong>Name:</strong> <?= htmlspecialchars($patient['full_name']) ?></li>
          <li class="list-group-item"><strong>Email:</strong> <?= htmlspecialchars($patient['email']) ?></li>
          <li class="list-group-item"><strong>Phone:</strong> <?= htmlspecialchars($patient['phone']) ?></li>
          <li class="list-group-item"><strong>Gender:</strong> <?= htmlspecialchars($patient['gender']) ?></li>
        </ul>

        <h5 class="card-title">Completed Lab Tests & Diagnosis</h5>
        <table class="table table-striped">
          <thead>
            <tr>
              <th>Test Type</th>
              <th>Preferred Date</th>
              <th>Status</th>
              <th>Result File</th>
              <th>Diagnosis</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $stmt = $conn->prepare("
              SELECT 
                b.test_type, b.preferred_date, b.status, b.result_file, b.created_at,
                d.diagnosis_note
              FROM bookings b
              LEFT JOIN diagnosis d ON d.booking_id = b.id
              WHERE b.patient_id = ? AND b.status = 'Completed'
              ORDER BY b.created_at DESC
            ");
            $stmt->bind_param("i", $patient_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
              echo "<tr><td colspan='6' class='text-center text-muted'>No completed tests found for this patient.</td></tr>";
            }

            while ($row = $result->fetch_assoc()):
            ?>
              <tr>
                <td><?= htmlspecialchars($row['test_type']) ?></td>
                <td><?= htmlspecialchars($row['preferred_date']) ?></td>
                <td><span class="badge bg-success"><?= htmlspecialchars($row['status']) ?></span></td>
                <td>
                  <?php if (!empty($row['result_file'])): ?>
                    <a href="../uploads/<?= htmlspecialchars($row['result_file']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">View</a>
                  <?php else: ?>
                    <span class="text-muted">No File</span>
                  <?php endif; ?>
                </td>
                <td><?= !empty($row['diagnosis_note']) ? htmlspecialchars($row['diagnosis_note']) : '<span class="text-muted">Not added</span>' ?></td>
                <td><?= htmlspecialchars($row['created_at']) ?></td>
              </tr>
            <?php endwhile; $stmt->close(); ?>
          </tbody>
        </table>

        <a href="patients.php" class="btn btn-secondary mt-4">← Back to Patients</a>

      </div>
    </div>
  </section>

</main>