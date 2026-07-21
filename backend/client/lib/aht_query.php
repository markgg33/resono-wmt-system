<?php

require_once dirname(__DIR__, 2) . "/connection_db.php";

/**
 * Convert decimal minutes into HH:MM:SS
 */
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

/**
 * Returns AHT rows.
 */
function getAHTData(
    mysqli $conn,
    string $startDate,
    string $endDate,
    string $workModeId,
    array $taskIds,
    int $departmentId = 0,
    int $userId = 0
) {
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
        SUM(TIME_TO_SEC(t.total_duration))/60,
        2
    ) AS total_minutes,

    ROUND(
        SUM(COALESCE(t.volume_remark,0)),
        2
    ) AS total_volume

FROM(

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

)t

INNER JOIN task_descriptions td
ON td.id=t.task_description_id

INNER JOIN users u
ON u.id=t.user_id

INNER JOIN user_departments ud
ON ud.user_id=t.user_id

WHERE

t.date BETWEEN ? AND ?

";

    $params = [
        $startDate,
        $endDate
    ];

    $types = "ss";

    //----------------------------------
    // Department
    //----------------------------------

    if ($departmentId > 0) {

        $sql .= "

AND ud.department_id=?

";

        $params[] = $departmentId;

        $types .= "i";
    }

    //----------------------------------
    // Employee
    //----------------------------------

    if ($userId > 0) {

        $sql .= "

AND t.user_id=?

";

        $params[] = $userId;

        $types .= "i";
    }

    //----------------------------------
    // Work Mode
    //----------------------------------

    if ($workModeId !== "" && $workModeId !== "all") {

        $sql .= "

AND t.work_mode_id=?

";

        $params[] = intval($workModeId);

        $types .= "i";
    }

    //----------------------------------
    // Tasks
    //----------------------------------

    if (!empty($taskIds)) {

        if (in_array("all", $taskIds)) {

            // Do nothing

        } else {

            $taskIds = array_map("intval", $taskIds);

            $placeholders = implode(
                ",",
                array_fill(
                    0,
                    count($taskIds),
                    "?"
                )
            );

            $sql .= "

AND t.task_description_id
IN ($placeholders)

";

            $params = array_merge(
                $params,
                $taskIds
            );

            $types .= str_repeat(
                "i",
                count($taskIds)
            );
        }
    }

    //----------------------------------
    // Grouping
    //----------------------------------

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

    $stmt->bind_param(
        $types,
        ...$params
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $data = [];

    while ($row = $result->fetch_assoc()) {

        $minutes =
            (float)$row["total_minutes"];

        $volume =
            (float)$row["total_volume"];

        $productionSeconds =
            $minutes * 60;

        $hours =
            floor($productionSeconds / 3600);

        $mins =
            floor(($productionSeconds % 3600) / 60);

        $productionTime =
            sprintf(
                "%02d:%02d",
                $hours,
                $mins
            );

        $actualAHT =
            $volume > 0
            ? round(
                $minutes / $volume,
                2
            )
            : 0;

        $data[] = [

            "employee_name" => $row["employee_name"],

            "task_name" => $row["description"],

            "production_time" => $productionTime,

            "total_minutes" => $minutes,

            "total_volume" => $volume,

            "standard_aht_value" =>
            (float)$row["standard_aht"],

            "actual_aht_value" =>
            $actualAHT,

            "standard_aht" =>
            minutesToTime(
                $row["standard_aht"]
            ),

            "aht" =>
            minutesToTime(
                $actualAHT
            )

        ];
    }

    return $data;
}
