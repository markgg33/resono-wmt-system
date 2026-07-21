<?php
session_start();
require_once "../connection_db.php";
header('Content-Type: application/json');

$teamId = intval($_GET['team_id'] ?? 0);

if ($teamId === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid team ID']);
    exit;
}

$sql = "
    SELECT 
        t.id,
        t.name,
        CONCAT(u.first_name, ' ', u.last_name) AS created_by,
        t.created_at
    FROM teams t
    JOIN users u ON u.id = t.created_by
    WHERE t.id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teamId);
$stmt->execute();
$team = $stmt->get_result()->fetch_assoc();

if (!$team) {
    echo json_encode(['status' => 'error', 'message' => 'Team not found']);
    exit;
}

$membersSql = "
    SELECT 
        u.id,
        CONCAT(u.first_name, ' ', u.last_name) AS name,
        u.role
    FROM team_members tm
    JOIN users u ON u.id = tm.user_id
    WHERE tm.team_id = ?
";

$stmt = $conn->prepare($membersSql);
$stmt->bind_param("i", $teamId);
$stmt->execute();
$members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'status' => 'success',
    'team' => $team,
    'members' => $members
]);
