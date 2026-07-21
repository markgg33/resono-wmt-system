<?php
session_start();
require_once "../connection_db.php";
header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? 0;
$role   = $_SESSION['role'] ?? 'user';

$input = json_decode(file_get_contents("php://input"), true);
$requestCode = trim($input['request_code'] ?? '');
$remarks = trim($input['remarks'] ?? '');

if ($requestCode === '') {
  echo json_encode(["status" => "error", "message" => "Missing request_code"]);
  exit;
}

// Approver rules (your existing logic)
$canApprove = in_array($role, ['admin','executive','hr']); // supervisor read-only per your rule
if (!$canApprove) {
  http_response_code(403);
  echo json_encode(["status" => "error", "message" => "Unauthorized"]);
  exit;
}

$conn->begin_transaction();

try {
  // Load all rows under request_code (pending only)
  $q = "SELECT id, to_user_id, type, points, status
        FROM commendations
        WHERE request_code = ?
        FOR UPDATE";
  $st = $conn->prepare($q);
  $st->bind_param("s", $requestCode);
  $st->execute();
  $res = $st->get_result();

  $rows = [];
  while ($r = $res->fetch_assoc()) $rows[] = $r;
  $st->close();

  if (!$rows) {
    throw new Exception("Request not found.");
  }

  // If already final, stop
  $alreadyFinal = false;
  foreach ($rows as $r) {
    if (in_array($r['status'], ['approved','rejected'])) { $alreadyFinal = true; break; }
  }
  if ($alreadyFinal) {
    throw new Exception("This request is already finalized.");
  }

  // Approve all rows
  $upd = "UPDATE commendations
          SET status='approved',
              checked_by=?,
              checked_at=NOW(),
              remarks=?,
              seen_at=NULL
          WHERE request_code=?";
  $u = $conn->prepare($upd);
  $u->bind_param("iss", $userId, $remarks, $requestCode);
  $u->execute();
  $u->close();

  // Apply points to each nominee
  $upPts = $conn->prepare("UPDATE users SET points = GREATEST(0, points + ?) WHERE id=?");
  foreach ($rows as $r) {
    $delta = (strtolower($r['type']) === 'deduct') ? -abs((int)$r['points']) : abs((int)$r['points']);
    $toUser = (int)$r['to_user_id'];
    $upPts->bind_param("ii", $delta, $toUser);
    $upPts->execute();
  }
  $upPts->close();

  $conn->commit();
  echo json_encode(["status" => "success"]);

} catch (Exception $e) {
  $conn->rollback();
  echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
