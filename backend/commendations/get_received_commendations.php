<?php
session_start();
require_once "../connection_db.php";
header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? 0;
if ($userId <= 0) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
    exit;
}

$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = max(1, (int)($_GET['limit'] ?? 15));
$offset = ($page - 1) * $limit;

/*
$sql = "
SELECT
  c.id,
  DATE_FORMAT(COALESCE(c.checked_at, c.created_at), '%Y-%m-%d') AS date,
  CONCAT(f.first_name,' ',f.last_name) AS nominator_name,
  v.name AS value_name,
  c.type,
  c.points,
  c.status
FROM commendations c
JOIN users f ON f.id = c.from_user_id
JOIN values_list v ON v.id = c.value_id
WHERE c.to_user_id = ?
  AND c.status = 'approved'
ORDER BY COALESCE(c.checked_at, c.created_at) DESC, c.id DESC
LIMIT ? OFFSET ?
";*/

$sql = "
SELECT
  MIN(c.id) AS id,
  DATE_FORMAT(COALESCE(MIN(c.checked_at), MIN(c.created_at)), '%Y-%m-%d') AS date,

  CASE 
    WHEN c.sender_type = 'external'
      THEN CONCAT('[EXTERNAL] ', c.external_name, ' (', c.external_email, ')')
    ELSE CONCAT(f.first_name,' ',f.last_name)
  END AS nominator_name,

  v.name AS value_name,
  c.type,
  c.points,
  c.status

FROM commendations c
LEFT JOIN users f ON f.id = c.from_user_id
JOIN values_list v ON v.id = c.value_id

WHERE c.to_user_id = ?
  AND c.status = 'approved'

GROUP BY COALESCE(c.request_code, CONCAT('LEGACY-', c.id))

ORDER BY COALESCE(MIN(c.checked_at), MIN(c.created_at)) DESC

LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $userId, $limit, $offset);
$stmt->execute();
$res = $stmt->get_result();

$data = [];
while ($r = $res->fetch_assoc()) $data[] = $r;
$stmt->close();

//$countSql = "SELECT COUNT(*) FROM commendations WHERE to_user_id=? AND status='approved'";
$countSql = "SELECT COUNT(*) FROM (
  SELECT COALESCE(request_code, CONCAT('LEGACY-', id))
  FROM commendations
  WHERE to_user_id=? AND status='approved'
  GROUP BY COALESCE(request_code, CONCAT('LEGACY-', id))
) x";
$cst = $conn->prepare($countSql);
$cst->bind_param("i", $userId);
$cst->execute();
$total = (int)$cst->get_result()->fetch_row()[0];
$cst->close();

//NEW QUERY TO FETCH TOTAL POINTS WITHOUT MODAL NOTIFICATIONS
$sumSql = "
  SELECT COALESCE(SUM(
    CASE WHEN type='deduct' THEN -points ELSE points END
  ), 0) AS total_points
  FROM commendations
  WHERE to_user_id = ? AND status='approved'
";
$sst = $conn->prepare($sumSql);
$sst->bind_param("i", $userId);
$sst->execute();
$totalPoints = (int)$sst->get_result()->fetch_assoc()['total_points'];
$sst->close();

echo json_encode([
    "status" => "success",
    "data" => $data,
    "total_points" => $totalPoints,
    "pagination" => [
        "page" => $page,
        "totalPages" => max(1, (int)ceil($total / $limit)),
        "total" => $total
    ]
]);
