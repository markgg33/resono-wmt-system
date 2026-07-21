<?php
/*require "connection_db.php";

$dept_id = $_GET['department_id'] ?? null;

if (!$dept_id) {
    echo json_encode([]);
    exit;
}

$sql = "
SELECT 
    bc.id, 
    bc.category_name
FROM department_work_modes dwm
INNER JOIN work_modes wm 
    ON dwm.work_mode_id = wm.id
INNER JOIN task_descriptions td 
    ON td.work_mode_id = wm.id
INNER JOIN billing_categories bc 
    ON td.billing_category_id = bc.id
WHERE dwm.department_id = ?
  AND bc.is_active = 1
  AND td.is_active = 1
  AND td.billing_category_id IS NOT NULL
  AND bc.category_name != 'Away - Time'
GROUP BY bc.id, bc.category_name
ORDER BY bc.category_name ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $dept_id);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);*/

require "connection_db.php";

$dept_ids = isset($_GET['department_ids'])
    ? trim((string) $_GET['department_ids'])
    : '';

$sql = "
SELECT 
    bc.id, 
    bc.category_name
FROM department_work_modes dwm
INNER JOIN work_modes wm 
    ON dwm.work_mode_id = wm.id
INNER JOIN task_descriptions td 
    ON td.work_mode_id = wm.id
INNER JOIN billing_categories bc 
    ON td.billing_category_id = bc.id
WHERE bc.is_active = 1
  AND td.is_active = 1
  AND td.billing_category_id IS NOT NULL
  AND bc.category_name != 'Away - Time'
";

// ✅ ONLY filter if department is provided AND not 'all'
/*if (!empty($dept_id) && $dept_id !== "all") {
    $sql .= " AND dwm.department_id = ? ";
}*/

// Filter by department(s) when provided; omit param for all departments
if ($dept_ids !== '') {
    $idsArray = array_values(array_filter(array_map('intval', explode(',', $dept_ids))));
    if (empty($idsArray)) {
        echo json_encode([]);
        exit;
    }
    $placeholders = implode(',', array_fill(0, count($idsArray), '?'));
    $sql .= " AND dwm.department_id IN ($placeholders) ";
}

$sql .= "
GROUP BY bc.id, bc.category_name
ORDER BY bc.category_name ASC
";

$stmt = $conn->prepare($sql);

// ✅ Bind only if needed
/*if (!empty($dept_id) && $dept_id !== "all") {
    $stmt->bind_param("i", $dept_id);
}*/

if ($dept_ids !== '') {
    $idsArray = array_values(array_filter(array_map('intval', explode(',', $dept_ids))));
    $types = str_repeat('i', count($idsArray));
    $stmt->bind_param($types, ...$idsArray);
}

$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
