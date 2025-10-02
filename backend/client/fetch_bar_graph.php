<?php
require_once "../connection_db.php";
header("Content-Type: application/json");

$deptId     = isset($_GET['dept_id']) ? intval($_GET['dept_id']) : 0;
$startDate  = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$endDate    = isset($_GET['end_date']) ? $_GET['end_date'] : null;
$mode       = isset($_GET['mode']) ? $_GET['mode'] : "daily"; // NEW

if (!$deptId || !$startDate || !$endDate) {
    echo json_encode(["success" => false, "message" => "Missing parameters"]);
    exit;
}

// ========== MODE: DAILY ==========
if ($mode === "daily") {
    $sql = "
    SELECT DATE(t.date) AS d,
           ROUND(SUM(TIME_TO_SEC(t.total_duration))/3600, 2) AS total_hours
    FROM (
        SELECT task_description_id, total_duration, date, user_id 
        FROM task_logs
        UNION ALL
        SELECT task_description_id, total_duration, date, user_id 
        FROM task_logs_archive
    ) t
    INNER JOIN user_departments ud 
        ON t.user_id = ud.user_id AND ud.is_primary = 1
    WHERE t.date BETWEEN ? AND ? 
      AND ud.department_id = ?
    GROUP BY d
    ORDER BY d
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $startDate, $endDate, $deptId);
    $stmt->execute();
    $result = $stmt->get_result();

    $raw = [];
    while ($row = $result->fetch_assoc()) {
        $raw[$row['d']] = floatval($row['total_hours']);
    }

    $labels = [];
    $values = [];
    $period = new DatePeriod(
        new DateTime($startDate),
        new DateInterval('P1D'),
        (new DateTime($endDate))->modify('+1 day')
    );

    foreach ($period as $dt) {
        $d = $dt->format("Y-m-d");
        $labels[] = $dt->format("M d, Y");
        $values[] = $raw[$d] ?? 0;
    }

    echo json_encode([
        "success" => true,
        "labels"  => $labels,
        "values"  => $values,
        "fte"     => null // not used in daily
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
        ON t.user_id = ud.user_id AND ud.is_primary = 1
    WHERE t.date BETWEEN ? AND ? 
      AND ud.department_id = ?
    GROUP BY ym
    ORDER BY ym
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $startDate, $endDate, $deptId);
    $stmt->execute();
    $result = $stmt->get_result();

    $labels = [];
    $values = [];
    $fteData = [];

    while ($row = $result->fetch_assoc()) {
        $month = $row['ym']; // e.g. 2025-09
        $totalHours = floatval($row['total_hours']);

        // Calculate working days in month (Mon-Fri)
        $dt = new DateTime($month . "-01");
        $daysInMonth = (int)$dt->format("t");
        $workingDays = 0;
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $day = new DateTime("{$month}-$d");
            $w = (int)$day->format("N");
            if ($w < 6) $workingDays++;
        }

        $fteEquivalent = $workingDays * 8;
        $fte = $fteEquivalent > 0 ? round($totalHours / $fteEquivalent, 2) : 0;

        $labels[] = $dt->format("F Y");
        $values[] = $totalHours;
        $fteData[] = ["month" => $dt->format("F Y"), "fte" => $fte];
    }

    echo json_encode([
        "success" => true,
        "labels"  => $labels,
        "values"  => $values,
        "fte"     => $fteData
    ]);
    exit;
}
