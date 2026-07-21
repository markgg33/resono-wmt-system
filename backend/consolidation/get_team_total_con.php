<?php
session_start();
require_once "../connection_db.php";
header('Content-Type: application/json');

$start = $_GET['start'] ?? '';
$end = $_GET['end'] ?? '';

$where = " WHERE c.status='approved' ";
$params = [];
$types = "";

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

$sql = "

SELECT
t.name AS team,
SUM(
CASE WHEN c.type='deduct'
THEN -c.points
ELSE c.points
END
) AS total_points

FROM teams t
JOIN team_members tm ON tm.team_id=t.id
JOIN commendations c ON c.to_user_id=tm.user_id

$where

GROUP BY t.id
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
