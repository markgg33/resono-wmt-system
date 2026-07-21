<?php
require '../connection_db.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$current_user_id = $_SESSION['user_id'];

// ✅ Fetch Admin, HR, Executive, and Supervisor users (exclude self)
$query = "
    SELECT 
        id, 
        CONCAT(
            first_name, 
            IF(middle_name IS NOT NULL AND middle_name != '', CONCAT(' ', LEFT(middle_name, 1), '.'), ''), 
            ' ', 
            last_name
        ) AS full_name,
        role
    FROM users
    WHERE role IN ('admin', 'hr', 'executive', 'supervisor')
      AND id != ?
      AND status = 'active'
    ORDER BY FIELD(role, 'admin', 'executive', 'hr', 'supervisor'), first_name ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$result = $stmt->get_result();

$recipients = [];
while ($row = $result->fetch_assoc()) {
    $recipients[] = $row;
}

echo json_encode($recipients);
$stmt->close();
$conn->close();
