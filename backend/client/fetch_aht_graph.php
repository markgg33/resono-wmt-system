<?php
require_once "../connection_db.php";
header("Content-Type: application/json");

$startDate = $_GET['start_date'] ?? null;
$endDate   = $_GET['end_date'] ?? null;

/*$workModeId = isset($_GET['work_mode_id'])
    ? intval($_GET['work_mode_id'])
    : 0;*/

$workModeId = $_GET['work_mode_id'] ?? "";

//NEW ADDITIONAL CODE
$userId = isset($_GET['user_id'])
    ? intval($_GET['user_id'])
    : 0;

//NEW ADDITIONAL CODE (2)
$departmentId = isset($_GET['department_id'])
    ? intval($_GET['department_id'])
    : 0;

$taskIdsRaw = $_GET['task_ids'] ?? null;
$taskIds = [];

if (!empty($taskIdsRaw)) {
    $taskIds = array_values(
        array_filter(
            array_map('intval', explode(',', $taskIdsRaw))
        )
    );
}

if (
    !$startDate ||
    !$endDate ||
    //!$workModeId 
    $workModeId === "" ||
    empty($taskIds)
) {
    echo json_encode([
        "success" => false,
        "message" => "Missing parameters"
    ]);
    exit;
}

$sql = "
SELECT

    t.user_id,

    CONCAT(
        u.first_name,
        ' ',
        u.last_name
    ) AS employee_name,

    td.id,
    td.description,
    td.standard_aht,

    ROUND(
        SUM(TIME_TO_SEC(t.total_duration)) / 60,
        2
    ) AS total_minutes,

    ROUND(
        SUM(COALESCE(t.volume_remark,0)),
        2
    ) AS total_volume

FROM (

    SELECT
        task_description_id,
        work_mode_id,
        user_id,
        total_duration,
        volume_remark,
        date
    FROM task_logs

    UNION ALL

    SELECT
        task_description_id,
        work_mode_id,
        user_id,
        total_duration,
        volume_remark,
        date
    FROM task_logs_archive

) t

INNER JOIN task_descriptions td
    ON td.id = t.task_description_id
INNER JOIN users u
    ON u.id = t.user_id

INNER JOIN user_departments ud
    ON ud.user_id = t.user_id

WHERE t.date BETWEEN ? AND ?

";

//AND t.work_mode_id = ? removed after where t.date BETWEEN

$params = [
    $startDate,
    $endDate,
    //$workModeId
];

$types = "ss"; // removed i after ss

if ($departmentId > 0) {

    $sql .= "
        AND ud.department_id = ?
    ";

    $params[] = $departmentId;

    $types .= "i";
}

if ($userId > 0) {

    $sql .= "
        AND t.user_id = ?
    ";

    $params[] = $userId;
    $types .= "i";
}


$placeholders = implode(
    ",",
    array_fill(0, count($taskIds), "?")
);

$sql .= "
AND t.task_description_id IN ($placeholders)
";

$params = array_merge($params, $taskIds);
$types .= str_repeat("i", count($taskIds));

$sql .= "
GROUP BY

t.user_id,

u.first_name,

u.last_name,

td.id,

td.description,

td.standard_aht

ORDER BY

u.first_name,

u.last_name,

td.description
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();

$result = $stmt->get_result();

$data = [];

// HELPER FOR CONVERTING AHT TO TIME FORMAT
function minutesToTime($minutes)
{
    $seconds = round($minutes * 60);

    $hours = floor($seconds / 3600);

    $minutesPart = floor(($seconds % 3600) / 60);

    $secondsPart = $seconds % 60;

    return sprintf(
        "%02d:%02d:%02d",
        $hours,
        $minutesPart,
        $secondsPart
    );
}

while ($row = $result->fetch_assoc()) {

    $minutes = (float)$row['total_minutes'];

    //FOR PRODUCTION TIME CALCULATION
    $totalSeconds = $minutes * 60;

    $hours = floor($totalSeconds / 3600);
    $mins  = floor(($totalSeconds % 3600) / 60);

    $productionTime = sprintf("%02d:%02d", $hours, $mins);

    $volume  = (float)$row['total_volume'];

    $aht = $volume > 0
        ? round($minutes / $volume, 2)
        : 0;

    /*$data[] = [
        "task_name"      => $row['description'],
        "standard_aht"   => (float)($row['standard_aht'] ?? 0),
        "production_time" => $productionTime,
        "total_minutes"  => $minutes,
        "total_volume"   => $volume,
        "aht"            => $aht
    ];*/

    $data[] = [

        "employee_name" => $row["employee_name"],

        "task_name" => $row['description'],

        "production_time" => $productionTime,

        "total_minutes" => $minutes,

        "total_volume" => $volume,

        // numeric values
        "standard_aht_value" => (float)$row['standard_aht'],
        "actual_aht_value"   => $aht,

        // formatted values
        "standard_aht" => minutesToTime($row['standard_aht']),
        "aht"          => minutesToTime($aht)

    ];
}

echo json_encode([
    "success" => true,
    "data" => $data
]);
