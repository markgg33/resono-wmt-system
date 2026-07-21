<?php
session_start();
require_once "../connection_db.php";
header('Content-Type: application/json');

// 🔐 Permission check
$allowedRoles = ['admin', 'executive', 'hr'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], $allowedRoles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$teamName = trim($data['team_name'] ?? '');
$members  = $data['members'] ?? [];
$creator  = $_SESSION['user_id'];

if ($teamName === '' || empty($members)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit;
}

$conn->begin_transaction();

try {
    // Insert team
    $stmt = $conn->prepare("
        INSERT INTO teams (name, created_by)
        VALUES (?, ?)
    ");
    $stmt->bind_param("si", $teamName, $creator);
    $stmt->execute();

    $teamId = $stmt->insert_id;

    // Insert members
    $memberStmt = $conn->prepare("
        INSERT INTO team_members (team_id, user_id)
        VALUES (?, ?)
    ");

    foreach ($members as $userId) {
        $memberStmt->bind_param("ii", $teamId, $userId);
        $memberStmt->execute();
    }

    $conn->commit();

    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to create team']);
}
