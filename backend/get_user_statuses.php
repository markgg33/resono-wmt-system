<?php
session_start();
require_once "connection_db.php";

header("Content-Type: application/json");

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$role = $_SESSION['role'];
if (!in_array($role, ["executive", "client", "admin", "user", "supervisor", 'hr'])) {
    echo json_encode(["success" => false, "message" => "Access denied"]);
    exit;
}

$mode   = $_GET['mode'] ?? "dashboard";
$filter = isset($_GET['department_id']) && $_GET['department_id'] !== ''
    ? intval($_GET['department_id'])
    : null;

$where = "WHERE 1=1";
$params = [];
$types  = "";

// Dashboard mode → only online users
if ($mode === "dashboard") {
    $where .= " AND u.is_online = 1";

    // Supervisor + Client restriction
    if (in_array($role, ["supervisor", "client"])) {
        $supSql = "SELECT department_id FROM user_departments WHERE user_id = ?";
        $stmt = $conn->prepare($supSql);
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $supRes = $stmt->get_result();
        $supDepartments = [];
        while ($row = $supRes->fetch_assoc()) {
            $supDepartments[] = $row['department_id'];
        }
        $stmt->close();

        if ($filter) {
            // Only allow filter if it’s inside their allowed list
            if (in_array($filter, $supDepartments)) {
                $where .= " AND d.id = ?";
                $types  .= "i";
                $params[] = $filter;
            } else {
                // If they try to force another department, deny
                echo json_encode(["success" => false, "message" => "Unauthorized department filter"]);
                exit;
            }
        } elseif (!empty($supDepartments)) {
            // Force filter to their assigned departments
            $in = implode(",", array_fill(0, count($supDepartments), "?"));
            $where .= " AND d.id IN ($in)";
            $types  .= str_repeat("i", count($supDepartments));
            $params = array_merge($params, $supDepartments);
        }
    } elseif ($filter) {
        // Admin / HR / Exec can filter any dept
        $where .= " AND d.id = ?";
        $types  .= "i";
        $params[] = $filter;
    }
}

// Build query
$sql = "SELECT 
            u.id,
            CONCAT(u.first_name, ' ', u.last_name) AS full_name,
            d.name AS department,
            u.is_online,
            u.profile_image
        FROM users u
        LEFT JOIN user_departments ud ON u.id = ud.user_id AND ud.is_primary = 1
        LEFT JOIN departments d ON ud.department_id = d.id
        $where
        GROUP BY u.id";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$users = [];

while ($row = $result->fetch_assoc()) {
    // latest task
    $latestSql = "SELECT CONCAT(w.name, ' - ', td.description) AS latest_task,
                         w.name AS work_mode,
                         td.description AS task_description,
                         t.start_time
                  FROM task_logs t
                  LEFT JOIN work_modes w ON t.work_mode_id = w.id
                  LEFT JOIN task_descriptions td ON t.task_description_id = td.id
                  WHERE t.user_id = ?
                  ORDER BY t.id DESC
                  LIMIT 1";
    $stmt2 = $conn->prepare($latestSql);
    $stmt2->bind_param("i", $row['id']);
    $stmt2->execute();
    $latestRes = $stmt2->get_result();
    $latestRow = $latestRes->fetch_assoc();

    $task       = $latestRow['latest_task'] ?? "No recent task";
    $modeName   = strtolower($latestRow['work_mode'] ?? "");
    $taskDesc   = strtolower($latestRow['task_description'] ?? "");
    $timeTagged = $latestRow['start_time'] ?? null;

    if ($mode === "dashboard" && ($taskDesc === "end shift" || stripos($task, "end shift") !== false)) {
        continue;
    }

    if ($row['is_online']) {
        if (strpos($modeName, "away") !== false || strpos($modeName, "meeting") !== false) {
            $status = "away";
        } elseif (!$latestRow) {
            $status = "online";
        } else {
            $status = "active";
        }
    } else {
        $status = "offline";
    }

    $profileImage = !empty($row['profile_image'])
        ? "../" . $row['profile_image']
        : "../assets/default-avatar.jpg";

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
