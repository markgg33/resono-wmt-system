<?php

//WORKING VERSION
/*
session_start();
require '../connection_db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit;
}

// Get logged-in user info
$user_id   = $_SESSION['user_id'] ?? null;
$userRole  = $_SESSION['role'] ?? 'user';

$data = json_decode(file_get_contents('php://input'), true);
$id            = $data['id'] ?? null;
$tracker_date  = $data['tracker_date'] ?? '';
$hours         = $data['hours'] ?? '';
$reason        = trim($data['reason'] ?? '');
$recipient_id  = $data['recipient_id'] ?? '';

if (!$id || !$tracker_date || !$hours || !$reason || !$recipient_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
    exit;
}

// Validate hours format HH:MM
if (!preg_match('/^(\d{1,2}):([0-5][0-9])$/', $hours, $matches)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid hours format. Use HH:MM.']);
    exit;
}
$hoursFormatted = sprintf('%02d:%02d:00', $matches[1], $matches[2]);

// Regular user: check ownership and pending status
if ($userRole === 'user') {
    $statusCheck = $conn->prepare("SELECT status FROM ot_requests WHERE id = ? AND user_id = ?");
    $statusCheck->bind_param("ii", $id, $user_id);
    $statusCheck->execute();
    $statusResult = $statusCheck->get_result()->fetch_assoc();
    $statusCheck->close();

    if (!$statusResult) {
        echo json_encode(['status' => 'error', 'message' => 'OT request not found or not owned by you.']);
        exit;
    }

    if ($statusResult['status'] !== 'pending') {
        echo json_encode(['status' => 'error', 'message' => 'Cannot edit approved/rejected request.']);
        exit;
    }

    // Update query for regular user
    $stmt = $conn->prepare("
        UPDATE ot_requests
        SET tracker_date = ?, hours = ?, reason = ?, recipient_id = ?
        WHERE id = ? AND user_id = ?
    ");
    $stmt->bind_param("sssiii", $tracker_date, $hoursFormatted, $reason, $recipient_id, $id, $user_id);
} else {
    // Higher roles: update any OT request regardless of owner or status
    $stmt = $conn->prepare("
        UPDATE ot_requests
        SET tracker_date = ?, hours = ?, reason = ?, recipient_id = ?
        WHERE id = ?
    ");
    $stmt->bind_param("ssssi", $tracker_date, $hoursFormatted, $reason, $recipient_id, $id);
}

// Execute update
if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'OT request updated successfully.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update OT request.']);
}

$stmt->close();
$conn->close();
*/

//8hrs and 15 mins excess OT Ruling
session_start();
require '../connection_db.php';
require_once __DIR__ . '/ot_validation_helpers.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit;
}

// Get logged-in user info
$user_id   = $_SESSION['user_id'] ?? null;
$userRole  = $_SESSION['role'] ?? 'user';

$data = json_decode(file_get_contents('php://input'), true);
$id            = $data['id'] ?? null;
$tracker_date  = $data['tracker_date'] ?? '';
$hours         = $data['hours'] ?? '';
$reason        = trim($data['reason'] ?? '');
$recipient_id  = $data['recipient_id'] ?? '';

if (!$id || !$tracker_date || !$hours || !$reason || !$recipient_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
    exit;
}

// Normalize inputs
$id = (int)$id;
$tracker_date = date('Y-m-d', strtotime($tracker_date));

// Fetch request owner + current tracker_date (validate based on employee, not editor)
$stmtOwner = $conn->prepare("SELECT user_id, tracker_date FROM ot_requests WHERE id = ? LIMIT 1");
$stmtOwner->bind_param("i", $id);
$stmtOwner->execute();
$ownerRow = $stmtOwner->get_result()->fetch_assoc();
$stmtOwner->close();

if (!$ownerRow) {
    echo json_encode(['status' => 'error', 'message' => 'OT request not found.']);
    exit;
}

$requestOwnerId = (int)$ownerRow['user_id'];
$currentTrackerDate = !empty($ownerRow['tracker_date'])
    ? date('Y-m-d', strtotime($ownerRow['tracker_date']))
    : null;


// Validate hours format HH:MM
if (!preg_match('/^(\d{1,2}):([0-5][0-9])$/', $hours, $matches)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid hours format. Use HH:MM.']);
    exit;
}
$hoursFormatted = sprintf('%02d:%02d:00', $matches[1], $matches[2]);

// Regular user: check ownership and pending status
if ($userRole === 'user') {
    $statusCheck = $conn->prepare("SELECT status FROM ot_requests WHERE id = ? AND user_id = ?");
    $statusCheck->bind_param("ii", $id, $user_id);
    $statusCheck->execute();
    $statusResult = $statusCheck->get_result()->fetch_assoc();
    $statusCheck->close();

    if (!$statusResult) {
        echo json_encode(['status' => 'error', 'message' => 'OT request not found or not owned by you.']);
        exit;
    }

    if ($statusResult['status'] !== 'pending') {
        echo json_encode(['status' => 'error', 'message' => 'Cannot edit approved/rejected request.']);
        exit;
    }
}

/* =====================================================
   VALIDATE OT HOURS AGAINST EXCESS PAID HOURS
=====================================================

// 1️⃣ Compute paid seconds for the day (similar to submit)
$shiftStmt = $conn->prepare("SELECT 1 FROM task_logs WHERE user_id=? AND date=? LIMIT 1");
$shiftStmt->bind_param("is", $user_id, $tracker_date);
$shiftStmt->execute();
$shiftStmt->store_result();
if ($shiftStmt->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'No shift found on this date.']);
    exit;
}
$shiftStmt->close();

// Fetch logs for the day
$logStmt = $conn->prepare("
    SELECT work_mode_id, task_description_id, start_time, end_time
    FROM task_logs
    WHERE user_id=? AND date=?
");
$logStmt->bind_param("is", $user_id, $tracker_date);
$logStmt->execute();
$result = $logStmt->get_result();

$paidSeconds = 0;
$usedPaidBreak = 0;

// Preload production work modes
$productionWorkModes = [];
$modeQuery = $conn->query("SELECT id, name FROM work_modes");
while ($wm = $modeQuery->fetch_assoc()) {
    $name = strtolower($wm['name']);
    if (!in_array($name, ['offphone', 'training', 'break', 'personal', 'technical_error'])) {
        $productionWorkModes[] = (int)$wm['id'];
    }
}

while ($log = $result->fetch_assoc()) {
    if (!$log['end_time']) continue;
    $startTs = strtotime($log['start_time']);
    $endTs = strtotime($log['end_time']);
    if ($endTs < $startTs) $endTs += 86400;
    $duration = max(0, $endTs - $startTs);

    // Fetch description
    $desc = '';
    if ($log['task_description_id']) {
        $stmt = $conn->prepare("SELECT description FROM task_descriptions WHERE id=?");
        $stmt->bind_param("i", $log['task_description_id']);
        $stmt->execute();
        $desc = strtolower($stmt->get_result()->fetch_assoc()['description'] ?? '');
        $stmt->close();
    }

    // Work mode name
    $wmName = '';
    $stmt = $conn->prepare("SELECT name FROM work_modes WHERE id=?");
    $stmt->bind_param("i", $log['work_mode_id']);
    $stmt->execute();
    $wmName = strtolower($stmt->get_result()->fetch_assoc()['name'] ?? '');
    $stmt->close();

    if (
        strpos($desc, 'resono') !== false ||
        strpos($desc, 'training') !== false ||
        strpos($desc, 'offphone') !== false
    ) {
        $paidSeconds += $duration;
    } elseif (strpos($desc, 'away - break') !== false) {
        $remainingPaid = max(0, 1800 - $usedPaidBreak);
        if ($remainingPaid > 0) {
            $paid = min($duration, $remainingPaid);
            $paidSeconds += $paid;
            $usedPaidBreak += $paid;
        }
    } elseif (strpos($desc, 'system down') !== false || strpos($desc, 'system issue') !== false || $wmName === 'technical_error') {
        $paidSeconds += $duration;
    } else {
        if (in_array((int)$log['work_mode_id'], $productionWorkModes)) {
            $paidSeconds += $duration;
        }
    }
}

$logStmt->close();

$requiredPaidSeconds = 29700; // 8h 15m
if ($paidSeconds < $requiredPaidSeconds) {
    echo json_encode(['status'=>'error','message'=>'Paid hours are less than 8 hours 15 minutes. Cannot file OT.']);
    exit;
}

// Calculate excess
$excessPaidSeconds = $paidSeconds - $requiredPaidSeconds;
$requestedOTSeconds = $matches[1]*3600 + $matches[2]*60;

if ($requestedOTSeconds > $excessPaidSeconds) {
    $excessH = floor($excessPaidSeconds/3600);
    $excessM = floor(($excessPaidSeconds%3600)/60);
    echo json_encode([
        'status'=>'error',
        'message'=>"Requested OT exceeds allowable excess. Maximum: {$excessH}:".str_pad($excessM,2,'0',STR_PAD_LEFT)
    ]);
    exit;
}
     */

/* =====================================================
   VALIDATE OT HOURS AGAINST THEORETICAL PAID HOURS EXCESS (NEW 8:00 BASIS)
===================================================== */

// 1) Check shift exists for the request owner (match submit: uses work_date)
$shiftStmt = $conn->prepare("SELECT 1 FROM task_logs WHERE user_id=? AND work_date=? LIMIT 1");
$shiftStmt->bind_param("is", $requestOwnerId, $tracker_date);
$shiftStmt->execute();
$shiftStmt->store_result();
if ($shiftStmt->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'No shift found on this date.']);
    exit;
}
$shiftStmt->close();

/* =====================================================
   BLOCK OT EDITS IF DTR AMENDMENTS ARE PENDING
===================================================== */

if (ot_has_pending_amendments(
    $conn,
    (int)$requestOwnerId,
    $tracker_date
)) {

    echo json_encode([
        'status' => 'error',
        'message' =>
        'This OT request cannot be update due to  pending DTR amendment request(s) for the selected date.'
    ]);

    exit;
}

// 2) Compute theoretical paid seconds (shared with submit/export)
$paidSeconds = ot_compute_theoretical_paid_seconds($conn, $requestOwnerId, $tracker_date);

// 3) Apply OT bounds
$requiredPaidSeconds = 8 * 3600;  // 8:00
$minOTSeconds = 15 * 60;          // 0:15
$requestedOTSeconds = ((int)$matches[1] * 3600) + ((int)$matches[2] * 60);

if ($requestedOTSeconds < $minOTSeconds) {
    echo json_encode(['status' => 'error', 'message' => 'Minimum overtime duration is 15 minutes.']);
    exit;
}

if ($paidSeconds < $requiredPaidSeconds) {
    echo json_encode(['status' => 'error', 'message' => 'You cannot file overtime unless theoretical paid hours reach at least 8:00.']);
    exit;
}

$maxOTSeconds = $paidSeconds - $requiredPaidSeconds;
$alreadyCommittedSeconds = ot_sum_committed_ot_seconds($conn, $requestOwnerId, $tracker_date, $id);
$remainingOTSeconds = max(0, $maxOTSeconds - $alreadyCommittedSeconds);

if ($requestedOTSeconds > $remainingOTSeconds) {
    $remH = floor($remainingOTSeconds / 3600);
    $remM = floor(($remainingOTSeconds % 3600) / 60);
    $capH = floor($maxOTSeconds / 3600);
    $capM = floor(($maxOTSeconds % 3600) / 60);
    $usedH = floor($alreadyCommittedSeconds / 3600);
    $usedM = floor(($alreadyCommittedSeconds % 3600) / 60);
    echo json_encode([
        'status' => 'error',
        'message' =>
        'You can only file up to ' . sprintf('%d:%02d', $remH, $remM) .
            ' more overtime for this date (' . sprintf('%d:%02d', $usedH, $usedM) .
            ' already pending or approved on other requests; daily OT cap ' . sprintf('%d:%02d', $capH, $capM) .
            ' above 8:00).'
    ]);
    exit;
}


/* =====================================================
   PREVENT MULTIPLE OT REQUESTS PER DAY (EDIT MODE)
===================================================== */
// Only block duplicates when the date is being changed.
if ($currentTrackerDate !== null && $tracker_date !== $currentTrackerDate) {
    $dupStmt = $conn->prepare("
        SELECT 1
        FROM ot_requests
        WHERE user_id = ?
          AND tracker_date = ?
          AND id != ?
        LIMIT 1
    ");
    $dupStmt->bind_param("isi", $requestOwnerId, $tracker_date, $id);
    $dupStmt->execute();
    $dupStmt->store_result();

    if ($dupStmt->num_rows > 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'You already have another overtime request for this date.'
        ]);
        exit;
    }
    $dupStmt->close();
}


/* =====================================================
   UPDATE OT REQUEST
===================================================== */
if ($userRole === 'user') {
    $stmt = $conn->prepare("
        UPDATE ot_requests
        SET tracker_date=?, hours=?, reason=?, recipient_id=?
        WHERE id=? AND user_id=?
    ");
    $stmt->bind_param("sssiii", $tracker_date, $hoursFormatted, $reason, $recipient_id, $id, $user_id);
} else {
    $stmt = $conn->prepare("
        UPDATE ot_requests
        SET tracker_date=?, hours=?, reason=?, recipient_id=?
        WHERE id=?
    ");
    $stmt->bind_param("ssssi", $tracker_date, $hoursFormatted, $reason, $recipient_id, $id);
}

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'OT request updated successfully.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update OT request.']);
}

$stmt->close();
$conn->close();
