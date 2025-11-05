<?php
session_start();
include_once '../config/db.php';
include_once 'includes/header.php';
include_once 'includes/sidebar.php';

if (!isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit();
}

$patient_id = $_SESSION['patient_id'];
$limit = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Switch view (Inbox or Sent)
$view = isset($_GET['view']) && $_GET['view'] === 'sent' ? 'sent' : 'inbox';

// Handle sending a new message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['receiver_id'])) {
    $receiver_id = intval($_POST['receiver_id']);
    $subject = trim($_POST['subject']);
    $body = trim($_POST['body']);

    if ($subject !== '' && $body !== '') {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, sender_role, receiver_role, subject, body) VALUES (?, ?, 'patient', 'doctor', ?, ?)");
        $stmt->bind_param("iiss", $patient_id, $receiver_id, $subject, $body);
        $stmt->execute();
        $stmt->close();
        echo "<div class='alert alert-success'>Message sent successfully.</div>";
    } else {
        echo "<div class='alert alert-danger'>Please fill in all fields.</div>";
    }
}

// Mark message as read if viewing
if (isset($_GET['read_id'])) {
    $msg_id = intval($_GET['read_id']);
    $conn->query("UPDATE messages SET is_read = 1 WHERE id = $msg_id AND receiver_id = $patient_id");
}

// Fetch messages
if ($view === 'inbox') {
    $query = "
        SELECT m.*, 
               CASE 
                   WHEN m.sender_role = 'doctor' THEN d.full_name
                   WHEN m.sender_role = 'admin' THEN 'Admin'
               END AS sender_name
        FROM messages m
        LEFT JOIN doctors d ON m.sender_id = d.id
        WHERE m.receiver_id = ? AND m.receiver_role = 'patient'
        ORDER BY m.created_at DESC
        LIMIT $limit OFFSET $offset
    ";
} else {
    $query = "
        SELECT m.*, 
               CASE 
                   WHEN m.receiver_role = 'doctor' THEN d.full_name
                   WHEN m.receiver_role = 'admin' THEN 'Admin'
               END AS receiver_name
        FROM messages m
        LEFT JOIN doctors d ON m.receiver_id = d.id
        WHERE m.sender_id = ? AND m.sender_role = 'patient'
        ORDER BY m.created_at DESC
        LIMIT $limit OFFSET $offset
    ";
}

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<main id="main" class="main">
  <div class="pagetitle">
    <h1>Messages</h1>
  </div>

  <section class="section">
    <div class="card">
      <div class="card-body pt-4">

        <div class="d-flex justify-content-between mb-3">
          <div>
            <a href="?view=inbox" class="btn btn-sm <?= $view === 'inbox' ? 'btn-primary' : 'btn-outline-primary' ?>">Inbox</a>
            <a href="?view=sent" class="btn btn-sm <?= $view === 'sent' ? 'btn-primary' : 'btn-outline-primary' ?>">Sent</a>
          </div>
          <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#sendMessageModal">+ New Message</button>
        </div>

        <?php if ($result->num_rows > 0): ?>
          <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th><?= $view === 'inbox' ? 'From' : 'To' ?></th>
                  <th>Subject</th>
                  <th>Date</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php $i = 1; while ($row = $result->fetch_assoc()): ?>
                  <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($view === 'inbox' ? $row['sender_name'] : $row['receiver_name']) ?></td>
                    <td><?= htmlspecialchars($row['subject']) ?></td>
                    <td><?= htmlspecialchars($row['created_at']) ?></td>
                    <td>
                      <?php if ($view === 'inbox'): ?>
                        <?php if ($row['is_read']): ?>
                          <span class="badge bg-success">Read</span>
                        <?php else: ?>
                          <span class="badge bg-warning text-dark">Unread</span>
                        <?php endif; ?>
                      <?php endif; ?>
                    </td>
                    <td>
                      <a href="?read_id=<?= $row['id'] ?>&view=<?= $view ?>" 
                         class="btn btn-sm btn-outline-info"
                         data-bs-toggle="modal"
                         data-bs-target="#viewMessageModal<?= $row['id'] ?>">
                         View
                      </a>
                    </td>
                  </tr>

                  <!-- View Message Modal -->
                  <div class="modal fade" id="viewMessageModal<?= $row['id'] ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-scrollable">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title"><?= htmlspecialchars($row['subject']) ?></h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                          <p><?= nl2br(htmlspecialchars($row['body'])) ?></p>
                          <hr>
                          <small class="text-muted">Sent on <?= $row['created_at'] ?></small>
                        </div>
                        <div class="modal-footer">
                          <?php if ($view === 'inbox'): ?>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#replyModal<?= $row['id'] ?>">Reply</button>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Reply Modal -->
                  <div class="modal fade" id="replyModal<?= $row['id'] ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                      <div class="modal-content">
                        <form method="POST">
                          <div class="modal-header">
                            <h5 class="modal-title">Reply to <?= htmlspecialchars($row['sender_name']) ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                          </div>
                          <div class="modal-body">
                            <input type="hidden" name="receiver_id" value="<?= $row['sender_id'] ?>">
                            <input type="text" name="subject" class="form-control mb-2" value="Re: <?= htmlspecialchars($row['subject']) ?>" required>
                            <textarea name="body" class="form-control" rows="4" placeholder="Type your reply..." required></textarea>
                          </div>
                          <div class="modal-footer">
                            <button type="submit" class="btn btn-success">Send Reply</button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="alert alert-info">No messages found.</div>
        <?php endif; ?>

      </div>
    </div>
  </section>
</main>

<!-- New Message Modal -->
<div class="modal fade" id="sendMessageModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title">Send Message</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="receiver_id" class="form-label">Select Doctor</label>
            <select name="receiver_id" id="receiver_id" class="form-select" required>
              <option value="">-- Choose Doctor --</option>
              <?php
              $doctors = $conn->query("SELECT id, full_name FROM doctors ORDER BY full_name ASC");
              while ($doc = $doctors->fetch_assoc()):
              ?>
                <option value="<?= $doc['id'] ?>"><?= htmlspecialchars($doc['full_name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <input type="text" name="subject" class="form-control mb-2" placeholder="Subject" required>
          <textarea name="body" class="form-control" rows="4" placeholder="Write your message..." required></textarea>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Send Message</button>
        </div>
      </form>
    </div>
  </div>
</div>
