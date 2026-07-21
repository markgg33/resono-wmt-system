<?php
session_start();
require 'connection_db.php';
require_once __DIR__ . '/ot-requests/ot_validation_helpers.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit;
}

$user_id      = $_SESSION['user_id'] ?? null;
$tracker_date = $_POST['tracker_date'] ?? '';
$hours        = $_POST['hours'] ?? '';
$reason       = trim($_POST['reason'] ?? '');
$recipient_id = $_POST['recipient_id'] ?? '';

if (!$user_id || !$tracker_date || !$hours || !$reason || !$recipient_id) {
    echo json_encode(["status" => "error", "message" => "Missing required fields."]);
    exit;
}

// Normalize date
$tracker_date = date('Y-m-d', strtotime($tracker_date));

// Normalize HH:MM → HH:MM:SS
if (preg_match('/^(\d{1,2}):([0-5][0-9])$/', $hours, $matches)) {
    $hours = sprintf('%02d:%02d:00', $matches[1], $matches[2]);
} else {
    echo json_encode(["status" => "error", "message" => "Invalid hours format."]);
    exit;
}

/* =====================================================
   1️⃣ CHECK IF USER HAS A SHIFT ON THAT DAY
===================================================== */
// USES DATE FOR COMPUTING OT 
/*$shiftStmt = $conn->prepare("
    SELECT 1 
    FROM task_logs 
    WHERE user_id = ? AND date = ?
    LIMIT 1
");*/

// USES WORK DATE FOR COMPUTING OT
$shiftStmt = $conn->prepare("
    SELECT 1 
    FROM task_logs 
    WHERE user_id = ? AND work_date = ?
    LIMIT 1
");
$shiftStmt->bind_param("is", $user_id, $tracker_date);
$shiftStmt->execute();
$shiftStmt->store_result();

if ($shiftStmt->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "You cannot file overtime without a shift on the selected date."
    ]);
    exit;
}
$shiftStmt->close();

/* =====================================================
   1️⃣b BLOCK OT IF THERE ARE PENDING DTR AMENDMENTS
===================================================== */

$amendStmt = $conn->prepare("
    SELECT da.id
    FROM dtr_amendments da

    LEFT JOIN task_logs tl
        ON tl.id = da.log_id

    LEFT JOIN task_logs_archive tla
        ON tla.id = da.log_id

    WHERE da.user_id = ?
      AND da.status = 'Pending'
      AND (
            tl.work_date = ?
         OR tla.work_date = ?
      )
    LIMIT 1
");

$amendStmt->bind_param(
    "iss",
    $user_id,
    $tracker_date,
    $tracker_date
);

$amendStmt->execute();
$amendStmt->store_result();

if ($amendStmt->num_rows > 0) {

    echo json_encode([
        "status" => "error",
        "message" =>
        "Submission Denied. Please wait until all amendments are approved or rejected before filing overtime."
    ]);

    exit;
}

$amendStmt->close();

/* =====================================================
   2️⃣ COMPUTE THEORETICAL PAID HOURS (shared helper)
===================================================== */
$paidSeconds = ot_compute_theoretical_paid_seconds($conn, (int)$user_id, $tracker_date);

/*
// 8 hrs 15 mins = 29,700 seconds
$requiredPaidSeconds = 29700;
if ($paidSeconds < $requiredPaidSeconds) {
    echo json_encode([
        "status" => "error",
        "message" => "Paid hours should be at least 8 hours and 15 minutes."
    ]);
    exit;
}*/

// =====================================================
// 3) OT LIMIT RULES (NEW)
//    - Expected payable base: 8:00
//    - Min OT: 0:15
//    - Max OT: theoretical paid excess beyond 8:00
// =====================================================

$requiredPaidSeconds = 8 * 3600;  // 8:00:00
$minOTSeconds        = 15 * 60;   // 0:15:00

// Convert requested OT (HH:MM:SS) to seconds
$parts = explode(':', $hours); // $hours is already normalized to HH:MM:SS
$reqH = (int)($parts[0] ?? 0);
$reqM = (int)($parts[1] ?? 0);
$reqS = (int)($parts[2] ?? 0);
$requestedOTSeconds = ($reqH * 3600) + ($reqM * 60) + $reqS;

// Min OT rule
if ($requestedOTSeconds < $minOTSeconds) {
    echo json_encode([
        "status" => "error",
        "message" => "Minimum overtime duration is 15 minutes."
    ]);
    exit;
}

// Must have at least 8h theoretical paid to file any OT
if ($paidSeconds < $requiredPaidSeconds) {
    echo json_encode([
        "status" => "error",
        "message" => "You cannot file overtime unless your theoretical paid hours reach at least 8:00."
    ]);
    exit;
}

// Max OT = theoretical paid beyond 8:00, minus any OT already filed (pending + approved) this date
$maxOTSeconds = $paidSeconds - $requiredPaidSeconds;
$alreadyCommittedSeconds = ot_sum_committed_ot_seconds($conn, (int)$user_id, $tracker_date, null);
$remainingOTSeconds = max(0, $maxOTSeconds - $alreadyCommittedSeconds);

error_log("
OT REQUEST CHECK

User: {$user_id}
Date: {$tracker_date}

Paid Seconds: {$paidSeconds}
Required Seconds: {$requiredPaidSeconds}

Max OT Seconds: {$maxOTSeconds}
Already Committed: {$alreadyCommittedSeconds}
Remaining OT: {$remainingOTSeconds}
Requested OT: {$requestedOTSeconds}
");

if ($requestedOTSeconds > $remainingOTSeconds) {
    $remH = floor($remainingOTSeconds / 3600);
    $remM = floor(($remainingOTSeconds % 3600) / 60);
    $capH = floor($maxOTSeconds / 3600);
    $capM = floor(($maxOTSeconds % 3600) / 60);
    $usedH = floor($alreadyCommittedSeconds / 3600);
    $usedM = floor(($alreadyCommittedSeconds % 3600) / 60);

    echo json_encode([
        "status" => "error",
        "message" =>
        "You can only file up to " . sprintf("%d:%02d", $remH, $remM) .
            " more overtime for this date (" . sprintf("%d:%02d", $usedH, $usedM) .
            " already pending or approved; daily OT cap " . sprintf("%d:%02d", $capH, $capM) .
            " above 8:00)."
    ]);
    exit;
}


/* =====================================================
   2️⃣b VALIDATE OT HOURS DON'T EXCEED EXCESS PAID HOURS
=====================================================

// Calculate excess paid hours (paid hours - 8:15)
$excessPaidSeconds = $paidSeconds - $requiredPaidSeconds;

// Convert requested OT hours to seconds
$requestedHoursParts = explode(':', $hours);
$requestedHours = (int)$requestedHoursParts[0];
$requestedMinutes = (int)$requestedHoursParts[1];
$requestedOTSeconds = ($requestedHours * 3600) + ($requestedMinutes * 60);

// Validate that requested OT doesn't exceed excess paid hours
if ($requestedOTSeconds > $excessPaidSeconds) {
    // Format excess for display
    $excessHours = floor($excessPaidSeconds / 3600);
    $excessMins = floor(($excessPaidSeconds % 3600) / 60);
    $excessDisplay = sprintf('%d:%02d', $excessHours, $excessMins);
    
    echo json_encode([
        "status" => "error",
        "message" => "You can only file up to {$excessDisplay} hours of overtime based on your paid hours excess."
    ]);
    exit;
}
     */

/* =====================================================
   2️⃣c PREVENT MULTIPLE OT REQUESTS PER DAY
===================================================== 
$dupStmt = $conn->prepare("
    SELECT 1 
    FROM ot_requests
    WHERE user_id = ? AND tracker_date = ?
    LIMIT 1
");
$dupStmt->bind_param("is", $user_id, $tracker_date);
$dupStmt->execute();
$dupStmt->store_result();

if ($dupStmt->num_rows > 0) {
    echo json_encode([
        "status" => "error",
        "message" => "You have already filed an overtime request for this date."
    ]);
    exit;
}
$dupStmt->close();*/

/* =====================================================
   2️⃣c PREVENT DUPLICATE PENDING OT REQUESTS (SOFT) //KEEP IN CASE OF FLAGGING DUPLICATE LEAVE IN PENDING STATUS
===================================================== 
$dupStmt = $conn->prepare("
    SELECT 1 
    FROM ot_requests
    WHERE user_id = ?
      AND tracker_date = ?
      AND status = 'pending'
    LIMIT 1
");
$dupStmt->bind_param("is", $user_id, $tracker_date);
$dupStmt->execute();
$dupStmt->store_result();

if ($dupStmt->num_rows > 0) {
    echo json_encode([
        "status" => "error",
        "message" => "You already have a pending overtime request for this date."
    ]);
    exit;
}
$dupStmt->close();



/* =====================================================
   3️⃣ INSERT OT REQUEST
===================================================== */

$stmt = $conn->prepare("
    INSERT INTO ot_requests (user_id, tracker_date, hours, reason, recipient_id, status)
    VALUES (?, ?, ?, ?, ?, 'pending')
");
$stmt->bind_param("isssi", $user_id, $tracker_date, $hours, $reason, $recipient_id);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Overtime request submitted successfully."
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to submit overtime request."
    ]);
}

$stmt->close();
$conn->close();
