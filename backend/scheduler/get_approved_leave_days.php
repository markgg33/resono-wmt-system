<?php
// ============================================
// get_approved_leave_days.php
// Returns approved leave per user/day for a range
// Mapping:
// - paid + Sick Leave -> Sick-PL
// - paid + others -> Leave
// - unpaid -> Unpaid
// ============================================
session_start();
require_once "../connection_db.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized."]);
    exit;
}

$start = $_GET['start'] ?? '';
$end   = $_GET['end'] ?? '';
$userId = $_GET['user_id'] ?? '';
$departmentId = $_GET['department_id'] ?? '';

function is_valid_date($d)
{
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt && $dt->format('Y-m-d') === $d;
}

if (!is_valid_date($start) || !is_valid_date($end)) {
    echo json_encode(["success" => false, "message" => "Invalid date range."]);
    exit;
}

// Build optional filters
$where = [];
$params = [$start, $end];
$types = "ss";

// If user_id is provided, filter it
if ($userId !== "" && is_numeric($userId)) {
    $where[] = "lr.user_id = ?";
    $params[] = (int)$userId;
    $types .= "i";
}

// If department is provided, filter it (multi-dept mapping safe)
if ($departmentId !== "" && is_numeric($departmentId)) {
    $where[] = "EXISTS (
        SELECT 1 FROM user_departments ud
        WHERE ud.user_id = lr.user_id
          AND ud.department_id = ?
    )";
    $params[] = (int)$departmentId;
    $types .= "i";
}

// IMPORTANT: status check (adjust if your DB stores Approved/approved)
$where[] = "LOWER(lr.status) = 'approved'";

// Query per-day rows
$sql = "
SELECT
  lr.user_id,
  lrd.leave_date,
  lr.leave_payment_status,
  lrd.leave_type
FROM leave_requests lr
JOIN leave_request_dates lrd
  ON lrd.leave_request_id = lr.id
WHERE lrd.leave_date BETWEEN ? AND ?
  AND " . implode(" AND ", $where) . "
ORDER BY lr.user_id ASC, lrd.leave_date ASC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Prepare failed: " . $conn->error]);
    exit;
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$leaveMap = []; // "userId|YYYY-MM-DD" => code

while ($r = $res->fetch_assoc()) {
    $uid = (int)$r['user_id'];
    $date = $r['leave_date'];
    $pay = strtolower(trim($r['leave_payment_status'] ?? '')); // paid/unpaid/...
    $type = $r['leave_type'];

    // Determine code
    /*$code = "Leave";
    if ($pay === "unpaid") {
        $code = "Unpaid";
    } else {
        // paid or default
        if ($type === "Sick Leave") $code = "Sick-PL";
        else $code = "Leave";
    }*/

    $typeLower = strtolower(trim($type));

    if ($typeLower === "toil") {
        $code = "TOIL"; // 👈 THIS IS THE FIX
    } elseif ($pay === "unpaid") {
        $code = "Unpaid";
    } elseif ($typeLower === "sick leave") {
        $code = "Sick-PL";
    } else {
        $code = "Leave";
    }

    $key = $uid . "|" . $date;

    // If somehow multiple entries exist, keep strongest:
    // Sick-PL > Leave > Unpaid (you can adjust)
    $priority = ["Unpaid" => 1, "Leave" => 2, "TOIL" => 3, "Sick-PL" => 3];
    if (!isset($leaveMap[$key]) || $priority[$code] > $priority[$leaveMap[$key]]) {
        $leaveMap[$key] = $code;
    }
}

$stmt->close();
$conn->close();

echo json_encode([
    "success" => true,
    "start" => $start,
    "end" => $end,
    "leaves" => $leaveMap
]);
