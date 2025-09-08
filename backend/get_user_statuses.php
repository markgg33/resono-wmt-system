<?php
session_start();
require_once "connection_db.php";

header("Content-Type: application/json");

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$role = $_SESSION['role'];
if (!in_array($role, ["executive", "client"])) {
    echo json_encode(["success" => false, "message" => "Access denied"]);
    exit;
}

// Check if filter is passed
$filter = isset($_GET['department_id']) && $_GET['department_id'] !== '' ? intval($_GET['department_id']) : null;

// Base WHERE clause
$where = "WHERE u.is_online = 1"; // only online

if ($role === "client") {
    // Restrict to specific depts
    $where .= " AND d.name IN ('Ancillary', 'Fraud Detection')";
}

if ($filter) {
    // If a filter is set, hide unassigned
    $where .= " AND u.department_id = $filter";
}

$sql = "SELECT u.id,
               CONCAT(u.first_name, ' ', u.last_name) AS full_name,
               d.name AS department,
               u.is_online,
               u.profile_image
        FROM users u
        LEFT JOIN departments d ON u.department_id = d.id
        $where";

$result = $conn->query($sql);

$users = [];
while ($row = $result->fetch_assoc()) {
    // Get latest task log
    $latestSql = "SELECT CONCAT(w.name, ' - ', td.description) AS latest_task,
                         w.name AS work_mode,
                         t.start_time
                  FROM task_logs t
                  LEFT JOIN work_modes w ON t.work_mode_id = w.id
                  LEFT JOIN task_descriptions td ON t.task_description_id = td.id
                  WHERE t.user_id = ?
                  ORDER BY t.id DESC
                  LIMIT 1";
    $stmt = $conn->prepare($latestSql);
    $stmt->bind_param("i", $row['id']);
    $stmt->execute();
    $latestRes = $stmt->get_result();
    $latestRow = $latestRes->fetch_assoc();

    $task = $latestRow['latest_task'] ?? "No recent task";
    $mode = strtolower($latestRow['work_mode'] ?? "");
    $timeTagged = $latestRow['start_time'] ?? null;

    // Decide status
    $status = "active"; // green
    if (strpos($mode, "away") !== false || strpos($mode, "meeting") !== false) {
        $status = "away"; // yellow
    }

    // Build profile image path (fallback if no upload yet)
    $profileImage = !empty($row['profile_image'])
        ? "../" . $row['profile_image']
        : "assets/default-avatar.jpg"; // fallback default avatar

    $users[] = [
        "full_name"     => $row['full_name'],
        "department"    => $row['department'] ?: "Unassigned",
        "latest_task"   => $task,
        "status"        => $status,
        "time_tagged"   => $timeTagged,
        "profile_image" => $profileImage
    ];
}

echo json_encode(["success" => true, "users" => $users]);
