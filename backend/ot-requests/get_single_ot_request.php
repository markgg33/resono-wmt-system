<?php
//WORKING VERSION
/*
session_start();
require '../connection_db.php';
header('Content-Type: application/json');

$id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

if (!$id || !$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters.']);
    exit;
}

// Fetch OT request
$stmt = $conn->prepare("
    SELECT 
    ot.*, 
    CONCAT(u.first_name, ' ', IFNULL(u.middle_name,''), ' ', u.last_name) AS employee_name,
    u.department_id AS request_department_id,
    ot.user_id AS request_owner_id
FROM ot_requests ot
JOIN users u ON ot.user_id = u.id
WHERE ot.id = ?

");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$request = $result->fetch_assoc();
$stmt->close();


if (!$request) {
    echo json_encode(['status' => 'error', 'message' => 'OT request not found.']);
    exit;
}

// Fetch recipients (all users who can approve OT)
$recipients = [];
$roles = ['admin', 'hr', 'executive', 'supervisor'];
$rolePlaceholders = implode(',', array_fill(0, count($roles), '?'));
$types = str_repeat('s', count($roles));

$stmt = $conn->prepare("
    SELECT id, CONCAT(first_name, ' ', IFNULL(middle_name,''), ' ', last_name) AS full_name
    FROM users
    WHERE role IN ($rolePlaceholders)
");
$stmt->bind_param($types, ...$roles);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $recipients[] = [
        'id' => $row['id'],
        'name' => $row['full_name'] // return as 'name' for consistency with JS
    ];
}
$stmt->close();


// Get current user role
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$userRole = $res->fetch_assoc()['role'] ?? 'user';
$stmt->close();

echo json_encode([
    'status' => 'success',
    'request' => $request,
    'recipients' => $recipients,
    'user_role' => $userRole
]);
$conn->close();

*/

session_start();
require '../connection_db.php';
header('Content-Type: application/json');

$id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

if (!$id || !$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters.']);
    exit;
}

/* ------------------------------------------
   1. Fetch OT request + employee info
------------------------------------------- */
$stmt = $conn->prepare("
    SELECT 
        ot.*, 
        CONCAT(u.first_name, ' ', IFNULL(u.middle_name, ''), ' ', u.last_name) AS employee_name,
        u.id AS employee_id
    FROM ot_requests ot
    JOIN users u ON ot.user_id = u.id
    WHERE ot.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$request = $result->fetch_assoc();
$stmt->close();

if (!$request) {
    echo json_encode(['status' => 'error', 'message' => 'OT request not found.']);
    exit;
}

$employeeId = $request['employee_id'];

/* ------------------------------------------
   2. Load all approver roles
------------------------------------------- */
$recipients = [];
$roles = ['admin', 'hr', 'executive', 'supervisor'];
$rolePlaceholders = implode(',', array_fill(0, count($roles), '?'));
$types = str_repeat('s', count($roles));

$stmt = $conn->prepare("
    SELECT id, CONCAT(first_name, ' ', IFNULL(middle_name,''), ' ', last_name) AS full_name
    FROM users
    WHERE role IN ($rolePlaceholders)
");
$stmt->bind_param($types, ...$roles);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $recipients[] = [
        'id' => $row['id'],
        'name' => $row['full_name']
    ];
}
$stmt->close();

/* ------------------------------------------
   3. Get current user's role
------------------------------------------- */
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$userRole = $res->fetch_assoc()['role'] ?? 'user';
$stmt->close();

/* ------------------------------------------
   4. Multi-department Authorization Logic
------------------------------------------- 

// Auto-approve access for highest roles
if (in_array($userRole, ['admin', 'hr', 'executive'])) {
    $hasAccess = true;
} elseif ($userRole === 'supervisor') {

    // 🔥 Supervisor multi-department logic
    // Load supervisor's departments
    $supervisorDepartments = [];
    $stmt = $conn->prepare("SELECT department_id FROM user_departments WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $resSup = $stmt->get_result();
    while ($r = $resSup->fetch_assoc()) {
        $supervisorDepartments[] = (int)$r['department_id'];
    }
    $stmt->close();

    // Load employee's departments
    $employeeDepartments = [];
    $stmt = $conn->prepare("SELECT department_id FROM user_departments WHERE user_id = ?");
    $stmt->bind_param("i", $employeeId);
    $stmt->execute();
    $resEmp = $stmt->get_result();
    while ($r = $resEmp->fetch_assoc()) {
        $employeeDepartments[] = (int)$r['department_id'];
    }
    $stmt->close();

    // Check intersection & exclude own requests
    if ($user_id !== $employeeId && count(array_intersect($supervisorDepartments, $employeeDepartments)) > 0) {
        $hasAccess = true;
    } else {
        $hasAccess = false;
    }
} else {
    $hasAccess = false;
}*/

/* ------------------------------------------
   4. Multi-department + Recipient Authorization Logic
------------------------------------------- */

// Auto access for highest roles
if (in_array($userRole, ['admin', 'hr', 'executive'])) {
    $hasAccess = true;
} elseif ($userRole === 'supervisor') {

    $hasAccess = false;

    // Prevent approving own request
    if ($user_id !== $employeeId) {

        // 🔥 1. Supervisor is the assigned recipient
        if (!empty($request['recipient_id']) && $request['recipient_id'] == $user_id) {
            $hasAccess = true;
        }

        // 🔥 2. Multi-department logic
        if (!$hasAccess) {
            // Supervisor departments
            $supervisorDepartments = [];
            $stmt = $conn->prepare("SELECT department_id FROM user_departments WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $resSup = $stmt->get_result();
            while ($r = $resSup->fetch_assoc()) {
                $supervisorDepartments[] = (int)$r['department_id'];
            }
            $stmt->close();

            // Employee departments
            $employeeDepartments = [];
            $stmt = $conn->prepare("SELECT department_id FROM user_departments WHERE user_id = ?");
            $stmt->bind_param("i", $employeeId);
            $stmt->execute();
            $resEmp = $stmt->get_result();
            while ($r = $resEmp->fetch_assoc()) {
                $employeeDepartments[] = (int)$r['department_id'];
            }
            $stmt->close();

            // Check intersection
            if (count(array_intersect($supervisorDepartments, $employeeDepartments)) > 0) {
                $hasAccess = true;
            }
        }
    }
} else {
    $hasAccess = false;
}


/* ------------------------------------------
   5. Return response
------------------------------------------- */
echo json_encode([
    'status' => 'success',
    'request' => $request,
    'recipients' => $recipients,
    'user_role' => $userRole,
    'has_access' => $hasAccess
]);

$conn->close();
