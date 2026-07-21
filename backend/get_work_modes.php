<?php
//WORKING VERSION
/*
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
*/

//WITH TOGGLE FROM ACTIVE TO INACTIVE
/*
require 'connection_db.php';
header('Content-Type: application/json');

$includeAll = isset($_GET['all']) && $_GET['all'] == '1';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT id, name, is_active FROM work_modes WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    echo json_encode($result->fetch_assoc() ?: []);
    $stmt->close();
} else {
    if ($includeAll) {
        $result = $conn->query("SELECT id, name, is_active FROM work_modes ORDER BY name ASC");
    } else {
        $result = $conn->query("SELECT id, name, is_active FROM work_modes WHERE is_active = 1 ORDER BY name ASC");
    }

    $workModes = [];
    while ($row = $result->fetch_assoc()) {
        $workModes[] = $row;
    }

    echo json_encode($workModes);
}

$conn->close();*/

require 'connection_db.php';
header('Content-Type: application/json');

$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$all = isset($_GET['all']);

if ($id) {
    $stmt = $conn->prepare("SELECT id, name, is_active FROM work_modes WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    echo json_encode($result);
    $stmt->close();
    exit;
}

// If ?all=1, return ALL work modes (active + inactive)
if ($all) {
    $query = "SELECT id, name, is_active FROM work_modes ORDER BY name ASC";
    $result = $conn->query($query);
    $modes = [];
    while ($row = $result->fetch_assoc()) {
        $modes[] = $row;
    }
    echo json_encode($modes);
    $conn->close();
    exit;
}

// Default: return only active (for other use cases)
$query = "SELECT id, name, is_active FROM work_modes WHERE is_active = 1 ORDER BY name ASC";
$result = $conn->query($query);
$modes = [];
while ($row = $result->fetch_assoc()) {
    $modes[] = $row;
}
echo json_encode($modes);
$conn->close();
