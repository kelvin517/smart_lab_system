<?php
// patients/mpesa_api.php
header('Content-Type: application/json');

$config = include('../config/mpesa_config.php');

// Fetch token
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Basic ' . base64_encode($config['consumer_key'] . ':' . $config['consumer_secret'])
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response);
$access_token = $result->access_token ?? null;

if (!$access_token) {
    echo json_encode(['error' => 'Failed to get access token']);
    exit;
}

// Payment details
$phone = $_POST['phone']; // phone from form
$amount = $_POST['amount'];
$booking_id = $_POST['booking_id'];

$timestamp = date('YmdHis');
$password = base64_encode($config['shortcode'] . $config['passkey'] . $timestamp);

$data = [
    'BusinessShortCode' => $config['shortcode'],
    'Password' => $password,
    'Timestamp' => $timestamp,
    'TransactionType' => 'CustomerPayBillOnline',
    'Amount' => (int)$amount,
    'PartyA' => $phone,
    'PartyB' => $config['shortcode'],
    'PhoneNumber' => $phone,
    'CallBackURL' => $config['callback_url'],
    'AccountReference' => 'SmartLab',
    'TransactionDesc' => 'Lab Test Payment',
];

$ch2 = curl_init('https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest');
curl_setopt($ch2, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $access_token
]);
curl_setopt($ch2, CURLOPT_POST, true);
curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
$response2 = curl_exec($ch2);
curl_close($ch2);

echo $response2;
