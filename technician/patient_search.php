<?php
session_start();
require_once "../config/db.php";

// Redirect if technician is not logged in
if (!isset($_SESSION['technician_id'])) {
    header("Location: technician_login.php");
    exit;
}

$search_results = [];
$search_query = "";

// Handle search
if (isset($_GET['q'])) {
    $search_query = trim($_GET['q']);

    $sql = "
        SELECT patient_id, full_name, email, phone
        FROM patients
        WHERE full_name LIKE ? 
            OR email LIKE ? 
            OR phone LIKE ?
            OR patient_id = ?
        ORDER BY full_name ASC
        LIMIT 50
    ";

    $stmt = $conn->prepare($sql);
    $like = "%{$search_query}%";
    $patient_id_num = intval($search_query);

    $stmt->bind_param("sssi", $like, $like, $like, $patient_id_num);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $search_results[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Search - Smart Lab System</title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background: #f6f9ff;
            font-family: 'Segoe UI', sans-serif;
        }
        .search-container {
            max-width: 900px;
            margin: 40px auto;
        }
        .card {
            border-radius: 12px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        }
        .table {
            margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="search-container">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="m-0"><i class="bi bi-search"></i> Search Patients</h5>
        </div>
        <div class="card-body">

            <!-- Search Form -->
            <form method="GET" class="row mb-3">
                <div class="col-md-10">
                    <input type="text" name="q" class="form-control"
                           placeholder="Search by name, email, phone, or patient ID..."
                           value="<?= htmlspecialchars($search_query) ?>" required>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100"><i class="bi bi-search"></i> Search</button>
                </div>
            </form>

            <!-- Search Results -->
            <?php if (!empty($search_query)): ?>
                <h6 class="text-muted">
                    Results for: <strong><?= htmlspecialchars($search_query) ?></strong>
                </h6>

                <?php if (empty($search_results)): ?>
                    <div class="alert alert-warning mt-3">No patients found.</div>
                <?php else: ?>
                    <table class="table table-bordered table-striped mt-3">
                        <thead>
                            <tr>
                                <th>Patient ID</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($search_results as $p): ?>
                            <tr>
                                <td><?= $p['patient_id'] ?></td>
                                <td><?= htmlspecialchars($p['full_name']) ?></td>
                                <td><?= htmlspecialchars($p['email']) ?></td>
                                <td><?= htmlspecialchars($p['phone']) ?></td>
                                <td>
                                    <a href="contact_patient.php?id=<?= $p['patient_id'] ?>"
                                       class="btn btn-sm btn-success">
                                        <i class="bi bi-chat-dots"></i> Contact
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            <?php endif; ?>

        </div>
    </div>
</div>

</body>
</html>
