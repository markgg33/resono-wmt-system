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

$values = $conn->query("
SELECT id,name
FROM values_list
WHERE status='active'
ORDER BY name
")->fetch_all(MYSQLI_ASSOC);

$sql = "

SELECT
t.name AS team,
v.id AS value_id,
SUM(
CASE WHEN c.type='deduct'
THEN -c.points
ELSE c.points
END
) AS pts

FROM teams t
JOIN team_members tm ON tm.team_id=t.id
JOIN commendations c ON c.to_user_id=tm.user_id
JOIN values_list v ON v.id=c.value_id

$where

GROUP BY t.id,v.id
ORDER BY t.name ASC

";

$stmt = $conn->prepare($sql);

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$res = $stmt->get_result();

$data = [];

while ($r = $res->fetch_assoc()) {

    $team = $r['team'];

    if (!isset($data[$team])) {
        $data[$team] = [
            'team' => $team,
            'values' => [],
            'total' => 0
        ];
    }

    $data[$team]['values'][$r['value_id']] = $r['pts'];
    $data[$team]['total'] += $r['pts'];
}

echo json_encode([
    "values" => $values,
    "data" => array_values($data)
]);
