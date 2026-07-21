<?php
/*
// ============================================
// save_scheduler_batch.php
// Bulk upsert + bulk delete into scheduler_days WORKING VERSION
// ============================================
session_start();
require_once "../connection_db.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized."]);
    exit;
}

$role = $_SESSION['role'];
$updated_by = (int)$_SESSION['user_id'];

$allowedRoles = ['admin', 'hr', 'executive', 'supervisor'];
if (!in_array($role, $allowedRoles, true)) {
    echo json_encode(["success" => false, "message" => "Access denied."]);
    exit;
}

$raw = file_get_contents("php://input");
$payload = json_decode($raw, true);

if (!is_array($payload)) {
    echo json_encode(["success" => false, "message" => "Invalid JSON payload."]);
    exit;
}

$changes = $payload['changes'] ?? [];
$deletes = $payload['deletes'] ?? [];

if (!is_array($changes) || !is_array($deletes)) {
    echo json_encode(["success" => false, "message" => "Invalid JSON payload."]);
    exit;
}

if (count($changes) === 0 && count($deletes) === 0) {
    echo json_encode(["success" => true, "message" => "No changes."]);
    exit;
}

// safety limit
if (count($changes) + count($deletes) > 5000) {
    echo json_encode(["success" => false, "message" => "Too many operations in one save (max 5000)."]);
    exit;
}

function is_valid_date($d)
{
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt && $dt->format('Y-m-d') === $d;
}

function is_valid_time_or_null($t)
{
    if ($t === null || $t === "") return true;
    return preg_match('/^\d{2}:\d{2}:\d{2}$/', $t) === 1;
}

// ✅ Standardize schedule codes on the backend
$validCodes = ['W', 'OFF', 'SH', 'RH', 'ABSENT', 'CB'];

$conn->begin_transaction();

try {
    $deleted = 0;
    $saved = 0;

    // --------------------------------------------
    // 1) DELETE rows
    // --------------------------------------------
    if (count($deletes) > 0) {
        $delSql = "DELETE FROM scheduler_days WHERE user_id=? AND work_date=?";
        $delStmt = $conn->prepare($delSql);
        if (!$delStmt) throw new Exception("Prepare delete failed: " . $conn->error);

        foreach ($deletes as $d) {
            $user_id = isset($d['user_id']) ? (int)$d['user_id'] : 0;
            $work_date = $d['work_date'] ?? '';

            if ($user_id <= 0 || !is_valid_date($work_date)) {
                throw new Exception("Invalid delete row detected.");
            }

            $delStmt->bind_param("is", $user_id, $work_date);
            if (!$delStmt->execute()) {
                throw new Exception("Delete failed: " . $delStmt->error);
            }
            $deleted++;
        }

        $delStmt->close();
    }

    // --------------------------------------------
    // 2) UPSERT rows
    // --------------------------------------------
    if (count($changes) > 0) {
        $sql = "
        INSERT INTO scheduler_days (user_id, work_date, schedule_code, call_time, updated_by)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
          schedule_code = VALUES(schedule_code),
          call_time     = VALUES(call_time),
          updated_by    = VALUES(updated_by),
          updated_at    = CURRENT_TIMESTAMP
        ";

        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception("Prepare upsert failed: " . $conn->error);

        foreach ($changes as $c) {
            $user_id = isset($c['user_id']) ? (int)$c['user_id'] : 0;
            $work_date = $c['work_date'] ?? '';
            $code = strtoupper(trim($c['schedule_code'] ?? ''));
            $call_time = $c['call_time'] ?? null;

            if ($user_id <= 0 || !is_valid_date($work_date) || !in_array($code, $validCodes, true)) {
                throw new Exception("Invalid change row detected.");
            }

            // Only allow call_time if W, otherwise force NULL
            /*if ($code !== 'W') {
                $call_time = null;
            } else {
                if (!is_valid_time_or_null($call_time)) {
                    throw new Exception("Invalid call_time format. Use HH:MM:SS.");
                }
                if ($call_time === "") $call_time = null;
            }

            // ✅ Allow call_time for W / RH / SH / CB only
            $codesWithCallTime = ['W', 'SH', 'RH', 'CB'];

            if (!in_array($code, $codesWithCallTime, true)) {
                // OFF/ABSENT/etc must never store call_time
                $call_time = null;
            } else {
                // For W/PH/CB: validate and (optionally) require call_time
                if (!is_valid_time_or_null($call_time)) {
                    throw new Exception("Invalid call_time format. Use HH:MM:SS.");
                }
                if ($call_time === "") $call_time = null;

                // ✅ OPTIONAL but recommended: require call_time for these codes
                if ($call_time === null) {
                    throw new Exception("Call time is required for $code.");
                }
            }

            $stmt->bind_param("isssi", $user_id, $work_date, $code, $call_time, $updated_by);
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            $saved++;
        }

        $stmt->close();
    }

    $conn->commit();
    echo json_encode(["success" => true, "saved" => $saved, "deleted" => $deleted]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
} finally {
    $conn->close();
}*/

// ============================================
// save_scheduler_batch.php
// Bulk upsert + bulk delete into scheduler_days
// + ALSO sync call_time into task_logs per (user_id, work_date)
// ============================================
session_start();
require_once "../connection_db.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized."]);
    exit;
}

$role = $_SESSION['role'];
$updated_by = (int)$_SESSION['user_id'];

$allowedRoles = ['admin', 'hr', 'executive', 'supervisor'];
if (!in_array($role, $allowedRoles, true)) {
    echo json_encode(["success" => false, "message" => "Access denied."]);
    exit;
}

$raw = file_get_contents("php://input");
$payload = json_decode($raw, true);

if (!is_array($payload)) {
    echo json_encode(["success" => false, "message" => "Invalid JSON payload."]);
    exit;
}

$changes = $payload['changes'] ?? [];
$deletes = $payload['deletes'] ?? [];

if (!is_array($changes) || !is_array($deletes)) {
    echo json_encode(["success" => false, "message" => "Invalid JSON payload."]);
    exit;
}

if (count($changes) === 0 && count($deletes) === 0) {
    echo json_encode(["success" => true, "message" => "No changes."]);
    exit;
}

// safety limit
if (count($changes) + count($deletes) > 5000) {
    echo json_encode(["success" => false, "message" => "Too many operations in one save (max 5000)."]);
    exit;
}

function is_valid_date($d)
{
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt && $dt->format('Y-m-d') === $d;
}

function is_valid_time_or_null($t)
{
    if ($t === null || $t === "") return true;
    return preg_match('/^\d{2}:\d{2}:\d{2}$/', $t) === 1;
}

// ✅ Standardize schedule codes on the backend
$validCodes = ['W', 'OFF', 'SH', 'RH', 'ABSENT', 'CB', 'SBL', 'LWOP', 'SPND'];

// ✅ Allow call_time for these codes only
$codesWithCallTime = ['W', 'SH', 'RH', 'CB'];

$conn->begin_transaction();

try {
    $deleted = 0;
    $saved = 0;
    $synced_logs_rows = 0;

    // ------------------------------------------------
    // Prepare sync statements once (performance)
    // ------------------------------------------------
    // Sync call_time into task_logs (set to value or NULL)
    $syncSql = "UPDATE task_logs SET call_time = ? WHERE user_id = ? AND work_date = ?";
    $syncStmt = $conn->prepare($syncSql);
    if (!$syncStmt) throw new Exception("Prepare sync task_logs failed: " . $conn->error);

    // Clear call_time from task_logs for deletes (or OFF/ABSENT, etc)
    $clearSql = "UPDATE task_logs SET call_time = NULL WHERE user_id = ? AND work_date = ?";
    $clearStmt = $conn->prepare($clearSql);
    if (!$clearStmt) throw new Exception("Prepare clear task_logs failed: " . $conn->error);

    // --------------------------------------------
    // 1) DELETE rows
    // --------------------------------------------
    if (count($deletes) > 0) {
        $delSql = "DELETE FROM scheduler_days WHERE user_id=? AND work_date=?";
        $delStmt = $conn->prepare($delSql);
        if (!$delStmt) throw new Exception("Prepare delete failed: " . $conn->error);

        foreach ($deletes as $d) {
            $user_id = isset($d['user_id']) ? (int)$d['user_id'] : 0;
            $work_date = $d['work_date'] ?? '';

            if ($user_id <= 0 || !is_valid_date($work_date)) {
                throw new Exception("Invalid delete row detected.");
            }

            $delStmt->bind_param("is", $user_id, $work_date);
            if (!$delStmt->execute()) {
                throw new Exception("Delete failed: " . $delStmt->error);
            }
            $deleted++;

            // ✅ ALSO clear task_logs call_time for that day (prevents stale call_time)
            $clearStmt->bind_param("is", $user_id, $work_date);
            if (!$clearStmt->execute()) {
                throw new Exception("Clear task_logs call_time failed: " . $clearStmt->error);
            }
            $synced_logs_rows += (int)$clearStmt->affected_rows;
        }

        $delStmt->close();
    }

    // --------------------------------------------
    // 2) UPSERT rows
    // --------------------------------------------
    if (count($changes) > 0) {
        $sql = "
        INSERT INTO scheduler_days (user_id, work_date, schedule_code, call_time, updated_by)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
          schedule_code = VALUES(schedule_code),
          call_time     = VALUES(call_time),
          updated_by    = VALUES(updated_by),
          updated_at    = CURRENT_TIMESTAMP
        ";

        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception("Prepare upsert failed: " . $conn->error);

        foreach ($changes as $c) {
            $user_id = isset($c['user_id']) ? (int)$c['user_id'] : 0;
            $work_date = $c['work_date'] ?? '';
            $code = strtoupper(trim($c['schedule_code'] ?? ''));
            $call_time = $c['call_time'] ?? null;

            if ($user_id <= 0 || !is_valid_date($work_date) || !in_array($code, $validCodes, true)) {
                throw new Exception("Invalid change row detected.");
            }

            // ✅ Allow call_time only for W/SH/RH/CB, else force NULL
            if (!in_array($code, $codesWithCallTime, true)) {
                $call_time = null;
            } else {
                if (!is_valid_time_or_null($call_time)) {
                    throw new Exception("Invalid call_time format. Use HH:MM:SS.");
                }
                if ($call_time === "") $call_time = null;

                // require call_time for these codes
                if ($call_time === null) {
                    throw new Exception("Call time is required for $code.");
                }
            }

            $stmt->bind_param("isssi", $user_id, $work_date, $code, $call_time, $updated_by);
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            $saved++;

            // ✅ ALSO sync call_time into task_logs for this shift work_date
            // - if OFF/ABSENT/etc, call_time is NULL -> task_logs becomes NULL too
            $syncStmt->bind_param("sis", $call_time, $user_id, $work_date);
            if (!$syncStmt->execute()) {
                throw new Exception("Sync task_logs call_time failed: " . $syncStmt->error);
            }
            $synced_logs_rows += (int)$syncStmt->affected_rows;
        }

        $stmt->close();
    }

    // close extra stmts
    $syncStmt->close();
    $clearStmt->close();

    $conn->commit();
    echo json_encode([
        "success" => true,
        "saved" => $saved,
        "deleted" => $deleted,
        "synced_logs_rows" => $synced_logs_rows
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
} finally {
    $conn->close();
}
