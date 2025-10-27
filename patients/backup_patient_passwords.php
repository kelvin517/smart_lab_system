<?php
// backup_patient_passwords.php
require_once '../config/db.php';

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="patient_password_backup_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

// Column headers
fputcsv($output, ['ID', 'Full Name', 'Email', 'Phone', 'Password Hash', 'Created At']);

// Fetch data from patients table
$sql = "SELECT id, full_name, email, phone, password, created_at FROM patients ORDER BY id";
$result = $conn->query($sql);

// Output each row
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
} else {
    fputcsv($output, ['No data found']);
}

fclose($output);
exit;