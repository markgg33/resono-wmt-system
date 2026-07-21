<?php
/*
session_start();
require '../connection_db.php';
header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$query = "
    SELECT id, role, CONCAT(first_name, ' ', last_name) AS username
    FROM users
    WHERE role IN ('admin', 'executive', 'hr', 'supervisor')
      AND id != ?
    ORDER BY role, first_name
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$recipients = [];
while ($row = $result->fetch_assoc()) {
    $recipients[] = $row;
}

echo json_encode(["status" => "success", "recipients" => $recipients]);*/

session_start();
require '../connection_db.php';

header('Content-Type: application/json');

$userId    = $_SESSION['user_id'] ?? null;
$requestId = intval($_GET['leave_id'] ?? 0);

if (!$userId) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

/*
|--------------------------------------------------------------------------
| Load all valid recipients
| - Admin / Executive / HR / Supervisor
| - EXCLUDE current user
| - BUT include the already-selected recipient for this request
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT id, role, CONCAT(first_name, ' ', last_name) AS username
    FROM users
    WHERE role IN ('admin', 'executive', 'hr', 'supervisor')
      AND (
          id != ?
          OR id = (
              SELECT recipient_id
              FROM leave_requests
              WHERE id = ?
          )
      )
    ORDER BY role, first_name
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $userId, $requestId);
$stmt->execute();

$result = $stmt->get_result();
$recipients = [];

while ($row = $result->fetch_assoc()) {
    $recipients[] = $row;
}

echo json_encode([
    "status" => "success",
    "recipients" => $recipients
]);

