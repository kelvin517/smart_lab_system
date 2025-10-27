<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['doctor_id'])) {
    header("Location: doctor_login.php");
    exit;
}

$doctor_id = $_SESSION['doctor_id'];

include 'includes/header.php';
include 'includes/sidebar.php';

// Fetch messages where the doctor is the sender (to patients)
$outbox_stmt = $conn->prepare("
    SELECT m.*, p.full_name AS receiver_name 
    FROM messages m 
    JOIN patients p ON m.receiver_id = p.id 
    WHERE m.sender = 'doctor' 
    ORDER BY m.sent_at DESC
");
$outbox_stmt->execute();
$outbox_result = $outbox_stmt->get_result();

// Fetch messages where the doctor is the receiver (from admin)
$inbox_stmt = $conn->prepare("
    SELECT m.*, a.full_name AS sender_name 
    FROM messages m 
    JOIN users a ON m.sender = 'admin' AND m.receiver_id = ? 
    WHERE m.sender = 'admin' 
    ORDER BY m.sent_at DESC
");
$inbox_stmt->bind_param("i", $doctor_id);
$inbox_stmt->execute();
$inbox_result = $inbox_stmt->get_result();
?>

<main id="main" class="main">
  <div class="pagetitle">
    <h1>Doctor Messages</h1>
  </div>

  <section class="section">
    <div class="row">

      <!-- INBOX -->
      <div class="col-lg-6">
        <div class="card">
          <div class="card-body pt-4">
            <h5 class="card-title">Inbox (From Admin)</h5>
            <?php if ($inbox_result->num_rows > 0): ?>
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>From</th>
                    <th>Subject</th>
                    <th>Date</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($msg = $inbox_result->fetch_assoc()): ?>
                    <tr>
                      <td><?= htmlspecialchars($msg['sender_name']) ?></td>
                      <td><?= htmlspecialchars($msg['subject']) ?></td>
                      <td><?= date('d M Y H:i', strtotime($msg['sent_at'])) ?></td>
                    </tr>
                    <tr>
                      <td colspan="3"><strong>Message:</strong> <?= nl2br(htmlspecialchars($msg['message'])) ?></td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            <?php else: ?>
              <p class="text-muted">No messages received from admin.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- OUTBOX -->
      <div class="col-lg-6">
        <div class="card">
          <div class="card-body pt-4">
            <h5 class="card-title">Outbox (To Patients)</h5>
            <?php if ($outbox_result->num_rows > 0): ?>
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>To</th>
                    <th>Subject</th>
                    <th>Date</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($msg = $outbox_result->fetch_assoc()): ?>
                    <tr>
                      <td><?= htmlspecialchars($msg['receiver_name']) ?></td>
                      <td><?= htmlspecialchars($msg['subject']) ?></td>
                      <td><?= date('d M Y H:i', strtotime($msg['sent_at'])) ?></td>
                    </tr>
                    <tr>
                      <td colspan="3"><strong>Message:</strong> <?= nl2br(htmlspecialchars($msg['message'])) ?></td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            <?php else: ?>
              <p class="text-muted">No messages sent yet.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>

    </div>
  </section>
</main>