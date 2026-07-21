<?php
//WORKING VERSION
/*
session_start();
require '../connection_db.php';
header('Content-Type: application/json');

if (!isset($_GET['request_uid'])) {
    echo json_encode(['error' => 'Missing request_uid']);
    exit;
}

$request_uid = $_GET['request_uid'];

$stmt = $conn->prepare("
    SELECT 
        tir.request_uid,
        CONCAT(
            u.first_name, ' ',
            IF(u.middle_name IS NOT NULL AND u.middle_name != '', CONCAT(LEFT(u.middle_name, 1), '. '), ''),
            u.last_name
        ) AS requestor_name,
        tir.date AS task_date,
        tir.created_at AS request_created_at,
        tir.start_time,
        tir.end_time,
        tir.reason,
        tir.status,
        wm.name AS work_mode,
        td.description AS task_description
    FROM task_insertion_requests tir
    JOIN users u ON tir.user_id = u.id
    LEFT JOIN work_modes wm ON tir.work_mode_id = wm.id
    LEFT JOIN task_descriptions td ON tir.task_description_id = td.id
    WHERE tir.request_uid = ?
");

$stmt->bind_param("s", $request_uid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $request = $result->fetch_assoc();
    echo json_encode($request);
} else {
    echo json_encode(['error' => 'Request not found']);
}

$stmt->close();
$conn->close();
*/

session_start();
require '../connection_db.php';
header('Content-Type: application/json');

if (!isset($_GET['request_uid'])) {
    echo json_encode(['error' => 'Missing request_uid']);
    exit;
}

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$role = strtolower($_SESSION['role']);
$request_uid = $_GET['request_uid'];

$stmt = $conn->prepare("
    SELECT 
        tir.request_uid,
        tir.user_id AS requester_id,
        CONCAT(
            u.first_name, ' ',
            IF(u.middle_name IS NOT NULL AND u.middle_name != '', CONCAT(LEFT(u.middle_name, 1), '. '), ''),
            u.last_name
        ) AS requestor_name,
        tir.date AS task_date,
        tir.created_at AS request_created_at,
        tir.start_time,
        tir.end_time,
        tir.reason,
        tir.status,
        wm.name AS work_mode,
        td.description AS task_description
    FROM task_insertion_requests tir
    JOIN users u ON tir.user_id = u.id
    LEFT JOIN work_modes wm ON tir.work_mode_id = wm.id
    LEFT JOIN task_descriptions td ON tir.task_description_id = td.id
    WHERE tir.request_uid = ?
");

$stmt->bind_param("s", $request_uid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Request not found']);
    exit;
}

$request = $result->fetch_assoc();


// ====================================================
//  CHECK APPROVAL PERMISSIONS
// ====================================================
$isAllowedToApprove = false;

// Admin / HR / Exec = always allowed
if (in_array($role, ['admin', 'executive', 'hr'])) {
    $isAllowedToApprove = true;

} elseif ($role === 'supervisor') {

    // ❌ Cannot approve own request
    if ($user_id != $request['requester_id']) {

        // Check if requestor is from same department(s)
        $deptCheck = $conn->prepare("
            SELECT 1 
            FROM user_departments ud
            WHERE ud.user_id = ?
            AND ud.department_id IN (
                SELECT department_id FROM user_departments WHERE user_id = ?
            )
            LIMIT 1
        ");

        $deptCheck->bind_param("ii", $request['requester_id'], $user_id);
        $deptCheck->execute();
        $dResult = $deptCheck->get_result();

        if ($dResult->num_rows > 0) {
            $isAllowedToApprove = true;
        }
    }
}

$request['can_approve'] = $isAllowedToApprove;

// ====================================================
//  FINAL CLEAN JSON OUTPUT
// ====================================================
echo json_encode($request);

$stmt->close();
$conn->close();

