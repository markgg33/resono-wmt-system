<?php
require 'connection_db.php';

$query = $_GET['query'] ?? '';

$sql = "SELECT id, CONCAT(first_name, ' ', last_name) AS name FROM users 
        WHERE CONCAT(first_name, ' ', last_name) LIKE ? 
        ORDER BY name LIMIT 10";
$stmt = $conn->prepare($sql);
$like = "%$query%";
$stmt->bind_param("s", $like);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

echo json_encode($users);
