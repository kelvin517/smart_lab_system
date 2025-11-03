<?php
// config/mpesa.php
// Daraja OAuth token helper (sandbox example).
// IMPORTANT: store keys outside webroot or in environment variables in production.

ini_set('display_errors', 1);
error_reporting(E_ALL);

// ==== Configuration - replace with your actual credentials or load from environment ====
$MPESA_ENV = 'sandbox'; // 'sandbox' or 'live'
$CONSUMER_KEY = 'Aoy6pCxgrseBtTuRMe98RX5MVEqGk2N9rn4ZNMfwRhyPjjY3';
$CONSUMER_SECRET = 'dXUEzhkAVwXA49PErj1PUXdXrqenS9LPPHEFsGzpDA6h6JL6uUoICGXkghhF5GGa';
$DEBUG = false; // set to false in production

$base = ($MPESA_ENV === 'live')
    ? 'https://api.safaricom.co.ke'
    : 'https://sandbox.safaricom.co.ke';

// OAuth endpoint
$token_url = $base . '/oauth/v1/generate?grant_type=client_credentials';

// build Authorization header
$credentials = base64_encode($CONSUMER_KEY . ':' . $CONSUMER_SECRET);
$headers = [
    "Authorization: Basic {$credentials}",
    "Accept: application/json"
];

$response = null;
$http_status = 0;

// -------------- Attempt cURL (preferred) --------------
if (function_exists('curl_init')) {
    $ch = curl_init($token_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // In production, set CURLOPT_SSL_VERIFYPEER = true and ensure CA bundle ok
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);

    if ($response === false) {
        $curlErr = curl_error($ch);
        $curlNo = curl_errno($ch);
        curl_close($ch);
        throw new \RuntimeException("cURL error ({$curlNo}): {$curlErr}");
    }

    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

} else {
    // -------------- Fallback to file_get_contents ----------
    if (ini_get('allow_url_fopen')) {
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers),
                'timeout' => 30
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ];
        $context = stream_context_create($opts);
        $response = @file_get_contents($token_url, false, $context);
        if ($response === false) {
            // try to get http response code from $http_response_header
            $http_status = 0;
            if (isset($http_response_header) && is_array($http_response_header)) {
                // parse status
                foreach ($http_response_header as $hdr) {
                    if (preg_match('#HTTP/\d+\.\d+\s+(\d+)#', $hdr, $m)) {
                        $http_status = (int)$m[1];
                        break;
                    }
                }
            }
            throw new \RuntimeException("file_get_contents() failed to fetch token. HTTP status: {$http_status}");
        }
        // try to parse status header as above
        if (isset($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $hdr) {
                if (preg_match('#HTTP/\d+\.\d+\s+(\d+)#', $hdr, $m)) {
                    $http_status = (int)$m[1];
                    break;
                }
            }
        }
    } else {
        // No cURL and allow_url_fopen disabled
        throw new \RuntimeException("Neither cURL is available nor allow_url_fopen is enabled. Install/enable cURL or set allow_url_fopen=On.");
    }
}

// -------------- Process response --------------
if ($http_status >= 400) {
    $msg = "Token endpoint returned HTTP status {$http_status}. Response: " . substr($response, 0, 400);
    throw new \RuntimeException($msg);
}

$payload = json_decode($response, true);
if (!is_array($payload) || !isset($payload['access_token'])) {
    $err = $response ?: 'empty response';
    throw new \RuntimeException("Invalid token response: " . substr($err, 0, 800));
}

// Got token
$access_token = $payload['access_token'];
$expires_in = $payload['expires_in'] ?? null;

// Debug output (disable in prod)
if ($DEBUG) {
    echo "Access Token: " . htmlspecialchars($access_token) . PHP_EOL;
    echo "Expires in: " . htmlspecialchars($expires_in) . PHP_EOL;
}

// Return token (if used as an include)
return [
    'access_token' => $access_token,
    'expires_in' => $expires_in,
    'raw' => $payload
];
