<?php
// Database connection settings
$host = "localhost";
$username = "root";
$password = "Kiptoo2003@#!";  // replace with your actual MySQL password
$database = "smart_lab_system";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
