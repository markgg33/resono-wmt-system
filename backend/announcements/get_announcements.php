<?php

require_once "../connection_db.php";

$page = $_GET['page'] ?? 1;
$limit = $_GET['limit'] ?? 3;

$offset = ($page - 1) * $limit;

$total = $conn->query("SELECT COUNT(*) as total FROM announcements WHERE status='active'")->fetch_assoc()['total'];

$totalPages = ceil($total / $limit);

$result = $conn->query("
SELECT *
FROM announcements
WHERE status='active'
ORDER BY created_at DESC
LIMIT $limit OFFSET $offset
");

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode([
    "data" => $data,
    "totalPages" => $totalPages
]);
