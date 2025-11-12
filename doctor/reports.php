<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['doctor_id'])) {
    header("Location: doctor_login.php");
    exit;
}

// Excel Export (using PhpSpreadsheet)
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    require '../vendor/autoload.php';
    //use PhpOffice\PhpSpreadsheet\Spreadsheet;
    //use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Headers
    $sheet->setCellValue('A1', 'Patient Name');
    $sheet->setCellValue('B1', 'Test Name');
    $sheet->setCellValue('C1', 'Technician');
    $sheet->setCellValue('D1', 'Result Summary');
    $sheet->setCellValue('E1', 'Medication');
    $sheet->setCellValue('F1', 'Diagnosis Date');

    // Data
    $query = "
      SELECT 
        p.full_name AS patient_name,
        b.test_name,
        t.full_name AS technician_name,
        d.result_summary,
        d.medication,
        DATE(d.created_at) AS diagnosis_date
      FROM diagnosis d
      JOIN bookings b ON d.booking_id = b.id
      JOIN patients p ON b.patient_id = p.id
      LEFT JOIN technicians t ON b.uploaded_by = t.technician_id
      ORDER BY d.created_at DESC
    ";

    $result = $conn->query($query);
    $rowCount = 2;

    while ($row = $result->fetch_assoc()) {
        $sheet->setCellValue('A' . $rowCount, $row['patient_name']);
        $sheet->setCellValue('B' . $rowCount, $row['test_name']);
        $sheet->setCellValue('C' . $rowCount, $row['technician_name']);
        $sheet->setCellValue('D' . $rowCount, $row['result_summary']);
        $sheet->setCellValue('E' . $rowCount, $row['medication']);
        $sheet->setCellValue('F' . $rowCount, $row['diagnosis_date']);
        $rowCount++;
    }

    $writer = new Xlsx($spreadsheet);
    $filename = "SmartLab_Reports_" . date('Ymd') . ".xlsx";
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment; filename=\"$filename\"");
    $writer->save("php://output");
    exit;
}

// PDF Export (using FPDF)
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    require('../fpdf/fpdf.php');

    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'Smart Laboratory - Test & Diagnosis Report', 0, 1, 'C');
    $pdf->Ln(5);

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(40, 10, 'Patient', 1);
    $pdf->Cell(30, 10, 'Test', 1);
    $pdf->Cell(30, 10, 'Technician', 1);
    $pdf->Cell(50, 10, 'Result Summary', 1);
    $pdf->Cell(40, 10, 'Diagnosis Date', 1);
    $pdf->Ln();

    $query = "
      SELECT 
        p.full_name AS patient_name,
        b.test_name,
        t.full_name AS technician_name,
        d.result_summary,
        DATE(d.created_at) AS diagnosis_date
      FROM diagnosis d
      JOIN bookings b ON d.booking_id = b.id
      JOIN patients p ON b.patient_id = p.id
      LEFT JOIN technicians t ON b.uploaded_by = t.technician_id
      ORDER BY d.created_at DESC
    ";

    $result = $conn->query($query);
    $pdf->SetFont('Arial', '', 9);

    while ($row = $result->fetch_assoc()) {
        $pdf->Cell(40, 10, $row['patient_name'], 1);
        $pdf->Cell(30, 10, $row['test_name'], 1);
        $pdf->Cell(30, 10, $row['technician_name'], 1);
        $pdf->Cell(50, 10, substr($row['result_summary'], 0, 35) . '...', 1);
        $pdf->Cell(40, 10, $row['diagnosis_date'], 1);
        $pdf->Ln();
    }

    $pdf->Output("D", "SmartLab_Reports_" . date('Ymd') . ".pdf");
    exit;
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<main id="main" class="main">
  <div class="pagetitle">
    <h1>Reports</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Reports</li>
      </ol>
    </nav>
  </div>

  <section class="section">
    <div class="card">
      <div class="card-body pt-4">
        <h5 class="card-title">Test and Diagnosis Reports</h5>

        <!-- Filter Form -->
        <form method="GET" class="row g-3 mb-4">
          <div class="col-md-3">
            <label class="form-label">From Date</label>
            <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($_GET['start_date'] ?? '') ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">To Date</label>
            <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($_GET['end_date'] ?? '') ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Technician</label>
            <select name="technician" class="form-select">
              <option value="">All</option>
              <?php
              $techQuery = $conn->query("SELECT technician_id, full_name FROM technicians ORDER BY full_name ASC");
              while ($tech = $techQuery->fetch_assoc()):
                $selected = ($_GET['technician'] ?? '') == $tech['technician_id'] ? 'selected' : '';
                echo "<option value='{$tech['technician_id']}' $selected>{$tech['full_name']}</option>";
              endwhile;
              ?>
            </select>
          </div>
          <div class="col-md-3 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">
              <i class="bi bi-filter"></i> Filter
            </button>
          </div>
        </form>

        <!-- Reports Table -->
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Patient</th>
              <th>Test Name</th>
              <th>Technician</th>
              <th>Result Summary</th>
              <th>Medication</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $query = "
              SELECT 
                p.full_name AS patient_name,
                b.test_name,
                t.full_name AS technician_name,
                d.result_summary,
                d.medication,
                DATE(d.created_at) AS diagnosis_date
              FROM diagnosis d
              JOIN bookings b ON d.booking_id = b.id
              JOIN patients p ON b.patient_id = p.id
              LEFT JOIN technicians t ON b.uploaded_by = t.technician_id
              WHERE 1=1
            ";

            if (!empty($_GET['start_date'])) {
              $start = $conn->real_escape_string($_GET['start_date']);
              $query .= " AND DATE(d.created_at) >= '$start'";
            }
            if (!empty($_GET['end_date'])) {
              $end = $conn->real_escape_string($_GET['end_date']);
              $query .= " AND DATE(d.created_at) <= '$end'";
            }
            if (!empty($_GET['technician'])) {
              $tech = (int)$_GET['technician'];
              $query .= " AND t.technician_id = $tech";
            }

            $query .= " ORDER BY d.created_at DESC";
            $result = $conn->query($query);

            if ($result && $result->num_rows > 0):
              $count = 1;
              while ($row = $result->fetch_assoc()):
            ?>
                <tr>
                  <td><?= $count++ ?></td>
                  <td><?= htmlspecialchars($row['patient_name']) ?></td>
                  <td><?= htmlspecialchars($row['test_name']) ?></td>
                  <td><?= htmlspecialchars($row['technician_name']) ?></td>
                  <td><?= htmlspecialchars($row['result_summary']) ?></td>
                  <td><?= htmlspecialchars($row['medication']) ?></td>
                  <td><?= htmlspecialchars($row['diagnosis_date']) ?></td>
                </tr>
            <?php endwhile; else: ?>
              <tr><td colspan="7" class="text-center text-muted">No records found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>

        <!-- Export Buttons -->
        <div class="mt-3 text-end">
          <a href="reports.php?export=excel" class="btn btn-outline-success">
            <i class="bi bi-file-earmark-excel"></i> Export to Excel
          </a>
          <a href="reports.php?export=pdf" class="btn btn-outline-danger">
            <i class="bi bi-file-earmark-pdf"></i> Export to PDF
          </a>
        </div>

      </div>
    </div>
  </section>
</main>

<?php include 'includes/footer.php'; ?>
