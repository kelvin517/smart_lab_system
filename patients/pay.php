<?php
session_start();
include '../config/db.php';
include '../config/mpesa_functions.php'; // contains Daraja STK push helper
include '../includes/mpesa.php';//contains mpesa config
if (!isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit;
}

$patient_id = $_SESSION['patient_id'];
$success = $error = '';

// Get the latest unpaid booking
$stmt = $conn->prepare("
    SELECT b.id AS booking_id, bl.id AS billing_id, bl.amount 
    FROM bookings b 
    INNER JOIN billing bl ON bl.booking_id = b.id 
    WHERE b.patient_id = ? AND bl.status = 'Pending'
    ORDER BY b.created_at DESC 
    LIMIT 1
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
$stmt->close();

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $booking) {
    $payment_method = $_POST['payment_method'];

    if ($payment_method === 'mpesa') {
        // M-PESA STK Push
        $phone = trim($_POST['phone']);
        if (empty($phone)) {
            $error = "Please enter your M-PESA phone number.";
        } else {
            $response = initiateStkPush($phone, $booking['amount'], $booking['billing_id']);
            if ($response && isset($response['CheckoutRequestID'])) {
                $checkoutRequestID = $response['CheckoutRequestID'];

                // Store the checkout request ID for callback tracking
                $update = $conn->prepare("
                    UPDATE billing 
                    SET checkout_request_id = ?, payment_method = 'M-PESA', updated_at = NOW()
                    WHERE id = ?
                ");
                $update->bind_param("si", $checkoutRequestID, $booking['billing_id']);
                if ($update->execute()) {
                    $success = "STK Push initiated. Please check your phone and enter M-PESA PIN to complete payment.";
                } else {
                    $error = "Database update failed: " . $conn->error;
                }
                $update->close();
            } else {
                $error = "Failed to initiate M-PESA payment. Please try again.";
            }
        }

    } elseif ($payment_method === 'cash') {
        // Manual cash payment (simulate)
        $update = $conn->prepare("
            UPDATE billing 
            SET status = 'Paid', payment_method = 'Cash', updated_at = NOW()
            WHERE id = ?
        ");
        $update->bind_param("i", $booking['billing_id']);
        if ($update->execute()) {
            $success = "Payment recorded successfully as Cash.";
        } else {
            $error = "Failed to update payment record.";
        }
        $update->close();

    } else {
        $error = "Invalid payment method selected.";
    }
}
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<main id="main" class="main">
  <div class="pagetitle">
    <h1>Make Payment</h1>
  </div>

  <section class="section">
    <div class="card p-4">

      <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
      <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <?php if (!empty($booking) && !$success): ?>
        <h5 class="mb-3">Booking ID: <?= htmlspecialchars($booking['booking_id']) ?></h5>
        <p>Amount Due: <strong>KES <?= number_format((float)$booking['amount'], 2) ?></strong></p>

        <form method="POST">
          <div class="mb-3">
            <label for="payment_method" class="form-label">Select Payment Method</label>
            <select name="payment_method" id="payment_method" class="form-select" required>
              <option value="">-- Select Payment Method --</option>
              <option value="mpesa">M-PESA (STK Push)</option>
              <option value="cash">Cash (Manual Payment)</option>
            </select>
          </div>

          <div id="mpesa-fields" style="display:none;">
            <div class="mb-3">
              <label for="phone" class="form-label">M-PESA Phone Number (e.g. 2547XXXXXXXX)</label>
              <input type="text" name="phone" id="phone" class="form-control" placeholder="Enter your M-PESA number">
            </div>
          </div>

          <button type="submit" class="btn btn-success">Proceed to Pay</button>
          <a href="book_test.php" class="btn btn-secondary ms-2">Back</a>
        </form>
      <?php endif; ?>

    </div>
  </section>
</main>

<script>
  document.getElementById('payment_method').addEventListener('change', function() {
    const mpesaFields = document.getElementById('mpesa-fields');
    mpesaFields.style.display = (this.value === 'mpesa') ? 'block' : 'none';
  });
</script>
