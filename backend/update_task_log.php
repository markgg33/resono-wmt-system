<?php
/*
// 🔐 SERVER TIME VALIDATED VERSION
require 'connection_db.php';
header('Content-Type: application/json');
date_default_timezone_set("Asia/Manila");

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id'] ?? null;
$end_time = $data['end_time'] ?? null;
$duration = $data['duration'] ?? null;

if (!$id || !$end_time || !$duration) {
    echo json_encode(["status" => "error", "message" => "Missing fields"]);
    exit;
}

// 🔐 SECURITY: Only validate that the time format is valid
// The time came FROM the server, so it's already in PH timezone
// Just ensure the date is today (prevent obvious tampering)
$server_date = date("Y-m-d");
$received_time_full = $server_date . " " . $end_time;
$received_timestamp = strtotime($received_time_full);

if (!$received_timestamp) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Invalid time format.',
        'server_time_diff' => $time_diff
    ]);
    exit;
}

// ✅ TIME VALIDATED - Use the frontend-provided end time (already formatted by server in PH timezone)
$final_end_time = $end_time;

// ==============================
// ⭐ FIX: Handle Midnight Crossing
// ==============================
$startQuery = $conn->prepare("SELECT start_time FROM task_logs WHERE id = ?");
$startQuery->bind_param("i", $id);
$startQuery->execute();
$startQuery->bind_result($start_time_from_db);
$startQuery->fetch();
$startQuery->close();

if ($start_time_from_db) {

    $startSec = strtotime("1970-01-01 $start_time_from_db UTC");
    $endSec   = strtotime("1970-01-01 $final_end_time UTC");

    // ⭐ Excel logic: if end < start → add 24 hours to end time
    if ($endSec < $startSec) {
        $endSec += 86400;
        $final_end_time = gmdate("H:i:s", $endSec);
    }
}

// Update with validated time
$stmt = $conn->prepare("UPDATE task_logs SET end_time = ?, total_duration = ? WHERE id = ?");
$stmt->bind_param("ssi", $final_end_time, $duration, $id);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "server_end_time" => $final_end_time
    ]);
} else {
    echo json_encode(["status" => "error", "message" => $stmt->error]);
}

$stmt->close();
$conn->close();
?>


require 'connection_db.php';
header('Content-Type: application/json');
date_default_timezone_set("Asia/Manila");

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id'] ?? null;
$end_time = $data['end_time'] ?? null;   // "HH:MM:SS"
$duration = $data['duration'] ?? null;   // "HH:MM:SS"

if (!$id || !$end_time || !$duration) {
    echo json_encode(["status" => "error", "message" => "Missing fields"]);
    exit;
}

// Fetch start_time (TIME)
$stmt = $conn->prepare("SELECT start_time FROM task_logs WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$start_time = $stmt->get_result()->fetch_assoc()['start_time'] ?? null;
$stmt->close();

if (!$start_time) {
    echo json_encode(["status" => "error", "message" => "Task not found"]);
    exit;
}

// We store TIME only, so keep end_time as-is.
// (Duration should already handle midnight crossing in JS.)
$final_end_time = $end_time;

$stmt = $conn->prepare("UPDATE task_logs SET end_time = ?, total_duration = ? WHERE id = ?");
$stmt->bind_param("ssi", $final_end_time, $duration, $id);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "server_end_time" => $final_end_time]);
} else {
    echo json_encode(["status" => "error", "message" => $stmt->error]);
}

$stmt->close();
$conn->close();
*/

/*
require 'connection_db.php';
header('Content-Type: application/json');
date_default_timezone_set("Asia/Manila");

$data = json_decode(file_get_contents("php://input"), true);

$id       = $data['id'] ?? null;
$end_time = $data['end_time'] ?? null; // "HH:MM:SS" (required)

// duration from JS is OPTIONAL now (we will compute server-side)
$duration_from_client = $data['duration'] ?? null;

if (!$id || !$end_time) {
    echo json_encode(["status" => "error", "message" => "Missing fields (id, end_time)"]);
    exit;
}

// Basic sanitize: accept HH:MM or HH:MM:SS, force to HH:MM:SS
$end_time = trim($end_time);
if (preg_match('/^\d{2}:\d{2}$/', $end_time)) {
    $end_time .= ':00';
}
if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $end_time)) {
    echo json_encode(["status" => "error", "message" => "Invalid end_time format"]);
    exit;
}

// Fetch work_date + start_time for this log
$stmt = $conn->prepare("SELECT work_date, start_time FROM task_logs WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$work_date  = $row['work_date'] ?? null;  // "YYYY-MM-DD"
$start_time = $row['start_time'] ?? null; // "HH:MM:SS" or "HH:MM"

if (!$work_date || !$start_time) {
    echo json_encode(["status" => "error", "message" => "Task not found or missing start_time/work_date"]);
    exit;
}

// Normalize start_time to HH:MM:SS
$start_time = trim($start_time);
if (preg_match('/^\d{2}:\d{2}$/', $start_time)) {
    $start_time .= ':00';
}
if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $start_time)) {
    echo json_encode(["status" => "error", "message" => "Invalid start_time format in DB"]);
    exit;
}

/**
 * ✅ OPTION A: compute end_date
 * If end_time < start_time => crossed midnight => end_date = work_date + 1 day
 
$end_date = $work_date;
if ($end_time < $start_time) {
    $dt = new DateTime($work_date);
    $dt->modify('+1 day');
    $end_date = $dt->format('Y-m-d');
}

// Compute duration using timestamps (server source of truth)
$startDT = new DateTime("$work_date $start_time");
$endDT   = new DateTime("$end_date $end_time");

$diffSeconds = $endDT->getTimestamp() - $startDT->getTimestamp();
if ($diffSeconds < 0) {
    // Safety fallback (should not happen if end_date logic is correct)
    $diffSeconds += 86400;
}

$hours   = intdiv($diffSeconds, 3600);
$minutes = intdiv($diffSeconds % 3600, 60);
$seconds = $diffSeconds % 60;

// MySQL TIME supports large hours, so keep it
$total_duration = sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);

// Update DB (end_time + end_date + total_duration)
$upd = $conn->prepare("UPDATE task_logs SET end_time = ?, end_date = ?, total_duration = ? WHERE id = ?");
$upd->bind_param("sssi", $end_time, $end_date, $total_duration, $id);

if ($upd->execute()) {
    echo json_encode([
        "status" => "success",
        "server_end_time" => $end_time,
        "server_end_date" => $end_date,
        "server_total_duration" => $total_duration,
        "client_duration_received" => $duration_from_client
    ]);
} else {
    echo json_encode(["status" => "error", "message" => $upd->error]);
}

$upd->close();
$conn->close();
*/

//RECENT WORKING VERSION
/*
require 'connection_db.php';
header('Content-Type: application/json');
date_default_timezone_set("Asia/Manila");

// Optional: prevent warnings/notices from corrupting JSON output
ini_set('display_errors', 0);
error_reporting(E_ALL);

$data = json_decode(file_get_contents("php://input"), true);

$id       = $data['id'] ?? null;
$end_time = $data['end_time'] ?? null;

if (!$id || !$end_time) {
    echo json_encode(["status" => "error", "message" => "Missing fields (id, end_time)"]);
    exit;
}

// Normalize end_time to HH:MM:SS
$end_time = trim($end_time);
if (preg_match('/^\d{2}:\d{2}$/', $end_time)) $end_time .= ':00';
if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $end_time)) {
    echo json_encode(["status" => "error", "message" => "Invalid end_time format"]);
    exit;
}

// Fetch needed info
$stmt = $conn->prepare("SELECT work_date, date, start_time, task_description_id FROM task_logs WHERE id = ?");
if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Prepare failed (SELECT): " . $conn->error]);
    exit;
}

$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(["status" => "error", "message" => "Task not found for id: {$id}"]);
    exit;
}

$work_date           = $row['work_date'];
$calendar_date       = $row['date'];
$start_time          = $row['start_time'];
$task_description_id = $row['task_description_id'];

if (!$work_date || !$calendar_date || !$start_time) {
    echo json_encode(["status" => "error", "message" => "Task missing required fields (work_date/date/start_time)"]);
    exit;
}

// Normalize start_time
$start_time = trim($start_time);
if (preg_match('/^\d{2}:\d{2}$/', $start_time)) $start_time .= ':00';
if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $start_time)) {
    echo json_encode(["status" => "error", "message" => "Invalid start_time format in DB"]);
    exit;
}

// End Shift check (better if you store a constant or lookup by name)
$isEndShift = ((int)$task_description_id === 11);

// Compute end_date
$end_date = $work_date;

if ($isEndShift) {
    $end_date = $calendar_date;
} else if ($end_time < $start_time) {
    $dt = new DateTime($work_date);
    $dt->modify('+1 day');
    $end_date = $dt->format('Y-m-d');
}

// Compute duration
$startDT = new DateTime("$work_date $start_time");
$endDT   = new DateTime("$end_date $end_time");

$diffSeconds = $endDT->getTimestamp() - $startDT->getTimestamp();
if ($diffSeconds < 0) $diffSeconds += 86400;

$hours   = intdiv($diffSeconds, 3600);
$minutes = intdiv($diffSeconds % 3600, 60);
$seconds = $diffSeconds % 60;

$total_duration = sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);

// End Shift always 0
if ($isEndShift) {
    $total_duration = "00:00:00";
}

$upd = $conn->prepare("UPDATE task_logs SET end_time = ?, end_date = ?, total_duration = ? WHERE id = ?");
if (!$upd) {
    echo json_encode(["status" => "error", "message" => "Prepare failed (UPDATE): " . $conn->error]);
    exit;
}

$upd->bind_param("sssi", $end_time, $end_date, $total_duration, $id);

if ($upd->execute()) {
    echo json_encode([
        "status" => "success",
        "server_end_time" => $end_time,
        "server_end_date" => $end_date,
        "server_total_duration" => $total_duration,
        "is_end_shift" => $isEndShift,
        "affected_rows" => $upd->affected_rows
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Execute failed: " . $upd->error]);
}

$upd->close();
$conn->close();
*/

session_start();

require 'connection_db.php';
header('Content-Type: application/json');
date_default_timezone_set("Asia/Manila");

// Optional: prevent warnings/notices from corrupting JSON output
ini_set('display_errors', 0);
error_reporting(E_ALL);

// ----------------------------------------------------
// SESSION VALIDATION
// ----------------------------------------------------
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode([
        "status" => "error",
        "message" => "Session expired. Please login again."
    ]);
    exit;
}

error_log("SESSION USER (insert): " . ($_SESSION['user_id'] ?? 'NULL'));

$data = json_decode(file_get_contents("php://input"), true);

$id       = $data['id'] ?? null;
$end_time = $data['end_time'] ?? null;

if (!$id || !$end_time) {
    echo json_encode(["status" => "error", "message" => "Missing fields (id, end_time)"]);
    exit;
}

/**
 * ✅ Force any time to "HH:MM:00" (minute-locked)
 */
function floorToMinute(?string $t): ?string
{
    if (!$t) return null;
    $t = trim($t);
    if ($t === '' || $t === '--') return null;

    if (preg_match('/^\d{2}:\d{2}$/', $t)) return $t . ':00';
    if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $t)) return substr($t, 0, 5) . ':00';

    return null;
}

$end_time = floorToMinute($end_time);
if (!$end_time) {
    echo json_encode(["status" => "error", "message" => "Invalid end_time format"]);
    exit;
}

// Fetch needed info
$stmt = $conn->prepare("SELECT work_date, date, start_time, task_description_id FROM task_logs WHERE id = ?");
if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Prepare failed (SELECT): " . $conn->error]);
    exit;
}

$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(["status" => "error", "message" => "Task not found for id: {$id}"]);
    exit;
}

$work_date           = $row['work_date'];
$calendar_date       = $row['date'];
$start_time          = $row['start_time'];
$task_description_id = $row['task_description_id'];

if (!$work_date || !$calendar_date || !$start_time) {
    echo json_encode(["status" => "error", "message" => "Task missing required fields (work_date/date/start_time)"]);
    exit;
}

$start_time = floorToMinute($start_time);
if (!$start_time) {
    echo json_encode(["status" => "error", "message" => "Invalid start_time format in DB"]);
    exit;
}

// End Shift check (your existing rule)
$isEndShift = ((int)$task_description_id === 11);

// Wall-clock day the segment started (insert/server calendar date), not work_date.
// Night-shift rows after midnight have date = e.g. Mar 23 but work_date = Mar 22; using
// work_date for end_date when end > start left end_date on Mar 22 and broke summaries
// that pair startDate from `date` with end_date.
$segment_start_date = $calendar_date;

// Compute end_date
$end_date = $segment_start_date;

if ($isEndShift) {
    $end_date = $calendar_date;
} else if ($end_time < $start_time) {
    $dt = new DateTime($segment_start_date);
    $dt->modify('+1 day');
    $end_date = $dt->format('Y-m-d');
}

// ✅ Compute duration in WHOLE MINUTES (ignore seconds fully) ✅ FORCE minute precision BEFORE computing 
$start_time = substr($start_time, 0, 5) . ':00';
$end_time   = substr($end_time, 0, 5) . ':00';

$startDT = new DateTime("$segment_start_date $start_time");
$endDT   = new DateTime("$end_date $end_time");

$diffSeconds = $endDT->getTimestamp() - $startDT->getTimestamp();
if ($diffSeconds < 0) $diffSeconds += 86400;

// floor to minute
$diffMinutes = intdiv($diffSeconds, 60);
$total_duration = sprintf("%02d:%02d:00", intdiv($diffMinutes, 60), $diffMinutes % 60);

// End Shift always 0
if ($isEndShift) {
    $total_duration = "00:00:00";
}

$upd = $conn->prepare("UPDATE task_logs SET end_time = ?, end_date = ?, total_duration = ? WHERE id = ?");
if (!$upd) {
    echo json_encode(["status" => "error", "message" => "Prepare failed (UPDATE): " . $conn->error]);
    exit;
}

$upd->bind_param("sssi", $end_time, $end_date, $total_duration, $id);

if ($upd->execute()) {
    echo json_encode([
        "status" => "success",
        "server_end_time" => $end_time,
        "server_end_date" => $end_date,
        "server_total_duration" => $total_duration,
        "is_end_shift" => $isEndShift,
        "affected_rows" => $upd->affected_rows
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Execute failed: " . $upd->error]);
}

$upd->close();
$conn->close();
