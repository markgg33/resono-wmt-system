<?php
session_start();
require_once "../connection_db.php";
header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? 0;
$input = json_decode(file_get_contents("php://input"), true);
$id = (int)($input['id'] ?? 0);

if ($userId <= 0 || $id <= 0) {
    echo json_encode(["status" => "error", "message" => "Invalid request"]);
    exit;
}

/*$sql = "UPDATE commendations
        SET seen_at = NOW()
        WHERE id = ?
          AND to_user_id = ?
          AND seen_at IS NULL";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id, $userId);
$stmt->execute();*/

// get request_code first
$stmt = $conn->prepare("SELECT request_code FROM commendations WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

$requestCode = $res['request_code'] ?? null;

// update ALL rows under same request
if ($requestCode) {
    $sql = "UPDATE commendations
            SET seen_at = NOW()
            WHERE request_code = ?
              AND to_user_id = ?
              AND seen_at IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $requestCode, $userId);
} else {
    // fallback legacy
    $sql = "UPDATE commendations
            SET seen_at = NOW()
            WHERE id = ?
              AND to_user_id = ?
              AND seen_at IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id, $userId);
}

$stmt->execute();

echo json_encode(["status" => "success"]);
