<?php
// ============================================================
// db_connection.php — Central Database Connection File
// Used by ALL PHP files. Secure and reusable.
// ============================================================

 $host = "localhost";
 $username = "root";
 $password = "";
 $database = "catering_db";

// Create connection using MySQLi
 $conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8 for proper encoding
 $conn->set_charset("utf8mb4");

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>