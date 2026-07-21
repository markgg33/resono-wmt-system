<?php

//FOR FETCHING LEAVE BALANCES
require_once "connection_db.php";
session_start();

$user_id = $_SESSION["user_id"] ?? 0;

if (!$user_id) {
    echo json_encode(["success" => false, "message" => "Not logged in"]);
    exit;
}

$stmt = $conn->prepare("
    SELECT vacation_leave, sick_leave, compassionate_leave, emergency_leave
    FROM users
    WHERE id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

echo json_encode([
    "success" => true,
    "leaves" => $result
]);
