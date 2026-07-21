<?php
session_start();
require '../connection_db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION['user_id'] ?? null;
$request_id = $data['id'] ?? null;
$remarks = trim($data['remarks'] ?? '');

if (!$user_id || !$request_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
    exit;
}

// Check if user has higher role
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$role = $res->fetch_assoc()['role'] ?? 'user';
$stmt->close();

if (!in_array($role, ['admin', 'hr', 'executive', 'supervisor'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized action.']);
    exit;
}

// Reject OT request
$stmt = $conn->prepare("
    UPDATE ot_requests
    SET status = 'rejected', remarks = ?, checked_by = ?, date_checked = NOW()
    WHERE id = ?
");
$stmt->bind_param("sii", $remarks, $user_id, $request_id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'OT request rejected successfully.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to reject OT request.']);
}

$stmt->close();
$conn->close();
