<?php
// ============================================
// get_scheduler_matrix.php
// Returns users + scheduler_days for a date range with ABSENT support
// ============================================
session_start();
require_once "../connection_db.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized."]);
    exit;
}

$role = $_SESSION['role'];
$userId = $_SESSION['user_id'];
$departmentId = $_GET['department_id'] ?? '';
$month = $_GET['month'] ?? '';
$start = $_GET['start'] ?? '';
$end = $_GET['end'] ?? '';

function is_valid_date($d)
{
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt && $dt->format('Y-m-d') === $d;
}

function parse_month_to_range($month)
{
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) return null;
    $y = (int)substr($month, 0, 4);
    $m = (int)substr($month, 5, 2);
    if ($m < 1 || $m > 12) return null;

    $start = sprintf("%04d-%02d-01", $y, $m);
    $end = date("Y-m-t", strtotime($start));
    return [$start, $end];
}

// Resolve date range
if ($month) {
    $range = parse_month_to_range($month);
    if (!$range) {
        echo json_encode(["success" => false, "message" => "Invalid month format (YYYY-MM)."]);
        exit;
    }
    [$start, $end] = $range;
} else {
    if (!is_valid_date($start) || !is_valid_date($end)) {
        echo json_encode(["success" => false, "message" => "Provide ?month=YYYY-MM OR ?start=YYYY-MM-DD&end=YYYY-MM-DD"]);
        exit;
    }
}

// Determine allowed departments
$allowedDepartments = [];
if (in_array($role, ['admin', 'executive', 'hr'])) {
    $res = $conn->query("SELECT id FROM departments");
    while ($row = $res->fetch_assoc()) $allowedDepartments[] = (int)$row['id'];
} else if ($role === 'supervisor') {
    $stmt = $conn->prepare("SELECT department_id FROM user_departments WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $allowedDepartments[] = (int)$row['department_id'];
    $stmt->close();
}

// --------------------------------------------
// Fetch users
// --------------------------------------------
$userSql = "
SELECT 
    u.id,
    u.first_name,
    u.middle_name,
    u.last_name,
    u.email,
    u.role,
    u.employee_id,
    u.status,
    u.profile_image,
    COALESCE(d.id, d2.id) AS department_id,
    COALESCE(d.name, d2.name) AS department_name
FROM users u
LEFT JOIN user_departments ud ON u.id = ud.user_id
LEFT JOIN departments d ON ud.department_id = d.id
LEFT JOIN departments d2 ON u.department_id = d2.id
";

$whereParts = ["u.status = 'active'"];
$params = [];
$types = "";

// Restrict by allowed departments
if (!empty($allowedDepartments)) {
    $placeholders = implode(',', array_fill(0, count($allowedDepartments), '?'));
    $whereParts[] = "COALESCE(d.id, d2.id) IN ($placeholders)";
    $types .= str_repeat("i", count($allowedDepartments));
    $params = array_merge($params, $allowedDepartments);
}

// Optional department filter
if ($departmentId !== "" && is_numeric($departmentId)) {
    if (!in_array((int)$departmentId, $allowedDepartments)) {
        echo json_encode(["success" => false, "message" => "Unauthorized department"]);
        exit;
    }
    $whereParts[] = "COALESCE(d.id, d2.id) = ?";
    $types .= "i";
    $params[] = (int)$departmentId;
}

if (!empty($whereParts)) $userSql .= " WHERE " . implode(" AND ", $whereParts);
$userSql .= " ORDER BY u.first_name ASC, u.last_name ASC";

$userStmt = $conn->prepare($userSql);
if (!$userStmt) {
    echo json_encode(["success" => false, "message" => "Prepare failed: " . $conn->error]);
    exit;
}
if ($types) $userStmt->bind_param($types, ...$params);
$userStmt->execute();
$userRes = $userStmt->get_result();

$usersMap = [];
$userIds = [];

while ($row = $userRes->fetch_assoc()) {
    $id = (int)$row['id'];
    if (!isset($usersMap[$id])) {
        $usersMap[$id] = [
            "id" => $id,
            "first_name" => $row['first_name'],
            "middle_name" => $row['middle_name'],
            "last_name" => $row['last_name'],
            "employee_id" => $row['employee_id'],
            "role" => $row['role'],
            "status" => $row['status'],
            "profile_image" => $row['profile_image'],
            "departments" => []
        ];
        $userIds[] = $id;
    }

    if (!empty($row['department_id'])) {
        $deptId = (int)$row['department_id'];
        $already = false;
        foreach ($usersMap[$id]['departments'] as $d) if ((int)$d['id'] === $deptId) $already = true;
        if (!$already) $usersMap[$id]['departments'][] = ["id" => $deptId, "name" => $row['department_name']];
    }
}
$userStmt->close();

$users = array_values($usersMap);
if (count($userIds) === 0) {
    echo json_encode(["success" => true, "start" => $start, "end" => $end, "users" => [], "scheduler" => []]);
    exit;
}

// --------------------------------------------
// Fetch scheduler_days for users
// --------------------------------------------
$placeholders = implode(',', array_fill(0, count($userIds), '?'));
$schedSql = "
SELECT user_id, work_date, schedule_code, call_time
FROM scheduler_days
WHERE work_date BETWEEN ? AND ?
  AND user_id IN ($placeholders)
";
$schedTypes = "ss" . str_repeat("i", count($userIds));
$schedParams = array_merge([$start, $end], $userIds);
$schedStmt = $conn->prepare($schedSql);
if (!$schedStmt) {
    echo json_encode(["success" => false, "message" => "Prepare failed: " . $conn->error]);
    exit;
}
$schedStmt->bind_param($schedTypes, ...$schedParams);
$schedStmt->execute();
$schedRes = $schedStmt->get_result();

$scheduler = [];
while ($r = $schedRes->fetch_assoc()) {
    $key = $r['user_id'] . "|" . $r['work_date'];
    $scheduler[$key] = [
        "schedule_code" => $r['schedule_code'],
        "call_time" => $r['call_time']
    ];
}
$schedStmt->close();

// --------------------------------------------
// Fetch approved leaves
// --------------------------------------------
$leaveUrl = "../scheduler/get_approved_leave_days.php?start=$start&end=$end";
$leaveMap = []; // fallback empty
// If needed, you can fetch via PHP include or replicate query here. 
// For now assuming $leaveMap is populated with key = "userId|YYYY-MM-DD" => code

// --------------------------------------------
// Fetch task logs to check for ABSENT
// --------------------------------------------
$taskPlaceholders = implode(',', array_fill(0, count($userIds), '?'));
$taskSql = "
SELECT user_id, work_date
FROM (
    SELECT user_id, work_date FROM task_logs
    WHERE user_id IN ($taskPlaceholders) AND work_date BETWEEN ? AND ?
    UNION ALL
    SELECT user_id, work_date FROM task_logs_archive
    WHERE user_id IN ($taskPlaceholders) AND work_date BETWEEN ? AND ?
) t
GROUP BY user_id, work_date
";
$taskTypes = str_repeat("i", count($userIds)) . "ss" . str_repeat("i", count($userIds)) . "ss";
$taskParams = array_merge($userIds, [$start, $end], $userIds, [$start, $end]);
$taskStmt = $conn->prepare($taskSql);
$taskStmt->bind_param($taskTypes, ...$taskParams);
$taskStmt->execute();
$taskRes = $taskStmt->get_result();

$taskMap = [];
while ($row = $taskRes->fetch_assoc()) $taskMap[$row['user_id'] . '|' . $row['work_date']] = true;

// --------------------------------------------
// ABSENT logic: mark scheduled work with no leave and no task as ABSENT
// --------------------------------------------
foreach ($scheduler as $key => &$entry) {
    $code = strtoupper($entry['schedule_code'] ?? '');

    if (!in_array($code, ['W', 'RH', 'SH', 'CB'])) continue;

    $onLeave = isset($leaveMap[$key]);
    $hasTask = isset($taskMap[$key]);

    // Parse work_date from key
    [$userIdPart, $datePart] = explode('|', $key);

    // Only mark ABSENT for dates **before today**
    if (!$onLeave && !$hasTask && strtotime($datePart) < strtotime(date('Y-m-d'))) {
        $entry['schedule_code'] = 'ABSENT';
    }
}

$conn->close();

echo json_encode([
    "success" => true,
    "start" => $start,
    "end" => $end,
    "users" => $users,
    "scheduler" => $scheduler
]);
