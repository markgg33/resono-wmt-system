<?php
require "../connection_db.php";
header("Content-Type: application/json");

if (!isset($_GET['id'])) {
    echo json_encode(["status" => "error", "message" => "Missing request ID"]);
    exit;
}

$requestId = intval($_GET['id']);

// ======================================================
// 1️⃣ GET OT REQUEST DETAILS
// ======================================================
$stmt = $conn->prepare("
    SELECT user_id, tracker_date, hours AS requested_hours
    FROM ot_requests
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param("i", $requestId);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "OT request not found"]);
    exit;
}

$ot = $res->fetch_assoc();
$userId   = $ot['user_id'];
$theDate  = $ot['tracker_date'];
$requestedHours = $ot['requested_hours']; // "hh:mm"
$stmt->close();

// ======================================================
// 2️⃣ LOAD LOGS (task_logs + archive)
// ======================================================
$sql = "
SELECT id, user_id, work_mode_id, task_description_id, date, start_time, end_time, total_duration, remarks
FROM task_logs
WHERE user_id = ? AND date = ?
UNION ALL
SELECT original_id AS id, user_id, work_mode_id, task_description_id, date, start_time, end_time, total_duration, remarks
FROM task_logs_archive
WHERE user_id = ? AND date = ?
ORDER BY date ASC, start_time ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("isis", $userId, $theDate, $userId, $theDate);
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (!$logs) {
    echo json_encode([
        "status" => "success",
        "request_id" => $requestId,
        "tracker_date" => $theDate,
        "message" => "No logs found for this date",
        "computed_ot" => "00:00"
    ]);
    exit;
}

// ======================================================
// 3️⃣ LOAD CACHED LOOKUP HELPERS
// ======================================================
function getDescription($conn, $id)
{
    static $cache = [];
    if (isset($cache[$id])) return $cache[$id];

    $q = $conn->prepare("SELECT description FROM task_descriptions WHERE id = ?");
    $q->bind_param("i", $id);
    $q->execute();
    $r = $q->get_result()->fetch_assoc();
    $cache[$id] = strtolower($r['description'] ?? "");
    return $cache[$id];
}

function getWorkMode($conn, $id)
{
    static $cache = [];
    if (isset($cache[$id])) return $cache[$id];

    $q = $conn->prepare("SELECT name FROM work_modes WHERE id = ?");
    $q->bind_param("i", $id);
    $q->execute();
    $r = $q->get_result()->fetch_assoc();
    $cache[$id] = strtolower($r['name'] ?? "");
    return $cache[$id];
}

// Preload PRODUCTION work modes
$productionModes = [];
$q = $conn->query("SELECT id, name FROM work_modes");
while ($wm = $q->fetch_assoc()) {
    $name = strtolower($wm['name']);
    if (
        strpos($name, "offphone") === false &&
        strpos($name, "training") === false &&
        strpos($name, "break") === false &&
        strpos($name, "personal") === false &&
        strpos($name, "technical_error") === false
    ) {
        $productionModes[] = intval($wm['id']);
    }
}

// ======================================================
// 4️⃣ CATEGORY BUCKETS + BREAK ALLOCATION
// ======================================================
$dur = [
    "production" => 0,
    "offphone" => 0,
    "training" => 0,
    "resono" => 0,
    "paid_break" => 0,
    "unpaid_break" => 0,
    "personal_time" => 0,
    "system_down" => 0
];

$usedPaid = 0;      // max 1800 sec
$usedUnpaid = 0;    // max 3600 sec

function allocateBreak($duration, &$dur, &$usedPaid, &$usedUnpaid)
{
    // Paid 30 minutes first
    $remaining = $duration;

    $paidAllowance = max(0, 1800 - $usedPaid);
    if ($paidAllowance > 0) {
        $add = min($remaining, $paidAllowance);
        $dur['paid_break'] += $add;
        $usedPaid += $add;
        $remaining -= $add;
    }

    // Unpaid next 60 minutes
    if ($remaining > 0) {
        $unpaidAllowance = max(0, 3600 - $usedUnpaid);
        if ($unpaidAllowance > 0) {
            $add = min($remaining, $unpaidAllowance);
            $dur['unpaid_break'] += $add;
            $usedUnpaid += $add;
            $remaining -= $add;
        }
    }

    // Excess → personal time
    if ($remaining > 0) {
        $dur['personal_time'] += $remaining;
    }
}

// ======================================================
// 5️⃣ PROCESS EACH LOG
// ======================================================
foreach ($logs as $log) {
    if (!$log["end_time"]) continue;

    $duration = strtotime($log["end_time"]) - strtotime($log["start_time"]);
    if ($duration <= 0) continue;

    $desc = getDescription($conn, $log['task_description_id']);
    $wmName = getWorkMode($conn, $log['work_mode_id']);

    if (strpos($desc, "resono") !== false) {
        $dur["resono"] += $duration;
    } elseif (strpos($desc, "training") !== false) {
        $dur["training"] += $duration;
    } elseif (strpos($desc, "offphone") !== false) {
        $dur["offphone"] += $duration;
    } elseif (strpos($desc, "away - break") !== false) {
        allocateBreak($duration, $dur, $usedPaid, $usedUnpaid);
    } elseif (
        strpos($desc, "system down") !== false ||
        strpos($desc, "system issue") !== false ||
        $wmName === "technical_error"
    ) {
        $dur["system_down"] += $duration;
    } else {
        if (in_array($log["work_mode_id"], $productionModes)) {
            $dur["production"] += $duration;
        }
    }
}

// ======================================================
// 6️⃣ FINAL COMPUTATION FOR OT
// OT = total_work – 9 hours
// ======================================================
$totalWorkSeconds =
    $dur["production"] +
    $dur["offphone"] +
    $dur["training"] +
    $dur["resono"] +
    $dur["paid_break"] +
    $dur["unpaid_break"];

$regularShiftSeconds = 9 * 3600;

$computedOTSeconds = max(0, $totalWorkSeconds - $regularShiftSeconds);

// Format HH:MM
function fmt($sec)
{
    $h = floor($sec / 3600);
    $m = floor(($sec % 3600) / 60);
    return sprintf("%02d:%02d", $h, $m);
}

// ======================================================
// 7️⃣ RETURN JSON
// ======================================================
echo json_encode([
    "status" => "success",
    "request_id" => $requestId,
    "tracker_date" => $theDate,
    "requested_ot" => $requestedHours,

    // Raw breakdown
    "raw_minutes" => $dur,

    "total_work" => fmt($totalWorkSeconds),
    "regular_hours" => "09:00",

    // Final computed OT
    "computed_ot" => fmt($computedOTSeconds)
]);
exit;
