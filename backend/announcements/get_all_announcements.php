<?php

require_once "../connection_db.php";

$page = $_GET['page'] ?? 1;
$limit = $_GET['limit'] ?? 10;

$start = $_GET['start'] ?? null;
$end = $_GET['end'] ?? null;

$offset = ($page - 1) * $limit;

$where = "1=1";

if($start) $where .= " AND DATE(created_at) >= '$start'";
if($end) $where .= " AND DATE(created_at) <= '$end'";

$total = $conn->query("SELECT COUNT(*) as total FROM announcements WHERE $where")->fetch_assoc()['total'];

$totalPages = ceil($total / $limit);

$result = $conn->query("
SELECT *
FROM announcements
WHERE $where
ORDER BY created_at DESC
LIMIT $limit OFFSET $offset
");

$data=[];

while($row=$result->fetch_assoc()){
    $data[]=$row;
}

echo json_encode([
    "data"=>$data,
    "totalPages"=>$totalPages
]);