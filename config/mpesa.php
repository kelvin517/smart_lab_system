<?php
function initiateSTKPush($phone, $amount, $booking_id) {
    $consumerKey = 'YOUR_CONSUMER_KEY';
    $consumerSecret = 'YOUR_CONSUMER_SECRET';
    $shortcode = '174379'; // test shortcode
    $passkey = 'YOUR_PASSKEY';
    $callbackUrl = 'https://yourdomain.com/smart_lab_system/config/callback_url.php';

    // 1. Generate access token
    $credentials = base64_encode("$consumerKey:$consumerSecret");
    $ch = curl_init('https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (!isset($response['access_token'])) {
        return ['success' => false, 'message' => 'Failed to get access token'];
    }

    $accessToken = $response['access_token'];

    // 2. Initiate STK Push
    $timestamp = date('YmdHis');
    $password = base64_encode($shortcode . $passkey . $timestamp);

    $stkData = [
        'BusinessShortCode' => $shortcode,
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => $amount,
        'PartyA' => $phone,
        'PartyB' => $shortcode,
        'PhoneNumber' => $phone,
        'CallBackURL' => $callbackUrl,
        'AccountReference' => 'Booking ' . $booking_id,
        'TransactionDesc' => 'Smart Lab Test Payment'
    ];

    $ch = curl_init('https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($stkData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (isset($result['ResponseCode']) && $result['ResponseCode'] == '0') {
        return ['success' => true, 'message' => 'STK Push successful'];
    } else {
        return ['success' => false, 'message' => $result['errorMessage'] ?? 'Unknown error'];
    }
}
?>
