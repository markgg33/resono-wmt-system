<?php
session_start();
require_once "../connection_db.php";

$value = $_GET['value'] ?? '';
$start = $_GET['start'] ?? '';
$end = $_GET['end'] ?? '';

$values = $conn->query("SELECT id,name FROM values_list WHERE status='active' ORDER BY name")->fetch_all(MYSQLI_ASSOC);

$sql = "SELECT
t.name AS team,
v.id AS value_id,
SUM(CASE WHEN c.type='deduct' THEN -c.points ELSE c.points END) AS pts
FROM teams t
JOIN team_members tm ON tm.team_id=t.id
JOIN commendations c ON c.to_user_id=tm.user_id
JOIN values_list v ON v.id=c.value_id
WHERE c.status='approved'";

$params = [];
$types = "";

if ($value) {
    $sql .= " AND v.id=?";
    $params[] = $value;
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

$sql .= " GROUP BY t.id,v.id";

$stmt = $conn->prepare($sql);

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$res = $stmt->get_result();

$teams = [];

while ($r = $res->fetch_assoc()) {

    $team = $r['team'];

    if (!isset($teams[$team])) {
        $teams[$team] = [
            'team' => $team,
            'values' => [],
            'total' => 0
        ];
    }

    $teams[$team]['values'][$r['value_id']] = $r['pts'];
    $teams[$team]['total'] += $r['pts'];
}

echo json_encode([
    'values' => $values,
    'data' => array_values($teams)
]);
