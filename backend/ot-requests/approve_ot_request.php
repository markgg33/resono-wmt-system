<?php
session_start();
require '../connection_db.php';
require_once __DIR__ . '/ot_validation_helpers.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$approver_id = $_SESSION['user_id'] ?? null;
$request_id = isset($data['id']) ? (int)$data['id'] : 0;
$remarks = trim($data['remarks'] ?? '');

if (!$approver_id || !$request_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
    exit;
}

$roleStmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$roleStmt->bind_param("i", $approver_id);
$roleStmt->execute();
$roleRow = $roleStmt->get_result()->fetch_assoc();
$roleStmt->close();

$role = $roleRow['role'] ?? 'user';
if (!in_array($role, ['admin', 'hr', 'executive', 'supervisor'], true)) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized action.']);
    exit;
}

$fetch = $conn->prepare("
    SELECT id, user_id, tracker_date, hours, status
    FROM ot_requests
    WHERE id = ?
    LIMIT 1
");
$fetch->bind_param("i", $request_id);
$fetch->execute();
$req = $fetch->get_result()->fetch_assoc();
$fetch->close();

if (!$req) {
    echo json_encode(['status' => 'error', 'message' => 'OT request not found.']);
    exit;
}

if (($req['status'] ?? '') !== 'pending') {
    echo json_encode(['status' => 'error', 'message' => 'Only pending OT requests can be approved.']);
    exit;
}

$owner_id = (int)$req['user_id'];
$tracker_date = date('Y-m-d', strtotime($req['tracker_date']));

$shiftStmt = $conn->prepare("SELECT 1 FROM task_logs WHERE user_id = ? AND work_date = ? LIMIT 1");
$shiftStmt->bind_param("is", $owner_id, $tracker_date);
$shiftStmt->execute();
$shiftStmt->store_result();
if ($shiftStmt->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'No shift found for this employee on the OT date.']);
    exit;
}
$shiftStmt->close();

/* =====================================================
   BLOCK APPROVAL IF DTR AMENDMENTS ARE PENDING
===================================================== */

if (ot_has_pending_amendments(
    $conn,
    (int)$owner_id,
    $tracker_date
)) {

    echo json_encode([
        'status' => 'error',
        'message' =>
        'Cannot approve this OT request because of the pending DTR amendment request(s). Please resolve the amendments.'
    ]);

    exit;
}

$paidSeconds = ot_compute_theoretical_paid_seconds($conn, $owner_id, $tracker_date);
$requiredPaidSeconds = 8 * 3600;
if ($paidSeconds < $requiredPaidSeconds) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Cannot approve: theoretical paid hours are below 8:00 for this date.'
    ]);
    exit;
}

$maxOTSeconds = $paidSeconds - $requiredPaidSeconds;
$committedSeconds = ot_sum_committed_ot_seconds($conn, $owner_id, $tracker_date, null);

if ($committedSeconds > $maxOTSeconds) {
    $capH = floor($maxOTSeconds / 3600);
    $capM = floor(($maxOTSeconds % 3600) / 60);
    $sumH = floor($committedSeconds / 3600);
    $sumM = floor(($committedSeconds % 3600) / 60);
    echo json_encode([
        'status' => 'error',
        'message' =>
        'Cannot approve: total pending/approved OT for this date (' .
            sprintf('%d:%02d', $sumH, $sumM) .
            ') exceeds the allowed OT (' .
            sprintf('%d:%02d', $capH, $capM) .
            ') based on theoretical paid hours above 8:00.'
    ]);
    exit;
}

$thisSeconds = ot_hours_string_to_seconds((string)($req['hours'] ?? ''));
if ($thisSeconds < 15 * 60) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid OT duration on request (minimum 15 minutes).']);
    exit;
}

$upd = $conn->prepare("
    UPDATE ot_requests
    SET status = 'approved', remarks = ?, checked_by = ?, date_checked = NOW()
    WHERE id = ? AND status = 'pending'
");
$upd->bind_param("sii", $remarks, $approver_id, $request_id);

if ($upd->execute() && $upd->affected_rows > 0) {
    echo json_encode(['status' => 'success', 'message' => 'OT request approved successfully.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to approve OT request (it may have already been processed).']);
}

$upd->close();
$conn->close();
