<?php
require 'connection_db.php';
header('Content-Type: application/json');

$result = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
$row = $result->fetch_assoc();

$enumList = $row['Type']; // e.g. enum('admin','executive','user','hr')

// Parse the ENUM values
preg_match_all("/'([^']+)'/", $enumList, $matches);
$roles = $matches[1];

echo json_encode($roles);
