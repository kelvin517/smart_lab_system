<?php
// ✅ Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include '../config/db.php';

// ✅ Redirect if not logged in
if (!isset($_SESSION['patient_id'])) {
  header("Location: login.php");
  exit;
}

$success = $error = '';
$patient_id = $_SESSION['patient_id'];

// ✅ Handle new test booking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_type = trim($_POST['test_name']);
    $preferred_date = $_POST['preferred_date'];
    $amount = 1000;

    // Check if same test already booked for the same date
    $check = $conn->prepare("SELECT patient_id FROM bookings WHERE patient_id=? AND test_name=? AND preferred_date=?");
    $check->bind_param("iss", $patient_id, $test_type, $preferred_date);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $error = "⚠️ You already booked this test on the same date.";
    } else {
        // Insert booking
        $stmt = $conn->prepare("INSERT INTO bookings (patient_id, test_name, status, preferred_date, created_at) VALUES (?, ?, 'Pending', ?, NOW())");
        $stmt->bind_param("iss", $patient_id, $test_type, $preferred_date);

        if ($stmt->execute()) {
            $booking_id = $stmt->insert_id;

            // ✅ Corrected billing record insertion
            $bill = $conn->prepare("INSERT INTO billing (booking_id, amount, status, created_at) VALUES (?, ?, 'Pending', NOW())");
            $bill->bind_param("id", $booking_id, $amount);
            $bill->execute();
            $bill->close();

            $success = "✅ Test booked successfully. Please proceed to payment.";
        } else {
            $error = "❌ Failed to book the test.";
        }
        $stmt->close();
    }
    $check->close();
}

// ✅ Handle booking cancellation
if (isset($_GET['cancel_id'])) {
    $cancel_id = intval($_GET['cancel_id']);

    $check = $conn->prepare("SELECT id FROM bookings WHERE id=? AND patient_id=?");
    $check->bind_param("ii", $cancel_id, $patient_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $update = $conn->prepare("UPDATE bookings SET status='Cancelled' WHERE id=?");
        $update->bind_param("i", $cancel_id);
        if ($update->execute()) {
            $success = "✅ Appointment cancelled successfully.";
        } else {
            $error = "❌ Failed to cancel appointment.";
        }
        $update->close();
    } else {
        $error = "❌ Invalid booking ID.";
    }
    $check->close();
}
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<main id="main" class="main">
  <div class="pagetitle">
    <h1><i class="bi bi-journal-medical"></i> Book Laboratory Test</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
        <li class="breadcrumb-item active">Book Test</li>
      </ol>
    </nav>
  </div>

  <section class="section dashboard">

    <!-- Alerts -->
    <?php if ($success): ?>
      <div class="alert alert-success alert-dismissible fade show">
        <?= $success ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php elseif ($error): ?>
      <div class="alert alert-danger alert-dismissible fade show">
        <?= $error ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <div class="row">
      <!-- LEFT COLUMN: Booking Form -->
      <div class="col-lg-6">
        <div class="card">
          <div class="card-body pt-4">
            <form method="POST" novalidate>
              <div class="row mb-3">
                <label class="col-sm-4 col-form-label"><i class="bi bi-flask"></i> Test Type</label>
                <div class="col-sm-8">
                  <select name="test_name" class="form-select" required>
                    <option value="">-- Select Test --</option>
                    <option>Malaria Test</option>
                    <option>Blood Sugar Test</option>
                    <option>COVID-19 PCR</option>
                    <option>Urinalysis</option>
                    <option>HIV Test</option>
                    <option>Cholesterol Test</option>
                    <option>Typhoid Test</option>
                    <option>Blood Group Test</option>
                  </select>
                </div>
              </div>

              <div class="row mb-3">
                <label class="col-sm-4 col-form-label"><i class="bi bi-calendar-event"></i> Preferred Date</label>
                <div class="col-sm-8">
                  <input type="date" name="preferred_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
                </div>
              </div>

              <div class="text-center">
                <button type="submit" class="btn btn-primary">
                  <i class="bi bi-check2-circle"></i> Confirm Booking
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN: Booking History -->
      <div class="col-lg-6">
        <div class="card">
          <div class="card-body pt-4">
            <h5 class="card-title"><i class="bi bi-clock-history"></i> My Test Bookings</h5>

            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead class="table-light">
                  <tr>
                    <th>Test</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $today = date('Y-m-d');
                  $stmt = $conn->prepare("
                    SELECT b.id, b.test_name, b.status, b.preferred_date, b.created_at, bl.status AS payment_status
                    FROM bookings b
                    LEFT JOIN billing bl ON bl.booking_id = b.id
                    WHERE b.patient_id = ?
                    ORDER BY b.created_at DESC
                  ");
                  $stmt->bind_param("i", $patient_id);
                  $stmt->execute();
                  $result = $stmt->get_result();
                  if ($result->num_rows > 0):
                    while ($row = $result->fetch_assoc()):
                  ?>
                  <tr class="<?= ($row['preferred_date'] >= $today) ? 'table-success' : 'table-secondary' ?>">
                    <td><?= htmlspecialchars($row['test_name']) ?></td>
                    <td><?= htmlspecialchars($row['preferred_date']) ?></td>
                    <td>
                      <span class="badge 
                        <?= $row['status'] === 'Cancelled' ? 'bg-danger' : 
                          ($row['status'] === 'Pending' ? 'bg-warning text-dark' : 'bg-success') ?>">
                        <?= htmlspecialchars($row['status']) ?>
                      </span>
                    </td>
                    <td>
                      <?php if ($row['payment_status'] === 'Paid'): ?>
                        <span class="badge bg-success">Paid</span>
                      <?php else: ?>
                        <a href="pay.php?booking_id=<?= $row['id'] ?>" class="badge bg-warning text-dark">
                          Pay Now
                        </a>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($row['status'] === 'Pending'): ?>
                        <a href="?cancel_id=<?= $row['id'] ?>" 
                           onclick="return confirm('Cancel this booking?')" 
                           class="text-danger small">
                          <i class="bi bi-x-circle"></i> Cancel
                        </a>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php endwhile; else: ?>
                    <tr><td colspan="5" class="text-center text-muted">No bookings found.</td></tr>
                  <?php endif; $stmt->close(); ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</main>
