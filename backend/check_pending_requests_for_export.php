<?php
session_start();
require 'connection_db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$role = $_SESSION['role'];
$allowed = ['admin', 'hr', 'executive', 'supervisor'];
if (!in_array($role, $allowed, true)) {
    echo json_encode(["status" => "error", "message" => "Forbidden"]);
    exit;
}

$start = $_GET['start'] ?? null;
$end   = $_GET['end'] ?? null;
$userId = $_GET['user_id'] ?? null;            // optional
$dept   = $_GET['department'] ?? null;         // optional: numeric, 'all', empty

if (!$start || !$end) {
    echo json_encode(["status" => "error", "message" => "Missing start/end"]);
    exit;
}

$startDate = date('Y-m-d', strtotime($start));
$endDate   = date('Y-m-d', strtotime($end));

/**
 * ✅ Build target users list
 * Assumes users table: users(id, first_name, last_name, department_id)
 * If your department column differs, change "department_id" here.
 */
$targetUsers = [];

if ($userId) {
    $uid = (int)$userId;
    $stmt = $conn->prepare("SELECT id, CONCAT(first_name,' ',last_name) AS name FROM users WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        echo json_encode(["status" => "error", "message" => "User not found"]);
        exit;
    }
    $targetUsers[] = ["id" => (int)$row['id'], "name" => $row['name']];
} else {
    if ($dept && $dept !== 'all' && $dept !== '') {
        $deptId = (int)$dept;

        // ✅ use user_departments mapping (multi-dept support)
        $stmt = $conn->prepare("
            SELECT DISTINCT u.id, CONCAT(u.first_name,' ',u.last_name) AS name
            FROM users u
            INNER JOIN user_departments ud ON ud.user_id = u.id
            WHERE ud.department_id = ?
              AND u.status = 'active'
        ");
        $stmt->bind_param("i", $deptId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $targetUsers[] = ["id" => (int)$r['id'], "name" => $r['name']];
        }
        $stmt->close();
    } else {
        // all employees
        $res = $conn->query("
            SELECT id, CONCAT(first_name,' ',last_name) AS name
            FROM users
            WHERE status = 'active'
        ");
        while ($r = $res->fetch_assoc()) {
            $targetUsers[] = ["id" => (int)$r['id'], "name" => $r['name']];
        }
    }
}


if (count($targetUsers) === 0) {
    echo json_encode(["status" => "success", "has_pending" => false, "summary" => [], "details" => []]);
    exit;
}

$userIds = array_map(fn($u) => (int)$u['id'], $targetUsers);
$placeholders = implode(',', array_fill(0, count($userIds), '?'));
$types = str_repeat('i', count($userIds));

/* =========================================================
   1) ✅ Pending OT (ot_requests.status = 'pending')
   tracker_date BETWEEN start/end
========================================================= */
$sqlOT = "
  SELECT user_id, COUNT(*) AS cnt
  FROM ot_requests
  WHERE status = 'pending'
    AND tracker_date BETWEEN ? AND ?
    AND user_id IN ($placeholders)
  GROUP BY user_id
";
$stmt = $conn->prepare($sqlOT);
$stmt->bind_param("ss" . $types, $startDate, $endDate, ...$userIds);
$stmt->execute();
$res = $stmt->get_result();
$pendingOT = [];
while ($r = $res->fetch_assoc()) $pendingOT[(int)$r['user_id']] = (int)$r['cnt'];
$stmt->close();

/* =========================================================
   2) ✅ Pending DTR Amendments (dtr_amendments.status = 'Pending')
   Join via log_id to task_logs/task_logs_archive to filter by date
========================================================= */
$sqlDTR = "
  SELECT a.user_id, COUNT(*) AS cnt
  FROM dtr_amendments a
  LEFT JOIN task_logs tl
    ON tl.id = a.log_id
  LEFT JOIN task_logs_archive tla
    ON tla.id = a.log_id
  WHERE a.status = 'Pending'
    AND (
      (tl.date BETWEEN ? AND ?)
      OR
      (tla.date BETWEEN ? AND ?)
    )
    AND a.user_id IN ($placeholders)
  GROUP BY a.user_id
";

$stmt = $conn->prepare($sqlDTR);

// ✅ bind dates first, then user ids (unpacking LAST)
$stmt->bind_param("ssss" . $types, $startDate, $endDate, $startDate, $endDate, ...$userIds);

$stmt->execute();
$res = $stmt->get_result();

$pendingDTR = [];
while ($r = $res->fetch_assoc()) {
    $pendingDTR[(int)$r['user_id']] = (int)$r['cnt'];
}
$stmt->close();


/* =========================================================
   3) ✅ Pending Leave (leave_requests.status = 'Pending')
   Overlap rule: start_date <= end AND end_date >= start
========================================================= */
$sqlLeave = "
  SELECT user_id, COUNT(*) AS cnt
  FROM leave_requests
  WHERE status = 'Pending'
    AND start_date <= ?
    AND end_date >= ?
    AND user_id IN ($placeholders)
  GROUP BY user_id
";
$stmt = $conn->prepare($sqlLeave);
$stmt->bind_param("ss" . $types, $endDate, $startDate, ...$userIds);
$stmt->execute();
$res = $stmt->get_result();
$pendingLeave = [];
while ($r = $res->fetch_assoc()) $pendingLeave[(int)$r['user_id']] = (int)$r['cnt'];
$stmt->close();

/* =========================================================
   Build response
========================================================= */
$details = [];
$totOT = 0;
$totDTR = 0;
$totLeave = 0;

foreach ($targetUsers as $u) {
    $uid = (int)$u['id'];
    $ot  = $pendingOT[$uid] ?? 0;
    $dtr = $pendingDTR[$uid] ?? 0;
    $lv  = $pendingLeave[$uid] ?? 0;

    if ($ot || $dtr || $lv) {
        $details[] = [
            "user_id" => $uid,
            "name" => $u['name'],
            "pending_ot" => $ot,
            "pending_dtr" => $dtr,
            "pending_leave" => $lv
        ];
    }

    $totOT += $ot;
    $totDTR += $dtr;
    $totLeave += $lv;
}

$hasPending = ($totOT + $totDTR + $totLeave) > 0;

echo json_encode([
    "status" => "success",
    "has_pending" => $hasPending,
    "summary" => [
        "pending_ot" => $totOT,
        "pending_dtr" => $totDTR,
        "pending_leave" => $totLeave,
        "affected_employees" => count($details)
    ],
    "details" => $details
]);
