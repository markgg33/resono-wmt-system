<?php
session_start();
require '../connection_db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$request_id = (int)($data['request_id'] ?? 0);

$user_id = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? null;

if (!$user_id || !$request_id) {
    echo json_encode(["status" => "error", "message" => "Invalid request"]);
    exit;
}

/* =========================================
   1. FETCH REQUEST
========================================= */
$stmt = $conn->prepare("SELECT * FROM leave_requests WHERE id=?");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$req = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$req) {
    echo json_encode(["status" => "error", "message" => "Request not found"]);
    exit;
}

$isOwner = $req['user_id'] == $user_id;
$isAdmin = in_array($role, ['admin', 'hr', 'executive', 'supervisor']);

if (!$isOwner && !$isAdmin) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

/* =========================================
   2. RESTORE CREDITS (ONLY IF APPROVED)
========================================= */
if ($req['status'] === 'Approved' && $req['leave_payment_status'] === 'Paid') {

    $stmt = $conn->prepare("
        SELECT leave_date, leave_type, availment
        FROM leave_request_dates
        WHERE leave_request_id = ?
    ");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {

        $leaveDate = $row['leave_date'];

        // 🔥 CHECK SCHEDULER (RH SKIP)
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

        $code = strtoupper($sched['schedule_code'] ?? '');

        if ($code === 'RH') {
            continue; // ❌ DO NOT RESTORE
        }

        // normalize leave type → DB column
        $field = strtolower(str_replace(' ', '_', $row['leave_type']));

        $conn->query("
            UPDATE users
            SET {$field} = {$field} + {$row['availment']}
            WHERE id = {$req['user_id']}
        ");
    }

    $stmt->close();
}

/* =========================================
   3. DELETE RECORDS
========================================= */

// delete child
$stmt = $conn->prepare("DELETE FROM leave_request_dates WHERE leave_request_id=?");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$stmt->close();

// delete main
$stmt = $conn->prepare("DELETE FROM leave_requests WHERE id=?");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$stmt->close();

/* =========================================
   4. RESPONSE
========================================= */
echo json_encode([
    "status" => "success",
    "message" => "Leave request deleted successfully"
]);
