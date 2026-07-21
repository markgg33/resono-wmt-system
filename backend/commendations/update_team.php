<?php
session_start();
require_once "../connection_db.php";
header('Content-Type: application/json');

$allowedRoles = ['admin', 'executive', 'hr'];
if (!in_array($_SESSION['role'], $allowedRoles)) {
    http_response_code(403);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$teamId = intval($data['team_id']);
$teamName = trim($data['team_name']);
$members = $data['members'] ?? [];

$conn->begin_transaction();

try {
    // Update team name
    $stmt = $conn->prepare("UPDATE teams SET name = ? WHERE id = ?");
    $stmt->bind_param("si", $teamName, $teamId);
    $stmt->execute();

    // 🔥 DELETE ALL existing members
    $stmt = $conn->prepare("DELETE FROM team_members WHERE team_id = ?");
    $stmt->bind_param("i", $teamId);
    $stmt->execute();

    // 🔥 INSERT updated members
    if (!empty($members)) {
        $stmt = $conn->prepare(
            "INSERT INTO team_members (team_id, user_id) VALUES (?, ?)"
        );
        foreach ($members as $userId) {
            $uid = intval($userId);
            $stmt->bind_param("ii", $teamId, $uid);
            $stmt->execute();
        }
    }

    $conn->commit();
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'status' => 'error',
        'message' => 'Update failed'
    ]);
}
