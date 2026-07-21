<?php

//WORKING VERSION
/*
require 'connection_db.php';
session_start();
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

$work_modes = [];

// 1️⃣ Get all departments linked to this user
$sql = "SELECT department_id FROM user_departments WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$department_ids = [];
while ($row = $result->fetch_assoc()) {
    $department_ids[] = $row['department_id'];
}

// 2️⃣ If user has departments, fetch all work modes linked to those departments
if (!empty($department_ids)) {
    // Prepare dynamic placeholders (?, ?, ?)
    $placeholders = implode(',', array_fill(0, count($department_ids), '?'));
    $types = str_repeat('i', count($department_ids));

    $sql = "
        SELECT DISTINCT wm.id, wm.name
        FROM work_modes wm
        INNER JOIN department_work_modes dwm ON dwm.work_mode_id = wm.id
        WHERE dwm.department_id IN ($placeholders)
        ORDER BY wm.name ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$department_ids);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($wm = $result->fetch_assoc()) {
        $work_modes[] = $wm;
    }
}

// 3️⃣ Dynamically include “Away-Time” if not already listed
$awayModeQuery = $conn->query("SELECT id, name FROM work_modes WHERE name LIKE '%Away%' LIMIT 1");
$awayMode = $awayModeQuery ? $awayModeQuery->fetch_assoc() : null;

if ($awayMode) {
    $alreadyIncluded = false;
    foreach ($work_modes as $wm) {
        if (strcasecmp($wm['name'], $awayMode['name']) === 0) {
            $alreadyIncluded = true;
            break;
        }
    }

    if (!$alreadyIncluded) {
        array_unshift($work_modes, $awayMode); // Add on top
    }
}

echo json_encode(['status' => 'success', 'work_modes' => $work_modes]);
$conn->close();*/

require 'connection_db.php';
session_start();
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

$work_modes = [];

// 1️⃣ Get all departments linked to this user
$sql = "SELECT department_id FROM user_departments WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$department_ids = [];
while ($row = $result->fetch_assoc()) {
    $department_ids[] = $row['department_id'];
}

// 2️⃣ If user has departments, fetch active work modes linked to those departments
if (!empty($department_ids)) {
    $placeholders = implode(',', array_fill(0, count($department_ids), '?'));
    $types = str_repeat('i', count($department_ids));

    $sql = "
        SELECT DISTINCT wm.id, wm.name
        FROM work_modes wm
        INNER JOIN department_work_modes dwm ON dwm.work_mode_id = wm.id
        WHERE dwm.department_id IN ($placeholders)
          AND wm.is_active = 1
        ORDER BY wm.name ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$department_ids);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($wm = $result->fetch_assoc()) {
        $work_modes[] = $wm;
    }
}

// 3️⃣ Always include “Away-Time” if active, even if not assigned
$awayModeQuery = $conn->query("SELECT id, name FROM work_modes WHERE name LIKE '%Away%' AND is_active = 1 LIMIT 1");
$awayMode = $awayModeQuery ? $awayModeQuery->fetch_assoc() : null;

if ($awayMode) {
    $alreadyIncluded = false;
    foreach ($work_modes as $wm) {
        if (strcasecmp($wm['name'], $awayMode['name']) === 0) {
            $alreadyIncluded = true;
            break;
        }
    }

    if (!$alreadyIncluded) {
        array_unshift($work_modes, $awayMode); // Add to top
    }
}

echo json_encode(['status' => 'success', 'work_modes' => $work_modes]);
$conn->close();
