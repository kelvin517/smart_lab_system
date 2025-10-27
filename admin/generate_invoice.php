<?php
include '../config/db.php';

if (!isset($_GET['id'])) {
    die("Invalid request.");
}

$bill_id = intval($_GET['id']);
$query = "SELECT b.*, u.full_name, u.email, bk.test_type 
          FROM billing b 
          JOIN bookings bk ON b.booking_id = bk.id
          JOIN users u ON bk.patient_id = u.id
          WHERE b.id = $bill_id";
$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    die("Invoice not found.");
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Invoice #<?= $bill_id ?></title>
  <style>
    body { font-family: Arial; margin: 40px; }
    h2 { text-align: center; }
    table { width: 60%; margin: auto; border-collapse: collapse; }
    th, td { padding: 10px; border: 1px solid #999; text-align: left; }
    .total { font-weight: bold; }
  </style>
</head>
<body>

<h2>Smart Laboratory Invoice</h2>

<p><strong>Invoice ID:</strong> <?= $data['id'] ?></p>
<p><strong>Patient:</strong> <?= $data['full_name'] ?> (<?= $data['email'] ?>)</p>
<p><strong>Test Type:</strong> <?= $data['test_type'] ?></p>
<p><strong>Date Issued:</strong> <?= $data['created_at'] ?></p>

<table>
  <tr><th>Description</th><th>Amount (KES)</th></tr>
  <tr><td><?= $data['test_type'] ?> Test</td><td><?= $data['amount'] ?></td></tr>
  <tr><td class="total">Total</td><td class="total">KES <?= $data['amount'] ?></td></tr>
</table>

<br>
<p style="text-align: center;"><button onclick="window.print()">Print Invoice</button></p>

</body>
</html>