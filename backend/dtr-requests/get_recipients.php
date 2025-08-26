<?php
session_start();
require '../connection_db.php'; // this should define $conn (MySQLi)
header('Content-Type: application/json');

// Only allow higher roles as recipients
$query = "
    SELECT id, role, CONCAT(first_name, ' ', last_name) AS username
    FROM users
    WHERE role IN ('admin', 'executive', 'hr')
    ORDER BY role, first_name
";

$result = $conn->query($query);

$recipients = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recipients[] = $row;
    }
    echo json_encode(["status" => "success", "recipients" => $recipients]);
} else {
    echo json_encode(["status" => "error", "message" => "Database error"]);
}
