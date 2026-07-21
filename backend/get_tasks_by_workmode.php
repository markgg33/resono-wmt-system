<?php
require 'connection_db.php';
header('Content-Type: application/json');

/*
|--------------------------------------------------------------------------
| ALL TASKS
|--------------------------------------------------------------------------
*/

if (isset($_GET['all'])) {

    $result = $conn->query("
    SELECT
        id,
        description
    FROM task_descriptions
    ORDER BY description ASC
");

    /* removed     WHERE description NOT IN (
        'Away - Break',
        'End Shift'
    ) between FROM and ORDER BY
        */

    $tasks = [];

    while ($row = $result->fetch_assoc()) {
        $tasks[] = $row;
    }

    echo json_encode($tasks);

    exit;
}

/*
|--------------------------------------------------------------------------
| WORK MODE TASKS
|--------------------------------------------------------------------------
*/

$workModeId = isset($_GET['work_mode_id'])
    ? intval($_GET['work_mode_id'])
    : 0;

if ($workModeId <= 0) {

    echo json_encode([]);

    exit;
}

$stmt = $conn->prepare("
    SELECT
        id,
        description
    FROM task_descriptions
    WHERE work_mode_id = ?
    ORDER BY description ASC
");


/* removed AND description NOT IN (
            'Away - Break',
            'End Shift'
      ) between WHERE and ORDER BY
            */

$stmt->bind_param("i", $workModeId);
$stmt->execute();

$result = $stmt->get_result();

$tasks = [];

while ($row = $result->fetch_assoc()) {
    $tasks[] = $row;
}

echo json_encode($tasks);

$stmt->close();
$conn->close();

//ORIGINAL CODE
/*$workModeId = $_GET['work_mode_id'] ?? null;

if (!$workModeId) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("SELECT id, description FROM task_descriptions WHERE work_mode_id = ? ORDER BY description ASC");
$stmt->bind_param("i", $workModeId);
$stmt->execute();
$result = $stmt->get_result();

$tasks = [];
while ($row = $result->fetch_assoc()) {
    $tasks[] = $row;
}

echo json_encode($tasks);
$stmt->close();
$conn->close();*/
