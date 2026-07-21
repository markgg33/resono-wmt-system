<?php
/*
session_start();
require_once "../connection_db.php";
header('Content-Type: application/json');

$start = $_GET['start'] ?? '';
$end   = $_GET['end'] ?? '';

$where = " WHERE c.status='approved' ";
$params = [];
$types = "";

if ($start) {
    $where .= " AND DATE(c.created_at) >= ?";
    $params[] = $start;
    $types .= "s";
}

if ($end) {
    $where .= " AND DATE(c.created_at) <= ?";
    $params[] = $end;
    $types .= "s";
}

// GET VALUES 
$values = $conn->query("
SELECT id,name
FROM values_list
WHERE status='active'
ORDER BY name
")->fetch_all(MYSQLI_ASSOC);

// MAIN QUERY 
$sql = "
SELECT
u.id,
CONCAT(u.first_name,' ',u.last_name) AS employee,
v.id AS value_id,
SUM(
CASE WHEN c.type='deduct'
THEN -c.points
ELSE c.points
END
) AS pts
FROM commendations c
JOIN users u ON u.id = c.to_user_id
JOIN values_list v ON v.id = c.value_id
$where
GROUP BY u.id,v.id
ORDER BY employee ASC
";

$stmt = $conn->prepare($sql);

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$res = $stmt->get_result();

$data = [];

while ($r = $res->fetch_assoc()) {

    $id = $r['id'];

    if (!isset($data[$id])) {
        $data[$id] = [
            'employee' => $r['employee'],
            'values' => [],
            'total' => 0
        ];
    }

    $data[$id]['values'][$r['value_id']] = $r['pts'];
    $data[$id]['total'] += $r['pts'];
}

echo json_encode([
    "values" => $values,
    "data" => array_values($data)
]);
*/

session_start();
require_once "../connection_db.php";
header('Content-Type: application/json');

/* =========================
   GET FILTERS
========================= */
$start = $_GET['start'] ?? '';
$end   = $_GET['end'] ?? '';
$level = $_GET['level'] ?? '';
$value = $_GET['value'] ?? '';

$where = " WHERE c.status='approved' ";
$params = [];
$types = "";

/* =========================
   DATE FILTERS
========================= */
if (!empty($start)) {
    $where .= " AND DATE(c.created_at) >= ?";
    $params[] = $start;
    $types .= "s";
}

if (!empty($end)) {
    $where .= " AND DATE(c.created_at) <= ?";
    $params[] = $end;
    $types .= "s";
}

/* =========================
   LEVEL FILTER (FINAL LOGIC)
========================= */
if (!empty($level)) {

    if ($level === "employee") {
        // Only regular users
        $where .= " AND u.role = ?";
        $params[] = "user";
        $types .= "s";
    }

    if ($level === "supervisor") {
        // Everything except user
        $where .= " AND u.role != ?";
        $params[] = "user";
        $types .= "s";
    }
}

/* =========================
   VALUE FILTER
========================= */
if (!empty($value)) {
    $where .= " AND c.value_id = ?";
    $params[] = $value;
    $types .= "i";
}

/* =========================
   GET VALUES (dynamic)
========================= */
if (!empty($value)) {
    // Show only selected value column
    $stmtVal = $conn->prepare("
        SELECT id, name
        FROM values_list
        WHERE id = ?
    ");
    $stmtVal->bind_param("i", $value);
    $stmtVal->execute();
    $values = $stmtVal->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    // Show all active values
    $values = $conn->query("
        SELECT id, name
        FROM values_list
        WHERE status='active'
        ORDER BY name
    ")->fetch_all(MYSQLI_ASSOC);
}

/* =========================
   MAIN QUERY
========================= */
$sql = "
SELECT
    u.id,
    CONCAT(u.first_name,' ',u.last_name) AS employee,
    v.id AS value_id,
    SUM(
        CASE 
            WHEN c.type='deduct' THEN -c.points
            ELSE c.points
        END
    ) AS pts
FROM commendations c
JOIN users u ON u.id = c.to_user_id
JOIN values_list v ON v.id = c.value_id
$where
GROUP BY u.id, v.id
ORDER BY employee ASC
";

$stmt = $conn->prepare($sql);

/* =========================
   BIND PARAMS
========================= */
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$res = $stmt->get_result();

/* =========================
   FORMAT DATA
========================= */
$data = [];

while ($r = $res->fetch_assoc()) {

    $id = $r['id'];

    if (!isset($data[$id])) {
        $data[$id] = [
            'employee' => $r['employee'],
            'values' => [],
            'total' => 0
        ];
    }

    $data[$id]['values'][$r['value_id']] = $r['pts'];
    $data[$id]['total'] += $r['pts'];
}

/* =========================
   OUTPUT
========================= */
echo json_encode([
    "values" => $values,
    "data" => array_values($data)
]);
