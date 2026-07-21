<?php
session_start();
require_once "../connection_db.php";
header('Content-Type: application/json');

$allowedRoles = ['admin', 'executive', 'hr'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowedRoles)) {
    http_response_code(403);
    exit;
}

$teamId = intval($_POST['team_id'] ?? 0);
if ($teamId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid team ID']);
    exit;
}

$conn->begin_transaction();

try {
    // 🔴 FIRST: delete members
    $stmt = $conn->prepare("DELETE FROM team_members WHERE team_id = ?");
    $stmt->bind_param("i", $teamId);
    $stmt->execute();

    // 🔴 THEN: delete team
    $stmt = $conn->prepare("DELETE FROM teams WHERE id = ?");
    $stmt->bind_param("i", $teamId);
    $stmt->execute();

    $conn->commit();
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => 'Delete failed']);
}
