<?php
session_start();
require '../connection_db.php';
header('Content-Type: application/json');

$userId   = $_SESSION['user_id'] ?? 0;
$userRole = $_SESSION['role'] ?? '';
$data = json_decode(file_get_contents("php://input"), true);

$requestId = intval($data['request_id'] ?? 0);
$remarks   = trim($data['remarks'] ?? '');


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
    echo json_encode(["status" => "error", "message" => "Not rejectable"]);
    exit;
}

if (
    $req['user_id'] == $userId &&
    !in_array($userRole, ['admin', 'hr', 'executive'])
) {
    echo json_encode([
        "status" => "error",
        "message" => "Cannot reject own request"
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


/* -----------------------------
   REJECT
----------------------------- */
$upd = $conn->prepare("
    UPDATE leave_requests
    SET status='Rejected',
        remarks=?,
        checked_by=?,
        checked_by_role=?
    WHERE id=?
");
$upd->bind_param("sisi", $remarks, $userId, $userRole, $requestId);
$upd->execute();
$upd->close();

echo json_encode(["status" => "success", "message" => "Leave rejected"]);
