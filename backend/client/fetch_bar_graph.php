<?php
require_once "../connection_db.php";
header("Content-Type: application/json");

$deptIdRaw = $_GET['dept_id'] ?? null;
$deptId = ($deptIdRaw === "all") ? "all" : intval($deptIdRaw);
$deptIdsRaw = $_GET['department_ids'] ?? null;
$deptIds = [];
if (!empty($deptIdsRaw)) {
    $deptIds = array_values(array_filter(array_map('intval', explode(',', $deptIdsRaw))));
}

// FOR BILLING CATEGORY FILTER
$billingIdsRaw = $_GET['billing_category_ids'] ?? null;

$billingIds = [];

if (!empty($billingIdsRaw)) {
    $billingIds = array_values(
        array_filter(
            array_map('intval', explode(',', $billingIdsRaw))
        )
    );
}

$startDate  = $_GET['start_date'] ?? null;
$endDate    = $_GET['end_date'] ?? null;
$mode       = $_GET['mode'] ?? "daily";

//NEW ADDITIONAL CODE
$userId = isset($_GET['user_id'])
    ? intval($_GET['user_id'])
    : 0;

if (!$startDate || !$endDate) {
    echo json_encode(["success" => false, "message" => "Missing parameters"]);
    exit;
}

// ========== MODE: DAILY ==========
if ($mode === "daily") {
    $sql = "
SELECT
DATE(t.date) AS d,

u.first_name,
u.last_name,

dpt.name AS department_name,

ROUND(SUM(TIME_TO_SEC(t.total_duration))/3600,2) AS total_hours
FROM (
    SELECT task_description_id, total_duration, date, user_id 
    FROM task_logs
    UNION ALL
    SELECT task_description_id, total_duration, date, user_id 
    FROM task_logs_archive
) t
 
INNER JOIN user_departments ud
ON t.user_id = ud.user_id
AND ud.is_primary = 1

INNER JOIN departments dpt
ON ud.department_id = dpt.id

INNER JOIN users u
ON u.id = t.user_id

INNER JOIN task_descriptions td
ON t.task_description_id = td.id

INNER JOIN work_modes wm
ON td.work_mode_id = wm.id
WHERE t.date BETWEEN ? AND ?
  AND wm.name != 'Away-Time'
";

    $params = [$startDate, $endDate];
    $types = "ss";

    // Optional employee filter
    if ($userId > 0) {
        $sql .= " AND t.user_id = ? ";
        $params[] = $userId;
        $types .= "i";
    }

    if (!empty($deptIds)) {
        $placeholders = implode(',', array_fill(0, count($deptIds), '?'));
        $sql .= " AND ud.department_id IN ($placeholders) ";
        $params = array_merge($params, $deptIds);
        $types .= str_repeat('i', count($deptIds));
    } elseif ($deptId !== "all" && $deptId) {
        $sql .= " AND ud.department_id = ? ";
        $params[] = $deptId;
        $types .= "i";
    }

    if (!empty($billingIds)) {
        $placeholders = implode(',', array_fill(0, count($billingIds), '?'));

        $sql .= " AND td.billing_category_id IN ($placeholders) ";

        $params = array_merge($params, $billingIds);

        $types .= str_repeat('i', count($billingIds));
    }

    $sql .= " GROUP BY d ORDER BY d";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $dailyData = [];
    while ($row = $result->fetch_assoc()) {
        $day = $row['d'];
        $totalHours = floatval($row['total_hours']);
        $fte = $totalHours > 0 ? round($totalHours / 8, 2) : 0; // ✅ Compute FTE
        $dailyData[$day] = [
            "total_hours" => $totalHours,
            "fte" => $fte
        ];
    }

    $labels = [];
    $values = [];
    $fteData = [];

    $period = new DatePeriod(
        new DateTime($startDate),
        new DateInterval('P1D'),
        (new DateTime($endDate))->modify('+1 day')
    );

    foreach ($period as $dt) {
        $d = $dt->format("Y-m-d");
        $labels[] = $dt->format("M d, Y");
        $values[] = $dailyData[$d]['total_hours'] ?? 0;
        $fteData[] = $dailyData[$d]['fte'] ?? 0;
    }

    echo json_encode([
        "success" => true,
        "labels"  => $labels,
        "values"  => $values,
        "fte"     => $fteData // ✅ send FTE array
    ]);
    exit;
}


// ========== MODE: MONTHLY ==========
if ($mode === "monthly") {
    $sql = "
SELECT DATE_FORMAT(t.date, '%Y-%m') AS ym,
       ROUND(SUM(TIME_TO_SEC(t.total_duration))/3600, 2) AS total_hours
FROM (
    SELECT task_description_id, total_duration, date, user_id 
    FROM task_logs
    UNION ALL
    SELECT task_description_id, total_duration, date, user_id 
    FROM task_logs_archive
) t
INNER JOIN user_departments ud
ON t.user_id = ud.user_id
AND ud.is_primary = 1

INNER JOIN departments dpt
ON ud.department_id = dpt.id

INNER JOIN users u
ON u.id = t.user_id

INNER JOIN task_descriptions td
ON t.task_description_id = td.id

INNER JOIN work_modes wm
ON td.work_mode_id = wm.id
WHERE t.date BETWEEN ? AND ?
  AND wm.name != 'Away-Time'
";

    $params = [$startDate, $endDate];
    $types = "ss";

    // Optional employee filter
    if ($userId > 0) {
        $sql .= " AND t.user_id = ? ";
        $params[] = $userId;
        $types .= "i";
    }

    if (!empty($deptIds)) {
        $placeholders = implode(',', array_fill(0, count($deptIds), '?'));
        $sql .= " AND ud.department_id IN ($placeholders) ";
        $params = array_merge($params, $deptIds);
        $types .= str_repeat('i', count($deptIds));
    } elseif ($deptId !== "all" && $deptId) {
        $sql .= " AND ud.department_id = ? ";
        $params[] = $deptId;
        $types .= "i";
    }

    if (!empty($billingIds)) {
        $placeholders = implode(',', array_fill(0, count($billingIds), '?'));

        $sql .= " AND td.billing_category_id IN ($placeholders) ";

        $params = array_merge($params, $billingIds);

        $types .= str_repeat('i', count($billingIds));
    }

    $sql .= " GROUP BY ym ORDER BY ym";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $labels = [];
    $values = [];
    $fteData = [];

    while ($row = $result->fetch_assoc()) {
        $month = $row['ym']; // e.g. 2025-09
        $totalHours = floatval($row['total_hours']);

        // ✅ Define month boundaries
        $rangeStart = new DateTime($startDate);
        $rangeEnd   = new DateTime($endDate);
        $monthStart = new DateTime($month . "-01");
        $monthEnd   = (clone $monthStart)->modify('last day of this month');

        // ✅ Limit to selected range
        if ($monthStart < $rangeStart) $monthStart = clone $rangeStart;
        if ($monthEnd > $rangeEnd) $monthEnd = clone $rangeEnd;

        // ✅ Count working days (Mon–Fri)
        $workingDays = 0;
        for ($d = clone $monthStart; $d <= $monthEnd; $d->modify('+1 day')) {
            if ((int)$d->format("N") < 6) $workingDays++;
        }

        // ✅ Compute FTE
        $fteEquivalent = $workingDays * 8; // total expected hours
        $fte = $fteEquivalent > 0 ? round($totalHours / $fteEquivalent, 2) : 0;

        $formattedMonth = $monthStart->format("F Y"); // 🧠 fixed variable name

        $labels[] = $formattedMonth;
        $values[] = $totalHours;
        $fteData[] = [
            "month" => $formattedMonth,
            "total_hours" => $totalHours,
            "fte" => $fte,
            "networkdays" => $workingDays,
            "fte_equiv" => $fteEquivalent
        ];
    }

    echo json_encode([
        "success" => true,
        "labels"  => $labels,
        "values"  => $values,
        "fte"     => $fteData
    ]);
    exit;
}
