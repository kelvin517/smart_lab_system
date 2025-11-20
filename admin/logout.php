<?php
session_start();

// Clear all session data
session_unset();
session_destroy();

// Prevent session reuse
session_write_close();

// Redirect to admin login
header("Location: admin_login.php");
exit();
