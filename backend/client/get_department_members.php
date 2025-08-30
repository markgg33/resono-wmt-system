<?php
require_once "../connection_db.php";
header('Content-Type: application/json');

try {
    $deptId = intval($_GET['dept_id']);

    $sql = "
        SELECT id, first_name, last_name
        FROM users
        WHERE department_id = ?
        ORDER BY first_name
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $deptId);
    $stmt->execute();
    $res = $stmt->get_result();

    $members = [];
    while ($row = $res->fetch_assoc()) {
        $members[] = $row;
    }

    echo json_encode($members);
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
