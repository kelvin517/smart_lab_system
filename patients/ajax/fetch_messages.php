<?php
session_start();
include_once '../../config/db.php';

$patient_id = $_SESSION['patient_id'];
$view = $_GET['view'] ?? 'inbox';

if ($view === 'read' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $conn->query("UPDATE messages SET is_read = 1 WHERE id=$id AND receiver_id=$patient_id");
    $msg = $conn->query("SELECT * FROM messages WHERE id=$id")->fetch_assoc();

    echo "<div class='card p-3'>
            <h5>" . htmlspecialchars($msg['subject']) . "</h5>
            <p class='mt-3'>" . nl2br(htmlspecialchars($msg['body'])) . "</p>
            <hr>
            <form class='reply-form'>
                <input type='hidden' name='receiver_id' value='{$msg['sender_id']}'>
                <input type='text' name='subject' value='Re: " . htmlspecialchars($msg['subject']) . "' class='form-control mb-2'>
                <textarea name='body' rows='3' class='form-control mb-2' placeholder='Type your reply...'></textarea>
                <button type='submit' class='btn btn-sm btn-success'>Send Reply</button>
            </form>
          </div>";
    exit;
}

$query = $view === 'sent'
    ? "SELECT m.*, d.full_name AS receiver_name FROM messages m
       LEFT JOIN doctors d ON m.receiver_id = d.id
       WHERE m.sender_id = $patient_id AND m.sender_role = 'patient'
       ORDER BY m.created_at DESC"
    : "SELECT m.*, d.full_name AS sender_name FROM messages m
       LEFT JOIN doctors d ON m.sender_id = d.id
       WHERE m.receiver_id = $patient_id AND m.receiver_role = 'patient'
       ORDER BY m.created_at DESC";

$res = $conn->query($query);

if ($res->num_rows === 0) {
    echo "<div class='alert alert-info text-center'>No messages found.</div>";
    exit;
}

echo "<table class='table table-bordered'>
        <thead class='table-light'>
            <tr>
                <th>From/To</th>
                <th>Subject</th>
                <th>Date</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>";

while ($msg = $res->fetch_assoc()) {
    $name = $view === 'sent' ? $msg['receiver_name'] : $msg['sender_name'];
    $status = $msg['is_read'] ? "<span class='badge bg-success'>Read</span>" : "<span class='badge bg-warning text-dark'>Unread</span>";
    echo "<tr>
            <td>" . htmlspecialchars($name ?? 'Admin') . "</td>
            <td>" . htmlspecialchars($msg['subject']) . "</td>
            <td>" . htmlspecialchars($msg['created_at']) . "</td>
            <td>$status</td>
            <td><button class='btn btn-sm btn-outline-info view-message' data-id='{$msg['id']}'>View</button></td>
          </tr>";
}

echo "</tbody></table>";
