<?php
require '../connection_db.php';
header('Content-Type: application/json');

$sql = "SELECT id, CONCAT(first_name, ' ', last_name) AS username FROM users ORDER BY first_name";
$result = $conn->query($sql);

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

echo json_encode(["status" => "success", "recipients" => $users]);
$conn->close();
