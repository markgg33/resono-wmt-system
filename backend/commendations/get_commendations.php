<?php
session_start();
require_once "../connection_db.php";
header('Content-Type: application/json');

$userId = (int)($_SESSION['user_id'] ?? 0);
$role   = $_SESSION['role'] ?? 'user';

// --------------------
// Filters
// --------------------
$start  = $_GET['start']  ?? '';
$end    = $_GET['end']    ?? '';
$status = $_GET['status'] ?? 'pending';
$type   = $_GET['type']   ?? 'all';
$user   = $_GET['user']   ?? '';   // nominee filter (to_user_id)
$value  = $_GET['value']  ?? '';   // value_id

$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = max(1, (int)($_GET['limit'] ?? 15));
$offset = ($page - 1) * $limit;

// IMPORTANT: define "request key" even for legacy rows w/o request_code
// (legacy rows won't be grouped unless you add request_code for them)
$requestExpr = "COALESCE(c.request_code, CONCAT('LEGACY-', c.id))";

// --------------------
// Build WHERE
// (we filter on the representative row `c` + EXISTS for nominee filter)
// --------------------
$where = [];
$filterParams = [];
$filterTypes  = "";

// Role restriction (user/supervisor sees only own submitted requests)
if (in_array($role, ['user', 'supervisor'], true)) {
  $where[] = "c.from_user_id = ?";
  $filterParams[] = $userId;
  $filterTypes .= "i";
}

if ($status !== 'all') {
  $where[] = "c.status = ?";
  $filterParams[] = $status;
  $filterTypes .= "s";
}

if ($type !== 'all') {
  $where[] = "c.type = ?";
  $filterParams[] = $type;
  $filterTypes .= "s";
}

if ($value !== '') {
  $where[] = "c.value_id = ?";
  $filterParams[] = (int)$value;
  $filterTypes .= "i";
}

if ($start !== '') {
  $where[] = "DATE(c.created_at) >= ?";
  $filterParams[] = $start;
  $filterTypes .= "s";
}

if ($end !== '') {
  $where[] = "DATE(c.created_at) <= ?";
  $filterParams[] = $end;
  $filterTypes .= "s";
}

/**
 * Nominee filter:
 * We want to keep ONE row per request, but only show requests that include the nominee.
 * So use EXISTS against the request group.
 */
if ($user !== '') {
  $where[] = "EXISTS (
        SELECT 1
        FROM commendations c3
        WHERE " . $requestExpr . " = COALESCE(c3.request_code, CONCAT('LEGACY-', c3.id))
          AND c3.to_user_id = ?
    )";
  $filterParams[] = (int)$user;
  $filterTypes .= "i";
}

$whereSql = $where ? (" WHERE " . implode(" AND ", $where)) : "";

// --------------------
// MAIN SQL (one row per request_code)
// We create a derived table r that gives one representative row id per request key.
// --------------------
/*$sql = "
SELECT
  c.id,
  DATE_FORMAT(c.created_at, '%Y-%m-%d') AS created_at,
  CONCAT(f.first_name,' ',f.last_name) AS nominator_name,
  (
    SELECT GROUP_CONCAT(CONCAT(u.first_name,' ',u.last_name) ORDER BY u.last_name, u.first_name SEPARATOR ', ')
    FROM commendations c2
    JOIN users u ON u.id = c2.to_user_id
    WHERE COALESCE(c2.request_code, CONCAT('LEGACY-', c2.id)) = r.req
  ) AS nominee_names,
  v.name AS value_name,
  c.points,
  c.type,
  c.status
FROM (
  SELECT
    COALESCE(request_code, CONCAT('LEGACY-', id)) AS req,
    MIN(id) AS rep_id
  FROM commendations
  GROUP BY req
) r
JOIN commendations c ON c.id = r.rep_id
JOIN users f ON f.id = c.from_user_id
JOIN `values_list` v ON v.id = c.value_id
{$whereSql}
ORDER BY c.created_at DESC
LIMIT ? OFFSET ?
";*/

// --------------------
// MAIN SQL (one row per request_code) TEST VERSION NEW
// --------------------

$sql = "
SELECT
  c.id,
  DATE_FORMAT(c.created_at, '%Y-%m-%d') AS created_at,
CASE
  WHEN c.sender_type = 'external'
    THEN CONCAT('[EXTERNAL] ', c.external_name)
  ELSE CONCAT(f.first_name,' ',f.last_name)
END AS nominator_name,

c.external_email,
  (
    SELECT GROUP_CONCAT(CONCAT(u.first_name,' ',u.last_name) ORDER BY u.last_name, u.first_name SEPARATOR ', ')
    FROM commendations c2
    JOIN users u ON u.id = c2.to_user_id
    WHERE COALESCE(c2.request_code, CONCAT('LEGACY-', c2.id)) = r.req
  ) AS nominee_names,
  v.name AS value_name,
  c.points,
  c.type,
  c.status
FROM (
  SELECT
    COALESCE(request_code, CONCAT('LEGACY-', id)) AS req,
    MIN(id) AS rep_id
  FROM commendations
  GROUP BY req
) r
JOIN commendations c ON c.id = r.rep_id
LEFT JOIN users f ON f.id = c.from_user_id
JOIN `values_list` v ON v.id = c.value_id
{$whereSql}
ORDER BY c.created_at DESC
LIMIT ? OFFSET ?
";


$mainParams = $filterParams;
$mainTypes  = $filterTypes . "ii";
$mainParams[] = $limit;
$mainParams[] = $offset;

$stmt = $conn->prepare($sql);
if (!$stmt) {
  echo json_encode(["status" => "error", "message" => $conn->error]);
  exit;
}

$stmt->bind_param($mainTypes, ...$mainParams);
$stmt->execute();
$res = $stmt->get_result();

$data = [];
while ($row = $res->fetch_assoc()) {
  $data[] = $row;
}
$stmt->close();

// --------------------
// COUNT (count requests, not rows)
// --------------------
/*$countSql = "
SELECT COUNT(*) FROM (
  SELECT r.req
  FROM (
    SELECT
      COALESCE(request_code, CONCAT('LEGACY-', id)) AS req,
      MIN(id) AS rep_id
    FROM commendations
    GROUP BY req
  ) r
  JOIN commendations c ON c.id = r.rep_id
  JOIN users f ON f.id = c.from_user_id
  JOIN `values_list` v ON v.id = c.value_id
  {$whereSql}
) x
";*/

// --------------------
// COUNT (count requests, not rows) TEST VERSION
// --------------------

$countSql = "
SELECT COUNT(*) FROM (
  SELECT r.req
  FROM (
    SELECT
      COALESCE(request_code, CONCAT('LEGACY-', id)) AS req,
      MIN(id) AS rep_id
    FROM commendations
    GROUP BY req
  ) r
  JOIN commendations c ON c.id = r.rep_id
  LEFT JOIN users f ON f.id = c.from_user_id
  JOIN `values_list` v ON v.id = c.value_id
  {$whereSql}
) x
";

$countStmt = $conn->prepare($countSql);
if (!$countStmt) {
  echo json_encode(["status" => "error", "message" => $conn->error]);
  exit;
}

if ($where) {
  $countStmt->bind_param($filterTypes, ...$filterParams);
}

$countStmt->execute();
$total = (int)$countStmt->get_result()->fetch_row()[0];
$countStmt->close();

echo json_encode([
  "data" => $data,
  "pagination" => [
    "page" => $page,
    "totalPages" => max(1, (int)ceil($total / $limit)),
    "total" => $total
  ]
]);
