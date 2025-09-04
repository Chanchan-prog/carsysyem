<?php
// Database connection settings
$host     = "localhost";   // Change if your MySQL host is different
$user     = "root";        // Change to your MySQL username
$password = "";            // Change to your MySQL password
$dbname   = "srms_makumbusho"; // Your database name from school.sql

// Create connection
$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional: set charset to UTF-8
$conn->set_charset("utf8");

?>
