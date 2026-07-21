<?php

//WORKING VERSION
/*require 'connection_db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$logId = $data['log_id'] ?? null;
$workModeId = $data['work_mode_id'] ?? null;
$taskDescriptionId = $data['task_description_id'] ?? null;

if (!$logId || !$workModeId || !$taskDescriptionId) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}

$stmt = $conn->prepare("UPDATE task_logs SET work_mode_id = ?, task_description_id = ? WHERE id = ?");
$stmt->bind_param("iii", $workModeId, $taskDescriptionId, $logId);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Task updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update task.']);
}

$stmt->close();
$conn->close();*/

/*
require 'connection_db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$logId = $data['log_id'] ?? null;
$workModeId = $data['work_mode_id'] ?? null;
$taskDescriptionId = $data['task_description_id'] ?? null;

if (!$logId || !$workModeId || !$taskDescriptionId) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}

// 🟢 Try updating the main table first
$stmt = $conn->prepare("UPDATE task_logs SET work_mode_id = ?, task_description_id = ? WHERE id = ?");
$stmt->bind_param("iii", $workModeId, $taskDescriptionId, $logId);
$stmt->execute();
$affected_main = $stmt->affected_rows;
$stmt->close();

// 🟡 If not found, update the archive
if ($affected_main === 0) {
    $stmt2 = $conn->prepare("UPDATE task_logs_archive SET work_mode_id = ?, task_description_id = ? WHERE id = ?");
    $stmt2->bind_param("iii", $workModeId, $taskDescriptionId, $logId);
    $stmt2->execute();
    $affected_archive = $stmt2->affected_rows;
    $stmt2->close();

    if ($affected_archive > 0) {
        echo json_encode(['success' => true, 'source' => 'archive', 'message' => 'Task updated successfully (archive).']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Record not found in either table.']);
    }
} else {
    echo json_encode(['success' => true, 'source' => 'main', 'message' => 'Task updated successfully.']);
}

$conn->close();
*/


require 'connection_db.php';
header('Content-Type: application/json');
date_default_timezone_set("Asia/Manila");

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$data = json_decode(file_get_contents("php://input"), true);

$logId = isset($data['log_id']) ? (int)$data['log_id'] : 0;
$newTaskDescriptionId = isset($data['task_description_id']) ? (int)$data['task_description_id'] : 0;

// ⚠️ we will NOT trust incoming work_mode_id anymore
// because task_descriptions already defines what work_mode_id it belongs to.

if (!$logId || !$newTaskDescriptionId) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}

function getTaskRow(mysqli $conn, int $taskId): ?array {
    $stmt = $conn->prepare("SELECT id, work_mode_id, description FROM task_descriptions WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $taskId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    return $row ?: null;
}

function isEndShiftName(string $name): bool {
    return stripos($name, 'end shift') !== false;
}

function fetchLog(mysqli $conn, string $table, int $logId): ?array {
    $sql = "SELECT id, work_mode_id, task_description_id, start_time, end_time, date, end_date, total_duration
            FROM {$table}
            WHERE id = ?
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $logId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    return $row ?: null;
}

function updateLogWithRules(
    mysqli $conn,
    string $table,
    array $existingRow,
    int $newWorkModeId,
    int $newTaskId,
    bool $oldIsEndShift,
    bool $newIsEndShift
): void {

    $setEndTime = false;  $endTimeVal = null;
    $setEndDate = false;  $endDateVal = null;
    $setTotal   = false;  $totalVal   = null;

    // A) Undo End Shift (re-open)
    if ($oldIsEndShift && !$newIsEndShift) {
        $setEndTime = true; $endTimeVal = null;
        $setEndDate = true; $endDateVal = null;
        $setTotal   = true; $totalVal   = null;
    }

    // B) New task is End Shift (force closed marker)
    if ($newIsEndShift) {
        $startTime = $existingRow['start_time'];
        $rowDate   = $existingRow['date'];
        $setEndTime = true; $endTimeVal = $startTime;
        $setEndDate = true; $endDateVal = $rowDate;
        $setTotal   = true; $totalVal   = "00:00:00";
    }

    $sql = "UPDATE {$table} SET work_mode_id = ?, task_description_id = ?";
    $types = "ii";
    $vals = [$newWorkModeId, $newTaskId];

    if ($setEndTime) { $sql .= ", end_time = ?"; $types .= "s"; $vals[] = $endTimeVal; }
    if ($setEndDate) { $sql .= ", end_date = ?"; $types .= "s"; $vals[] = $endDateVal; }
    if ($setTotal)   { $sql .= ", total_duration = ?"; $types .= "s"; $vals[] = $totalVal; }

    $sql .= " WHERE id = ? LIMIT 1";
    $types .= "i";
    $vals[] = (int)$existingRow['id'];

    $stmt = $conn->prepare($sql);

    // Build bind refs (handles NULL correctly)
    $bind = [];
    $bind[] = $types;

    $refVars = [];
    foreach ($vals as $i => $v) {
        if ($v === null) {
            $refVars[$i] = null;
        } else {
            $refVars[$i] = $v;
        }
        $bind[] = &$refVars[$i];
    }

    call_user_func_array([$stmt, 'bind_param'], $bind);
    $stmt->execute();
    $stmt->close();
}

try {
    // ✅ Determine new work_mode_id from task_descriptions
    $newTaskRow = getTaskRow($conn, $newTaskDescriptionId);
    if (!$newTaskRow) {
        echo json_encode(['success' => false, 'message' => 'Invalid task_description_id (not found).']);
        exit;
    }

    $newWorkModeId = (int)$newTaskRow['work_mode_id'];
    $newTaskName   = (string)$newTaskRow['description'];
    $newIsEndShift = isEndShiftName($newTaskName);

    // 1) Main
    $row = fetchLog($conn, "task_logs", $logId);
    if ($row) {
        $oldTaskRow = getTaskRow($conn, (int)$row['task_description_id']);
        $oldName = $oldTaskRow ? (string)$oldTaskRow['description'] : '';
        $oldIsEndShift = isEndShiftName($oldName);

        updateLogWithRules($conn, "task_logs", $row, $newWorkModeId, $newTaskDescriptionId, $oldIsEndShift, $newIsEndShift);

        echo json_encode([
            'success' => true,
            'source' => 'main',
            'message' => 'Task updated successfully.',
            'resolved_work_mode_id' => $newWorkModeId
        ]);
        exit;
    }

    // 2) Archive
    $rowA = fetchLog($conn, "task_logs_archive", $logId);
    if ($rowA) {
        $oldTaskRow = getTaskRow($conn, (int)$rowA['task_description_id']);
        $oldName = $oldTaskRow ? (string)$oldTaskRow['description'] : '';
        $oldIsEndShift = isEndShiftName($oldName);

        updateLogWithRules($conn, "task_logs_archive", $rowA, $newWorkModeId, $newTaskDescriptionId, $oldIsEndShift, $newIsEndShift);

        echo json_encode([
            'success' => true,
            'source' => 'archive',
            'message' => 'Task updated successfully (archive).',
            'resolved_work_mode_id' => $newWorkModeId
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Record not found in either table.']);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Update failed.',
        'error' => $e->getMessage()
    ]);
} finally {
    $conn->close();
}
