<?php
session_start();
require 'connection_db.php'; // Adjust path if needed

// Optional: Update is_online to 0 if user is logged in
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("UPDATE users SET is_online = 0 WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
}

// Destroy session
$_SESSION = [];
session_unset();
session_destroy();

// Redirect to login page
header("Location: ../index.php");
exit;
