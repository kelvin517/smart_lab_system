<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['doctor_id'])) {
    header("Location: doctor_login.php");
    exit;
}

$doctor_id = $_SESSION['doctor_id'];
$success = $error = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipient_id = $_POST['recipient_id'];
    $subject = trim($_POST['subject']);
    $body = trim($_POST['message_body']);

    if (!empty($recipient_id) && !empty($subject) && !empty($body)) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, sender_role, receiver_role, subject, body, created_at) VALUES (?, ?, 'doctor', 'patient', ?, ?, NOW())");
        $stmt->bind_param("iiss", $doctor_id, $recipient_id, $subject, $body);

        if ($stmt->execute()) {
            $success = "Message sent successfully.";
        } else {
            $error = "Failed to send message.";
        }
        $stmt->close();
    } else {
        $error = "Please fill in all fields.";
    }
}

// Fetch patients list
$patients = [];
$res = $conn->query("SELECT id, full_name FROM patients ORDER BY full_name");
while ($row = $res->fetch_assoc()) {
    $patients[] = $row;
}
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<main id="main" class="main">
  <div class="pagetitle">
    <h1>Messages</h1>
  </div>

  <section class="section">
    <div class="row">
      <div class="col-lg-12">

        <!-- Compose Message -->
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Send Message to Patient</h5>

            <?php if ($success): ?>
              <div class="alert alert-success"><?= $success ?></div>
            <?php elseif ($error): ?>
              <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
              <div class="row mb-3">
                <label class="col-sm-2 col-form-label">Recipient</label>
                <div class="col-sm-10">
                  <select name="recipient_id" class="form-select" required>
                    <option value="">-- Select Patient --</option>
                    <?php foreach ($patients as $patient): ?>
                      <option value="<?= $patient['id'] ?>"><?= htmlspecialchars($patient['full_name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <div class="row mb-3">
                <label class="col-sm-2 col-form-label">Subject</label>
                <div class="col-sm-10">
                  <input type="text" name="subject" class="form-control" required>
                </div>
              </div>

              <div class="row mb-3">
                <label class="col-sm-2 col-form-label">Message</label>
                <div class="col-sm-10">
                  <textarea name="message_body" class="form-control" rows="5" required></textarea>
                </div>
              </div>

              <div class="text-center">
                <button type="submit" class="btn btn-primary">Send</button>
              </div>
            </form>
          </div>
        </div>

        <!-- Inbox / Outbox Tabs -->
        <div class="card">
          <div class="card-body pt-3">
            <ul class="nav nav-tabs" id="messageTabs" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link active" id="inbox-tab" data-bs-toggle="tab" data-bs-target="#inbox" type="button" role="tab">Inbox</button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="outbox-tab" data-bs-toggle="tab" data-bs-target="#outbox" type="button" role="tab">Outbox</button>
              </li>
            </ul>

            <div class="tab-content pt-3" id="messageTabsContent">
              <!-- Inbox -->
              <div class="tab-pane fade show active" id="inbox" role="tabpanel">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th>From</th>
                      <th>Subject</th>
                      <th>Message</th>
                      <th>Date</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    $stmt = $conn->prepare("
                      SELECT a.full_name AS sender_name, m.subject, m.body, m.created_at
                      FROM messages m
                      JOIN admins a ON a.id = m.sender_id
                      WHERE m.receiver_id = ? AND m.receiver_role = 'doctor' AND m.sender_role = 'admin'
                      ORDER BY m.created_at DESC
                    ");
                    $stmt->bind_param("i", $doctor_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($msg = $result->fetch_assoc()):
                    ?>
                      <tr>
                        <td><?= htmlspecialchars($msg['sender_name']) ?></td>
                        <td><?= htmlspecialchars($msg['subject']) ?></td>
                        <td><?= nl2br(htmlspecialchars($msg['body'])) ?></td>
                        <td><?= htmlspecialchars($msg['created_at']) ?></td>
                      </tr>
                    <?php endwhile; $stmt->close(); ?>
                  </tbody>
                </table>
              </div>

              <!-- Outbox -->
              <div class="tab-pane fade" id="outbox" role="tabpanel">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th>To</th>
                      <th>Subject</th>
                      <th>Message</th>
                      <th>Date</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    $stmt = $conn->prepare("
                      SELECT p.full_name AS receiver_name, m.subject, m.body, m.created_at
                      FROM messages m
                      JOIN patients p ON p.id = m.receiver_id
                      WHERE m.sender_id = ? AND m.sender_role = 'doctor'
                      ORDER BY m.created_at DESC
                    ");
                    $stmt->bind_param("i", $doctor_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($msg = $result->fetch_assoc()):
                    ?>
                      <tr>
                        <td><?= htmlspecialchars($msg['receiver_name']) ?></td>
                        <td><?= htmlspecialchars($msg['subject']) ?></td>
                        <td><?= nl2br(htmlspecialchars($msg['body'])) ?></td>
                        <td><?= htmlspecialchars($msg['created_at']) ?></td>
                      </tr>
                    <?php endwhile; $stmt->close(); ?>
                  </tbody>
                </table>
              </div>
            </div>

          </div>
        </div>

      </div>
    </div>
  </section>
</main>