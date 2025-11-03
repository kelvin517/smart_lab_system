<?php
// patients/pay.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include '../config/db.php';
include '../config/mpesa.php';

if (!isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit;
}

$patient_id = (int) $_SESSION['patient_id'];
$success = $error = '';

if (!isset($_GET['booking_id']) || !is_numeric($_GET['booking_id'])) {
    $error = "Invalid booking ID.";
    $booking = null;
    $billing = null;
} else {
    $booking_id = (int) $_GET['booking_id'];

    $stmt = $conn->prepare("SELECT id, patient_id, test_name, preferred_date, status FROM bookings WHERE id = ? AND patient_id = ?");
    if (!$stmt) {
        $error = "Database error: " . $conn->error;
    } else {
        $stmt->bind_param("ii", $booking_id, $patient_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $booking = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        if (!$booking) {
            $error = "Booking not found or not authorized.";
        } else {
            $bstmt = $conn->prepare("SELECT id, amount, status, payment_method, date_paid FROM billing WHERE booking_id = ? ORDER BY id DESC LIMIT 1");
            $bstmt->bind_param("i", $booking_id);
            $bstmt->execute();
            $bres = $bstmt->get_result();
            $billing = $bres ? $bres->fetch_assoc() : null;
            $bstmt->close();
        }
    }
}

// Simulated / Cash POST handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_method'])) {
    $method = $_POST['payment_method'];
    if (in_array($method, ['Cash', 'Simulated'])) {
        $amount = (float)($_POST['amount'] ?? 0);
        $payment_method = $method;

        if (!empty($billing)) {
            $update = $conn->prepare("UPDATE billing SET status='Paid', payment_method=?, amount=?, date_paid=NOW() WHERE id=?");
            $update->bind_param("sdi", $payment_method, $amount, $billing['id']);
            $update->execute();
            $success = "Payment marked as paid via $payment_method.";
        } else {
            $insert = $conn->prepare("INSERT INTO billing (booking_id, amount, status, payment_method, date_paid, created_at) VALUES (?, ?, 'Paid', ?, NOW(), NOW())");
            $insert->bind_param("ids", $booking_id, $amount, $payment_method);
            $insert->execute();
            $success = "Payment successful ($payment_method).";
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Make Payment — SmartLab</title>
  <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <script src="https://checkout.flutterwave.com/v3.js"></script>
  <style>
    body { background:#f5f7fa; font-family: Arial, Helvetica, sans-serif; }
    .container { max-width: 800px; margin: 30px auto; }
  </style>
</head>
<body>
<div class="container">
  <h3>Make Payment</h3>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <?php if (!empty($booking)): ?>
    <div class="card mb-3">
      <div class="card-body">
        <p><strong>Booking ID:</strong> <?= htmlspecialchars($booking['id']) ?></p>
        <p><strong>Test:</strong> <?= htmlspecialchars($booking['test_name']) ?></p>
        <p><strong>Preferred Date:</strong> <?= htmlspecialchars($booking['preferred_date']) ?></p>
        <p><strong>Status:</strong> <?= htmlspecialchars($booking['status']) ?></p>
      </div>
    </div>

    <?php
      $display_amount = !empty($billing['amount']) ? (float)$billing['amount'] : 1000.00;
    ?>

    <div class="card mb-3">
      <div class="card-body">
        <h5>Payment Options</h5>
        <form id="paymentForm" method="POST" class="row g-3">
          <input type="hidden" name="booking_id" id="booking_id" value="<?= $booking['id'] ?>">

          <div class="col-md-6">
            <label class="form-label">Payment Method</label>
            <select name="payment_method" id="payment_method" class="form-select" required>
              <option value="">Select method</option>
              <option value="Mpesa">Mpesa (STK Push)</option>
              <option value="Card">Card (Flutterwave)</option>
              <option value="Cash">Cash (Manual)</option>
              <option value="Simulated">Simulated</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Amount (KES)</label>
            <input type="number" step="0.01" name="amount" id="amount" class="form-control" value="<?= number_format($display_amount, 2, '.', '') ?>">
          </div>

          <div class="col-md-12" id="mpesa_phone_field" style="display:none;">
            <label class="form-label">Phone Number (07XXXXXXXX)</label>
            <input type="text" name="phone" id="phone" class="form-control" placeholder="07XXXXXXXX">
          </div>

          <div class="col-12">
            <button type="button" id="payNowBtn" class="btn btn-success"><i class="bi bi-check-circle"></i> Pay Now</button>
            <a href="book_test.php" class="btn btn-secondary ms-2">Back</a>
          </div>
        </form>
      </div>
    </div>
  <?php endif; ?>
</div>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
const payNowBtn = document.getElementById('payNowBtn');
const methodSelect = document.getElementById('payment_method');
const phoneField = document.getElementById('mpesa_phone_field');
const flutterwaveKey = "FLWPUBK_TEST-xxxxxxxxxxxxxxxxxxxxx-X"; // Replace with your Flutterwave public key

methodSelect.addEventListener('change', function() {
  phoneField.style.display = this.value === 'Mpesa' ? 'block' : 'none';
});

payNowBtn.addEventListener('click', function() {
  const method = methodSelect.value;
  const formData = new FormData(document.getElementById('paymentForm'));
  const amount = document.getElementById('amount').value;
  const phone = document.getElementById('phone')?.value;
  const booking_id = document.getElementById('booking_id').value;

  if (!method) {
    alert("Please select a payment method.");
    return;
  }

  if (method === 'Mpesa') {
    if (!phone || !phone.startsWith("07")) {
      alert("Enter a valid Mpesa phone number.");
      return;
    }
    fetch('mpesa_api.php', {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.ResponseDescription) {
        alert('Check your phone to complete the payment.');
      } else {
        alert('Error: ' + JSON.stringify(data));
      }
    });
  }
  else if (method === 'Card') {
    FlutterwaveCheckout({
      public_key: flutterwaveKey,
      tx_ref: "SmartLab-" + Date.now(),
      amount: parseFloat(amount),
      currency: "KES",
      payment_options: "card",
      customer: {
        email: "<?= $_SESSION['patient_email'] ?? 'test@smartlab.com' ?>",
        phone_number: phone || "0712345678",
        name: "<?= $_SESSION['patient_name'] ?? 'SmartLab Patient' ?>",
      },
      callback: function (response) {
        if (response.status === "successful") {
          alert("Payment successful!");
          fetch("update_payment.php", {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded"},
            body: "booking_id=" + booking_id + "&amount=" + amount + "&method=Card"
          });
        } else {
          alert("Payment failed or cancelled.");
        }
      },
      customizations: {
        title: "Smart Laboratory System",
        description: "Lab Test Payment",
        logo: "../assets/img/logo.png",
      },
    });
  }
  else {
    // Cash / Simulated
    document.getElementById('paymentForm').submit();
  }
});
</script>
</body>
</html>
