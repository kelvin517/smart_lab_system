<?php
// config/confirm_payment.php
require_once __DIR__ . '/mpesa_config.php';
require_once __DIR__ . '/mpesa_functions.php';
require_once __DIR__ . '/../config/db.php'; // DB connection; adjust path

// Read raw POST body
$body = file_get_contents('php://input');
mpesa_log("Callback received: $body");

// respond immediately 200 to Safaricom (they expect 200)
http_response_code(200);
header('Content-Type: application/json');

// Parse the JSON
$data = json_decode($body, true);
if (!$data) {
    echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Invalid JSON']);
    exit;
}

// Daraja sends structure under "Body" => "stkCallback" for STK Push
if (isset($data['Body']['stkCallback'])) {
    $stk = $data['Body']['stkCallback'];
    $merchantRequestID = $stk['MerchantRequestID'] ?? null;
    $checkoutRequestID = $stk['CheckoutRequestID'] ?? null;
    $resultCode = $stk['ResultCode'] ?? 1;
    $resultDesc = $stk['ResultDesc'] ?? '';

    // Default fields
    $amount = null;
    $mpesaReceiptNumber = null;
    $phoneNumber = null;
    $transactionDate = null;

    if (isset($stk['CallbackMetadata']['Item']) && is_array($stk['CallbackMetadata']['Item'])) {
        foreach ($stk['CallbackMetadata']['Item'] as $item) {
            if ($item['Name'] === 'Amount') $amount = $item['Value'];
            if ($item['Name'] === 'MpesaReceiptNumber') $mpesaReceiptNumber = $item['Value'];
            if ($item['Name'] === 'PhoneNumber') $phoneNumber = $item['Value'];
            if ($item['Name'] === 'TransactionDate') $transactionDate = $item['Value'];
        }
    }

    // Persist to DB (example) - adapt table/column names to your schema
    try {
        // Use bookings->id from AccountReference mapping if you encode booking id in AccountReference.
        // Example: if AccountReference = "BOOKING-12", parse booking id 12.
        $accountRef = $stk['MerchantRequestID'] ?? ''; // sometimes AccountReference is elsewhere; check your request mapping
        // Basic attempt to read booking id from AccountReference if you set it that way
        $booking_id = null;
        if (isset($data['Body']['stkCallback']['CallbackMetadata'])) {
            // no accountRef location guaranteed — adapt as needed
        }

        $stmt = $conn->prepare("INSERT INTO mpesa_transactions (merchant_request_id, checkout_request_id, result_code, result_desc, amount, mpesa_receipt, phone, transaction_date, raw_json, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $raw = $body;
        $stmt->bind_param("ssisssiss", $merchantRequestID, $checkoutRequestID, $resultCode, $resultDesc, $amount, $mpesaReceiptNumber, $phoneNumber, $transactionDate, $raw);
        $stmt->execute();
        $stmt->close();

        // If success, update billing table by locating billing row using checkoutRequestID/merchantRequestID or booking id
        if ($resultCode == 0) {
            // Example: mark billing as paid where checkout_request_id matches (you should store checkout_request_id when initiating STK)
            $u = $conn->prepare("UPDATE billing SET status = 'Paid', mpesa_receipt = ?, paid_on = NOW() WHERE checkout_request_id = ?");
            $u->bind_param("ss", $mpesaReceiptNumber, $checkoutRequestID);
            $u->execute();
            $u->close();
        }

    } catch (Exception $e) {
        mpesa_log("DB error in confirm: " . $e->getMessage());
    }

    echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    exit;
}

// default
echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'No stkCallback found']);
