<?php

session_start();
require '../connection_db.php';
header('Content-Type: application/json');

$userId   = $_SESSION['user_id'] ?? 0;
$userRole = $_SESSION['role'] ?? '';

$data = json_decode(file_get_contents("php://input"), true);
$requestId = intval($data['request_id'] ?? 0);

if (!$userId || !$requestId) {
    echo json_encode(["status" => "error", "message" => "Invalid request"]);
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
$req = $stmt->get_result()->fetch_assoc();
$stmt->close();


if (!$req || $req['status'] !== 'Pending') {
    echo json_encode(["status" => "error", "message" => "Not approvable"]);
    exit;
}

if (
    $req['user_id'] == $userId &&
    !in_array($userRole, ['admin', 'hr', 'executive'])
) {
    echo json_encode([
        "status" => "error",
        "message" => "Cannot approve own request"
    ]);
    exit;
}


/* -----------------------------
   PERMISSION
----------------------------- */
$allowed = in_array($userRole, ['admin', 'hr', 'executive']);

if ($userRole === 'supervisor') {

    $chk = $conn->prepare("
        SELECT 1
        FROM user_departments ud_req
        JOIN user_departments ud_sup
          ON ud_req.department_id = ud_sup.department_id
        WHERE ud_req.user_id = ?
          AND ud_sup.user_id = ?
        LIMIT 1
    ");

    $chk->bind_param("ii", $req['user_id'], $userId);
    $chk->execute();

    $allowed =
        $chk->get_result()->num_rows > 0 ||
        $req['recipient_id'] == $userId;

    $chk->close();
}

if (!$allowed) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Access denied'
    ]);
    exit;
}


//-----------------------------
//   DEDUCT BALANCE
//----------------------------- 
/*
$column = match ($req['leave_type']) {
    'Vacation Leave' => 'vacation_leave',
    'Sick Leave' => 'sick_leave',
    'Emergency Leave' => 'emergency_leave',
    'Compassionate Leave' => 'compassionate_leave'
};*/

/* -----------------------------
   FETCH PER-DATE LEAVE BREAKDOWN
----------------------------- 
$dates = $conn->prepare("
    SELECT leave_type, SUM(availment) AS days
    FROM leave_request_dates
    WHERE leave_request_id = ?
    GROUP BY leave_type
");
$dates->bind_param("i", $requestId);
$dates->execute();
$result = $dates->get_result();
$dates->close();

if ($result->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "No leave dates found"
    ]);
    exit;
}*/

/* -----------------------------
   FETCH PER-DATE LEAVE BREAKDOWN TEST
----------------------------- */
$dates = $conn->prepare("
    SELECT leave_date, leave_type, availment
    FROM leave_request_dates
    WHERE leave_request_id = ?
");
$dates->bind_param("i", $requestId);
$dates->execute();
$res = $dates->get_result();
$dates->close();

if ($res->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "No leave dates found"
    ]);
    exit;
}

/* -----------------------------
   LEAVE TYPE → USER COLUMN MAP
----------------------------- */
$columnMap = [
    'Vacation Leave'      => 'vacation_leave',
    'Sick Leave'          => 'sick_leave',
    'Emergency Leave'     => 'emergency_leave',
    'Compassionate Leave' => 'compassionate_leave'
];

$conn->begin_transaction();

$balStmt = $conn->prepare("
    SELECT vacation_leave, sick_leave, emergency_leave, compassionate_leave
    FROM users
    WHERE id = ?
    FOR UPDATE
");
$balStmt->bind_param("i", $req['user_id']);
$balStmt->execute();
$balances = $balStmt->get_result()->fetch_assoc();
$balStmt->close();

if (!$balances) {
    echo json_encode(["status" => "error", "message" => "User not found"]);
    exit;
}


// -----------------------------
// DETERMINE PAYMENT STATUS //ADDED ALL LEAVE TYPES
// -----------------------------
$paymentStatus = in_array($req['leave_type'], [
    'Vacation Leave',
    'Sick Leave',
    'Emergency Leave',
    'Compassionate Leave'
]) ? 'Paid' : 'Unpaid';


try {
    /*
    $conn->query("
        UPDATE users
        SET $column = $column - {$req['leave_days']}
        WHERE id = {$req['user_id']}
    ");*/
    // DEDUCT PER LEAVE TYPE (NEW)
    /*$hasUnpaid = false;

    while ($row = $result->fetch_assoc()) {

        $col = $columnMap[$row['leave_type']] ?? null;
        if (!$col) continue;

        $requestedDays = floatval($row['days']);
        $availableDays = floatval($balances[$col]);

        if ($availableDays <= 0) {
            // ❌ No balance at all
            $hasUnpaid = true;
            continue;
        }

        if ($availableDays < $requestedDays) {
            // ⚠ Partial balance
            $hasUnpaid = true;
            $deduct = $availableDays;
        } else {
            // ✅ Full coverage
            $deduct = $requestedDays;
        }

        // Deduct ONLY what is available
        $stmt = $conn->prepare("
        UPDATE users
        SET $col = $col - ?
        WHERE id = ?
    ");
        $stmt->bind_param("di", $deduct, $req['user_id']);
        $stmt->execute();
        $stmt->close();

        // ✅ update in-memory balance
        $balances[$col] -= $deduct;
    }*/

    // DEDUCT PER LEAVE TYPE (v2 check)
    $hasUnpaid = false;

    while ($row = $res->fetch_assoc()) {

        $leaveDate = $row['leave_date'];
        $leaveType = $row['leave_type'];
        $availment = floatval($row['availment']);

        $col = $columnMap[$leaveType] ?? null;
        if (!$col) continue;

        // 🔥 CHECK SCHEDULER
        $schedStmt = $conn->prepare("
        SELECT schedule_code
        FROM scheduler_days
        WHERE user_id = ?
          AND work_date = ?
        LIMIT 1
    ");
        $schedStmt->bind_param("is", $req['user_id'], $leaveDate);
        $schedStmt->execute();
        $sched = $schedStmt->get_result()->fetch_assoc();
        $schedStmt->close();

        $code = strtoupper($sched['schedule_code'] ?? '');

        // ❗ SKIP RH
        if ($code === 'RH') {
            continue;
        }

        $available = floatval($balances[$col]);

        /*
        if ($available <= 0) {
            $hasUnpaid = true;
            continue;
        }

        if ($available < $availment) {
            $hasUnpaid = true;
            $deduct = $available;
        } else {
            $deduct = $availment;
        }*/

        if ($available < $availment) {
            $hasUnpaid = true;
            continue;
        }

        $deduct = $availment;

        $upd = $conn->prepare("
        UPDATE users
        SET $col = $col - ?
        WHERE id = ?
    ");
        $upd->bind_param("di", $deduct, $req['user_id']);
        $upd->execute();
        $upd->close();

        $balances[$col] -= $deduct;
    }

    $paymentStatus = $hasUnpaid ? 'Unpaid' : 'Paid';


    $upd = $conn->prepare("
        UPDATE leave_requests
        SET status='Approved',
            checked_by=?,
            checked_by_role=?,
            leave_payment_status=?
        WHERE id=?
    ");
    $upd->bind_param("issi", $userId, $userRole, $paymentStatus, $requestId);
    $upd->execute();
    $upd->close();

    $conn->commit();
    echo json_encode(["status" => "success", "message" => "Leave approved"]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
