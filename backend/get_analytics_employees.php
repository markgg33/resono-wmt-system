<?php

require_once "connection_db.php";

header("Content-Type: application/json");

$departmentIds = [];

if (!empty($_GET['department_ids'])) {

    foreach (explode(",", $_GET['department_ids']) as $id) {

        $id = intval($id);

        if ($id > 0) {
            $departmentIds[] = $id;
        }
    }
}

$sql = "

SELECT DISTINCT

    u.id,
    u.first_name,
    u.middle_name,
    u.last_name

FROM users u

INNER JOIN user_departments ud
    ON u.id = ud.user_id

WHERE
    u.status = 'Active'
";

$params = [];
$types = "";

if (!empty($departmentIds)) {

    $placeholders = implode(",", array_fill(0, count($departmentIds), "?"));

    $sql .= "

    AND ud.department_id IN ($placeholders)

    ";

    $params = $departmentIds;

    $types = str_repeat("i", count($departmentIds));
}

$sql .= "

ORDER BY
    u.last_name,
    u.first_name

";

$stmt = $conn->prepare($sql);

if (!empty($params)) {

    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$result = $stmt->get_result();

$employees = [];

while ($row = $result->fetch_assoc()) {

    $employees[] = $row;
}

echo json_encode($employees);
