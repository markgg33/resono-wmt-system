<?php
session_start();
require '../connection_db.php';
header('Content-Type: application/json');

$userId   = $_SESSION['user_id'] ?? 0;
$userRole = $_SESSION['role'] ?? '';

if (!$userId) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["status" => "error", "message" => "Invalid JSON"]);
    exit;
}

$requestId = intval($data['leave_id'] ?? 0);
$reason    = trim($data['reason'] ?? '');
$items     = $data['items'] ?? [];
$recipient = intval($data['recipient_id'] ?? 0);

if (!$requestId || !$reason || !is_array($items) || count($items) === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Missing fields",
        "debug" => $data
    ]);
    exit;
}


/* -----------------------------
   FETCH REQUEST
----------------------------- */
$stmt = $conn->prepare("
    SELECT *
    FROM leave_requests
    WHERE id = ?
");

$stmt->bind_param("i", $requestId);
$stmt->execute();
$request = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$request || $request['status'] !== 'Pending') {
    echo json_encode(["status" => "error", "message" => "Request not editable"]);
    exit;
}

/* -----------------------------
   PERMISSION CHECK
----------------------------- */
$allowed = false;

// Owner
if ($request['user_id'] == $userId) {
    $allowed = true;
}

// Elevated roles
if (in_array($userRole, ['admin', 'hr', 'executive'])) {
    $allowed = true;
}

if ($userRole === 'supervisor') {
    // supervisors should NOT edit others' requests
    $allowed = ($request['user_id'] == $userId);
}


if (!$allowed) {
    echo json_encode(["status" => "error", "message" => "Access denied"]);
    exit;
}

// ----------------------------------------------------
// ROLE GUARD (TEST)
// ----------------------------------------------------

$restrictedTypes = ['emergency', 'compassionate'];

if (!in_array($_SESSION['role'], ['admin', 'hr', 'executive'])) {
    foreach ($items as $row) {
        if (in_array($row['leave_type'], $restrictedTypes)) {
            echo json_encode([
                "status" => "error",
                "message" => "You are not allowed to file Emergency or Compassionate Leave."
            ]);
            exit;
        }
    }
}

foreach ($items as $row) {
    if ($row['leave_type'] === 'toil' && $userId != 2) {
        echo json_encode([
            "status" => "error",
            "message" => "You are not allowed to use TOIL."
        ]);
        exit;
    }
}

/* -----------------------------
   NORMALIZE ITEMS
----------------------------- */
$map = [
    "vacation" => "Vacation Leave",
    "sick" => "Sick Leave",
    "emergency" => "Emergency Leave",
    "compassionate" => "Compassionate Leave",
    "toil"          => "TOIL"
];

$dates = [];
$totalDays = 0;

foreach ($items as $row) {
    if (!isset($map[$row['leave_type']])) continue;

    $dates[] = [
        'date' => $row['leave_date'],
        'type'       => $map[$row['leave_type']], // KEEP TYPE
        'availment' => (float)$row['availment']
    ];

    //Added for TOIL TO NOT count against total leave days
    if ($map[$row['leave_type']] !== "TOIL") {
        $totalDays += (float)$row['availment'];
    }
}

$dateOnly = array_column($dates, 'date');

$startDate = min($dateOnly);
$endDate   = max($dateOnly);
$mainType  = $map[$items[0]['leave_type']];

/* -----------------------------
   TRANSACTION
----------------------------- */
$conn->begin_transaction();

try {
    $upd = $conn->prepare("
    UPDATE leave_requests
    SET 
        reason = ?,
        recipient_id = ?,
        start_date = ?,
        end_date = ?,
        leave_type = ?,
        leave_days = ?
    WHERE id = ?
");
    $upd->bind_param(
        "sisssdi",
        $reason,
        $recipient,
        $startDate,
        $endDate,
        $mainType,
        $totalDays,
        $requestId
    );

    $upd->execute();
    $upd->close();

    $conn->query("DELETE FROM leave_request_dates WHERE leave_request_id=$requestId");

    // ADDED LEAVE_TYPE AND TYPE
    $ins = $conn->prepare("
        INSERT INTO leave_request_dates (leave_request_id, leave_date, leave_type, availment)
        VALUES (?, ?, ?, ?)
    ");
    foreach ($dates as $d) {
        $ins->bind_param("issd", $requestId, $d['date'], $d['type'], $d['availment']);
        $ins->execute();
    }
    $ins->close();

    $conn->commit();
    echo json_encode(["status" => "success", "message" => "Leave request updated"]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
