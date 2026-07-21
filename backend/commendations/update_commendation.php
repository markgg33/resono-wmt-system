<?php
session_start();
require_once "../connection_db.php";
header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? 0;
$role   = $_SESSION['role'] ?? 'user';

$payload = json_decode(file_get_contents("php://input"), true);
$requestCode = trim($payload['request_code'] ?? '');
$valueId = (int)($payload['value_id'] ?? 0);
$points  = (int)($payload['points'] ?? 0);
$reason  = trim($payload['reason'] ?? '');
$nomineeIds = $payload['nominees'] ?? [];

if ($requestCode === '' || $valueId <= 0 || $points <= 0 || $reason === '') {
    echo json_encode(["status" => "error", "message" => "Missing required fields."]);
    exit;
}
if (!is_array($nomineeIds) || count($nomineeIds) === 0) {
    echo json_encode(["status" => "error", "message" => "At least 1 nominee is required."]);
    exit;
}

// fetch one row to validate permission + status
$q = "SELECT * FROM commendations WHERE request_code = ? LIMIT 1";
$stmt = $conn->prepare($q);
$stmt->bind_param("s", $requestCode);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(["status" => "error", "message" => "Request not found"]);
    exit;
}

if (in_array($row['status'], ['approved', 'rejected'])) {
    echo json_encode(["status" => "error", "message" => "Cannot edit finalized request"]);
    exit;
}

$isOwner = ((int)$row['from_user_id'] === (int)$userId);

// permission rules (same as get_single)
$canEdit = false;
if (in_array($role, ['admin', 'executive', 'hr'])) $canEdit = true;
else if ($role === 'user' && $isOwner) $canEdit = true;
// supervisor read-only
if ($role === 'supervisor') $canEdit = false;

if (!$canEdit) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$nomineeIds = array_values(array_unique(array_map('intval', $nomineeIds)));
$placeholders = implode(",", array_fill(0, count($nomineeIds), "?"));
$types = "s" . str_repeat("i", count($nomineeIds));

$conn->begin_transaction();

try {
    // 1) Update shared fields for all rows in request_code
    $u = "UPDATE commendations SET value_id=?, points=?, reason=? WHERE request_code=?";
    $st = $conn->prepare($u);
    $st->bind_param("iiss", $valueId, $points, $reason, $requestCode);
    $st->execute();
    $st->close();

    // 2) Remove nominees not in remaining list
    $d = "DELETE FROM commendations WHERE request_code=? AND to_user_id NOT IN ($placeholders)";
    $st2 = $conn->prepare($d);

    // bind: request_code + nomineeIds
    $bindParams = array_merge([$requestCode], $nomineeIds);

    // mysqli bind_param needs refs
    $refs = [];
    $refs[] = &$types;
    foreach ($bindParams as $k => $v) $refs[] = &$bindParams[$k];
    call_user_func_array([$st2, 'bind_param'], $refs);

    $st2->execute();
    $st2->close();

    $conn->commit();
    echo json_encode(["status" => "success"]);
} catch (Throwable $e) {
    $conn->rollback();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
