<?php
// Database credentials
$host = 'localhost';           // Usually 'localhost'
$db   = 'rsn_wmt_db';  // Change this to your database name
$user = 'root';        // Change this to your DB user
$pass = 'P@ssword3309807';    // Change this to your DB password

// Create connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional: set charset
$conn->set_charset("utf8mb4");
