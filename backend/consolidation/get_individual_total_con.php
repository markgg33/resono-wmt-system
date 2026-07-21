<?php
session_start();
require_once "../connection_db.php";
header('Content-Type: application/json');

$start = $_GET['start'] ?? '';
$end   = $_GET['end'] ?? '';
$level = $_GET['level'] ?? 'employee';

$where = " WHERE c.status='approved' ";
$params = [];
$types = "";

/* DATE FILTERS */

if ($start) {
    $where .= " AND DATE(c.created_at)>=?";
    $params[] = $start;
    $types .= "s";
}

if ($end) {
    $where .= " AND DATE(c.created_at)<=?";
    $params[] = $end;
    $types .= "s";
}

/* LEVEL FILTER */

if ($level === "employee") {
    $where .= " AND u.role='user'";
} else {
    $where .= " AND u.role IN('admin','executive','hr','supervisor')";
}

$sql = "

SELECT
u.id,
CONCAT(u.first_name,' ',u.last_name) AS employee,
SUM(
CASE WHEN c.type='deduct'
THEN -c.points
ELSE c.points
END
) AS total_points

FROM commendations c
JOIN users u ON u.id=c.to_user_id

$where

GROUP BY u.id
ORDER BY total_points DESC

";

$stmt = $conn->prepare($sql);

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$res = $stmt->get_result();

$data = [];

while ($r = $res->fetch_assoc()) {
    $data[] = $r;
}

echo json_encode(["data" => $data]);
