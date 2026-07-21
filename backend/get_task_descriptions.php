<?php
//WORKING VERSION
/*
require 'connection_db.php';
header('Content-Type: application/json');

$workModeId = $_GET['work_mode_id'] ?? null;

if (!$workModeId) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("SELECT id, description FROM task_descriptions WHERE work_mode_id = ?");
$stmt->bind_param("i", $workModeId);
$stmt->execute();

$result = $stmt->get_result();
$tasks = [];

while ($row = $result->fetch_assoc()) {
    $tasks[] = $row;
}

echo json_encode($tasks);

$stmt->close();
$conn->close();*/

//WITH TOGGLE FROM ACTIVE TO INACTIVE
require 'connection_db.php';
header('Content-Type: application/json');

$workModeId = $_GET['work_mode_id'] ?? null;
$includeAll = isset($_GET['all']) && $_GET['all'] == '1';

if (!$workModeId) {
    echo json_encode([]);
    exit;
}

/*if ($includeAll) {
    //$stmt = $conn->prepare("SELECT id, description, is_active FROM task_descriptions WHERE work_mode_id = ?");
    $stmt = $conn->prepare("SELECT td.id, td.description, td.billing_category_id, bc.category_name, td.is_active FROM task_descriptions td 
    LEFT JOIN billing_categories bc 
    ON bc.id = td.billing_category_id WHERE work_mode_id = ?");
} else {
    $stmt = $conn->prepare("SELECT id, description, is_active FROM task_descriptions WHERE work_mode_id = ? AND is_active = 1");
}*/

// WITH ORDERING
if ($includeAll) {
    $stmt = $conn->prepare("
        SELECT 
            td.id, 
            td.description,     
            td.billing_category_id, 
            td.standard_aht,
            bc.category_name, 
            td.is_active
        FROM task_descriptions td
        LEFT JOIN billing_categories bc ON bc.id = td.billing_category_id
        WHERE td.work_mode_id = ?
        ORDER BY td.display_order ASC, td.id ASC
    ");
} else {
    $stmt = $conn->prepare("
        SELECT id, description, standard_aht, is_active
        FROM task_descriptions 
        WHERE work_mode_id = ? AND is_active = 1
        ORDER BY display_order ASC, id ASC
    ");
}

$stmt->bind_param("i", $workModeId);
$stmt->execute();
$result = $stmt->get_result();

$tasks = [];
while ($row = $result->fetch_assoc()) {
    $tasks[] = $row;
}

echo json_encode($tasks);

$stmt->close();
$conn->close();
