<?php
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
    SELECT task_description_id, total_duration, date, user_id
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
