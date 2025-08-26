<?php
require 'connection_db.php';
header('Content-Type: application/json');

// Run query
$result = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");

$departments = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
    $result->free();
}

// Close connection
$conn->close();

// Return JSON
echo json_encode($departments);
