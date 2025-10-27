<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../admin_login.php");
    exit();
}

include 'includes/header.php';
include 'includes/sidebar.php';

// Handle new notification submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    $recipient = $_POST['recipient_type'];

    $insert = mysqli_query($conn, "INSERT INTO notifications (title, message, recipient_type)
                                   VALUES ('$title', '$message', '$recipient')");
    if ($insert) {
        $success = "Notification sent!";
    } else {
        $error = "Failed to send notification.";
    }
}
?>

<main id="main" class="main">
  <div class="pagetitle">
    <h1>Notifications</h1>
  </div>

  <section class="section">
    <div class="card mb-3">
      <div class="card-body">
        <h5 class="card-title">Send New Notification</h5>

        <?php if (isset($success)): ?>
          <div class="alert alert-success"><?= $success ?></div>
        <?php elseif (isset($error)): ?>
          <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
          <div class="mb-3">
            <label>Title</label>
            <input type="text" name="title" class="form-control" required>
          </div>

          <div class="mb-3">
            <label>Message</label>
            <textarea name="message" class="form-control" rows="4" required></textarea>
          </div>

          <div class="mb-3">
            <label>Recipient</label>
            <select name="recipient_type" class="form-control" required>
              <option value="all">All Users</option>
              <option value="staff">Staff Only</option>
              <option value="patient">Patients Only</option>
            </select>
          </div>

          <button type="submit" class="btn btn-primary">Send</button>
        </form>
      </div>
    </div>

    <!-- List All Notifications -->
    <div class="card">
      <div class="card-body">
        <h5 class="card-title">Sent Notifications</h5>
        <table class="table table-bordered">
          <thead>
            <tr>
              <th>Title</th>
              <th>Message</th>
              <th>Recipient</th>
              <th>Date Sent</th>
            </tr>
          </thead>
          <tbody>
          <?php
            $getNotices = mysqli_query($conn, "SELECT * FROM notifications ORDER BY created_at DESC");
            while ($row = mysqli_fetch_assoc($getNotices)) {
              echo "<tr>
                <td>{$row['title']}</td>
                <td>{$row['message']}</td>
                <td>{$row['recipient_type']}</td>
                <td>{$row['created_at']}</td>
              </tr>";
            }
          ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</main>