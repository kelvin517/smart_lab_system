<?php
session_start();

// Destroy all technician session variables
if (isset($_SESSION['technician_id'])) {
    session_unset();
    session_destroy();
}

// Redirect to login page
header("Location: login_technician.php");
exit;
?>
