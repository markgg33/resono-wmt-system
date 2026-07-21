<?php
session_start();
require_once "../connection_db.php";

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

$value = $_GET['value'] ?? '';
$user = $_GET['user'] ?? '';
$start = $_GET['start'] ?? '';
$end = $_GET['end'] ?? '';

$values = $conn->query("SELECT id,name FROM values_list WHERE status='active'")->fetch_all(MYSQLI_ASSOC);

$sql = "SELECT
u.id,
CONCAT(u.first_name,' ',u.last_name) AS employee,
v.id AS value_id,
SUM(CASE WHEN c.type='deduct' THEN -c.points ELSE c.points END) AS pts
FROM users u
LEFT JOIN commendations c ON c.to_user_id=u.id
LEFT JOIN values_list v ON v.id=c.value_id
WHERE c.status='approved'";

$params = [];
$types = "";

if ($value) {
    $sql .= " AND v.id=?";
    $params[] = $value;
    $types .= "i";
}

if ($user) {
    $sql .= " AND u.id=?";
    $params[] = $user;
    $types .= "i";
}

if ($start) {
    $sql .= " AND DATE(c.created_at)>=?";
    $params[] = $start;
    $types .= "s";
}

if ($end) {
    $sql .= " AND DATE(c.created_at)<=?";
    $params[] = $end;
    $types .= "s";
}

$sql .= " GROUP BY u.id,v.id LIMIT $limit OFFSET $offset";

$stmt = $conn->prepare($sql);

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$res = $stmt->get_result();

$users = [];

while ($r = $res->fetch_assoc()) {

    $id = $r['id'];

    if (!isset($users[$id])) {
        $users[$id] = [
            'employee' => $r['employee'],
            'values' => [],
            'total' => 0
        ];
    }

    $users[$id]['values'][$r['value_id']] = $r['pts'];
    $users[$id]['total'] += $r['pts'];
}

echo json_encode([
    'values' => $values,
    'data' => array_values($users)
]);
