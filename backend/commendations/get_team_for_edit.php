<?php
session_start();
require_once "../connection_db.php";
header('Content-Type: application/json');

$allowedRoles = ['admin', 'executive', 'hr'];
if (!in_array($_SESSION['role'], $allowedRoles)) {
    http_response_code(403);
    exit;
}

$teamId = intval($_GET['team_id'] ?? 0);

$sql = "SELECT id, name FROM teams WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teamId);
$stmt->execute();
$team = $stmt->get_result()->fetch_assoc();

if (!$team) {
    echo json_encode(['status' => 'error', 'message' => 'Team not found']);
    exit;
}

$membersSql = "SELECT user_id FROM team_members WHERE team_id = ?";
$stmt = $conn->prepare($membersSql);
$stmt->bind_param("i", $teamId);
$stmt->execute();
$members = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'user_id');

echo json_encode([
    'status' => 'success',
    'team' => $team,
    'members' => $members
]);
