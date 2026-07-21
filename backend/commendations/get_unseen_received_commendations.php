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

/*$sql = "
SELECT
  c.id,
  c.request_code,
  c.type,
  c.points,
  c.reason,
  c.remarks,
  c.created_at,
  c.checked_at,
  CONCAT(f.first_name,' ',f.last_name) AS nominator_name,
  v.name AS value_name
FROM commendations c
JOIN users f ON f.id = c.from_user_id
JOIN values_list v ON v.id = c.value_id
WHERE c.to_user_id = ?
  AND c.status = 'approved'
  AND c.seen_at IS NULL
ORDER BY COALESCE(c.checked_at, c.created_at) ASC, c.id ASC
LIMIT 50
";*/

$sql = "
SELECT
  MIN(c.id) AS id,
  COALESCE(c.request_code, CONCAT('LEGACY-', c.id)) AS request_code,
  c.type,
  c.points,
  c.reason,
  c.remarks,
  MIN(c.created_at) AS created_at,
  MIN(c.checked_at) AS checked_at,

  CASE 
    WHEN c.sender_type = 'external'
      THEN CONCAT('[EXTERNAL] ', c.external_name, ' (', c.external_email, ')')
    ELSE CONCAT(f.first_name,' ',f.last_name)
  END AS nominator_name,

  v.name AS value_name

FROM commendations c
LEFT JOIN users f ON f.id = c.from_user_id
JOIN values_list v ON v.id = c.value_id

WHERE c.to_user_id = ?
  AND c.status = 'approved'
  AND c.seen_at IS NULL

GROUP BY COALESCE(c.request_code, CONCAT('LEGACY-', c.id))

ORDER BY COALESCE(MIN(c.checked_at), MIN(c.created_at)) ASC

LIMIT 50
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();

$data = [];
while ($r = $res->fetch_assoc()) $data[] = $r;

echo json_encode(["status" => "success", "data" => $data]);
