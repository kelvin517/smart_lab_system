<?php
session_start();
session_unset();
session_destroy();

// Correct redirect using absolute path (localhost safe)
header("Location: /Smart-Laboratory/patients/login.php");
exit;
