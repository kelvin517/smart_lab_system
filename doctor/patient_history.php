<?php
session_start();
require_once '../config/db.php';

// Redirect if doctor is not logged in
if (!isset($_SESSION['doctor_id'])) {
    header("Location: doctor_login.php");
    exit;
}

$doctor_id = $_SESSION['doctor_id'];

// ✅ Get patient_id safely from URL
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;

if ($patient_id <= 0) {
    echo "<script>alert('Invalid or missing patient identifier. Please go back and select a patient.'); 
          window.location.href='patients.php';</script>";
    exit;
}

// ✅ Verify doctor has access to this patient
$access_check = $conn->prepare("
    SELECT COUNT(*) as has_access 
    FROM bookings 
    WHERE patient_id = ? AND doctor_id = ?
");
$access_check->bind_param("ii", $patient_id, $doctor_id);
$access_check->execute();
$access_result = $access_check->get_result()->fetch_assoc();
$access_check->close();

if (!$access_result || $access_result['has_access'] == 0) {
    echo "<script>alert('You do not have access to view this patient history.'); 
          window.location.href='patients.php';</script>";
    exit;
}

// ✅ Fetch patient details
$patient_stmt = $conn->prepare("
    SELECT id, full_name, email, phone, gender, date_of_birth, address, date_registered 
    FROM patients 
    WHERE id = ?
");
$patient_stmt->bind_param("i", $patient_id);
$patient_stmt->execute();
$patient = $patient_stmt->get_result()->fetch_assoc();
$patient_stmt->close();

if (!$patient) {
    echo "<script>alert('Patient not found.'); window.location.href='patients.php';</script>";
    exit;
}

// ✅ Calculate patient age from date of birth
$age = '';
if (!empty($patient['date_of_birth'])) {
    $birthDate = new DateTime($patient['date_of_birth']);
    $today = new DateTime();
    $age = $today->diff($birthDate)->y;
}

// ✅ Fetch patient test history with technician and diagnosis info
$history_sql = "
    SELECT 
        b.id AS booking_id,
        b.test_name,
        b.status,
        b.result_file,
        b.created_at,
        b.test_date,
        t.full_name AS technician_name,
        d.diagnosis_note,
        d.medication,
        d.dosage,
        d.follow_up_date,
        d.created_at as diagnosis_date
    FROM bookings b
    LEFT JOIN technicians t ON b.uploaded_by = t.technician_id
    LEFT JOIN diagnosis d ON d.booking_id = b.id
    WHERE b.patient_id = ?
    ORDER BY b.created_at DESC
";

$history_stmt = $conn->prepare($history_sql);
$history_stmt->bind_param("i", $patient_id);
$history_stmt->execute();
$history = $history_stmt->get_result();

// ✅ Statistics for the patient
$stats_sql = "
    SELECT 
        COUNT(*) as total_tests,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_tests,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_tests
    FROM bookings 
    WHERE patient_id = ?
";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $patient_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<main id="main" class="main">

  <div class="pagetitle">
    <h1>Patient Medical History</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item"><a href="patients.php">Patients</a></li>
        <li class="breadcrumb-item active"><?= htmlspecialchars($patient['full_name']) ?></li>
      </ol>
    </nav>
  </div>

  <section class="section">
    <div class="row">
      
      <!-- Patient Summary Cards -->
      <div class="col-lg-3 col-md-6">
        <div class="card info-card sales-card">
          <div class="card-body">
            <h5 class="card-title">Total Tests</h5>
            <div class="d-flex align-items-center">
              <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                <i class="bi bi-clipboard-check"></i>
              </div>
              <div class="ps-3">
                <h6><?= $stats['total_tests'] ?? 0 ?></h6>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-md-6">
        <div class="card info-card revenue-card">
          <div class="card-body">
            <h5 class="card-title">Completed</h5>
            <div class="d-flex align-items-center">
              <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                <i class="bi bi-check-circle"></i>
              </div>
              <div class="ps-3">
                <h6><?= $stats['completed_tests'] ?? 0 ?></h6>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-md-6">
        <div class="card info-card customers-card">
          <div class="card-body">
            <h5 class="card-title">Pending</h5>
            <div class="d-flex align-items-center">
              <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                <i class="bi bi-clock-history"></i>
              </div>
              <div class="ps-3">
                <h6><?= $stats['pending_tests'] ?? 0 ?></h6>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-md-6">
        <div class="card info-card">
          <div class="card-body">
            <h5 class="card-title">Patient Age</h5>
            <div class="d-flex align-items-center">
              <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                <i class="bi bi-person"></i>
              </div>
              <div class="ps-3">
                <h6><?= $age ? $age . ' years' : 'N/A' ?></h6>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-12">
        <div class="card shadow-sm border-0">
          <div class="card-body pt-4">
            
            <!-- Patient Details Section -->
            <div class="row mb-4">
              <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h5 class="card-title text-primary mb-0">
                    <i class="bi bi-person-vcard me-2"></i>Patient Information
                  </h5>
                  <div class="btn-group">
                    <button class="btn btn-sm btn-outline-primary" onclick="window.print()">
                      <i class="bi bi-printer me-1"></i>Print
                    </button>
                    <a href="message_patient.php?patient_id=<?= $patient_id ?>" class="btn btn-sm btn-outline-success">
                      <i class="bi bi-chat-dots me-1"></i>Message
                    </a>
                  </div>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <table class="table table-borderless">
                  <tr>
                    <td width="40%"><strong>Full Name:</strong></td>
                    <td><?= htmlspecialchars($patient['full_name']) ?></td>
                  </tr>
                  <tr>
                    <td><strong>Email:</strong></td>
                    <td><?= htmlspecialchars($patient['email']) ?></td>
                  </tr>
                  <tr>
                    <td><strong>Phone:</strong></td>
                    <td><?= htmlspecialchars($patient['phone']) ?></td>
                  </tr>
                  <tr>
                    <td><strong>Gender:</strong></td>
                    <td><?= htmlspecialchars($patient['gender']) ?></td>
                  </tr>
                </table>
              </div>
              <div class="col-md-6">
                <table class="table table-borderless">
                  <tr>
                    <td width="40%"><strong>Date of Birth:</strong></td>
                    <td><?= !empty($patient['date_of_birth']) ? htmlspecialchars($patient['date_of_birth']) : 'N/A' ?></td>
                  </tr>
                  <tr>
                    <td><strong>Age:</strong></td>
                    <td><?= $age ? $age . ' years' : 'N/A' ?></td>
                  </tr>
                  <tr>
                    <td><strong>Address:</strong></td>
                    <td><?= !empty($patient['address']) ? htmlspecialchars($patient['address']) : 'N/A' ?></td>
                  </tr>
                  <tr>
                    <td><strong>Registered:</strong></td>
                    <td><?= htmlspecialchars($patient['date_registered']) ?></td>
                  </tr>
                </table>
              </div>
            </div>

            <!-- Test History Section -->
            <div class="row mt-4">
              <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h5 class="card-title text-success mb-0">
                    <i class="bi bi-clock-history me-2"></i>Medical Test History
                  </h5>
                  <span class="badge bg-primary"><?= $history->num_rows ?> records found</span>
                </div>

                <?php if ($history->num_rows > 0): ?>
                <div class="table-responsive">
                  <table class="table table-hover align-middle" id="patientHistoryTable">
                    <thead class="table-light">
                      <tr>
                        <th>#</th>
                        <th>Test Name</th>
                        <th>Test Date</th>
                        <th>Status</th>
                        <th>Technician</th>
                        <th>Result</th>
                        <th>Diagnosis & Medication</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php $count = 1; while ($row = $history->fetch_assoc()): ?>
                      <tr>
                        <td><?= $count++ ?></td>
                        <td>
                          <strong><?= htmlspecialchars($row['test_name']) ?></strong>
                          <br><small class="text-muted">ID: #<?= $row['booking_id'] ?></small>
                        </td>
                        <td>
                          <?= !empty($row['test_date']) ? htmlspecialchars($row['test_date']) : htmlspecialchars($row['created_at']) ?>
                        </td>
                        <td>
                          <?php 
                            $status_class = [
                              'Completed' => 'bg-success',
                              'Pending' => 'bg-warning text-dark',
                              'In Progress' => 'bg-info',
                              'Cancelled' => 'bg-danger'
                            ];
                            $status = $row['status'] ?: 'Pending';
                            $class = $status_class[$status] ?? 'bg-secondary';
                          ?>
                          <span class="badge <?= $class ?>"><?= htmlspecialchars($status) ?></span>
                        </td>
                        <td>
                          <?= !empty($row['technician_name']) ? htmlspecialchars($row['technician_name']) : '<span class="text-muted">N/A</span>' ?>
                        </td>
                        <td>
                          <?php if (!empty($row['result_file'])): ?>
                            <a href="../uploads/<?= htmlspecialchars($row['result_file']) ?>" 
                               target="_blank" class="btn btn-sm btn-outline-success" 
                               data-bs-toggle="tooltip" title="Download Result">
                              <i class="bi bi-download"></i>
                            </a>
                          <?php else: ?>
                            <span class="text-muted">Not Available</span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <?php if (!empty($row['diagnosis_note'])): ?>
                            <div class="diagnosis-info">
                              <strong>Diagnosis:</strong>
                              <p class="mb-1 small"><?= nl2br(htmlspecialchars($row['diagnosis_note'])) ?></p>
                              <?php if (!empty($row['medication'])): ?>
                                <strong>Medication:</strong>
                                <p class="mb-1 small"><?= htmlspecialchars($row['medication']) ?></p>
                              <?php endif; ?>
                              <?php if (!empty($row['dosage'])): ?>
                                <strong>Dosage:</strong>
                                <p class="mb-1 small"><?= htmlspecialchars($row['dosage']) ?></p>
                              <?php endif; ?>
                              <?php if (!empty($row['follow_up_date'])): ?>
                                <strong>Follow-up:</strong>
                                <p class="mb-0 small text-info"><?= htmlspecialchars($row['follow_up_date']) ?></p>
                              <?php endif; ?>
                            </div>
                          <?php else: ?>
                            <span class="text-muted">No diagnosis added</span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary view-details" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#testDetailsModal"
                                    data-test='<?= htmlspecialchars(json_encode($row)) ?>'>
                              <i class="bi bi-eye"></i>
                            </button>
                            <?php if (empty($row['diagnosis_note'])): ?>
                              <a href="add_diagnosis.php?booking_id=<?= $row['booking_id'] ?>&patient_id=<?= $patient_id ?>" 
                                 class="btn btn-outline-success"
                                 data-bs-toggle="tooltip" title="Add Diagnosis">
                                <i class="bi bi-plus-circle"></i>
                              </a>
                            <?php endif; ?>
                          </div>
                        </td>
                      </tr>
                      <?php endwhile; ?>
                    </tbody>
                  </table>
                </div>
                <?php else: ?>
                  <div class="text-center py-5">
                    <i class="bi bi-clipboard-x display-1 text-muted"></i>
                    <h4 class="text-muted mt-3">No Test Records Found</h4>
                    <p class="text-muted">This patient hasn't undergone any tests yet.</p>
                  </div>
                <?php endif; ?>

              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  </section>
</main>

<!-- Test Details Modal -->
<div class="modal fade" id="testDetailsModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Test Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="testDetailsContent">
        <!-- Content will be loaded via JavaScript -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

<style>
.info-card .card-icon {
  font-size: 1.5rem;
  width: 60px;
  height: 60px;
}
.sales-card .card-icon { background-color: #e3f2fd; color: #1976d2; }
.revenue-card .card-icon { background-color: #e8f5e8; color: #2e7d32; }
.customers-card .card-icon { background-color: #fff3e0; color: #ef6c00; }
.diagnosis-info { max-width: 300px; font-size: 0.875rem; }
.table td { vertical-align: middle; }
</style>

<script>
// Initialize tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
  return new bootstrap.Tooltip(tooltipTriggerEl)
});

// Test details modal handler
document.querySelectorAll('.view-details').forEach(button => {
  button.addEventListener('click', function() {
    const testData = JSON.parse(this.getAttribute('data-test'));
    const modalContent = document.getElementById('testDetailsContent');
    
    let content = `
      <div class="row">
        <div class="col-md-6">
          <p><strong>Test Name:</strong> ${testData.test_name}</p>
          <p><strong>Booking ID:</strong> #${testData.booking_id}</p>
          <p><strong>Status:</strong> <span class="badge bg-success">${testData.status}</span></p>
        </div>
        <div class="col-md-6">
          <p><strong>Test Date:</strong> ${testData.test_date || testData.created_at}</p>
          <p><strong>Technician:</strong> ${testData.technician_name || 'N/A'}</p>
        </div>
      </div>
    `;
    
    if (testData.diagnosis_note) {
      content += `
        <hr>
        <h6>Diagnosis Information</h6>
        <p><strong>Diagnosis:</strong> ${testData.diagnosis_note}</p>
        ${testData.medication ? `<p><strong>Medication:</strong> ${testData.medication}</p>` : ''}
        ${testData.dosage ? `<p><strong>Dosage:</strong> ${testData.dosage}</p>` : ''}
        ${testData.follow_up_date ? `<p><strong>Follow-up Date:</strong> ${testData.follow_up_date}</p>` : ''}
        <p><small class="text-muted">Diagnosis added on: ${testData.diagnosis_date}</small></p>
      `;
    }
    
    if (testData.result_file) {
      content += `
        <hr>
        <h6>Test Results</h6>
        <a href="../uploads/${testData.result_file}" target="_blank" class="btn btn-success">
          <i class="bi bi-download me-1"></i>Download Result File
        </a>
      `;
    }
    
    modalContent.innerHTML = content;
  });
});

// Simple search functionality
function searchTable() {
  const input = document.getElementById('searchInput');
  const filter = input.value.toLowerCase();
  const table = document.getElementById('patientHistoryTable');
  const tr = table.getElementsByTagName('tr');
  
  for (let i = 1; i < tr.length; i++) {
    const td = tr[i].getElementsByTagName('td');
    let found = false;
    for (let j = 0; j < td.length; j++) {
      if (td[j]) {
        const txtValue = td[j].textContent || td[j].innerText;
        if (txtValue.toLowerCase().indexOf(filter) > -1) {
          found = true;
          break;
        }
      }
    }
    tr[i].style.display = found ? '' : 'none';
  }
}
</script>