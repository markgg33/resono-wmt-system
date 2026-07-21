<?php
session_start();
require '../connection_db.php';
header('Content-Type: application/json');

$user_id   = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? null;
$requestId = intval($_GET['id'] ?? 0);

if (!$user_id || !$requestId) {
    echo json_encode(["status" => "error", "message" => "Invalid request"]);
    exit;
}

/* -----------------------------------------
   BASE QUERY (PARENT REQUEST)
------------------------------------------ */
$sql = "
    SELECT 
        lr.*,
        CONCAT(u.first_name, ' ', u.last_name) AS employee_name,
        u.department_id
    FROM leave_requests lr
    JOIN users u ON u.id = lr.user_id
    WHERE lr.id = ?
";

$params = [$requestId];
$types  = "i";

$fullAccess = ['admin', 'executive', 'hr'];

/* -----------------------------------------
   ROLE FILTERING
------------------------------------------ */
if ($user_role === 'supervisor') {

    $sql .= "
        AND (
            lr.recipient_id = ?
            OR EXISTS (
                SELECT 1
                FROM user_departments ud1
                JOIN user_departments ud2
                  ON ud1.department_id = ud2.department_id
                WHERE ud1.user_id = lr.user_id
                  AND ud2.user_id = ?
            )
        )
    ";

    $params[] = $user_id;
    $params[] = $user_id;
    $types .= "ii";
} elseif (!in_array($user_role, $fullAccess)) {

    $sql .= " AND lr.user_id = ?";
    $params[] = $user_id;
    $types .= "i";
}


/* -----------------------------------------
   EXECUTE PARENT QUERY
------------------------------------------ */
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

if (!$res->num_rows) {
    echo json_encode(["status" => "error", "message" => "Access denied"]);
    exit;
}

$leave = $res->fetch_assoc();
$stmt->close();

/* -----------------------------------------
   LOAD CHILD DATES
------------------------------------------ */
$dateStmt = $conn->prepare("
   SELECT leave_date, availment, leave_type
    FROM leave_request_dates
    WHERE leave_request_id = ?
    ORDER BY leave_date ASC
");
$dateStmt->bind_param("i", $requestId);
$dateStmt->execute();
$dateRes = $dateStmt->get_result();

/*$items = [];
$types = [];

foreach ($items as $i) {
    $types[] = $i['leave_type']; // already normalized
}

$types = array_unique($types);

$headerLeaveType =
    count($types) > 1
    ? 'mixed'
    : ($types[0] ?? '');*/


while ($d = $dateRes->fetch_assoc()) {
    $type = strtolower($d['leave_type']);

    $type = match ($type) {
        'vacation leave' => 'vacation',
        'sick leave' => 'sick',
        'emergency leave' => 'emergency',
        'compassionate leave' => 'compassionate',
        'toil' => 'toil',
        default => strtolower(str_replace(" leave", "", $type))
    };

    $items[] = [
        "leave_date" => $d['leave_date'],
        "availment"  => floatval($d['availment']),
        // Normalized type for TOIL
        //"leave_type" => strtolower(str_replace(" Leave", "", $d['leave_type']))
        "leave_type" => $type
    ];
}

$types = array_unique(array_column($items, 'leave_type'));

$headerLeaveType = count($types) > 1
    ? 'mixed'
    : ($types[0] ?? '');

$dateStmt->close();

/* -----------------------------------------
   ATTACHMENTS
------------------------------------------
$attachments = $leave['attachments']
    ? json_decode($leave['attachments'], true)
    : [];
    */
$attachments = [];

$baseUrl =
    (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' .
    $_SERVER['HTTP_HOST'] .
    dirname(dirname(dirname($_SERVER['SCRIPT_NAME'])));

if (!empty($leave['attachments'])) {
    $files = json_decode($leave['attachments'], true);

    if (is_array($files)) {
        foreach ($files as $file) {
            $attachments[] = [
                "filename" => $file,
                "url" => $baseUrl . "/uploads/leave_attachments/" . $file
            ];
        }
    }
}



/* -----------------------------------------
   PERMISSIONS
------------------------------------------ 
$permissions = [
    "can_edit"    => false,
    "can_approve" => false
];

$isOwner   = ($leave['user_id'] == $user_id);
$isPending = ($leave['status'] === 'Pending');

/* -------------------------------
   ADMIN / EXEC / HR
-------------------------------- 
if (in_array($user_role, ['admin', 'executive', 'hr']) && $isPending) {
    $permissions['can_edit']    = true;
    $permissions['can_approve'] = true;
}

/* -------------------------------
   USER (own request only)
-------------------------------- 
if ($user_role === 'user' && $isOwner && $isPending) {
    $permissions['can_edit'] = true;
}

/* -------------------------------
   SUPERVISOR
-------------------------------- 
if ($user_role === 'supervisor' && $isPending) {

    // ❌ cannot approve own request
    if (!$isOwner) {
        // ✔ already validated by SQL (department OR recipient)
        $permissions['can_approve'] = true;
    }

    // ✔ can edit own request
    if ($isOwner) {
        $permissions['can_edit'] = true;
    }
}*/

/* -----------------------------------------
   PERMISSIONS (TEST)
------------------------------------------ */

$permissions = [
    "can_edit"    => false,
    "can_approve" => false,
    "can_cancel"  => false,
    //"can_delete"  => false,
    "can_revert"  => false
];

$isOwner   = ($leave['user_id'] == $user_id);
$isPending = ($leave['status'] === 'Pending');
$isApproved = ($leave['status'] === 'Approved');

/* -------------------------------
   OWNER (USER)
-------------------------------- */
if ($isOwner && $isPending) {
    $permissions['can_edit']   = true;
    $permissions['can_cancel'] = true;
    //$permissions['can_delete'] = true;
}

/* -------------------------------
   ADMIN / EXEC / HR
-------------------------------- */
if (in_array($user_role, ['admin', 'executive', 'hr'])) {

    //$permissions['can_delete'] = true;

    if ($isPending) {
        $permissions['can_edit'] = true;
        $permissions['can_approve'] = true;
        //$permissions['can_cancel'] = true;
    }

    if ($isApproved) {
        $permissions['can_cancel'] = true;
        $permissions['can_revert'] = true;
    }
}

/* -------------------------------
   SUPERVISOR
-------------------------------- */
if ($user_role === 'supervisor') {

    if (!$isOwner && $isPending) {
        $permissions['can_approve'] = true;
    }

    if ($isOwner && $isPending) {
        $permissions['can_edit']   = true;
        $permissions['can_cancel'] = true;
        //$permissions['can_delete'] = true;
    }

    // allow delete for others (based on your rule)
    if (!$isOwner) {
        //$permissions['can_delete'] = true;
    }
}


/* -----------------------------------------
   RESPONSE
------------------------------------------ */
echo json_encode([
    "status" => "success",
    "leave" => [
        "id" => $leave['id'],
        "user_id" => $leave['user_id'],
        "employee_name" => $leave['employee_name'],
        "reason" => $leave['reason'],
        "recipient_id" => $leave['recipient_id'],
        //"leave_type" => strtolower(str_replace(" Leave", "", $leave['leave_type'])),
        "leave_type" => $headerLeaveType,
        "status" => $leave['status'],
        "items" => $items,
        "attachments" => $attachments
    ],
    "current_user" => [
        "id" => $user_id,
        "role" => $user_role
    ],
    "permissions" => $permissions
]);
