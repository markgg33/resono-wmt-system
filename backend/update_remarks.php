<?php

//WORKING VERSION

/*require __DIR__ . '/connection_db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id'] ?? null;
$remarks = $data['remarks'] ?? '';

if (!$id) {
    echo json_encode(["status" => "error", "message" => "Missing ID"]);
    exit;
}

$stmt = $conn->prepare("UPDATE task_logs SET remarks = ? WHERE id = ?");
$stmt->bind_param("si", $remarks, $id);

if ($stmt->execute()) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => $stmt->error]);
}

$stmt->close();
$conn->close();*/

/*session_start();
require_once "connection_db.php";
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id'])) {
    echo json_encode(["status" => "error", "message" => "Missing ID"]);
    exit;
}

$id = (int)$data['id'];
$remarks = trim($data['remarks'] ?? '');

// Prepare SQL dynamically depending on value
if ($remarks === '') {
    $sql = "UPDATE %s SET remarks = NULL WHERE id = ?";
    $types = "i";
    $params = [$id];
} else {
    $sql = "UPDATE %s SET remarks = ? WHERE id = ?";
    $types = "si";
    $params = [$remarks, $id];
}

// Try main table first
$stmt = $conn->prepare(sprintf($sql, "task_logs"));
$stmt->bind_param($types, ...$params);
$stmt->execute();
$affected_rows_main = $stmt->affected_rows;
$stmt->close();

// Try archive if not found
if ($affected_rows_main === 0) {
    $stmt2 = $conn->prepare(sprintf($sql, "task_logs_archive"));
    $stmt2->bind_param($types, ...$params);
    $stmt2->execute();
    $affected_rows_archive = $stmt2->affected_rows;
    $stmt2->close();

    if ($affected_rows_archive > 0) {
        echo json_encode(["status" => "success", "source" => "archive", "id" => $id, "remarks" => $remarks]);
    } else {
        echo json_encode(["status" => "error", "message" => "Record not found in either table"]);
    }
} else {
    echo json_encode(["status" => "success", "source" => "main", "id" => $id, "remarks" => $remarks]);
}

$conn->close();
?>*/

require __DIR__ . '/connection_db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id'] ?? null;
$remarks = $data['remarks'] ?? '';

if (!$id) {
    echo json_encode(["status" => "error", "message" => "Missing ID"]);
    exit;
}

// Try updating task_logs first
$stmt = $conn->prepare("UPDATE task_logs SET remarks = ? WHERE id = ?");
$stmt->bind_param("si", $remarks, $id);
$stmt->execute();
$affected_rows_main = $stmt->affected_rows;
$stmt->close();

// If not found in main, try the archive table
if ($affected_rows_main === 0) {
    $stmt2 = $conn->prepare("UPDATE task_logs_archive SET remarks = ? WHERE id = ?");
    $stmt2->bind_param("si", $remarks, $id);
    $stmt2->execute();
    $affected_rows_archive = $stmt2->affected_rows;
    $stmt2->close();

    if ($affected_rows_archive > 0) {
        echo json_encode(["status" => "success", "source" => "archive"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Record not found in either table"]);
    }
} else {
    echo json_encode(["status" => "success", "source" => "main"]);
}

$conn->close();


