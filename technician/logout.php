<?php
session_start();
session_unset();
session_destroy();

// Redirect to technician login page
header("Location: /Smart-Laboratory/technician/login_technician.php");
exit;