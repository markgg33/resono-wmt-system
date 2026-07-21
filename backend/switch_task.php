<?php

session_start();

require 'connection_db.php';

header('Content-Type: application/json');
date_default_timezone_set("Asia/Manila");

ini_set('display_errors', 0);
error_reporting(E_ALL);

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode([
        "status" => "error",
        "message" => "Session expired. Please login again."
    ]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

/**
 * Force HH:MM:00
 */
function floorToMinute(?string $t): ?string
{
    if (!$t) return null;

    $t = trim($t);

    if ($t === '' || $t === '--') {
        return null;
    }

    if (preg_match('/^\d{2}:\d{2}$/', $t)) {
        return $t . ':00';
    }

    if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $t)) {
        return substr($t, 0, 5) . ':00';
    }

    return null;
}

$previous_task_id   = $data['previous_task_id'] ?? null;

$work_mode_id       = $data['work_mode_id'] ?? null;
$task_description_id = $data['task_description_id'] ?? null;

$date               = $data['date'] ?? date('Y-m-d');
$work_date          = $data['work_date'] ?? $date;

$start_time         = floorToMinute($data['start_time'] ?? null);
$call_time          = floorToMinute($data['call_time'] ?? null);

$remarks            = $data['remarks'] ?? '';

$insert_end_time    = !empty($data['end_time'])
    ? floorToMinute($data['end_time'])
    : null;

$insert_end_date    = $data['end_date'] ?? null;

if (
    !$work_mode_id ||
    !$task_description_id ||
    !$start_time
) {
    echo json_encode([
        "status" => "error",
        "message" => "Missing required fields"
    ]);
    exit;
}

$conn->begin_transaction();

try {

    /**
     * ======================================================
     * CLOSE PREVIOUS TASK
     * ======================================================
     */
    if (!empty($previous_task_id)) {

        $stmt = $conn->prepare("
            SELECT
                id,
                date,
                work_date,
                start_time,
                task_description_id
            FROM task_logs
            WHERE id = ?
              AND user_id = ?
        ");

        $stmt->bind_param(
            "ii",
            $previous_task_id,
            $user_id
        );

        $stmt->execute();

        $prev = $stmt->get_result()->fetch_assoc();

        $stmt->close();

        if (!$prev) {
            throw new Exception(
                "Previous task not found."
            );
        }

        $segment_start_date = $prev['date'];

        $prev_start_time = floorToMinute(
            $prev['start_time']
        );

        $prev_end_time = $start_time;

        $isEndShiftPrev =
            ((int)$prev['task_description_id'] === 11);

        $end_date = $segment_start_date;

        if (
            !$isEndShiftPrev &&
            $prev_end_time < $prev_start_time
        ) {
            $dt = new DateTime(
                $segment_start_date
            );

            $dt->modify('+1 day');

            $end_date = $dt->format('Y-m-d');
        }

        $startDT = new DateTime(
            $segment_start_date . ' ' . $prev_start_time
        );

        $endDT = new DateTime(
            $end_date . ' ' . $prev_end_time
        );

        $diffSeconds =
            $endDT->getTimestamp()
            - $startDT->getTimestamp();

        if ($diffSeconds < 0) {
            $diffSeconds += 86400;
        }

        $diffMinutes = intdiv(
            $diffSeconds,
            60
        );

        $total_duration = sprintf(
            "%02d:%02d:00",
            intdiv($diffMinutes, 60),
            $diffMinutes % 60
        );

        $upd = $conn->prepare("
            UPDATE task_logs
            SET
                end_time = ?,
                end_date = ?,
                total_duration = ?
            WHERE id = ?
              AND user_id = ?
        ");

        $upd->bind_param(
            "sssii",
            $prev_end_time,
            $end_date,
            $total_duration,
            $previous_task_id,
            $user_id
        );

        if (!$upd->execute()) {
            throw new Exception(
                $upd->error
            );
        }

        $upd->close();
    }

    /**
     * ======================================================
     * INSERT NEW TASK
     * ======================================================
     */
    $total_duration_insert = null;

    if ($insert_end_time) {
        $total_duration_insert = "00:00:00";
    }

    $ins = $conn->prepare("
        INSERT INTO task_logs
        (
            user_id,
            work_mode_id,
            task_description_id,
            date,
            work_date,
            start_time,
            call_time,
            remarks,
            end_time,
            end_date,
            total_duration
        )
        VALUES
        (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");

    $ins->bind_param(
        "iiissssssss",
        $user_id,
        $work_mode_id,
        $task_description_id,
        $date,
        $work_date,
        $start_time,
        $call_time,
        $remarks,
        $insert_end_time,
        $insert_end_date,
        $total_duration_insert
    );

    if (!$ins->execute()) {
        throw new Exception(
            $ins->error
        );
    }

    $insertedId = $ins->insert_id;

    $ins->close();

    $conn->commit();

    echo json_encode([
        "status" => "success",
        "inserted_id" => $insertedId
    ]);
} catch (Exception $e) {

    $conn->rollback();

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}

$conn->close();
