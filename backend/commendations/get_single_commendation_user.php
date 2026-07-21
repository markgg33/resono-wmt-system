<?php
session_start();
require_once "../connection_db.php";
header('Content-Type: application/json');

$userId = (int)($_SESSION['user_id'] ?? 0);
$role   = $_SESSION['role'] ?? 'user';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(["status" => "error", "message" => "Invalid id"]);
    exit;
}

// 1) Find the row + request_code
$q = "
SELECT c.*,
  CONCAT(f.first_name,' ',f.last_name) AS nominator_name
FROM commendations c
JOIN users f ON f.id = c.from_user_id
WHERE c.id = ?
LIMIT 1
";
$stmt = $conn->prepare($q);
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(["status" => "error", "message" => "Not found"]);
    exit;
}

$requestCode = $row['request_code'] ?? ("LEGACY-" . $row['id']);

// ✅ SECURITY: only owner can view on user page
// (If you want supervisors to also view their own only, keep this)
if (in_array($role, ['user', 'supervisor'], true)) {
    if ((int)$row['from_user_id'] !== (int)$userId) {
        echo json_encode(["status" => "error", "message" => "Unauthorized"]);
        exit;
    }
}

// 2) Fetch all nominees under the same request_code
$q2 = "
SELECT
  c.id,
  c.to_user_id,
  CONCAT(u.first_name,' ',u.last_name) AS nominee_name
FROM commendations c
JOIN users u ON u.id = c.to_user_id
WHERE COALESCE(c.request_code, CONCAT('LEGACY-', c.id)) = ?
ORDER BY u.last_name, u.first_name
";
$stmt2 = $conn->prepare($q2);
$stmt2->bind_param("s", $requestCode);
$stmt2->execute();
$nominees = [];
$res2 = $stmt2->get_result();
while ($r = $res2->fetch_assoc()) $nominees[] = $r;
$stmt2->close();

// 3) Load active values (optional, but keeps modal consistent)
$vals = [];
$vq = "SELECT id, name, status FROM `values_list` ORDER BY name ASC";
$vr = $conn->query($vq);
while ($vr && ($v = $vr->fetch_assoc())) $vals[] = $v;

$isFinal = in_array($row['status'], ['approved', 'rejected'], true);

echo json_encode([
    "status" => "success",
    "data" => [
        "id" => (int)$row['id'],
        "request_code" => $requestCode,
        "nominator_name" => $row['nominator_name'],
        "type" => $row['type'],
        "value_id" => (int)$row['value_id'],
        "points" => (int)$row['points'],
        "reason" => $row['reason'],
        "attachment" => $row['attachment'],
        "status" => $row['status'],
        "remarks" => $row['remarks'],
        "created_at" => $row['created_at'],
        "nominees" => $nominees
    ],
    "values" => $vals,
    "context" => [
        // ✅ FORCE VIEW-ONLY on user side
        "can_edit" => false,
        "can_approve" => false,
        "is_final" => $isFinal
    ]
]);
