<?php
/*
require "../connection_db.php";

$start = $_GET['start_date'] ?? null;
$end   = $_GET['end_date'] ?? null;
$departmentId = $_GET['department_id'] ?? null;
$departmentIds = $_GET['department_ids'] ?? null;
$billingId = $_GET['billing_category_id'] ?? null;
$billingIds = $_GET['billing_category_ids'] ?? null;

if (!$start || !$end) {
    echo json_encode(["success" => false, "message" => "Missing date range"]);
    exit;
}

$sql = "
SELECT 
    bc.category_name,
    ROUND(SUM(TIME_TO_SEC(t.total_duration)) / 3600, 2) AS total_hours
FROM (
    SELECT task_description_id, total_duration, work_date AS date, user_id
    FROM task_logs
    UNION ALL
    SELECT task_description_id, total_duration, archived_month AS date, user_id
    FROM task_logs_archive
) t
INNER JOIN task_descriptions td ON t.task_description_id = td.id
INNER JOIN work_modes wm ON td.work_mode_id = wm.id
INNER JOIN billing_categories bc ON td.billing_category_id = bc.id
INNER JOIN user_departments ud ON t.user_id = ud.user_id AND ud.is_primary = 1
WHERE DATE(t.date) BETWEEN ? AND ?
  AND td.billing_category_id IS NOT NULL
  AND wm.name != 'Away-Time'
";

$params = [$start, $end];
$types = "ss";

if (!empty($departmentIds)) {
    $ids = array_filter(array_map('intval', explode(',', $departmentIds)));
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql .= " AND ud.department_id IN ($placeholders) ";
        $params = array_merge($params, $ids);
        $types .= str_repeat('i', count($ids));
    }
} elseif (!empty($departmentId)) {
    $sql .= " AND ud.department_id = ? ";
    $params[] = (int) $departmentId;
    $types .= "i";
}

if (!empty($billingIds)) {
    $ids = array_filter(array_map('intval', explode(',', $billingIds)));
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql .= " AND td.billing_category_id IN ($placeholders) ";
        $params = array_merge($params, $ids);
        $types .= str_repeat('i', count($ids));
    }
} elseif (!empty($billingId)) {
    $sql .= " AND td.billing_category_id = ? ";
    $params[] = (int) $billingId;
    $types .= "i";
}

$sql .= "
GROUP BY bc.category_name
ORDER BY total_hours DESC
";

$stmt = $conn->prepare($sql);

// ✅ Dynamic binding
$stmt->bind_param($types, ...$params);

$stmt->execute();
$result = $stmt->get_result();

$labels = [];
$values = [];
$fte = [];

$rangeStart = new DateTime($start);
$rangeEnd   = new DateTime($end);

$workingDays = 0;
for ($d = clone $rangeStart; $d <= $rangeEnd; $d->modify('+1 day')) {
    if ((int)$d->format("N") < 6) $workingDays++;
}

$fteEquivalent = $workingDays * 8;

while ($row = $result->fetch_assoc()) {
    $totalHours = floatval($row['total_hours']);

    $labels[] = $row['category_name'];
    $values[] = floatval($row['total_hours']);

    $computedFte = $fteEquivalent > 0
        ? round($totalHours / $fteEquivalent, 2)
        : 0;

    $fte[] = $computedFte;
}

echo json_encode([
    "success" => true,
    "labels" => $labels,
    "values" => $values,
    "fte" => $fte
]);
*/

require "../connection_db.php";

$start = $_GET['start_date'] ?? null;
$end   = $_GET['end_date'] ?? null;

$departmentId  = $_GET['department_id'] ?? null;
$departmentIds = $_GET['department_ids'] ?? null;

$billingId  = $_GET['billing_category_id'] ?? null;
$billingIds = $_GET['billing_category_ids'] ?? null;

if (!$start || !$end) {
    echo json_encode([
        "success" => false,
        "message" => "Missing date range"
    ]);
    exit;
}

$isAllDepartments = true;
$selectedDepartments = [];

if (!empty($departmentIds)) {
    $selectedDepartments = array_filter(array_map('intval', explode(',', $departmentIds)));
    $isAllDepartments = false;
} elseif (!empty($departmentId)) {
    $selectedDepartments[] = intval($departmentId);
    $isAllDepartments = false;
}

$billingFilter = [];

if (!empty($billingIds)) {
    $billingFilter = array_filter(array_map('intval', explode(',', $billingIds)));
} elseif (!empty($billingId)) {
    $billingFilter[] = intval($billingId);
}

//
// USERS
//

if ($isAllDepartments) {

    $stmt = $conn->prepare("
        SELECT DISTINCT
            u.id,
            u.first_name,
            u.middle_name,
            u.last_name
        FROM users u
        JOIN user_departments ud
            ON u.id=ud.user_id
        WHERE ud.is_primary=1
    ");
} else {

    $placeholders = implode(',', array_fill(0, count($selectedDepartments), '?'));

    $sql = "
        SELECT DISTINCT
            u.id,
            u.first_name,
            u.middle_name,
            u.last_name
        FROM users u
        JOIN user_departments ud
            ON u.id=ud.user_id
        WHERE ud.is_primary=1
        AND ud.department_id IN ($placeholders)
    ";

    $stmt = $conn->prepare($sql);

    $types = str_repeat("i", count($selectedDepartments));
    $stmt->bind_param($types, ...$selectedDepartments);
}

$stmt->execute();

$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt->close();

//
// Working days (same as current graph)
//

$rangeStart = new DateTime($start);
$rangeEnd = new DateTime($end);

$workingDays = 0;

for ($d = clone $rangeStart; $d <= $rangeEnd; $d->modify('+1 day')) {

    if ((int)$d->format('N') < 6) {
        $workingDays++;
    }
}

$fteEquivalent = $workingDays * 8;

$billingTotals = [];

foreach ($users as $user) {

    $logs = getLogs(
        $conn,
        $user['id'],
        $start,
        $end
    );

    foreach ($logs as $log) {

        $task = getTaskData(
            $conn,
            $log['task_description_id']
        );

        if (empty($task['billing_category'])) {
            continue;
        }

        //
        // Billing filter
        //

        if (!empty($billingFilter)) {

            if (!in_array($task['billing_category_id'], $billingFilter)) {
                continue;
            }
        }

        $seconds = durationToSeconds(
            $log['total_duration']
        );

        if (!isset($billingTotals[$task['billing_category']])) {

            $billingTotals[$task['billing_category']] = 0;
        }

        $billingTotals[$task['billing_category']] += $seconds;
    }
}

arsort($billingTotals);

$labels = [];
$values = [];
$fte = [];

foreach ($billingTotals as $category => $seconds) {

    $hours = round($seconds / 3600, 2);

    $labels[] = $category;

    $values[] = $hours;

    $fte[] = $fteEquivalent > 0
        ? round($hours / $fteEquivalent, 2)
        : 0;
}

echo json_encode([
    "success" => true,
    "labels" => $labels,
    "values" => $values,
    "fte" => $fte
]);

function getLogs($conn, $userId, $monthStart, $monthEnd)
{
    $q = "
        SELECT
            id,
            user_id,
            task_description_id,
            date,
            work_date,
            end_date,
            start_time,
            end_time,
            total_duration,
            remarks,
            COALESCE(volume_remark, '-') AS volume_remark
        FROM task_logs
        WHERE user_id = ?
          AND work_date BETWEEN ? AND ?

        UNION ALL

        SELECT
            original_id,
            user_id,
            task_description_id,
            date,
            work_date,
            NULL AS end_date,
            start_time,
            end_time,
            total_duration,
            remarks,
            '-' AS volume_remark
        FROM task_logs_archive
        WHERE user_id = ?
          AND work_date BETWEEN ? AND ?

        ORDER BY work_date ASC, date ASC, start_time ASC
    ";

    $stmt = $conn->prepare($q);
    $stmt->bind_param(
        "ississ",
        $userId,
        $monthStart,
        $monthEnd,
        $userId,
        $monthStart,
        $monthEnd
    );

    $stmt->execute();
    $res = $stmt->get_result();
    $logs = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $logs;
}

function getTaskData($conn, $descId)
{
    static $cache = [];

    if (isset($cache[$descId])) {
        return $cache[$descId];
    }

    $stmt = $conn->prepare("
        SELECT 
            td.description,
            td.billing_category_id,
            bc.category_name
        FROM task_descriptions td
        LEFT JOIN billing_categories bc 
            ON td.billing_category_id = bc.id
            AND bc.is_active = 1
        WHERE td.id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $descId);
    $stmt->execute();

    $row = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    $data = [
        'description' => $row['description'] ?? '',
        'billing_category' => $row['category_name'] ?? 'Uncategorized',
        'billing_category_id' => $row['billing_category_id'] ?? null
    ];

    $cache[$descId] = $data;

    return $data;
}

function durationToSeconds($value)
{
    if (!$value) return 0;

    $value = trim($value);

    if ($value == '' || $value == '--')
        return 0;

    $parts = explode(':', $value);

    $h = intval($parts[0] ?? 0);
    $m = intval($parts[1] ?? 0);
    $s = intval($parts[2] ?? 0);

    return ($h * 3600) + ($m * 60) + $s;
}
