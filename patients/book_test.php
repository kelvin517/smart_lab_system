<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit;
}

$success = $error = '';
$patient_id = $_SESSION['patient_id'];

// Handle new booking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_type = $_POST['test_type'];
    $preferred_date = $_POST['preferred_date'];
    $amount = 1000;

    $stmt = $conn->prepare("INSERT INTO bookings (patient_id, test_type, status, preferred_date, created_at) VALUES (?, ?, 'Pending', ?, NOW())");
    $stmt->bind_param("iss", $patient_id, $test_type, $preferred_date);

    if ($stmt->execute()) {
        $booking_id = $stmt->insert_id;

        $bill_stmt = $conn->prepare("INSERT INTO billing (booking_id, amount, status, created_at) VALUES (?, ?, 'Pending', NOW())");
        $bill_stmt->bind_param("id", $booking_id, $amount);
        if ($bill_stmt->execute()) {
            $success = "Test booked successfully. Please proceed to payment.";
        } else {
            $error = "Booking successful, but failed to create billing.";
        }
        $bill_stmt->close();
    } else {
        $error = "Failed to book the test.";
    }
    $stmt->close();
}

// Handle appointment cancellation
if (isset($_GET['cancel_id'])) {
    $cancel_id = intval($_GET['cancel_id']);

    $check = $conn->prepare("SELECT id FROM bookings WHERE id = ? AND patient_id = ?");
    $check->bind_param("ii", $cancel_id, $patient_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $update = $conn->prepare("UPDATE bookings SET status = 'Cancelled' WHERE id = ?");
        $update->bind_param("i", $cancel_id);
        if ($update->execute()) {
            $success = "Appointment cancelled successfully.";
        } else {
            $error = "Failed to cancel the appointment.";
        }
        $update->close();
    } else {
        $error = "Invalid booking ID.";
    }
    $check->close();
}
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<main id="main" class="main">

  <div class="pagetitle">
    <h1>Book Lab Test</h1>
  </div>

  <section class="section dashboard">
    <div class="row">
      <div class="col-lg-8">

        <div class="card">
          <div class="card-body pt-4">

            <?php if ($success): ?>
              <div class="alert alert-success"><?= $success ?></div>
            <?php elseif ($error): ?>
              <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
              <div class="row mb-3">
                <label class="col-sm-4 col-form-label">Select Test Type</label>
                <div class="col-sm-8">
                  <select name="test_type" class="form-select" required>
                    <option value="">-- Choose Test --</option>
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
                <label class="col-sm-4 col-form-label">Preferred Date</label>
                <div class="col-sm-8">
                  <input type="date" name="preferred_date" class="form-control" required>
                </div>
              </div>

              <div class="text-center">
                <button type="submit" class="btn btn-primary">Book Test</button>
              </div>
            </form>
          </div>
        </div>

      </div>
    </div>

    <!-- Bookings Table -->
    <div class="card mt-4">
      <div class="card-body pt-3">
        <h5 class="card-title">My Test Bookings</h5>

        <table class="table datatable">
          <thead>
            <tr>
              <th>Test Type</th>
              <th>Preferred Date</th>
              <th>Status</th>
              <th>Payment</th>
              <th>Booked On</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $today = date('Y-m-d');
            $stmt = $conn->prepare("
              SELECT b.id, b.test_type, b.status, b.preferred_date, b.created_at, bl.status AS payment_status
              FROM bookings b
              LEFT JOIN billing bl ON bl.booking_id = b.id
              WHERE b.patient_id = ?
              ORDER BY b.created_at DESC
            ");
            $stmt->bind_param("i", $patient_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()):
            ?>
              <tr class="<?= ($row['preferred_date'] >= $today) ? 'table-success' : 'table-secondary' ?>">
                <td><?= htmlspecialchars($row['test_type']) ?></td>
                <td><?= htmlspecialchars($row['preferred_date']) ?></td>
                <td>
                  <span class="badge <?= $row['status'] === 'Cancelled' ? 'bg-danger' : 'bg-info' ?>">
                    <?= htmlspecialchars($row['status']) ?>
                  </span>
                </td>
                <td>
                  <?php if ($row['payment_status'] === 'Paid'): ?>
                    <span class="badge bg-success">Paid</span>
                  <?php else: ?>
                    <a href="pay.php" class="badge bg-warning text-dark">Pending</a><br>
                    <?php if ($row['status'] === 'Pending'): ?>
                      <a href="?cancel_id=<?= $row['id'] ?>" class="text-danger small" onclick="return confirm('Cancel this appointment?');">Cancel</a>
                    <?php endif; ?>
                  <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($row['created_at']) ?></td>
              </tr>
            <?php endwhile; $stmt->close(); ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</main>