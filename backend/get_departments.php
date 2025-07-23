<?php
require 'connection_db.php';
header('Content-Type: application/json');

$result = $conn->query("SELECT id, name FROM departments ORDER BY name");
$departments = [];

while ($row = $result->fetch_assoc()) {
    $departments[] = $row;
}

echo json_encode($departments);
