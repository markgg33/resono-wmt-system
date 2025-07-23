<?php
require 'connection_db.php';
header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT id, name FROM work_modes WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    echo json_encode($result->fetch_assoc() ?: []);
    $stmt->close();
} else {
    $result = $conn->query("SELECT id, name FROM work_modes ORDER BY name ASC");

    $workModes = [];
    while ($row = $result->fetch_assoc()) {
        $workModes[] = $row;
    }

    echo json_encode($workModes);
}

$conn->close();
