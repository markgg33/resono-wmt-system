<?php
/*include 'connection_db.php';

$dept_id = $_GET['dept_id'];

$allModes = [];
$assigned = [];

// fetch all work modes
$result = $conn->query("SELECT id, name FROM work_modes ORDER BY name ASC");
while ($row = $result->fetch_assoc()) {
  $allModes[] = ["id" => (int)$row['id'], "name" => $row['name']];
}

// fetch assigned work modes for department
$stmt = $conn->prepare("SELECT work_mode_id FROM department_work_modes WHERE department_id = ?");
$stmt->bind_param("i", $dept_id);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) {
  $assigned[] = (int)$r['work_mode_id'];
}

echo json_encode(["allModes" => $allModes, "assigned" => $assigned]);
$conn->close();*/

include 'connection_db.php';

header("Content-Type: application/json");

$dept_id = isset($_GET['dept_id']) ? intval($_GET['dept_id']) : 0;


/*
|--------------------------------------------------------------------------
| ANALYTICS MODE
|--------------------------------------------------------------------------
| Returns only the work modes assigned to the selected department.
|
| Example:
| get_department_work_modes.php?dept_id=3&analytics=1
|--------------------------------------------------------------------------
*/

if (isset($_GET['analytics'])) {

  /*
    |--------------------------------------------------------
    | ALL WORK MODES
    |--------------------------------------------------------
    */

  if (isset($_GET['all'])) {

    $result = $conn->query("
            SELECT
                id,
                name
            FROM work_modes
            ORDER BY name ASC
        ");

    $modes = [];

    while ($row = $result->fetch_assoc()) {
      $modes[] = $row;
    }

    echo json_encode($modes);
    exit;
  }

  /*
    |--------------------------------------------------------
    | DEPARTMENT WORK MODES
    |--------------------------------------------------------
    */

  if ($dept_id <= 0) {
    echo json_encode([]);
    exit;
  }

  $stmt = $conn->prepare("
        SELECT
            wm.id,
            wm.name
        FROM department_work_modes dm
        INNER JOIN work_modes wm
            ON wm.id = dm.work_mode_id
        WHERE dm.department_id = ?
        ORDER BY wm.name ASC
    ");

  $stmt->bind_param("i", $dept_id);
  $stmt->execute();

  $result = $stmt->get_result();

  $modes = [];

  while ($row = $result->fetch_assoc()) {
    $modes[] = $row;
  }

  echo json_encode($modes);

  exit;
}

/*
|--------------------------------------------------------------------------
| EXISTING DEPARTMENT MANAGEMENT RESPONSE
|--------------------------------------------------------------------------
*/

$allModes = [];
$assigned = [];

// fetch all work modes
$result = $conn->query("
    SELECT
        id,
        name
    FROM work_modes
    ORDER BY name ASC
");

while ($row = $result->fetch_assoc()) {

  $allModes[] = [
    "id" => (int)$row["id"],
    "name" => $row["name"]
  ];
}

// assigned work modes

$stmt = $conn->prepare("
    SELECT work_mode_id
    FROM department_work_modes
    WHERE department_id=?
");

$stmt->bind_param("i", $dept_id);
$stmt->execute();

$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {

  $assigned[] = (int)$row["work_mode_id"];
}

echo json_encode([
  "allModes" => $allModes,
  "assigned" => $assigned
]);

$conn->close();
