<?php
//WORKING VERSION
/*
session_start();
require '../connection_db.php';
header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['role'] ?? null;

if (!$userId || !in_array($userRole, ['admin', 'executive', 'hr', 'supervisor'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

//$data = json_decode(file_get_contents("php://input"), true);
$requestId = $_POST['request_id'] ?? null;
$decision  = $_POST['decision'] ?? null; // "Approved" or "Rejected"

if (!$requestId || !in_array($decision, ['Approved', 'Rejected'])) {
    echo json_encode(["status" => "error", "message" => "Invalid input"]);
    exit;
}

// Get amendment details
$stmt = $conn->prepare("SELECT * FROM dtr_amendments WHERE id = ?");
$stmt->bind_param("i", $requestId);
$stmt->execute();
$amendment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$amendment) {
    echo json_encode(["status" => "error", "message" => "Request not found"]);
    exit;
}

if ($decision === "Approved") {
    $logId    = $amendment['log_id'];
    $field    = $amendment['field'];
    $newValue = $amendment['new_value'];

    if ($field === "date") {
        // new_value format: YYYY-MM-DD|start|end
        list($newDate, $newStart, $newEnd) = explode("|", $newValue . "||");

        // Update individually if provided
        $sqlParts = [];
        $params   = [];
        $types    = "";

        if (!empty($newDate)) {
            $sqlParts[] = "date = ?";
            $params[] = $newDate;
            $types .= "s";
        }
        if (!empty($newStart)) {
            $sqlParts[] = "start_time = ?";
            $params[] = $newStart;
            $types .= "s";
        }
        if (!empty($newEnd)) {
            $sqlParts[] = "end_time = ?";
            $params[] = $newEnd;
            $types .= "s";
        }

        if (!empty($sqlParts)) {
            $sql = "UPDATE task_logs SET " . implode(", ", $sqlParts) . " WHERE id = ?";
            $params[] = $logId;
            $types .= "i";

            $upd = $conn->prepare($sql);
            $upd->bind_param($types, ...$params);
            $upd->execute();
            $upd->close();

            // Recalculate duration if both start & end available
            if (!empty($newStart) && !empty($newEnd)) {
                $dur = $conn->prepare("UPDATE task_logs SET total_duration = TIMEDIFF(end_time, start_time) WHERE id = ?");
                $dur->bind_param("i", $logId);
                $dur->execute();
                $dur->close();
            }
        }
    } elseif ($field === "start_time" || $field === "end_time") {
        // Simple time updates
        $update = $conn->prepare("UPDATE task_logs SET {$field} = ? WHERE id = ?");
        $update->bind_param("si", $newValue, $logId);
        $update->execute();
        $update->close();

        // Recalculate duration if both times exist
        $dur = $conn->prepare("UPDATE task_logs 
                               SET total_duration = TIMEDIFF(end_time, start_time) 
                               WHERE id = ?");
        $dur->bind_param("i", $logId);
        $dur->execute();
        $dur->close();

        // 🔹 If end_time was updated, shift next task's start_time
        if ($field === 'end_time') {
            $getTaskA = $conn->prepare("SELECT user_id, end_time FROM task_logs WHERE id = ?");
            $getTaskA->bind_param("i", $logId);
            $getTaskA->execute();
            $taskA = $getTaskA->get_result()->fetch_assoc();
            $getTaskA->close();

            if ($taskA) {
                $userIdTask = $taskA['user_id'];
                $newEndTime = $taskA['end_time'];

                $getTaskB = $conn->prepare("SELECT id FROM task_logs WHERE user_id = ? AND id > ? ORDER BY id ASC LIMIT 1");
                $getTaskB->bind_param("ii", $userIdTask, $logId);
                $getTaskB->execute();
                $taskB = $getTaskB->get_result()->fetch_assoc();
                $getTaskB->close();

                if ($taskB) {
                    $taskBId = $taskB['id'];

                    $updB = $conn->prepare("UPDATE task_logs SET start_time = ? WHERE id = ?");
                    $updB->bind_param("si", $newEndTime, $taskBId);
                    $updB->execute();
                    $updB->close();

                    $durB = $conn->prepare("UPDATE task_logs 
                                            SET total_duration = TIMEDIFF(end_time, start_time) 
                                            WHERE id = ?");
                    $durB->bind_param("i", $taskBId);
                    $durB->execute();
                    $durB->close();
                }
            }
        }
    }
}

// ✅ Update amendment status
$stmt = $conn->prepare("UPDATE dtr_amendments 
                        SET status = ?, processed_by = ?, processed_at = NOW() 
                        WHERE id = ?");
$stmt->bind_param("sii", $decision, $userId, $requestId);
$stmt->execute();
$stmt->close();

echo json_encode(["status" => "success", "message" => "Request $decision"]);*/

//NEW VERSION (SAFE VERSION)
/*
session_start();
require '../connection_db.php';
header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['role'] ?? null;

if (!$userId || !in_array($userRole, ['admin', 'executive', 'hr', 'supervisor'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$requestId = $_POST['request_id'] ?? null;
$decision  = $_POST['decision'] ?? null; // "Approved" or "Rejected"

if (!$requestId || !in_array($decision, ['Approved', 'Rejected'])) {
    echo json_encode(["status" => "error", "message" => "Invalid input"]);
    exit;
}

// Get amendment details
$stmt = $conn->prepare("SELECT * FROM dtr_amendments WHERE id = ?");
$stmt->bind_param("i", $requestId);
$stmt->execute();
$amendment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$amendment) {
    echo json_encode(["status" => "error", "message" => "Request not found"]);
    exit;
}

// ✅ Update status first
$updateStatus = $conn->prepare("UPDATE dtr_amendments SET status = ?, processed_by = ?, processed_at = NOW() WHERE id = ?");
$updateStatus->bind_param("sii", $decision, $userId, $requestId);
$updateStatus->execute();
$updateStatus->close();

if ($decision === "Approved") {
    $logId    = $amendment['log_id'];
    $field    = $amendment['field'];
    $newValue = $amendment['new_value'];

    if ($field === "date") {
        // new_value format: YYYY-MM-DD|start|end
        list($newDate, $newStart, $newEnd) = explode("|", $newValue . "||");

        $sqlParts = [];
        $params   = [];
        $types    = "";

        if (!empty($newDate)) {
            $sqlParts[] = "date = ?";
            $params[] = $newDate;
            $types .= "s";
        }
        if (!empty($newStart)) {
            $sqlParts[] = "start_time = ?";
            $params[] = $newStart;
            $types .= "s";
        }
        if (!empty($newEnd)) {
            $sqlParts[] = "end_time = ?";
            $params[] = $newEnd;
            $types .= "s";
        }

        if (!empty($sqlParts)) {
            $sql = "UPDATE task_logs SET " . implode(", ", $sqlParts) . " WHERE id = ?";
            $params[] = $logId;
            $types .= "i";

            $upd = $conn->prepare($sql);
            $upd->bind_param($types, ...$params);
            $upd->execute();
            $upd->close();

            if (!empty($newStart) && !empty($newEnd)) {
                $dur = $conn->prepare("UPDATE task_logs SET total_duration = TIMEDIFF(end_time, start_time) WHERE id = ?");
                $dur->bind_param("i", $logId);
                $dur->execute();
                $dur->close();
            }
        }
    } elseif ($field === "start_time" || $field === "end_time") {
        $sql = "UPDATE task_logs SET {$field} = ?, total_duration = TIMEDIFF(end_time, start_time) WHERE id = ?";
        $upd = $conn->prepare($sql);
        $upd->bind_param("si", $newValue, $logId);
        $upd->execute();
        $upd->close();
    }
}

echo json_encode(["status" => "success", "message" => "Request processed successfully."]);*/

// process_amendment.php WORKING VERSION
/*
session_start();
require '../connection_db.php';
header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['role'] ?? null;

if (!$userId || !in_array($userRole, ['admin', 'executive', 'hr', 'supervisor'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$requestId = $_POST['request_id'] ?? null;
$decision  = $_POST['decision'] ?? null;

if (!$requestId || !in_array($decision, ['Approved', 'Rejected'])) {
    echo json_encode(["status" => "error", "message" => "Invalid input"]);
    exit;
}

// Fetch amendment details
$stmt = $conn->prepare("SELECT * FROM dtr_amendments WHERE id = ?");
$stmt->bind_param("i", $requestId);
$stmt->execute();
$amendment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$amendment) {
    echo json_encode(["status" => "error", "message" => "Request not found"]);
    exit;
}

// ✅ Update amendment request status
$updateStatus = $conn->prepare("UPDATE dtr_amendments SET status = ?, processed_by = ?, processed_at = NOW() WHERE id = ?");
$updateStatus->bind_param("sii", $decision, $userId, $requestId);
$updateStatus->execute();
$updateStatus->close();

if ($decision !== "Approved") {
    echo json_encode(["status" => "success", "message" => "Request rejected."]);
    exit;
}

// ✅ PROCESS APPROVED AMENDMENTS
$logId    = $amendment['log_id'];
$field    = $amendment['field'];
$newValue = trim($amendment['new_value']);
$oldValue = trim($amendment['old_value']);

if ($field === "date") {
    // Format: YYYY-MM-DD|HH:MM(optional)
    list($newDate, $newStart, $newEnd) = explode("|", $newValue . "||");

    $sqlParts = [];
    $params   = [];
    $types    = "";

    if (!empty($newDate)) {
        $sqlParts[] = "date = ?";
        $params[] = $newDate;
        $types .= "s";
    }
    if (!empty($newStart)) {
        $sqlParts[] = "start_time = ?";
        $params[] = $newStart;
        $types .= "s";
    }
    if (!empty($newEnd)) {
        $sqlParts[] = "end_time = ?";
        $params[] = $newEnd;
        $types .= "s";
    }

    if (!empty($sqlParts)) {
        $sql = "UPDATE task_logs SET " . implode(", ", $sqlParts) . " WHERE id = ?";
        $params[] = $logId;
        $types .= "i";

        $upd = $conn->prepare($sql);
        $upd->bind_param($types, ...$params);
        $upd->execute();
        $upd->close();

        // update duration if both times exist
        if (!empty($newStart) && !empty($newEnd)) {
            $dur = $conn->prepare("UPDATE task_logs SET total_duration = TIMEDIFF(end_time, start_time) WHERE id = ?");
            $dur->bind_param("i", $logId);
            $dur->execute();
            $dur->close();
        }
    }
} elseif ($field === "start_time") {
    // Handle start_time-only or with date
    $parts = explode(" ", $newValue);
    $newDate = null;
    $newStart = null;

    if (count($parts) == 2) {
        [$newDate, $newStart] = $parts;
    } else {
        $newStart = $parts[0]; // Only time provided
    }

    // ✅ Update current log's start_time (+ date if given)
    $sqlParts = ["start_time = ?"];
    $params = [$newStart];
    $types = "s";

    if (!empty($newDate)) {
        $sqlParts[] = "date = ?";
        $params[] = $newDate;
        $types .= "s";
    }

    $sql = "UPDATE task_logs SET " . implode(", ", $sqlParts) . ", total_duration = TIMEDIFF(end_time, start_time) WHERE id = ?";
    $params[] = $logId;
    $types .= "i";

    $upd = $conn->prepare($sql);
    $upd->bind_param($types, ...$params);
    $upd->execute();
    $upd->close();

    // ✅ Also adjust the previous task's end_time to match this new start_time
    $prev = $conn->prepare("
        SELECT id FROM task_logs 
        WHERE user_id = (SELECT user_id FROM task_logs WHERE id = ?) 
        AND date = (SELECT date FROM task_logs WHERE id = ?)
        AND id < ? 
        ORDER BY id DESC LIMIT 1
    ");
    $prev->bind_param("iii", $logId, $logId, $logId);
    $prev->execute();
    $prevRes = $prev->get_result()->fetch_assoc();
    $prev->close();

    if ($prevRes) {
        $prevId = $prevRes['id'];
        $adj = $conn->prepare("UPDATE task_logs SET end_time = ?, total_duration = TIMEDIFF(end_time, start_time) WHERE id = ?");
        $adj->bind_param("si", $newStart, $prevId);
        $adj->execute();
        $adj->close();
    }
} elseif ($field === "end_time") {
    // ✅ Normal end_time update
    $sql = "UPDATE task_logs SET end_time = ?, total_duration = TIMEDIFF(end_time, start_time) WHERE id = ?";
    $upd = $conn->prepare($sql);
    $upd->bind_param("si", $newValue, $logId);
    $upd->execute();
    $upd->close();
}

echo json_encode(["status" => "success", "message" => "Amendment processed successfully."]);
*/

/*
//WITH ARCHIVE AWARE LOGIC
session_start();
require '../connection_db.php';
header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['role'] ?? null;

if (!$userId || !in_array($userRole, ['admin', 'executive', 'hr', 'supervisor'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$requestId = $_POST['request_id'] ?? null;
$decision  = $_POST['decision'] ?? null;

if (!$requestId || !in_array($decision, ['Approved', 'Rejected'])) {
    echo json_encode(["status" => "error", "message" => "Invalid input"]);
    exit;
}

// Fetch amendment details
$stmt = $conn->prepare("SELECT * FROM dtr_amendments WHERE id = ?");
$stmt->bind_param("i", $requestId);
$stmt->execute();
$amendment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$amendment) {
    echo json_encode(["status" => "error", "message" => "Request not found"]);
    exit;
}

// ✅ CRITICAL: Supervisors cannot approve/reject their own requests
if ($userRole === "supervisor" && (int)$amendment['user_id'] === (int)$userId) {
    echo json_encode(["status" => "error", "message" => "You cannot approve or reject your own request."]);
    exit;
}

// Update amendment request status
$updateStatus = $conn->prepare("UPDATE dtr_amendments SET status = ?, processed_by = ?, processed_at = NOW() WHERE id = ?");
$updateStatus->bind_param("sii", $decision, $userId, $requestId);
$updateStatus->execute();
$updateStatus->close();

if ($decision !== "Approved") {
    echo json_encode(["status" => "success", "message" => "Request rejected."]);
    exit;
}

// PROCESS APPROVED AMENDMENTS
$logId    = $amendment['log_id'];
$field    = $amendment['field'];
$newValue = trim($amendment['new_value']);

// 🟢 Determine the correct table (task_logs or task_logs_archive)
$tables_to_check = ['task_logs', 'task_logs_archive'];
$target_table = null;

foreach ($tables_to_check as $table) {
    $check = $conn->prepare("SELECT id FROM {$table} WHERE id = ?");
    $check->bind_param("i", $logId);
    $check->execute();
    $res = $check->get_result();
    if ($res->num_rows > 0) {
        $target_table = $table;
        $check->close();
        break;
    }
    $check->close();
}

if (!$target_table) {
    echo json_encode(["status" => "error", "message" => "Original log not found in either table."]);
    exit;
}

if ($field === "date") {
    list($newDate, $newStart, $newEnd) = explode("|", $newValue . "||");

    $sqlParts = [];
    $params   = [];
    $types    = "";

    if (!empty($newDate)) {
        $sqlParts[] = "date = ?";
        $params[] = $newDate;
        $types .= "s";
    }
    if (!empty($newStart)) {
        $sqlParts[] = "start_time = ?";
        $params[] = $newStart;
        $types .= "s";
    }
    if (!empty($newEnd)) {
        $sqlParts[] = "end_time = ?";
        $params[] = $newEnd;
        $types .= "s";
    }

    if (!empty($sqlParts)) {
        $sql = "UPDATE {$target_table} SET " . implode(", ", $sqlParts) . " WHERE id = ?";
        $params[] = $logId;
        $types .= "i";

        $upd = $conn->prepare($sql);
        $upd->bind_param($types, ...$params);
        $upd->execute();
        $upd->close();

        // Update duration if both times exist
        if (!empty($newStart) && !empty($newEnd)) {
            $dur = $conn->prepare("UPDATE {$target_table} SET total_duration = TIMEDIFF(end_time, start_time) WHERE id = ?");
            $dur->bind_param("i", $logId);
            $dur->execute();
            $dur->close();
        }
    }
} elseif ($field === "start_time") {
    $parts = explode(" ", $newValue);
    $newDate = null;
    $newStart = null;

    if (count($parts) == 2) {
        [$newDate, $newStart] = $parts;
    } else {
        $newStart = $parts[0];
    }

    $sqlParts = ["start_time = ?"];
    $params = [$newStart];
    $types = "s";

    if (!empty($newDate)) {
        $sqlParts[] = "date = ?";
        $params[] = $newDate;
        $types .= "s";
    }

    $sql = "UPDATE {$target_table} SET " . implode(", ", $sqlParts) . ", total_duration = TIMEDIFF(end_time, start_time) WHERE id = ?";
    $params[] = $logId;
    $types .= "i";

    $upd = $conn->prepare($sql);
    $upd->bind_param($types, ...$params);
    $upd->execute();
    $upd->close();

    // Adjust previous task's end_time in the same table as the target log
    $prev = $conn->prepare("
    SELECT id FROM {$target_table} 
    WHERE user_id = (SELECT user_id FROM {$target_table} WHERE id = ?) 
      AND date = (SELECT date FROM {$target_table} WHERE id = ?)
      AND id < ? 
    ORDER BY id DESC LIMIT 1
");
    $prev->bind_param("iii", $logId, $logId, $logId);
    $prev->execute();
    $prevRes = $prev->get_result()->fetch_assoc();
    $prev->close();

    if ($prevRes) {
        $prevId = $prevRes['id'];
        $adj = $conn->prepare("
        UPDATE {$target_table} 
        SET end_time = ?, total_duration = TIMEDIFF(end_time, start_time) 
        WHERE id = ?
    ");
        $adj->bind_param("si", $newStart, $prevId);
        $adj->execute();
        $adj->close();
    }
} elseif ($field === "end_time") {
    $sql = "UPDATE {$target_table} SET end_time = ?, total_duration = TIMEDIFF(end_time, start_time) WHERE id = ?";
    $upd = $conn->prepare($sql);
    $upd->bind_param("si", $newValue, $logId);
    $upd->execute();
    $upd->close();
}

echo json_encode(["status" => "success", "message" => "Amendment processed successfully."]);
*/

//WITH ARCHIVE AWARE + MIDNIGHT-CROSSING SAFE LOGIC
session_start();
require '../connection_db.php';
header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['role'] ?? null;

// After recompute_duration_overnight()
function getDescription(mysqli $conn, int $descId): string
{
    static $descCache = [];
    if (isset($descCache[$descId])) return $descCache[$descId];

    $stmt = $conn->prepare("SELECT description FROM task_descriptions WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $descId);
    $stmt->execute();
    $res = $stmt->get_result();
    $desc = $res->fetch_assoc()['description'] ?? '';
    $stmt->close();

    $descCache[$descId] = $desc;
    return $desc;
}

/**
 * Normalize time strings to "HH:MM:SS" (minute precision preserved).
 * Returns null if empty/invalid.
 */
function normalizeTime(?string $t): ?string
{
    if ($t === null) return null;
    $t = trim($t);
    if ($t === '' || $t === '--') return null;

    // HH:MM -> HH:MM:00
    if (preg_match('/^\d{2}:\d{2}$/', $t)) return $t . ':00';
    // HH:MM:SS -> keep
    if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $t)) return $t;

    return null;
}

if (!$userId || !in_array($userRole, ['admin', 'executive', 'hr', 'supervisor'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$requestId = $_POST['request_id'] ?? null;
$decision  = $_POST['decision'] ?? null;

if (!$requestId || !in_array($decision, ['Approved', 'Rejected'])) {
    echo json_encode(["status" => "error", "message" => "Invalid input"]);
    exit;
}

// Fetch amendment details
$stmt = $conn->prepare("SELECT * FROM dtr_amendments WHERE id = ?");
$stmt->bind_param("i", $requestId);
$stmt->execute();
$amendment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$amendment) {
    echo json_encode(["status" => "error", "message" => "Request not found"]);
    exit;
}

// ✅ CRITICAL: Supervisors cannot approve/reject their own requests
if ($userRole === "supervisor" && (int)$amendment['user_id'] === (int)$userId) {
    echo json_encode(["status" => "error", "message" => "You cannot approve or reject your own request."]);
    exit;
}

// Update amendment request status
$updateStatus = $conn->prepare("UPDATE dtr_amendments SET status = ?, processed_by = ?, processed_at = NOW() WHERE id = ?");
$updateStatus->bind_param("sii", $decision, $userId, $requestId);
$updateStatus->execute();
$updateStatus->close();

if ($decision !== "Approved") {
    echo json_encode(["status" => "success", "message" => "Request rejected."]);
    exit;
}

// PROCESS APPROVED AMENDMENTS
$logId    = (int)($amendment['log_id'] ?? 0);
$field    = $amendment['field'] ?? '';
$newValue = trim((string)($amendment['new_value'] ?? ''));

// 🟢 Determine the correct table (task_logs or task_logs_archive)
$tables_to_check = ['task_logs', 'task_logs_archive'];
$target_table = null;

foreach ($tables_to_check as $table) {
    $check = $conn->prepare("SELECT id FROM {$table} WHERE id = ? LIMIT 1");
    $check->bind_param("i", $logId);
    $check->execute();
    $res = $check->get_result();
    if ($res && $res->num_rows > 0) {
        $target_table = $table;
        $check->close();
        break;
    }
    $check->close();
}

if (!$target_table) {
    echo json_encode(["status" => "error", "message" => "Original log not found in either table."]);
    exit;
}

/**
 * ✅ Overnight-safe duration recompute (no end_date needed)
 * - if end_time < start_time => treat end as next day
 */
function recompute_duration_overnight(mysqli $conn, string $table, int $id): void
{
    $sql = "
      UPDATE {$table}
      SET total_duration = SEC_TO_TIME(
        TIMESTAMPDIFF(
          SECOND,
          CONCAT(date, ' ', start_time),
          CONCAT(
            DATE_ADD(date, INTERVAL (end_time < start_time) DAY),
            ' ',
            end_time
          )
        )
      )
      WHERE id = ?
    ";
    $st = $conn->prepare($sql);
    $st->bind_param("i", $id);
    $st->execute();
    $st->close();
}

/**
 * ✅ Keep shift fields consistent after an amendment:
 * - work_date: based on call_time + start_time vs "date" (calendar date)
 * - end_date: based on midnight-crossing between start_time and end_time (calendar logic)
 *
 * Notes:
 * - We treat `date` as the segment's start calendar day (matches `update_task_log.php` logic).
 * - If call_time exists and start_time < call_time => segment belongs to previous work_date (night shift).
 */
function recompute_work_date_and_end_date(mysqli $conn, string $table, int $id): void
{
    $stmt = $conn->prepare("
        SELECT date, work_date, call_time, start_time, end_time, task_description_id
        FROM {$table}
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) return;

    $calendarDate = trim((string)($row['date'] ?? ''));
    if ($calendarDate === '') return;

    $startTime = normalizeTime($row['start_time'] ?? null);
    $endTime   = normalizeTime($row['end_time'] ?? null);
    $callTime  = normalizeTime($row['call_time'] ?? null);

    // 1) Recompute work_date (shift day)
    $workDate = $calendarDate;
    if ($callTime && $startTime && $startTime < $callTime) {
        $dt = new DateTime($calendarDate);
        $dt->modify('-1 day');
        $workDate = $dt->format('Y-m-d');
    }

    // 2) Recompute end_date (calendar day end)
    /*$endDate = null;
    if ($endTime && $startTime) {
        $endDate = $calendarDate;
        if ($endTime < $startTime) {
            $dt = new DateTime($calendarDate);
            $dt->modify('+1 day');
            $endDate = $dt->format('Y-m-d');
        }
    }*/

    $endDate = $calendarDate;

    if (!empty($startTime) && !empty($endTime)) {

        $startTs = strtotime($startTime);
        $endTs   = strtotime($endTime);

        if ($startTs !== false && $endTs !== false && $endTs < $startTs) {

            $dt = new DateTime($calendarDate);
            $dt->modify('+1 day');
            $endDate = $dt->format('Y-m-d');

            error_log(
                "[OVERNIGHT DETECTED] Log ID {$id} | Date={$calendarDate} | Start={$startTime} | End={$endTime}"
            );
        }
    }

    // 3) Persist work_date + end_date (and normalize times if needed)
    $upd = $conn->prepare("
        UPDATE {$table}
        SET work_date = ?, end_date = ?
        WHERE id = ?
        LIMIT 1
    ");
    $upd->bind_param("ssi", $workDate, $endDate, $id);
    $upd->execute();
    $upd->close();
}

if ($field === "date") {
    // Payload format: newDate|newStart|newEnd
    list($newDate, $newStart, $newEnd) = explode("|", $newValue . "||");

    $newDate  = trim((string)$newDate);
    $newStart = normalizeTime($newStart);
    $newEnd   = normalizeTime($newEnd);

    $sqlParts = [];
    $params   = [];
    $types    = "";

    if (!empty($newDate)) {
        $sqlParts[] = "date = ?";
        $params[] = $newDate;
        $types .= "s";
    }
    if (!empty($newStart)) {
        $sqlParts[] = "start_time = ?";
        $params[] = $newStart;
        $types .= "s";
    }
    if (!empty($newEnd)) {
        $sqlParts[] = "end_time = ?";
        $params[] = $newEnd;
        $types .= "s";
    }

    if (!empty($sqlParts)) {
        $sql = "UPDATE {$target_table} SET " . implode(", ", $sqlParts) . " WHERE id = ?";
        $params[] = $logId;
        $types .= "i";

        $upd = $conn->prepare($sql);
        $upd->bind_param($types, ...$params);
        $upd->execute();
        $upd->close();

        // ✅ Always resync work_date/end_date after date/time amendments
        recompute_work_date_and_end_date($conn, $target_table, $logId);
        // ✅ Recompute duration (overnight-safe); will only be meaningful when both times exist
        recompute_duration_overnight($conn, $target_table, $logId);
    }
} elseif ($field === "start_time") {
    // newValue may be "YYYY-MM-DD HH:MM:SS" or just "HH:MM:SS"
    $parts = preg_split('/\s+/', $newValue);
    $newDate = null;
    $newStart = null;

    if (count($parts) >= 2) {
        $newDate  = trim($parts[0]);
        $newStart = normalizeTime($parts[1]);
    } else {
        $newStart = normalizeTime($parts[0] ?? '');
    }

    if (empty($newStart)) {
        echo json_encode(["status" => "error", "message" => "Invalid start_time value."]);
        exit;
    }

    // Update start_time (+ optional calendar date)
    $sqlParts = ["start_time = ?"];
    $params = [$newStart];
    $types = "s";

    /*if (!empty($newDate)) {
        $sqlParts[] = "date = ?";
        $params[] = $newDate;
        $types .= "s";
    }*/

    // Only allow date updates if this is actually a date amendment
    if ($field === 'date' && !empty($newDate)) {
        $sqlParts[] = "date = ?";
        $params[] = $newDate;
        $types .= "s";
    }

    $sql = "UPDATE {$target_table} SET " . implode(", ", $sqlParts) . " WHERE id = ?";
    $params[] = $logId;
    $types .= "i";

    $upd = $conn->prepare($sql);
    $upd->bind_param($types, ...$params);
    $upd->execute();
    $upd->close();

    // ✅ Recompute duration of the edited log (overnight-safe)
    /*recompute_work_date_and_end_date($conn, $target_table, $logId);
    recompute_duration_overnight($conn, $target_table, $logId);*/

    /**
     * ✅ SHIFT-AWARE previous-row adjustment:
     * - Use work_date (shift day) instead of date (calendar day)
     * - Search across BOTH task_logs and task_logs_archive
     * - Pick the latest row BEFORE the amended row within the same shift timeline
     */
    $ctx = $conn->prepare("SELECT user_id, work_date, date FROM {$target_table} WHERE id = ? LIMIT 1");
    $ctx->bind_param("i", $logId);
    $ctx->execute();
    $target = $ctx->get_result()->fetch_assoc();
    $ctx->close();

    if ($target) {
        $targetUser = (int)$target['user_id'];
        $targetWorkDate = $target['work_date'] ?: $target['date']; // fallback
        $targetCalendarDate = $newDate ?: ($target['date'] ?? null);

        if (!empty($targetWorkDate) && !empty($targetCalendarDate)) {

            $prevSql = "
              SELECT id, 'task_logs' AS src, date, start_time
              FROM task_logs
              WHERE user_id = ? AND work_date = ? AND id <> ?
                AND (date < ? OR (date = ? AND start_time < ?))

              UNION ALL

              SELECT id, 'task_logs_archive' AS src, date, start_time
              FROM task_logs_archive
              WHERE user_id = ? AND work_date = ? AND id <> ?
                AND (date < ? OR (date = ? AND start_time < ?))

              ORDER BY date DESC, start_time DESC
              LIMIT 1
            ";

            $prev = $conn->prepare($prevSql);
            $prev->bind_param(
                "isssssisssss",
                $targetUser,
                $targetWorkDate,
                $logId,
                $targetCalendarDate,
                $targetCalendarDate,
                $newStart,
                $targetUser,
                $targetWorkDate,
                $logId,
                $targetCalendarDate,
                $targetCalendarDate,
                $newStart
            );
            $prev->execute();
            $prevRow = $prev->get_result()->fetch_assoc();
            $prev->close();

            if ($prevRow) {
                $prevId = (int)$prevRow['id'];
                $prevTable = $prevRow['src']; // task_logs or task_logs_archive

                // Set previous end_time to the amended start_time
                $adj = $conn->prepare("UPDATE {$prevTable} SET end_time = ? WHERE id = ?");
                $adj->bind_param("si", $newStart, $prevId);
                $adj->execute();
                $adj->close();

                // Recompute everything for previous row
                recompute_work_date_and_end_date(
                    $conn,
                    $prevTable,
                    $prevId
                );

                recompute_duration_overnight(
                    $conn,
                    $prevTable,
                    $prevId
                );
            }
        }
    }

    // --- SPECIAL HANDLING FOR END SHIFT ---
    $descStmt = $conn->prepare("SELECT task_description_id FROM {$target_table} WHERE id = ? LIMIT 1");
    $descStmt->bind_param("i", $logId);
    $descStmt->execute();
    $descIdRow = $descStmt->get_result()->fetch_assoc();
    $descStmt->close();

    $isEndShift = false;
    if ($descIdRow && !empty($descIdRow['task_description_id'])) {
        $descText = strtolower(getDescription($conn, $descIdRow['task_description_id']));
        $isEndShift = strpos($descText, 'end shift') !== false;
    }

    if ($isEndShift) {
        // Force end_time = start_time for End Shift
        $setEnd = $newStart;
        $updEnd = $conn->prepare("UPDATE {$target_table} SET end_time = ? WHERE id = ?");
        $updEnd->bind_param("si", $setEnd, $logId);
        $updEnd->execute();
        $updEnd->close();

        // End shift should always have end_date aligned too
        recompute_work_date_and_end_date($conn, $target_table, $logId);
        recompute_duration_overnight($conn, $target_table, $logId);
    } else {
        // Regular log: recompute duration once
        recompute_work_date_and_end_date($conn, $target_table, $logId);
        recompute_duration_overnight($conn, $target_table, $logId);
    }
} elseif ($field === "end_time") {
    $newEnd = normalizeTime($newValue);
    if (empty($newEnd)) {
        echo json_encode(["status" => "error", "message" => "Invalid end_time value."]);
        exit;
    }

    $sql = "UPDATE {$target_table} SET end_time = ? WHERE id = ?";
    $upd = $conn->prepare($sql);
    $upd->bind_param("si", $newEnd, $logId);
    $upd->execute();
    $upd->close();

    // ✅ Recompute duration overnight-safe
    recompute_work_date_and_end_date($conn, $target_table, $logId);
    recompute_duration_overnight($conn, $target_table, $logId);
}

echo json_encode(["status" => "success", "message" => "Amendment processed successfully."]);
