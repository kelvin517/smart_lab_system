<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit;
}

$patient_id = $_SESSION['patient_id'];
$success = $error = "";

// Handle message submission (new or reply)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subject'], $_POST['message_body'], $_POST['recipient'])) {
    $subject = trim($_POST['subject']);
    $body = trim($_POST['message_body']);
    $recipient = $_POST['recipient'];

    if (strpos($recipient, '-') !== false) {
        [$receiver_role, $receiver_id] = explode("-", $recipient);

        $stmt = $conn->prepare("INSERT INTO messages 
            (sender_id, receiver_id, sender_role, receiver_role, subject, body, created_at)
            VALUES (?, ?, 'patient', ?, ?, ?, NOW())");
        $stmt->bind_param("iisss", $patient_id, $receiver_id, $receiver_role, $subject, $body);

        if ($stmt->execute()) {
            $success = "Message sent successfully!";
        } else {
            $error = "Failed to send message.";
        }

        $stmt->close();
    } else {
        $error = "Invalid recipient selected.";
    }
}

// Pagination setup
$limit = 5;
$page = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
$offset = ($page - 1) * $limit;

// Count total messages
$count_stmt = $conn->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND receiver_role = 'patient'");
$count_stmt->bind_param("i", $patient_id);
$count_stmt->execute();
$count_stmt->bind_result($total);
$count_stmt->fetch();
$count_stmt->close();

$total_pages = ceil($total / $limit);

// Fetch messages
$query = "SELECT m.subject, m.body, m.created_at, m.sender_role, m.sender_id,
    CASE 
        WHEN m.sender_role = 'admin' THEN (SELECT full_name FROM admins WHERE id = m.sender_id)
        WHEN m.sender_role = 'doctor' THEN (SELECT full_name FROM doctors WHERE id = m.sender_id)
        ELSE 'System'
    END AS full_name
    FROM messages m
    WHERE m.receiver_id = ? AND m.receiver_role = 'patient'
    ORDER BY m.created_at DESC
    LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $patient_id, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<main id="main" class="main">
  <div class="pagetitle">
    <h1>Messages</h1>
  </div>

  <section class="section">
    <div class="card">
      <div class="card-body pt-4">
        <h5 class="card-title">Inbox (From Doctors/Admins)</h5>

        <?php if ($success): ?>
          <div class="alert alert-success"><?= $success ?></div>
        <?php elseif ($error): ?>
          <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <table class="table table-striped">
          <thead>
            <tr>
              <th>From</th>
              <th>Subject</th>
              <th>Message</th>
              <th>Sent At</th>
              <th>Reply</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($row['full_name']) ?> (<?= $row['sender_role'] ?>)</td>
                <td><strong><?= htmlspecialchars($row['subject']) ?></strong></td>
                <td><?= nl2br(htmlspecialchars($row['body'])) ?></td>
                <td><?= $row['created_at'] ?></td>
                <td>
                  <button class="btn btn-sm btn-outline-primary"
                          data-bs-toggle="modal"
                          data-bs-target="#replyModal"
                          data-subject="<?= htmlspecialchars($row['subject']) ?>"
                          data-recipient="<?= $row['sender_role'] . '-' . $row['sender_id'] ?>">
                    Reply
                  </button>
                </td>
              </tr>
            <?php endwhile; $stmt->close(); ?>
          </tbody>
        </table>

        <!-- Pagination -->
        <nav>
          <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
              <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>
          </ul>
        </nav>
      </div>
    </div>

    <!-- Compose Message -->
    <div class="card mt-4">
      <div class="card-body">
        <h5 class="card-title">Send Message to Doctor/Admin</h5>
        <form method="POST">
          <div class="mb-3">
            <label class="form-label">To</label>
            <select name="recipient" class="form-select" required>
              <option value="">-- Select Recipient --</option>
              <optgroup label="Doctors">
                <?php
                $res = $conn->query("SELECT id, full_name FROM doctors");
                while ($row = $res->fetch_assoc()):
                ?>
                  <option value="doctor-<?= $row['id'] ?>"><?= htmlspecialchars($row['full_name']) ?> (Doctor)</option>
                <?php endwhile; ?>
              </optgroup>
              <optgroup label="Admins">
                <?php
                $res = $conn->query("SELECT id, full_name FROM admins");
                while ($row = $res->fetch_assoc()):
                ?>
                  <option value="admin-<?= $row['id'] ?>"><?= htmlspecialchars($row['full_name']) ?> (Admin)</option>
                <?php endwhile; ?>
              </optgroup>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Subject</label>
            <input type="text" name="subject" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Message</label>
            <textarea name="message_body" class="form-control" rows="4" required></textarea>
          </div>

          <div class="text-center">
            <button type="submit" class="btn btn-primary">Send Message</button>
          </div>
        </form>
      </div>
    </div>
  </section>
</main>

<!-- Reply Modal -->
<div class="modal fade" id="replyModal" tabindex="-1" aria-labelledby="replyModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="replyModalLabel">Reply Message</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="recipient" id="reply-recipient">
        <div class="mb-3">
          <label class="form-label">Subject</label>
          <input type="text" name="subject" class="form-control" id="reply-subject" readonly>
        </div>
        <div class="mb-3">
          <label class="form-label">Message</label>
          <textarea name="message_body" rows="4" class="form-control" required></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Send Reply</button>
      </div>
    </form>
  </div>
</div>

<script>
  const replyModal = document.getElementById('replyModal');
  replyModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const subject = button.getAttribute('data-subject');
    const recipient = button.getAttribute('data-recipient');
    document.getElementById('reply-subject').value = "Re: " + subject;
    document.getElementById('reply-recipient').value = recipient;
  });
</script>