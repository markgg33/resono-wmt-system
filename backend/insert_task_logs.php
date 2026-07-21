<?php
/*
// WORKING VERSION + CALL TIME LOCK + SERVER TIME VALIDATION
session_start();
require 'connection_db.php';
header('Content-Type: application/json');
date_default_timezone_set("Asia/Manila");

$data = json_decode(file_get_contents("php://input"), true);

$user_id             = $_SESSION['user_id'] ?? null;
$work_mode_id        = $data['work_mode_id'] ?? null;
$task_description_id = $data['task_description_id'] ?? null;
$date                = $data['date'] ?? null;
$start_time          = $data['start_time'] ?? null;
$remarks             = $data['remarks'] ?? '';
$call_time_input     = $data['call_time'] ?? null; // 🆕 from frontend

if (!$user_id || !$work_mode_id || !$task_description_id || !$date || !$start_time) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

// 🔐 VERIFY DATE MATCHES SERVER DATE (to prevent date tampering)
// This is the ONLY validation we need since the time came FROM the server
$server_date = date("Y-m-d");

if ($date !== $server_date) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Date mismatch. Your system date is incorrect.',
        'expected_date' => $server_date,
        'received_date' => $date
    ]);
    exit;
}

// ✅ TIME VALIDATED - Use the frontend-provided PH time (already formatted by server)
// This preserves the exact moment the user clicked "Start Task"
$start_datetime = "$date $start_time";

// ----------------------------------------------------
// 🧠 CHECK IF THIS IS FIRST TASK OF THE DAY
// ----------------------------------------------------
$stmt = $conn->prepare("
        SELECT COUNT(*) AS cnt
        FROM task_logs
        WHERE user_id = ? AND date = ?
    ");
$stmt->bind_param("is", $user_id, $server_date);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

$isFirstTask = ($result['cnt'] == 0);

// Use the call time as selected by the user, regardless of when they actually logged in
$final_call_time = ($isFirstTask && $call_time_input) ? $call_time_input : null;

// ----------------------------------------------------
// 🟢 INSERT TASK LOG WITH SERVER TIME
// ----------------------------------------------------
$stmt = $conn->prepare("
        INSERT INTO task_logs 
        (user_id, work_mode_id, task_description_id, date, start_time, call_time, remarks) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

$stmt->bind_param(
    "iiissss",
    $user_id,
    $work_mode_id,
    $task_description_id,
    $server_date,
    $start_datetime,
    $final_call_time,
    $remarks
);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'inserted_id' => $stmt->insert_id,
        'call_time_saved' => $final_call_time,
        'message' => 'Task logged successfully in PH timezone'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to insert: ' . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>
*/

/*
session_start();
require 'connection_db.php';
header('Content-Type: application/json');
date_default_timezone_set("Asia/Manila");

$data = json_decode(file_get_contents("php://input"), true);

$user_id             = $_SESSION['user_id'] ?? null;
$work_mode_id        = $data['work_mode_id'] ?? null;
$task_description_id = $data['task_description_id'] ?? null;
$start_time          = $data['start_time'] ?? null; // "HH:MM:SS" (from server JS)
$remarks             = $data['remarks'] ?? '';
$call_time_input     = $data['call_time'] ?? null;  // first task only

if (!$user_id || !$work_mode_id || !$task_description_id || !$start_time) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

$server_date = date("Y-m-d");
$server_time = date("H:i:s");

// -----------------------------
// 1) Determine call_time to use
// -----------------------------
/*
  - If first task "today calendar date" -> accept call_time_input
  - Otherwise reuse latest saved call_time (from recent logs)

$stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM task_logs WHERE user_id = ? AND date = ?");
$stmt->bind_param("is", $user_id, $server_date);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$isFirstTaskToday = ((int)$row['cnt'] === 0);

$final_call_time = null;

if ($isFirstTaskToday && $call_time_input) {
    $final_call_time = $call_time_input;
} else {
    // reuse latest non-null call_time from recent logs (today or yesterday)
    $stmt = $conn->prepare("
        SELECT call_time
        FROM task_logs
        WHERE user_id = ?
          AND call_time IS NOT NULL
          AND date >= DATE_SUB(?, INTERVAL 1 DAY)
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->bind_param("is", $user_id, $server_date);
    $stmt->execute();
    $final_call_time = $stmt->get_result()->fetch_assoc()['call_time'] ?? null;
    $stmt->close();
}

// -----------------------------------
// 2) Compute work_date based on call_time
// -----------------------------------
$work_date = $server_date;

if ($final_call_time) {
    // if current server time is BEFORE call_time => belongs to yesterday's work_date
    if ($server_time < $final_call_time) {
        $work_date = date("Y-m-d", strtotime($server_date . " -1 day"));
    }
}

// -----------------------------------
// 3) Insert (IMPORTANT: start_time is TIME only)
// -----------------------------------
$stmt = $conn->prepare("
    INSERT INTO task_logs
        (user_id, work_mode_id, task_description_id, date, work_date, start_time, call_time, remarks)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "iiisssss",
    $user_id,
    $work_mode_id,
    $task_description_id,
    $server_date,   // calendar date
    $work_date,     // shift date
    $start_time,    // TIME
    $final_call_time,
    $remarks
);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'inserted_id' => $stmt->insert_id,
        'date' => $server_date,
        'work_date' => $work_date,
        'call_time_saved' => $final_call_time
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to insert: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
*/

/*
session_start();
require 'connection_db.php';
header('Content-Type: application/json');
date_default_timezone_set("Asia/Manila");

$data = json_decode(file_get_contents("php://input"), true);

$user_id             = $_SESSION['user_id'] ?? null;
$work_mode_id        = $data['work_mode_id'] ?? null;
$task_description_id = $data['task_description_id'] ?? null;
$start_time          = $data['start_time'] ?? null; // "HH:MM:SS" (from server JS)
$remarks             = $data['remarks'] ?? '';
$call_time_input     = $data['call_time'] ?? null;  // first task only
$work_date_input = $data['work_date'] ?? null;
$end_time_input  = $data['end_time'] ?? null;
$end_date_input  = $data['end_date'] ?? null;

if ($work_date_input) {
    $work_date =  $work_date_input;
}

$end_time = null;
$end_date = null;
$total_duration = null;

if (!$user_id || !$work_mode_id || !$task_description_id || !$start_time) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

$server_date = date("Y-m-d");
$server_time = date("H:i:s");

// -----------------------------
// 1) Determine call_time to use
// -----------------------------
/*
  - If first task "today calendar date" -> accept call_time_input
  - Otherwise reuse latest saved call_time (from recent logs)

$stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM task_logs WHERE user_id = ? AND date = ?");
$stmt->bind_param("is", $user_id, $server_date);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$isFirstTaskToday = ((int)$row['cnt'] === 0);

$final_call_time = null;

if ($isFirstTaskToday && $call_time_input) {
    $final_call_time = $call_time_input;
} else {
    // reuse latest non-null call_time from recent logs (today or yesterday)
    $stmt = $conn->prepare("
        SELECT call_time
        FROM task_logs
        WHERE user_id = ?
          AND call_time IS NOT NULL
          AND date >= DATE_SUB(?, INTERVAL 1 DAY)
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->bind_param("is", $user_id, $server_date);
    $stmt->execute();
    $final_call_time = $stmt->get_result()->fetch_assoc()['call_time'] ?? null;
    $stmt->close();
}

// -----------------------------------
// 2) Compute work_date based on call_time
// -----------------------------------
$work_date = $server_date;

if ($final_call_time) {
    // if current server time is BEFORE call_time => belongs to yesterday's work_date
    if ($server_time < $final_call_time) {
        $work_date = date("Y-m-d", strtotime($server_date . " -1 day"));
    }
}

// -----------------------------------
// 3) Insert (IMPORTANT: start_time is TIME only)
// -----------------------------------

if (!empty($end_time_input)) {
    $end_time = trim($end_time_input);
    if (preg_match('/^\d{2}:\d{2}$/', $end_time)) $end_time .= ':00';

    if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $end_time)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid end_time format']);
        exit;
    }

    // use explicit end_date if provided
    $end_date = $end_date_input ?: $server_date;

    // ✅ duration for "End Shift" should be 00:00:00
    $total_duration = "00:00:00";
}

$stmt = $conn->prepare("
    INSERT INTO task_logs
      (user_id, work_mode_id, task_description_id, date, work_date, start_time, call_time, remarks, end_time, end_date, total_duration)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "iiissssssss",
    $user_id,
    $work_mode_id,
    $task_description_id,
    $server_date,
    $work_date,
    $start_time,
    $final_call_time,
    $remarks,
    $end_time,
    $end_date,
    $total_duration
);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'inserted_id' => $stmt->insert_id,
        'date' => $server_date,
        'work_date' => $work_date,
        'call_time_saved' => $final_call_time
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to insert: ' . $stmt->error]);
}

$stmt->close();
$conn->close();


*/ 

//RECENT WORKING VERSION
/*
session_start();
require 'connection_db.php';
header('Content-Type: application/json');
date_default_timezone_set("Asia/Manila");

// Optional: prevent warnings/notices from corrupting JSON output
ini_set('display_errors', 0);
error_reporting(E_ALL);

$data = json_decode(file_get_contents("php://input"), true);

$user_id             = $_SESSION['user_id'] ?? null;
$work_mode_id        = $data['work_mode_id'] ?? null;
$task_description_id = $data['task_description_id'] ?? null;
$start_time          = $data['start_time'] ?? null; // "HH:MM:SS"
$remarks             = $data['remarks'] ?? '';

$call_time_input     = $data['call_time'] ?? null;  // from UI
$work_date_input     = $data['work_date'] ?? null;  // from JS (IMPORTANT)

$end_time_input      = $data['end_time'] ?? null;   // for End Shift inserts
$end_date_input      = $data['end_date'] ?? null;   // for End Shift inserts

if (!$user_id || !$work_mode_id || !$task_description_id || !$start_time) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

// Normalize start_time to HH:MM:SS
$start_time = trim($start_time);
if (preg_match('/^\d{2}:\d{2}$/', $start_time)) $start_time .= ':00';
if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $start_time)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid start_time format']);
    exit;
}

$server_date = date("Y-m-d");
$server_time = date("H:i:s");


// =========================================================
// ✅ PATCH START: Decide WORK DATE FIRST (shift-aware logic)
// =========================================================

// ✅ Determine work_date (SHIFT DATE) early so we can check open task per shift
$work_date = $server_date;

if (!empty($work_date_input)) {
    $work_date = $work_date_input; // ✅ JS is source of truth
}

// =========================================================
// ✅ PATCH END
// =========================================================


// ---------------------------------------------------------
// ✅ PATCH START: 0) Determine if user has an OPEN task FOR THIS work_date
// ---------------------------------------------------------
$stmt = $conn->prepare("
    SELECT id, call_time
    FROM task_logs
    WHERE user_id = ?
      AND work_date = ?
      AND (end_time IS NULL OR TRIM(end_time) = '')
    ORDER BY id DESC
    LIMIT 1
");
$stmt->bind_param("is", $user_id, $work_date);
$stmt->execute();
$open = $stmt->get_result()->fetch_assoc();
$stmt->close();

$hasOpenTask = !empty($open);   // ✅ open row exists for this shift day
$isNewShift  = !$hasOpenTask;   // ✅ new shift only if none open for this work_date
// ---------------------------------------------------------
// ✅ PATCH END
// ---------------------------------------------------------


// ---------------------------------------------------------
// ✅ PATCH START: 1) Determine call_time (shift-aware reuse)
// ---------------------------------------------------------
$final_call_time = null;

if ($isNewShift && !empty($call_time_input)) {
    // ✅ first task of this shift/day: accept UI call time
    $final_call_time = $call_time_input;
} else {
    // ✅ ongoing shift: reuse call_time WITHIN THIS work_date
    $stmt = $conn->prepare("
        SELECT call_time
        FROM task_logs
        WHERE user_id = ?
          AND work_date = ?
          AND call_time IS NOT NULL
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->bind_param("is", $user_id, $work_date);
    $stmt->execute();
    $final_call_time = $stmt->get_result()->fetch_assoc()['call_time'] ?? null;
    $stmt->close();
}

// Normalize call_time to HH:MM:SS if present
if ($final_call_time) {
    $final_call_time = trim($final_call_time);
    if (preg_match('/^\d{2}:\d{2}$/', $final_call_time)) $final_call_time .= ':00';
    if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $final_call_time)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid call_time format']);
        exit;
    }
}
// ---------------------------------------------------------
// ✅ PATCH END
// ---------------------------------------------------------


// ---------------------------------------------------------
// ✅ PATCH START: 2) (Optional fallback) If JS did NOT send work_date_input
// Keep your “before call_time => yesterday” rule ONLY in this fallback case.
// ---------------------------------------------------------
if (empty($work_date_input) && !$isNewShift && $final_call_time) {
    if ($server_time < $final_call_time) {
        $work_date = date("Y-m-d", strtotime($server_date . " -1 day"));
    }
}
// ---------------------------------------------------------
// ✅ PATCH END
// ---------------------------------------------------------


// ---------------------------------------------------------
// 3) End Shift immediate close support (optional inputs)
// ---------------------------------------------------------
$end_time = null;
$end_date = null;
$total_duration = null;

if (!empty($end_time_input)) {
    $end_time = trim($end_time_input);
    if (preg_match('/^\d{2}:\d{2}$/', $end_time)) $end_time .= ':00';
    if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $end_time)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid end_time format']);
        exit;
    }

    $end_date = !empty($end_date_input) ? $end_date_input : $server_date;
    $total_duration = "00:00:00";
}

// ---------------------------------------------------------
// 4) Insert
// ---------------------------------------------------------
$stmt = $conn->prepare("
    INSERT INTO task_logs
      (user_id, work_mode_id, task_description_id, date, work_date, start_time, call_time, remarks, end_time, end_date, total_duration)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "iiissssssss",
    $user_id,
    $work_mode_id,
    $task_description_id,
    $server_date,
    $work_date,
    $start_time,
    $final_call_time,
    $remarks,
    $end_time,
    $end_date,
    $total_duration
);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'inserted_id' => $stmt->insert_id,
        'date' => $server_date,
        'work_date' => $work_date,
        'call_time_saved' => $final_call_time,
        'is_new_shift' => $isNewShift
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to insert: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
*/


session_start();
require 'connection_db.php';
header('Content-Type: application/json');
date_default_timezone_set("Asia/Manila");

// Optional: prevent warnings/notices from corrupting JSON output
ini_set('display_errors', 0);
error_reporting(E_ALL);

error_log("SESSION USER (insert): " . ($_SESSION['user_id'] ?? 'NULL'));

$data = json_decode(file_get_contents("php://input"), true);

$user_id             = $_SESSION['user_id'] ?? null;
$work_mode_id        = $data['work_mode_id'] ?? null;
$task_description_id = $data['task_description_id'] ?? null;
$start_time          = $data['start_time'] ?? null; // "HH:MM:SS"
$remarks             = $data['remarks'] ?? '';

$call_time_input     = $data['call_time'] ?? null;  // from UI
$work_date_input     = $data['work_date'] ?? null;  // from JS (IMPORTANT)

$end_time_input      = $data['end_time'] ?? null;   // for End Shift inserts
$end_date_input      = $data['end_date'] ?? null;   // for End Shift inserts

if (!$user_id || !$work_mode_id || !$task_description_id || !$start_time) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

/**
 * ✅ Force any time to "HH:MM:00" (minute-locked)
 */
function floorToMinute(?string $t): ?string {
    if (!$t) return null;
    $t = trim($t);
    if ($t === '' || $t === '--') return null;

    // HH:MM -> HH:MM:00
    if (preg_match('/^\d{2}:\d{2}$/', $t)) return $t . ':00';

    // HH:MM:SS -> HH:MM:00
    if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $t)) return substr($t, 0, 5) . ':00';

    return null;
}

// Normalize + minute-lock start_time
$start_time = floorToMinute($start_time);
if (!$start_time) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid start_time format']);
    exit;
}

$server_date = date("Y-m-d");
$server_time = date("H:i:s");

// =========================================================
// ✅ Decide WORK DATE FIRST (shift-aware logic)
// =========================================================
$work_date = $server_date;
if (!empty($work_date_input)) {
    $work_date = $work_date_input; // ✅ JS is source of truth
}

// ---------------------------------------------------------
// ✅ Determine if user has an OPEN task FOR THIS work_date
// ---------------------------------------------------------
$stmt = $conn->prepare("
    SELECT id, call_time
    FROM task_logs
    WHERE user_id = ?
      AND work_date = ?
      AND (end_time IS NULL OR TRIM(end_time) = '')
    ORDER BY id DESC
    LIMIT 1
");
$stmt->bind_param("is", $user_id, $work_date);
$stmt->execute();
$open = $stmt->get_result()->fetch_assoc();
$stmt->close();

$hasOpenTask = !empty($open);
$isNewShift  = !$hasOpenTask;

// ---------------------------------------------------------
// ✅ Determine call_time (shift-aware reuse) + minute-lock
// ---------------------------------------------------------
$final_call_time = null;

if ($isNewShift && !empty($call_time_input)) {
    $final_call_time = $call_time_input;
} else {
    $stmt = $conn->prepare("
        SELECT call_time
        FROM task_logs
        WHERE user_id = ?
          AND work_date = ?
          AND call_time IS NOT NULL
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->bind_param("is", $user_id, $work_date);
    $stmt->execute();
    $final_call_time = $stmt->get_result()->fetch_assoc()['call_time'] ?? null;
    $stmt->close();
}

$final_call_time = floorToMinute($final_call_time);
if ($final_call_time === null && $isNewShift && !empty($call_time_input)) {
    // If user provided call_time but it was invalid
    echo json_encode(['status' => 'error', 'message' => 'Invalid call_time format']);
    exit;
}

// ---------------------------------------------------------
// ✅ Optional fallback if JS did NOT send work_date_input
// Keep your “before call_time => yesterday” rule ONLY here.
// ---------------------------------------------------------
if (empty($work_date_input) && !$isNewShift && $final_call_time) {
    if ($server_time < $final_call_time) {
        $work_date = date("Y-m-d", strtotime($server_date . " -1 day"));
    }
}

// ---------------------------------------------------------
// 3) End Shift immediate close support (optional inputs)
// ---------------------------------------------------------
$end_time = null;
$end_date = null;
$total_duration = null;

if (!empty($end_time_input)) {
    $end_time = floorToMinute($end_time_input);
    if (!$end_time) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid end_time format']);
        exit;
    }

    $end_date = !empty($end_date_input) ? $end_date_input : $server_date;
    $total_duration = "00:00:00"; // End shift is always 0
}

// ---------------------------------------------------------
// 4) Insert
// ---------------------------------------------------------
$stmt = $conn->prepare("
    INSERT INTO task_logs
      (user_id, work_mode_id, task_description_id, date, work_date, start_time, call_time, remarks, end_time, end_date, total_duration)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "iiissssssss",
    $user_id,
    $work_mode_id,
    $task_description_id,
    $server_date,
    $work_date,
    $start_time,
    $final_call_time,
    $remarks,
    $end_time,
    $end_date,
    $total_duration
);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'inserted_id' => $stmt->insert_id,
        'date' => $server_date,
        'work_date' => $work_date,
        'call_time_saved' => $final_call_time,
        'is_new_shift' => $isNewShift
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to insert: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
