<?php

//WORKING VERSION
/*
ob_clean();
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require '../connection_db.php';
header("Content-Type: application/json");

$userId = $_SESSION['user_id'] ?? 0;

if (!$userId) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

// ----------------------------------------------------
// GET DEPARTMENT
// ----------------------------------------------------
$getDept = $conn->prepare("SELECT department_id FROM users WHERE id = ?");
$getDept->bind_param("i", $userId);
$getDept->execute();
$departmentId = $getDept->get_result()->fetch_assoc()['department_id'] ?? null;
$getDept->close();

// ----------------------------------------------------
// REQUIRED FIELDS
// ----------------------------------------------------
$reason      = trim($_POST['reason'] ?? "");
$recipientId = intval($_POST['recipient_id'] ?? 0);
$items       = json_decode($_POST['items'] ?? "[]", true);

if (!$reason || !$recipientId || empty($items)) {
    echo json_encode(["status" => "error", "message" => "Missing required fields"]);
    exit;
}

// ----------------------------------------------------
// FILE UPLOADS
// ----------------------------------------------------
$uploadedFiles = [];
if (!empty($_FILES['attachments']['name'][0])) {
    $uploadDir = "../../uploads/leave_attachments/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    foreach ($_FILES['attachments']['tmp_name'] as $i => $tmpName) {
        $filename = time() . "_" . basename($_FILES['attachments']['name'][$i]);
        if (move_uploaded_file($tmpName, $uploadDir . $filename)) {
            $uploadedFiles[] = $filename;
        }
    }
}
$attachmentsJson = json_encode($uploadedFiles);

// ----------------------------------------------------
// MAP TYPES
// ----------------------------------------------------
$map = [
    "vacation"      => "Vacation Leave",
    "sick"          => "Sick Leave",
    "emergency"     => "Emergency Leave",
    "compassionate" => "Compassionate Leave"
];

// ----------------------------------------------------
// ROLE GUARD (TEST)
// ----------------------------------------------------

$restrictedTypes = ['emergency', 'compassionate'];

if (!in_array($_SESSION['role'], ['admin', 'hr', 'executive'])) {
    foreach ($items as $row) {
        if (in_array($row['leave_type'], $restrictedTypes)) {
            echo json_encode([
                "status" => "error",
                "message" => "You are not allowed to file Emergency or Compassionate Leave."
            ]);
            exit;
        }
    }
}


// ----------------------------------------------------
// NORMALIZE ITEMS
// ----------------------------------------------------
$dates = [];
$totalDays = 0;

foreach ($items as $row) {
    if (
        empty($row['leave_date']) ||
        empty($row['leave_type']) ||
        !isset($row['availment']) ||
        !isset($map[$row['leave_type']])
    ) continue;

    $dates[] = [
        "date"      => $row['leave_date'],
        "type"      => $map[$row['leave_type']],
        "availment" => floatval($row['availment'])
    ];
    $totalDays += floatval($row['availment']);
}

if (!$dates) {
    echo json_encode(["status" => "error", "message" => "No valid leave dates"]);
    exit;
}

$dateOnly = array_column($dates, 'date');

$startDate = min($dateOnly);
$endDate   = max($dateOnly);
$mainType  = $dates[0]['type']; // display only

// ----------------------------------------------------
// TRANSACTION
// ----------------------------------------------------
$conn->begin_transaction();

try {

    // INSERT PARENT REQUEST
    $stmt = $conn->prepare("
        INSERT INTO leave_requests (
            user_id, department_id, recipient_id,
            reason, start_date, end_date,
            leave_type, leave_days, attachments
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "iiissssds",
        $userId,
        $departmentId,
        $recipientId,
        $reason,
        $startDate,
        $endDate,
        $mainType,
        $totalDays,
        $attachmentsJson
    );

    $stmt->execute();
    $requestId = $stmt->insert_id;
    $stmt->close();

    // INSERT CHILD DATES
    $dateStmt = $conn->prepare("
    INSERT INTO leave_request_dates
    (leave_request_id, leave_date, leave_type, availment)
    VALUES (?, ?, ?, ?)
");

    foreach ($dates as $d) {
        $dateStmt->bind_param(
            "issd",
            $requestId,
            $d['date'],
            $d['type'],
            $d['availment']
        );
        $dateStmt->execute();
    }

    $dateStmt->close();

    $conn->commit();

    echo json_encode([
        "status" => "success",
        "message" => "Leave request submitted",
        "rows" => [[
            "id" => $requestId,
            "created_at" => date("Y-m-d H:i:s"),
            "start_date" => $startDate,
            "leave_type" => $mainType,
            "employee_name" => $_SESSION['user_name'] ?? "",
            "status" => "Pending",
            "checked_by" => null,
            "leave_payment_status" => "Not Required"
        ]]
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

$conn->close();
*/

ob_clean();
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require '../connection_db.php';
header("Content-Type: application/json");

$userId = $_SESSION['user_id'] ?? 0;

if (!$userId) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

// ----------------------------------------------------
// GET DEPARTMENT
// ----------------------------------------------------
$getDept = $conn->prepare("SELECT department_id FROM users WHERE id = ?");
$getDept->bind_param("i", $userId);
$getDept->execute();
$departmentId = $getDept->get_result()->fetch_assoc()['department_id'] ?? null;
$getDept->close();

// ----------------------------------------------------
// REQUIRED FIELDS
// ----------------------------------------------------
$reason      = trim($_POST['reason'] ?? "");
$recipientId = intval($_POST['recipient_id'] ?? 0);
$items       = json_decode($_POST['items'] ?? "[]", true);

if (!$reason || !$recipientId || empty($items)) {
    echo json_encode(["status" => "error", "message" => "Missing required fields"]);
    exit;
}

// ----------------------------------------------------
// FILE UPLOADS
// ----------------------------------------------------
$uploadedFiles = [];
if (!empty($_FILES['attachments']['name'][0])) {
    $uploadDir = "../../uploads/leave_attachments/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    foreach ($_FILES['attachments']['tmp_name'] as $i => $tmpName) {
        $filename = time() . "_" . basename($_FILES['attachments']['name'][$i]);
        if (move_uploaded_file($tmpName, $uploadDir . $filename)) {
            $uploadedFiles[] = $filename;
        }
    }
}
$attachmentsJson = json_encode($uploadedFiles);

// ----------------------------------------------------
// MAP TYPES
// ----------------------------------------------------
$map = [
    "vacation"      => "Vacation Leave",
    "sick"          => "Sick Leave",
    "emergency"     => "Emergency Leave",
    "compassionate" => "Compassionate Leave",
    "toil"          => "TOIL"
];

// ----------------------------------------------------
// ROLE GUARD
// ----------------------------------------------------
$restrictedTypes = ['emergency', 'compassionate'];

if (!in_array($_SESSION['role'], ['admin', 'hr', 'executive'])) {
    foreach ($items as $row) {
        if (in_array($row['leave_type'], $restrictedTypes)) {
            echo json_encode([
                "status" => "error",
                "message" => "You are not allowed to file Emergency or Compassionate Leave."
            ]);
            exit;
        }
    }
}

foreach ($items as $row) {
    if ($row['leave_type'] === 'toil' && $userId != 2) {
        echo json_encode([
            "status" => "error",
            "message" => "You are not allowed to file TOIL."
        ]);
        exit;
    }
}

// ----------------------------------------------------
// NORMALIZE ITEMS & CALCULATE TOTALS
// ----------------------------------------------------
$dates = [];
$totalDays = 0;
$totalWholeDaySL = 0; // track whole-day Sick Leave in current request

foreach ($items as $row) {
    if (
        empty($row['leave_date']) ||
        empty($row['leave_type']) ||
        !isset($row['availment']) ||
        !isset($map[$row['leave_type']])
    ) continue;

    $avail = floatval($row['availment']);
    $dates[] = [
        "date"      => $row['leave_date'],
        "type"      => $map[$row['leave_type']],
        "availment" => $avail
    ];
    //Added for TOIL TO NOT count against total leave days
    if ($map[$row['leave_type']] !== "TOIL") {
        $totalDays += $avail;
    }

    if ($map[$row['leave_type']] === "Sick Leave" && $avail === 1) {
        $totalWholeDaySL++;
    }
}

if (!$dates) {
    echo json_encode(["status" => "error", "message" => "No valid leave dates"]);
    exit;
}

$dateOnly = array_column($dates, 'date');
$startDate = min($dateOnly);
$endDate   = max($dateOnly);
$mainType  = $dates[0]['type']; // display only

// ----------------------------------------------------
// SICK LEAVE ATTACHMENT VALIDATION (MONTHLY RESET)
// ----------------------------------------------------

// Determine request month/year
$reqMonth = date('m', strtotime($startDate));
$reqYear  = date('Y', strtotime($startDate));

// Calculate sick leave in CURRENT request
$sickLeaveCurrent = array_reduce($dates, function ($carry, $d) {
    return $d['type'] === 'Sick Leave' ? $carry + $d['availment'] : $carry;
}, 0);

if ($sickLeaveCurrent > 0) {

    // Get total SL for the SAME MONTH only
    $chkExistingSL = $conn->prepare("
    SELECT COALESCE(SUM(lrd.availment),0)
    FROM leave_request_dates lrd
    JOIN leave_requests lr ON lr.id = lrd.leave_request_id
    WHERE lr.user_id = ?
      AND lrd.leave_type = 'Sick Leave'
      AND lr.status IN ('Pending','Approved')
      AND MONTH(lrd.leave_date) = ?
      AND YEAR(lrd.leave_date) = ?
");
    $chkExistingSL->bind_param("iii", $userId, $reqMonth, $reqYear);
    $chkExistingSL->execute();
    $chkExistingSL->bind_result($existingSickLeave);
    $chkExistingSL->fetch();
    $chkExistingSL->close();

    // Monthly total SL
    $totalSickLeave = $existingSickLeave + $sickLeaveCurrent;

    // Require attachment if total reaches 2 days in the SAME month
    if ($totalSickLeave >= 2 && empty($uploadedFiles)) {
        echo json_encode([
            "status" => "error",
            "message" => "Medical certificate is required once Sick Leave reaches 2 days within the month."
        ]);
        exit;
    }
}


// ----------------------------------------------------
// TRANSACTION
// ----------------------------------------------------
$conn->begin_transaction();

try {
    // INSERT PARENT REQUEST
    $stmt = $conn->prepare("
        INSERT INTO leave_requests (
            user_id, department_id, recipient_id,
            reason, start_date, end_date,
            leave_type, leave_days, attachments
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "iiissssds",
        $userId,
        $departmentId,
        $recipientId,
        $reason,
        $startDate,
        $endDate,
        $mainType,
        $totalDays,
        $attachmentsJson
    );

    $stmt->execute();
    $requestId = $stmt->insert_id;
    $stmt->close();

    // INSERT CHILD DATES
    $dateStmt = $conn->prepare("
        INSERT INTO leave_request_dates
        (leave_request_id, leave_date, leave_type, availment)
        VALUES (?, ?, ?, ?)
    ");

    foreach ($dates as $d) {
        $dateStmt->bind_param(
            "issd",
            $requestId,
            $d['date'],
            $d['type'],
            $d['availment']
        );
        $dateStmt->execute();
    }

    $dateStmt->close();

    $conn->commit();

    echo json_encode([
        "status" => "success",
        "message" => "Leave request submitted",
        "rows" => [[
            "id" => $requestId,
            "created_at" => date("Y-m-d H:i:s"),
            "start_date" => $startDate,
            "leave_type" => $mainType,
            "employee_name" => $_SESSION['user_name'] ?? "",
            "status" => "Pending",
            "checked_by" => null,
            "leave_payment_status" => "Not Required"
        ]]
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

$conn->close();
