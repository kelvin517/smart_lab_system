<?php
// technician/reports.php
// Reports & Analytics for technicians (uses test_name for filtering)
// Robust and defensive: uses escaping and fallback if queries fail

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/db.php'; // expects $conn (mysqli)

if (!isset($_SESSION['technician_id'])) {
    header("Location: technician_login.php");
    exit;
}

$technician_id = (int) ($_SESSION['technician_id'] ?? 0);
$technician_username = $_SESSION['technician_username'] ?? 'Technician';

// ----- Filters (safe defaults) -----
$start_date = !empty($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date   = !empty($_GET['end_date'])   ? $_GET['end_date']   : date('Y-m-t');
$test_name  = isset($_GET['test_name'])  ? trim($_GET['test_name']) : 'all';
$status     = isset($_GET['status'])     ? trim($_GET['status']) : 'all';

// Basic validation for dates (fallback to safe defaults)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) $start_date = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date))   $end_date   = date('Y-m-t');

// Escape values for inline use (we still avoid direct interpolation of user input into SQL)
$start_date_esc = $conn->real_escape_string($start_date);
$end_date_esc   = $conn->real_escape_string($end_date);
$test_name_esc  = $conn->real_escape_string($test_name);
$status_esc     = $conn->real_escape_string($status);

// Build WHERE clause parts
$where_parts = [];
$where_parts[] = "DATE(b.preferred_date) BETWEEN '{$start_date_esc}' AND '{$end_date_esc}'";

if ($test_name_esc !== 'all' && $test_name_esc !== '') {
    // exact match on test_name
    $where_parts[] = "b.test_name = '{$test_name_esc}'";
}

if ($status_esc !== 'all' && $status_esc !== '') {
    $where_parts[] = "b.status = '{$status_esc}'";
}

// Combine where clause
$where_clause = implode(' AND ', $where_parts);

// Helper: execute query and return associative result or [] on failure
function safe_query_assoc($conn, $sql) {
    $res = $conn->query($sql);
    if (!$res) {
        // For debugging, you can uncomment the following line:
        // error_log("SQL Error: ".$conn->error." — Query: ".$sql);
        return false;
    }
    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    return $rows;
}

// ----- Main statistics (single-row) -----
// Use COALESCE to avoid NULLs for aggregates
$stats_sql = "
    SELECT 
        COUNT(*) AS total_tests,
        SUM(CASE WHEN b.status = 'completed' THEN 1 ELSE 0 END) AS completed_tests,
        SUM(CASE WHEN b.status = 'pending' THEN 1 ELSE 0 END) AS pending_tests,
        SUM(CASE WHEN b.status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress_tests,
        SUM(CASE WHEN b.priority = 'high' THEN 1 ELSE 0 END) AS urgent_tests,
        AVG(
            CASE 
              WHEN b.completed_date IS NOT NULL AND b.preferred_date IS NOT NULL
                THEN TIMESTAMPDIFF(MINUTE, b.preferred_date, b.completed_date)
              ELSE NULL
            END
        ) AS avg_completion_time
    FROM bookings b
    WHERE {$where_clause}
";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result ? $stats_result->fetch_assoc() : [
    'total_tests' => 0, 'completed_tests'=>0, 'pending_tests'=>0,
    'in_progress_tests'=>0, 'urgent_tests'=>0, 'avg_completion_time'=>0
];

// ----- Tests by test_name -----
$tests_by_type_sql = "
    SELECT b.test_name, COUNT(*) AS count
    FROM bookings b
    WHERE {$where_clause}
    GROUP BY b.test_name
    ORDER BY count DESC
";
$tests_by_type = safe_query_assoc($conn, $tests_by_type_sql) ?: [];

// ----- Tests by status -----
$tests_by_status_sql = "
    SELECT b.status, COUNT(*) AS count
    FROM bookings b
    WHERE {$where_clause}
    GROUP BY b.status
    ORDER BY count DESC
";
$tests_by_status = safe_query_assoc($conn, $tests_by_status_sql) ?: [];

// ----- Daily tests for line chart -----
$daily_tests_sql = "
    SELECT DATE(b.preferred_date) AS date, COUNT(*) AS count
    FROM bookings b
    WHERE {$where_clause}
    GROUP BY DATE(b.preferred_date)
    ORDER BY DATE(b.preferred_date) ASC
";
$daily_tests = safe_query_assoc($conn, $daily_tests_sql) ?: [];

// ----- Top tests (limit 10) -----
$top_tests_sql = "
    SELECT b.test_name, COUNT(*) AS count
    FROM bookings b
    WHERE {$where_clause}
    GROUP BY b.test_name
    ORDER BY count DESC
    LIMIT 10
";
$top_tests = safe_query_assoc($conn, $top_tests_sql) ?: [];

// ----- Technician performance (uses assigned_technician -> technicians.id) -----
$tech_performance_sql = "
    SELECT t.technician_id AS technician_id, COALESCE(t.full_name, t.username) AS name,
        COUNT(b.id) AS total_tests,
        SUM(CASE WHEN b.status = 'completed' THEN 1 ELSE 0 END) AS completed,
        AVG(
          CASE 
            WHEN b.completed_date IS NOT NULL AND b.preferred_date IS NOT NULL
              THEN TIMESTAMPDIFF(MINUTE, b.preferred_date, b.completed_date)
            ELSE NULL
          END
        ) AS avg_time
    FROM bookings b
    LEFT JOIN technicians t ON b.assigned_technician = t.technician_id
    WHERE {$where_clause}
    GROUP BY t.technician_id, name
    ORDER BY completed DESC
";
$tech_performance = safe_query_assoc($conn, $tech_performance_sql) ?: [];

// ----- Test name list for filter dropdown (distinct test_name) -----
$test_names_sql = "SELECT DISTINCT test_name FROM bookings WHERE test_name IS NOT NULL ORDER BY test_name ASC";
$test_names_res = $conn->query($test_names_sql);
$test_names = [];
if ($test_names_res) {
    while ($r = $test_names_res->fetch_assoc()) {
        $test_names[] = $r['test_name'];
    }
}

// ----- Recent completed tests (join patient info) -----
$recent_tests_sql = "
    SELECT b.id, b.test_name, b.status, b.preferred_date, b.completed_date, b.result_file,
           p.patient_id AS patient_id, p.full_name, p.gender
    FROM bookings b
    LEFT JOIN patients p ON b.patient_id = p.patient_id
    WHERE b.status = 'completed' AND {$where_clause}
    ORDER BY b.completed_date DESC
    LIMIT 10
";
$recent_tests = safe_query_assoc($conn, $recent_tests_sql) ?: [];

// Prepare data for charts
$daily_labels = array_map(function($r){ return $r['date']; }, $daily_tests);
$daily_counts = array_map(function($r){ return (int)$r['count']; }, $daily_tests);

$tests_types_labels = array_map(function($r){ return $r['test_name']; }, $tests_by_type);
$tests_types_counts = array_map(function($r){ return (int)$r['count']; }, $tests_by_type);

// Defensive defaults
$total_tests = (int) ($stats['total_tests'] ?? 0);
$completed_tests = (int) ($stats['completed_tests'] ?? 0);
$pending_tests = (int) ($stats['pending_tests'] ?? 0);
$urgent_tests = (int) ($stats['urgent_tests'] ?? 0);
$avg_completion_time = $stats['avg_completion_time'] !== null ? (float)$stats['avg_completion_time'] : 0.0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Reports & Analytics - Technician</title>
  <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
  <style>
    :root{
      --primary:#0d6efd; --muted:#6c757d;
    }
    body { font-family: "Segoe UI", Roboto, Arial, sans-serif; background:#f4f6f9; }
    .container-fluid { padding:24px; }
    .card { border-radius:10px; box-shadow:0 6px 18px rgba(0,0,0,0.06); border:0; }
    .stat { padding:18px; border-radius:8px; color:#fff; }
    .stat.total { background: linear-gradient(90deg,#667eea,#764ba2); }
    .stat.completed { background: linear-gradient(90deg,#34a853,#2ecc71); }
    .stat.pending { background: linear-gradient(90deg,#fbbc05,#f39c12); color:#000; }
    .stat.urgent { background: linear-gradient(90deg,#ea4335,#e74c3c); }
    .filter-row .form-control, .filter-row .form-select { min-height:46px; }
    .chart-card { height:340px; }
    @media (max-width: 768px){ .chart-card { height:260px; } }
  </style>
</head>
<body>
  <div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h3 class="mb-0">Reports & Analytics</h3>
        <small class="text-muted">Technician: <?= htmlspecialchars($technician_username) ?></small>
      </div>
      <div>
        <button class="btn btn-outline-secondary me-2" onclick="resetFilters()">Reset</button>
        <button class="btn btn-primary" onclick="exportCSV()">Export CSV</button>
      </div>
    </div>

    <!-- Filters -->
    <div class="card mb-3 p-3 filter-row">
      <form class="row g-2" method="GET" id="filterForm">
        <div class="col-md-3">
          <label class="form-label">Start Date</label>
          <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">End Date</label>
          <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Test Name</label>
          <select name="test_name" class="form-select">
            <option value="all">All Tests</option>
            <?php foreach ($test_names as $tn): ?>
              <option value="<?= htmlspecialchars($tn) ?>" <?= $tn === $test_name ? 'selected' : '' ?>><?= htmlspecialchars($tn) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="all" <?= $status==='all' ? 'selected' : '' ?>>All</option>
            <option value="pending" <?= $status==='pending' ? 'selected' : '' ?>>Pending</option>
            <option value="in_progress" <?= $status==='in_progress' ? 'selected' : '' ?>>In Progress</option>
            <option value="completed" <?= $status==='completed' ? 'selected' : '' ?>>Completed</option>
          </select>
        </div>
        <div class="col-md-1 d-flex align-items-end">
          <button class="btn btn-primary w-100">Apply</button>
        </div>
      </form>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-3">
      <div class="col-md-3"><div class="stat total"><h5>Total Tests</h5><h2><?= $total_tests ?></h2></div></div>
      <div class="col-md-3"><div class="stat completed"><h5>Completed</h5><h2><?= $completed_tests ?></h2></div></div>
      <div class="col-md-3"><div class="stat pending"><h5>Pending</h5><h2><?= $pending_tests ?></h2></div></div>
      <div class="col-md-3"><div class="stat urgent"><h5>Urgent</h5><h2><?= $urgent_tests ?></h2></div></div>
    </div>

    <!-- Charts row -->
    <div class="row g-3 mb-3">
      <div class="col-lg-8">
        <div class="card p-3 chart-card">
          <h6>Daily Tests</h6>
          <canvas id="dailyChart" style="height:260px"></canvas>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="card p-3 chart-card">
          <h6>Tests by Type</h6>
          <canvas id="typeChart" style="height:260px"></canvas>
        </div>
      </div>
    </div>

    <!-- Recent and Top -->
    <div class="row g-3">
      <div class="col-lg-8">
        <div class="card p-3">
          <h6>Recent Completed Tests</h6>
          <div class="table-responsive">
            <table class="table table-hover">
              <thead><tr><th>Patient</th><th>Test</th><th>Completed Date</th><th>Status</th></tr></thead>
              <tbody>
                <?php if (count($recent_tests) > 0): ?>
                  <?php foreach ($recent_tests as $rt): ?>
                    <tr>
                      <td><?= htmlspecialchars($rt['full_name'] ?? 'Unknown') ?> <br><small class="text-muted">ID: <?= htmlspecialchars($rt['patient_id'] ?? '') ?></small></td>
                      <td><?= htmlspecialchars($rt['test_name'] ?? '') ?></td>
                      <td><?= htmlspecialchars($rt['completed_date'] ?? $rt['preferred_date'] ?? '') ?></td>
                      <td><span class="badge bg-success"><?= htmlspecialchars($rt['status'] ?? 'completed') ?></span></td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr><td colspan="4" class="text-center text-muted">No recent completed tests in this range.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card p-3">
          <h6>Top Tests</h6>
          <ul class="list-group list-group-flush">
            <?php if (count($top_tests) > 0): ?>
              <?php foreach ($top_tests as $tt): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <?= htmlspecialchars($tt['test_name']) ?>
                  <span class="badge bg-primary"><?= (int)$tt['count'] ?></span>
                </li>
              <?php endforeach; ?>
            <?php else: ?>
              <li class="list-group-item text-center text-muted">No data</li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </div>

  </div>

  <script>
    // Prepare data from PHP
    const dailyLabels = <?= json_encode($daily_labels) ?>;
    const dailyCounts = <?= json_encode($daily_counts) ?>;
    const typesLabels = <?= json_encode($tests_types_labels) ?>;
    const typesCounts = <?= json_encode($tests_types_counts) ?>;

    // Daily Chart
    const ctxDaily = document.getElementById('dailyChart');
    const dailyChart = new Chart(ctxDaily, {
      type: 'line',
      data: {
        labels: dailyLabels,
        datasets: [{
          label: 'Tests per day',
          data: dailyCounts,
          borderColor: '#0d6efd',
          backgroundColor: 'rgba(13,110,253,0.08)',
          fill: true,
          tension: 0.3,
          pointRadius: 3
        }]
      },
      options: {
        responsive: true,
        scales: { y: { beginAtZero: true } }
      }
    });

    // Types chart (doughnut)
    const ctxType = document.getElementById('typeChart');
    const typeChart = new Chart(ctxType, {
      type: 'doughnut',
      data: {
        labels: typesLabels,
        datasets: [{
          data: typesCounts,
          backgroundColor: ['#0d6efd','#20c997','#ffc107','#dc3545','#6f42c1','#fd7e14','#198754','#0dcaf0'],
          hoverOffset: 6
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } }
      }
    });

    // Export CSV: pulls table rows and makes CSV
    function exportCSV() {
      const rows = [];
      // header
      rows.push(['Patient', 'Test', 'Completed Date', 'Status']);
      <?php foreach ($recent_tests as $rt): ?>
        rows.push([<?= json_encode($rt['full_name'] ?? '') ?>, <?= json_encode($rt['test_name'] ?? '') ?>, <?= json_encode($rt['completed_date'] ?? $rt['preferred_date'] ?? '') ?>, <?= json_encode($rt['status'] ?? '') ?>]);
      <?php endforeach; ?>
      let csv = rows.map(r => r.map(c => `"${(c+'').replace(/"/g,'""')}"`).join(',')).join('\r\n');
      const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'lab-report-<?= date('Y-m-d') ?>.csv';
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
    }

    // Reset filters
    function resetFilters() {
      document.querySelector('input[name="start_date"]').value = '<?= date('Y-m-01') ?>';
      document.querySelector('input[name="end_date"]').value = '<?= date('Y-m-t') ?>';
      document.querySelector('select[name="test_name"]').value = 'all';
      document.querySelector('select[name="status"]').value = 'all';
      document.getElementById('filterForm').submit();
    }
  </script>

  <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
