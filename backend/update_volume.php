<?php
//WORKING VERSION

/*session_start();
require_once "connection_db.php";
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id'])) {
    echo json_encode(["status" => "error", "message" => "Missing ID"]);
    exit;
}

$id = (int)$data['id'];
$volume = $data['volume_remark'];

// Handle empty/null
if ($volume === null || $volume === "") {
    $stmt = $conn->prepare("UPDATE task_logs SET volume_remark = NULL WHERE id = ?");
    $stmt->bind_param("i", $id);
} else {
    if (!is_numeric($volume)) {
        echo json_encode(["status" => "error", "message" => "Volume must be numeric or empty"]);
        exit;
    }
    $volume = (float)$volume;
    $stmt = $conn->prepare("UPDATE task_logs SET volume_remark = ? WHERE id = ?");
    $stmt->bind_param("si", $volume, $id);
}

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "id" => $id,
        "volume_remark" => ($volume === "" ? null : $volume)
    ]);
} else {
    echo json_encode(["status" => "error", "message" => $stmt->error]);
}

$stmt->close();
$conn->close();*/

session_start();
require_once "connection_db.php";
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id'])) {
    echo json_encode(["status" => "error", "message" => "Missing ID"]);
    exit;
}

$id = (int)$data['id'];
$volume = $data['volume_remark'];

// Prepare base SQL depending on input
if ($volume === null || $volume === "") {
    $sql = "UPDATE %s SET volume_remark = NULL WHERE id = ?";
    $types = "i";
    $params = [$id];
} else {
    if (!is_numeric($volume)) {
        echo json_encode(["status" => "error", "message" => "Volume must be numeric or empty"]);
        exit;
    }
    $volume = (float)$volume;
    $sql = "UPDATE %s SET volume_remark = ? WHERE id = ?";
    $types = "si";
    $params = [$volume, $id];
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
        echo json_encode(["status" => "success", "source" => "archive", "id" => $id, "volume_remark" => $volume]);
    } else {
        echo json_encode(["status" => "error", "message" => "Record not found in either table"]);
    }
} else {
    echo json_encode(["status" => "success", "source" => "main", "id" => $id, "volume_remark" => $volume]);
}

$conn->close();

