<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include '../config/db.php';

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("Invalid request.");
}

$bill_id = intval($_GET['id']);

// Fetch invoice data with proper error handling
$query = "SELECT 
            b.*, 
            p.full_name, 
            p.email, 
            p.phone,
            p.address,
            bk.test_name,
            bk.preferred_date,
            s.full_name as doctor_name
          FROM billing b 
          JOIN bookings bk ON b.booking_id = bk.id
          JOIN patients p ON bk.patient_id = p.patient_id
          LEFT JOIN staff s ON bk.doctor_id = s.id
          WHERE b.id = ?";
          
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $bill_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    die("Invoice not found.");
}

// Calculate due date (7 days from creation)
$due_date = date('Y-m-d', strtotime($data['created_at'] . ' +7 days'));

// Format dates
$issue_date = date('F j, Y', strtotime($data['created_at']));
$due_date_formatted = date('F j, Y', strtotime($due_date));
$appointment_date = date('F j, Y', strtotime($data['preferred_date']));
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Invoice #<?= $bill_id ?> - Smart Laboratory System</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Inter', sans-serif;
      line-height: 1.4;
      color: #333;
      background: white;
      padding: 0;
      font-size: 12px;
    }
    
    .invoice-container {
      width: 210mm;
      height: 297mm;
      margin: 0 auto;
      background: white;
      padding: 15mm;
      position: relative;
    }
    
    .invoice-header {
      background: linear-gradient(135deg, #2c3e50, #3498db);
      color: white;
      padding: 20px 30px;
      text-align: center;
      margin: -15mm -15mm 20px -15mm;
      border-radius: 0;
    }
    
    .invoice-header h1 {
      font-size: 28px;
      font-weight: 700;
      margin-bottom: 5px;
    }
    
    .invoice-header .subtitle {
      font-size: 13px;
      opacity: 0.9;
    }
    
    .invoice-body {
      padding: 0;
    }
    
    .company-info {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin-bottom: 25px;
      padding-bottom: 20px;
      border-bottom: 1px solid #e9ecef;
    }
    
    .info-card {
      background: #f8f9fa;
      padding: 15px;
      border-radius: 8px;
      border-left: 3px solid #3498db;
      font-size: 11px;
    }
    
    .info-card h3 {
      color: #2c3e50;
      margin-bottom: 10px;
      font-size: 13px;
      font-weight: 600;
    }
    
    .invoice-details {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 20px;
      margin-bottom: 20px;
    }
    
    .invoice-meta {
      background: #f8f9fa;
      padding: 15px;
      border-radius: 8px;
      font-size: 11px;
    }
    
    .invoice-table {
      width: 100%;
      border-collapse: collapse;
      margin: 20px 0;
      font-size: 11px;
    }
    
    .invoice-table th {
      background: #2c3e50;
      color: white;
      padding: 10px 12px;
      text-align: left;
      font-weight: 600;
      font-size: 11px;
    }
    
    .invoice-table td {
      padding: 10px 12px;
      border-bottom: 1px solid #e9ecef;
    }
    
    .invoice-table tr:last-child td {
      border-bottom: none;
    }
    
    .total-row {
      background: #f8f9fa;
      font-weight: 600;
    }
    
    .total-row td {
      font-size: 12px;
      color: #2c3e50;
    }
    
    .amount {
      text-align: right;
      font-weight: 500;
    }
    
    .status-badge {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 15px;
      font-size: 10px;
      font-weight: 600;
      text-transform: uppercase;
    }
    
    .status-paid {
      background: #d4edda;
      color: #155724;
    }
    
    .status-pending {
      background: #fff3cd;
      color: #856404;
    }
    
    .status-cancelled {
      background: #f8d7da;
      color: #721c24;
    }
    
    .payment-info {
      background: #e8f4f8;
      padding: 15px;
      border-radius: 8px;
      margin-top: 20px;
      border-left: 3px solid #3498db;
      font-size: 11px;
    }
    
    .payment-info h4 {
      color: #2c3e50;
      margin-bottom: 10px;
      font-weight: 600;
      font-size: 13px;
    }
    
    .terms-section {
      margin-top: 20px;
      padding: 15px;
      background: #f8f9fa;
      border-radius: 8px;
      font-size: 10px;
    }
    
    .terms-section h4 {
      color: #2c3e50;
      margin-bottom: 8px;
      font-weight: 600;
      font-size: 12px;
    }
    
    .terms-section ul {
      margin-left: 15px;
    }
    
    .terms-section li {
      margin-bottom: 3px;
    }
    
    .invoice-footer {
      position: absolute;
      bottom: 15mm;
      left: 15mm;
      right: 15mm;
      text-align: center;
      padding-top: 15px;
      border-top: 1px solid #e9ecef;
      font-size: 10px;
      color: #666;
    }
    
    .action-buttons {
      display: flex;
      gap: 10px;
      justify-content: center;
      margin-top: 15px;
    }
    
    .btn {
      padding: 8px 15px;
      border: none;
      border-radius: 5px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      font-size: 11px;
    }
    
    .btn-primary {
      background: #3498db;
      color: white;
    }
    
    .btn-primary:hover {
      background: #2980b9;
    }
    
    .btn-secondary {
      background: #6c757d;
      color: white;
    }
    
    .btn-secondary:hover {
      background: #5a6268;
    }
    
    .print-only {
      display: none;
    }
    
    @media print {
      body {
        background: white;
        padding: 0;
        margin: 0;
      }
      
      .invoice-container {
        box-shadow: none;
        padding: 15mm;
        height: auto;
        min-height: 297mm;
      }
      
      .invoice-header {
        margin: -15mm -15mm 20px -15mm;
      }
      
      .action-buttons {
        display: none;
      }
      
      .print-only {
        display: block;
      }
      
      .no-print {
        display: none;
      }
    }
    
    @media screen {
      body {
        background: #f8f9fa;
        padding: 20px;
      }
      
      .invoice-container {
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        border-radius: 10px;
        overflow: hidden;
      }
      
      .invoice-header {
        border-radius: 10px 10px 0 0;
      }
    }
    
    @media (max-width: 768px) {
      body {
        padding: 10px;
      }
      
      .invoice-container {
        width: 100%;
        height: auto;
        padding: 20px;
      }
      
      .company-info,
      .invoice-details {
        grid-template-columns: 1fr;
      }
      
      .invoice-header {
        margin: -20px -20px 20px -20px;
      }
      
      .action-buttons {
        flex-direction: column;
      }
    }
    
    /* Ensure everything fits on one page */
    .page-break {
      page-break-after: always;
    }
    
    .avoid-break {
      page-break-inside: avoid;
    }
  </style>
</head>
<body>
  <div class="invoice-container">
    <!-- Header -->
    <div class="invoice-header">
      <h1>INVOICE</h1>
      <p class="subtitle">Smart Laboratory System - Professional Diagnostic Services</p>
    </div>
    
    <!-- Body -->
    <div class="invoice-body">
      <!-- Company & Patient Info -->
      <div class="company-info avoid-break">
        <div class="info-card">
          <h3>Laboratory Information</h3>
          <p><strong>Smart Laboratory System</strong></p>
          <p>123 Healthcare Avenue, Nairobi</p>
          <p>Phone: +254 700 123 456</p>
          <p>Email: info@smartlab.co.ke</p>
        </div>
        
        <div class="info-card">
          <h3>Bill To</h3>
          <p><strong><?= htmlspecialchars($data['full_name']) ?></strong></p>
          <p>Email: <?= htmlspecialchars($data['email']) ?></p>
          <p>Phone: <?= htmlspecialchars($data['phone'] ?? 'N/A') ?></p>
        </div>
      </div>
      
      <!-- Invoice Details -->
      <div class="invoice-details avoid-break">
        <div>
          <h3 style="color: #2c3e50; margin-bottom: 10px; font-size: 13px;">Service Details</h3>
          <p><strong>Test Type:</strong> <?= htmlspecialchars($data['test_name']) ?></p>
          <p><strong>Appointment Date:</strong> <?= $appointment_date ?></p>
          <?php if (!empty($data['doctor_name'])): ?>
          <p><strong>Doctor:</strong> Dr. <?= htmlspecialchars($data['doctor_name']) ?></p>
          <?php endif; ?>
        </div>
        
        <div class="invoice-meta">
          <p><strong>Invoice #:</strong> INV-<?= str_pad($data['id'], 6, '0', STR_PAD_LEFT) ?></p>
          <p><strong>Issue Date:</strong> <?= $issue_date ?></p>
          <p><strong>Due Date:</strong> <?= $due_date_formatted ?></p>
          <p><strong>Status:</strong> 
            <span class="status-badge status-<?= $data['status'] ?>">
              <?= ucfirst($data['status']) ?>
            </span>
          </p>
        </div>
      </div>
      
      <!-- Invoice Table -->
      <div class="avoid-break">
        <table class="invoice-table">
          <thead>
            <tr>
              <th>Description</th>
              <th>Qty</th>
              <th>Unit Price (KES)</th>
              <th>Amount (KES)</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>
                <strong><?= htmlspecialchars($data['test_name']) ?> Test</strong>
                <br>
                <small>Laboratory diagnostic analysis</small>
              </td>
              <td>1</td>
              <td class="amount"><?= number_format($data['amount'], 2) ?></td>
              <td class="amount"><?= number_format($data['amount'], 2) ?></td>
            </tr>
            
            <tr>
              <td>Professional Service Fee</td>
              <td>1</td>
              <td class="amount">0.00</td>
              <td class="amount">0.00</td>
            </tr>
            
            <tr class="total-row">
              <td colspan="3" style="text-align: right; padding-right: 20px;"><strong>Total Amount:</strong></td>
              <td class="amount"><strong>KES <?= number_format($data['amount'], 2) ?></strong></td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Payment Information -->
      <div class="payment-info avoid-break">
        <h4>Payment Instructions</h4>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
          <div>
            <p><strong>Bank Transfer:</strong></p>
            <p>Bank: KCB</p>
            <p>Account: Smart Laboratory</p>
            <p>A/C No: 1234567890</p>
          </div>
          <div>
            <p><strong>Mobile Money:</strong></p>
            <p>Paybill: 123456</p>
            <p>Account: INV-<?= str_pad($data['id'], 6, '0', STR_PAD_LEFT) ?></p>
          </div>
        </div>
      </div>
      
      <!-- Terms & Conditions -->
      <div class="terms-section avoid-break">
        <h4>Terms & Conditions</h4>
        <ul>
          <li>Payment due within 7 days of invoice date</li>
          <li>Late payments subject to 5% monthly fee</li>
          <li>Results available within 24-48 hours</li>
          <li>Contact: accounts@smartlab.co.ke</li>
        </ul>
      </div>
    </div>
    
    <!-- Footer -->
    <div class="invoice-footer">
      <p>&copy; <?= date('Y') ?> Smart Laboratory System. All rights reserved.</p>
      <p class="print-only">Generated on: <?= date('F j, Y \a\t g:i A') ?></p>
      
      <div class="action-buttons no-print">
        <button class="btn btn-primary" onclick="window.print()">
          <i class="fas fa-print"></i> Print Invoice
        </button>
        <a href="billing.php" class="btn btn-secondary">
          <i class="fas fa-arrow-left"></i> Back
        </a>
      </div>
    </div>
  </div>

  <script>
    // Auto-print if print parameter is set
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('print') === '1') {
      window.print();
    }
    
    // Ensure proper printing
    window.addEventListener('beforeprint', function() {
      document.body.style.zoom = '100%';
    });
  </script>
  
  <!-- Font Awesome for icons -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>