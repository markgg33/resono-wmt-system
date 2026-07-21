<?php
session_start();
require_once "../connection_db.php";

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$userId = $_SESSION['user_id'];

$sql = "SELECT 
            vacation_leave,
            sick_leave,
            compassionate_leave,
            emergency_leave
        FROM users
        WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        "vacationLeave" => (float)$row["vacation_leave"],
        "sickLeave" => (float)$row["sick_leave"],
        "compLeave" => (float)$row["compassionate_leave"],
        "emergencyLeave" => (float)$row["emergency_leave"]
    ]);
} else {
    echo json_encode(["error" => "User not found"]);
}
