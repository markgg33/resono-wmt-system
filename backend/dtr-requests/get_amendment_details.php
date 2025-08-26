<?php
session_start();
require_once("../connection_db.php");

if (!in_array($_SESSION['role'], ['admin','executive','hr'])) {
    echo json_encode(["status"=>"error", "message"=>"Unauthorized"]);
    exit;
}

$request_uid = $_GET['request_uid'] ?? '';

$sql = "SELECT a.*, u.username AS requestor_name, t.task_description, t.date
        FROM dtr_amendments a
        JOIN users u ON a.user_id = u.id
        JOIN task_logs t ON a.task_log_id = t.id
        WHERE a.request_uid = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $request_uid);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    echo json_encode(["status"=>"success", "request"=>$row]);
} else {
    echo json_encode(["status"=>"error", "message"=>"Request not found"]);
}
