<?php
// patients/mpesa_callback.php
include '../config/db.php';

$data = file_get_contents('php://input');
$logFile = "../logs/mpesa_callback_" . date("Ymd_His") . ".json";
file_put_contents($logFile, $data);

$response = json_decode($data, true);
$resultCode = $response['Body']['stkCallback']['ResultCode'] ?? null;

if ($resultCode == 0) {
    $mpesaReceipt = $response['Body']['stkCallback']['CallbackMetadata']['Item'][1]['Value'];
    $amount = $response['Body']['stkCallback']['CallbackMetadata']['Item'][0]['Value'];
    $phone = $response['Body']['stkCallback']['CallbackMetadata']['Item'][4]['Value'];

    // Save to database
    $stmt = $conn->prepare("INSERT INTO billing (booking_id, amount, status, payment_method, date_paid, created_at) VALUES (?, ?, 'Paid', 'Mpesa', NOW(), NOW())");
    $stmt->bind_param("id", $booking_id, $amount);
    $stmt->execute();
}
