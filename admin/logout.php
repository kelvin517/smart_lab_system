<?php
session_start();
session_unset();
session_destroy();

// Redirect to login page (full path)
header("Location: http://localhost/Smart-Laboratory/admin/admin_login.php");
exit();
