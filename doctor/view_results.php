<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/db.php';

if (!isset($_SESSION['doctor_id'])) {
    header("Location: doctor_login.php");
    exit;
}

$doctor_name = $_SESSION['doctor_name'];
$success = $error = "";

// -------------------- HANDLE UPLOAD --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_result'])) {
    $booking_id = intval($_POST['booking_id']);

    if (isset($_FILES['result_file']) && $_FILES['result_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['result_file']['tmp_name'];
        $file_name = time() . '_' . basename($_FILES['result_file']['name']);
        $destination = '../uploads/' . $file_name;

        if (move_uploaded_file($file_tmp, $destination)) {
            $uploaded_by = $_SESSION['doctor_id'];
            $uploaded_by_role = 'doctor';

            $update = $conn->prepare("UPDATE bookings SET result_file = ?, status = 'Completed', uploaded_by = ?, uploaded_by_role = ? WHERE id = ?");
            $update->bind_param("sisi", $file_name, $uploaded_by, $uploaded_by_role, $booking_id);

            if ($update->execute()) {
                $success = "Result uploaded successfully.";
            } else {
                $error = "Failed to update the database after upload.";
            }

            $update->close();
        } else {
            $error = "Error moving uploaded file to server.";
        }
    } else {
        $error = "Invalid or missing file. Please upload a valid test result file.";
    }
}

// -------------------- FILTER LOGIC --------------------
$filter_technician = $_GET['technician'] ?? '';
$filter_date = $_GET['date'] ?? '';

$where = "b.status = 'Completed' AND b.uploaded_by_role = 'technician'";
$params = [];
$types = "";

if (!empty($filter_technician)) {
    $where .= " AND b.uploaded_by = ?";
    $params[] = $filter_technician;
    $types .= "i";
}

if (!empty($filter_date)) {
    $where .= " AND DATE(b.created_at) = ?";
    $params[] = $filter_date;
    $types .= "s";
}

// -------------------- QUERY: TECHNICIAN-UPLOADED RESULTS --------------------
$sql = "
    SELECT 
        b.*, 
        p.full_name AS patient_name,
        (SELECT full_name FROM users WHERE id = b.uploaded_by AND role = 'technician') AS uploader_name
    FROM bookings b
    JOIN patients p ON b.patient_id = p.patient_id
    WHERE $where
    ORDER BY b.created_at DESC
";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>View Results - Doctor Dashboard</title>

  <!-- NiceAdmin / Bootstrap -->
  <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
  <style>
    body {
      background-color: #f6f9ff;
    }
    .sidebar {
      background: #01356f;
      color: #fff;
    }
    .sidebar a {
      color: #d9e3f1;
      transition: 0.3s;
    }
    .sidebar a:hover {
      background: #0a4275;
      color: #fff;
      border-radius: 10px;
    }
    .card {
      border-radius: 15px;
    }
    .table-hover tbody tr:hover {
      background-color: #eef4ff;
    }
    .btn-outline-primary:hover {
      background-color: #0d6efd;
      color: #fff;
    }
    .header {
      background: #ffffff;
      border-bottom: 1px solid #e0e0e0;
    }
  </style>
</head>

<body>

<!-- ======= Header ======= -->
<header id="header" class="header fixed-top d-flex align-items-center shadow-sm">
  <div class="d-flex align-items-center justify-content-between px-3">
    <a href="dashboard.php" class="logo d-flex align-items-center">
      <img src="../assets/img/logo.png" alt="">
      <span class="d-none d-lg-block ms-2 fw-bold text-primary">SmartLab Doctor</span>
    </a>
    <i class="bi bi-list toggle-sidebar-btn"></i>
  </div>

  <nav class="header-nav ms-auto">
    <ul class="d-flex align-items-center">
      <li class="nav-item dropdown pe-3">
        <a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown">
          <i class="bi bi-bell"></i>
          <span class="badge bg-danger badge-number">3</span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow notifications">
          <li class="dropdown-header">You have 3 new notifications</li>
          <li><hr class="dropdown-divider"></li>
          <li class="notification-item"><i class="bi bi-check-circle text-success"></i> New results uploaded</li>
          <li class="notification-item"><i class="bi bi-chat-dots text-primary"></i> 2 new messages</li>
          <li class="notification-item"><i class="bi bi-person text-info"></i> New patient registration</li>
        </ul>
      </li>

      <li class="nav-item dropdown pe-3">
        <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
          <img src="../assets/img/preg.jpeg" alt="Profile" class="rounded-circle" width="36">
          <span class="d-none d-md-block dropdown-toggle ps-2"><?= htmlspecialchars($doctor_name) ?></span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
          <li class="dropdown-header text-center">
            <h6><?= htmlspecialchars($doctor_name) ?></h6>
            <span>Doctor</span>
          </li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item d-flex align-items-center" href="profile.php"><i class="bi bi-person"></i> <span>My Profile</span></a></li>
          <li><a class="dropdown-item d-flex align-items-center" href="change_password.php"><i class="bi bi-key"></i> <span>Change Password</span></a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item d-flex align-items-center text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i> <span>Logout</span></a></li>
        </ul>
      </li>
    </ul>
  </nav>
</header>

<!-- ======= Sidebar ======= -->
<aside id="sidebar" class="sidebar">
  <ul class="sidebar-nav mt-4" id="sidebar-nav">
    <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a></li>
    <li class="nav-item"><a class="nav-link" href="view_results.php"><i class="bi bi-file-earmark-medical"></i><span>View Results</span></a></li>
    <li class="nav-item"><a class="nav-link" href="add_diagnosis.php"><i class="bi bi-plus-circle"></i><span>Add Diagnosis</span></a></li>
    <li class="nav-item"><a class="nav-link" href="patient_history.php"><i class="bi bi-journal-medical"></i><span>Patient History</span></a></li>
    <li class="nav-item"><a class="nav-link" href="send_message.php"><i class="bi bi-envelope"></i><span>Messages</span></a></li>
    <li class="nav-item"><a class="nav-link" href="profile.php"><i class="bi bi-person"></i><span>My Profile</span></a></li>
    <li class="nav-item"><a class="nav-link text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i><span>Logout</span></a></li>
  </ul>
</aside>

<!-- ======= Main Content ======= -->
<main id="main" class="main mt-5 pt-4">
  <div class="pagetitle">
    <h1 class="fw-bold text-primary">Completed Test Results</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">View Results</li>
      </ol>
    </nav>
  </div>

  <section class="section">
    <div class="card shadow border-0">
      <div class="card-body pt-4">

        <h5 class="card-title"><i class="bi bi-funnel"></i> Filter Test Results</h5>
        <form class="row g-3 mb-4" method="GET">
          <div class="col-md-4">
            <label class="form-label">Technician</label>
            <select class="form-select" name="technician">
              <option value="">-- All --</option>
              <?php
              $techs = $conn->query("SELECT id, full_name FROM users WHERE role = 'technician'");
              while ($tech = $techs->fetch_assoc()):
              ?>
              <option value="<?= $tech['id'] ?>" <?= ($tech['id'] == $filter_technician) ? 'selected' : '' ?>>
                <?= htmlspecialchars($tech['full_name']) ?>
              </option>
              <?php endwhile; ?>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Date</label>
            <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($filter_date) ?>">
          </div>

          <div class="col-md-4 align-self-end">
            <button class="btn btn-primary w-100" type="submit"><i class="bi bi-search"></i> Apply Filter</button>
          </div>
        </form>

        <!-- Alerts -->
        <?php if ($success): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php elseif ($error): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <!-- Results Table -->
        <h5 class="card-title mt-4"><i class="bi bi-clipboard2-check"></i> Technician Uploaded Results</h5>
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-primary">
              <tr>
                <th>Patient</th>
                <th>Test Type</th>
                <th>Status</th>
                <th>Date</th>
                <th>Result File</th>
                <th>Uploaded By</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()):
                  $file_path = '../uploads/' . $row['result_file'];
                  $has_result = !empty($row['result_file']) && file_exists($file_path);
                ?>
                <tr>
                  <td><?= htmlspecialchars($row['patient_name']) ?></td>
                  <td><?= htmlspecialchars($row['test_type']) ?></td>
                  <td><span class="badge bg-success"><i class="bi bi-check2-circle"></i> Completed</span></td>
                  <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($row['created_at']))) ?></td>
                  <td>
                    <?php if ($has_result): ?>
                      <div class="btn-group">
                        <a href="<?= $file_path ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                          <i class="bi bi-eye"></i> View
                        </a>
                        <a href="<?= $file_path ?>" download class="btn btn-sm btn-outline-success">
                          <i class="bi bi-download"></i> Download
                        </a>
                      </div>
                    <?php else: ?>
                      <span class="text-muted fst-italic">Not available</span>
                    <?php endif; ?>
                  </td>
                  <td><?= htmlspecialchars($row['uploader_name'] ?? 'Unknown Technician') ?></td>
                </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6" class="text-center text-muted py-4">
                    <i class="bi bi-info-circle"></i> No technician-uploaded test results found.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </section>
</main>

<!-- ======= JS ======= -->
<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
