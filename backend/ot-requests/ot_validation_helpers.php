<?php

/**
 * Parse ot_requests.hours (HH:MM or HH:MM:SS) to seconds.
 */
function ot_hours_string_to_seconds(string $hours): int
{
    $hours = trim($hours);
    if ($hours === '') {
        return 0;
    }
    $parts = explode(':', $hours);
    $h = (int)($parts[0] ?? 0);
    $m = (int)($parts[1] ?? 0);
    $s = (int)($parts[2] ?? 0);
    return ($h * 3600) + ($m * 60) + $s;
}

/**
 * Sum OT hours already committed for a user/date: pending + approved (rejected excluded).
 * Optionally exclude one request row (e.g. current request while editing).
 */
function ot_sum_committed_ot_seconds(mysqli $conn, int $userId, string $trackerDateYmd, ?int $excludeRequestId = null): int
{
    if ($excludeRequestId !== null) {
        $stmt = $conn->prepare("
            SELECT hours
            FROM ot_requests
            WHERE user_id = ?
              AND tracker_date = ?
              AND status IN ('pending', 'approved')
              AND id != ?
        ");
        $stmt->bind_param("isi", $userId, $trackerDateYmd, $excludeRequestId);
    } else {
        $stmt = $conn->prepare("
            SELECT hours
            FROM ot_requests
            WHERE user_id = ?
              AND tracker_date = ?
              AND status IN ('pending', 'approved')
        ");
        $stmt->bind_param("is", $userId, $trackerDateYmd);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $total = 0;
    while ($row = $res->fetch_assoc()) {
        $total += ot_hours_string_to_seconds((string)($row['hours'] ?? ''));
    }
    $stmt->close();
    return $total;
}

/**
 * Theoretical paid seconds for a work_date (matches submit_overtime_request / export logic).
 */
function ot_compute_theoretical_paid_seconds(mysqli $conn, int $userId, string $workDateYmd): int
{
    $logStmt = $conn->prepare("
        SELECT work_mode_id, task_description_id, start_time, end_time, end_date, work_date, date, call_time
        FROM task_logs
        WHERE user_id = ? AND work_date = ?
    ");
    $logStmt->bind_param("is", $userId, $workDateYmd);
    $logStmt->execute();
    $result = $logStmt->get_result();
    $logs = [];
    while ($log = $result->fetch_assoc()) {
        $logs[] = $log;
    }
    $logStmt->close();

    $callTimeRaw = null;
    foreach ($logs as $l) {
        if (!empty($l['call_time']) && $l['call_time'] !== '--') {
            $callTimeRaw = trim($l['call_time']);
            break;
        }
    }

    $loginDT = null;
    foreach ($logs as $l) {
        if (empty($l['start_time']) || $l['start_time'] === '--') {
            continue;
        }
        $loginStartDate = !empty($l['date']) ? $l['date'] : $workDateYmd;
        $dt = new DateTime($loginStartDate . " " . trim($l['start_time']));
        if ($loginDT === null || $dt < $loginDT) {
            $loginDT = $dt;
        }
    }

    $callTimeNorm = null;
    if (!empty($callTimeRaw)) {
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $callTimeRaw, $m)) {
            $callTimeNorm = sprintf('%02d:%02d:00', (int)$m[1], (int)$m[2]);
        } elseif (preg_match('/^(\d{1,2}):(\d{2}):(\d{2})$/', $callTimeRaw, $m)) {
            $callTimeNorm = sprintf('%02d:%02d:%02d', (int)$m[1], (int)$m[2], (int)$m[3]);
        } else {
            $callTimeNorm = $callTimeRaw;
        }
    }

    $productionBoundaryDT = null;
    if ($loginDT instanceof DateTime && !empty($callTimeNorm)) {
        try {
            $callDT = new DateTime($loginDT->format('Y-m-d') . " " . $callTimeNorm);
            if ($loginDT <= $callDT) {
                $productionBoundaryDT = $callDT;
            }
        } catch (Exception $e) {
            $productionBoundaryDT = null;
        }
    }

    $paidSeconds = 0;
    $usedPaidBreak = 0;
    $descCache = [];
    $wmCache = [];

    // ==========================================
    // MATCH MONTHLY SUMMARY PRODUCTION LOGIC
    // ==========================================

    $productionWorkModes = [];

    $modeQuery = $conn->query("
    SELECT id, name
    FROM work_modes
");

    while ($wm = $modeQuery->fetch_assoc()) {

        $wmName = strtolower($wm['name']);

        if (
            strpos($wmName, 'offphone') === false &&
            strpos($wmName, 'training') === false &&
            strpos($wmName, 'break') === false &&
            strpos($wmName, 'personal') === false &&
            strpos($wmName, 'technical_error') === false
        ) {
            $productionWorkModes[] = (int)$wm['id'];
        }
    }

    foreach ($logs as $log) {
        if (empty($log['end_time'])) {
            continue;
        }

        $startDate = !empty($log['date']) ? $log['date'] : ($log['work_date'] ?? $workDateYmd);
        $endDate   = !empty($log['end_date']) ? $log['end_date'] : $startDate;

        $startT = $log['start_time'];
        $endT   = $log['end_time'];

        if (empty($log['end_date']) && $endDate === $startDate && $endT < $startT) {
            $endDate = date("Y-m-d", strtotime($startDate . " +1 day"));
        }

        if ($endDate < $startDate) {
            $endDate = $startDate;
            if ($endT < $startT) {
                $endDate = date("Y-m-d", strtotime($startDate . " +1 day"));
            }
        }

        $startDT = new DateTime("$startDate $startT");
        $endDT   = new DateTime("$endDate $endT");

        $durationSeconds = $endDT->getTimestamp() - $startDT->getTimestamp();
        if ($durationSeconds <= 0) {
            continue;
        }
        $duration = intdiv($durationSeconds, 60) * 60;
        if ($duration <= 0) {
            continue;
        }

        $desc = '';
        $taskDescId = $log['task_description_id'] ?? null;
        if (!empty($taskDescId)) {
            $taskDescId = (int)$taskDescId;
            if (!isset($descCache[$taskDescId])) {
                $stmt = $conn->prepare("SELECT description FROM task_descriptions WHERE id = ?");
                $stmt->bind_param("i", $taskDescId);
                $stmt->execute();
                $descCache[$taskDescId] = strtolower($stmt->get_result()->fetch_assoc()['description'] ?? '');
                $stmt->close();
            }
            $desc = $descCache[$taskDescId];
        }

        $wmName = '';
        $workModeId = (int)($log['work_mode_id'] ?? 0);
        if ($workModeId > 0) {
            if (!isset($wmCache[$workModeId])) {
                $stmt = $conn->prepare("SELECT name FROM work_modes WHERE id = ?");
                $stmt->bind_param("i", $workModeId);
                $stmt->execute();
                $wmCache[$workModeId] = strtolower($stmt->get_result()->fetch_assoc()['name'] ?? '');
                $stmt->close();
            }
            $wmName = $wmCache[$workModeId];
        }

        if (strpos($desc, 'resono') !== false) {
            $paidSeconds += $duration;
        } elseif (strpos($desc, 'training') !== false) {
            $paidSeconds += $duration;
        } elseif (strpos($desc, 'offphone') !== false || strpos($desc, 'team huddle') !== false) {
            $paidSeconds += $duration;
        } elseif (strpos($desc, 'away - break') !== false) {
            $remainingPaid = max(0, 1800 - $usedPaidBreak);
            if ($remainingPaid > 0) {
                $paid = min($duration, $remainingPaid);
                $paidSeconds += $paid;
                $usedPaidBreak += $paid;
            }
        } elseif (strpos($desc, 'system') !== false || $wmName === 'technical_error') {
            $paidSeconds += $duration;
        } /*else {
            $productionDuration = $duration;
            if ($productionBoundaryDT instanceof DateTime) {
                $boundaryTs = $productionBoundaryDT->getTimestamp();
                $effectiveStartTs = $startDT->getTimestamp();
                if ($effectiveStartTs < $boundaryTs) {
                    $effectiveStartTs = $boundaryTs;
                }
                $productionDuration = $endDT->getTimestamp() - $effectiveStartTs;
                if ($productionDuration < 0) {
                    $productionDuration = 0;
                }
                $productionDuration = intdiv($productionDuration, 60) * 60;
            }
            $paidSeconds += $productionDuration;
        }*/ else {

            // Must match Monthly Summary production rules
            if (!in_array($workModeId, $productionWorkModes)) {
                continue;
            }

            $productionDuration = $duration;

            if ($productionBoundaryDT instanceof DateTime) {

                $boundaryTs = $productionBoundaryDT->getTimestamp();

                $effectiveStartTs = $startDT->getTimestamp();

                if ($effectiveStartTs < $boundaryTs) {
                    $effectiveStartTs = $boundaryTs;
                }

                $productionDuration =
                    $endDT->getTimestamp() - $effectiveStartTs;

                if ($productionDuration < 0) {
                    $productionDuration = 0;
                }

                $productionDuration =
                    intdiv($productionDuration, 60) * 60;
            }

            $paidSeconds += $productionDuration;
        }
    }

    /* ==========================================
   ADD PAID LEAVE HOURS
========================================== */

    $leaveHoursSeconds = 0;

    $leaveStmt = $conn->prepare("
    SELECT
        lrd.availment,
        lrd.leave_type,
        lr.leave_payment_status
    FROM leave_requests lr
    JOIN leave_request_dates lrd
        ON lrd.leave_request_id = lr.id
    WHERE lr.user_id = ?
      AND lr.status = 'Approved'
      AND lrd.leave_date = ?
");

    $leaveStmt->bind_param("is", $userId, $workDateYmd);
    $leaveStmt->execute();

    $leaveRes = $leaveStmt->get_result();

    while ($leave = $leaveRes->fetch_assoc()) {

        // Only PAID leave counts
        if (($leave['leave_payment_status'] ?? '') !== 'Paid') {
            continue;
        }

        $leaveType = strtolower(trim($leave['leave_type'] ?? ''));

        // TOIL should not contribute
        if (
            $leaveType === 'toil' ||
            $leaveType === 'time in lieu off'
        ) {
            continue;
        }

        $availment = floatval($leave['availment']);

        if ($availment >= 1) {
            $leaveHoursSeconds += 8 * 3600;
        } elseif ($availment == 0.5) {
            $leaveHoursSeconds += 4 * 3600;
        }
    }

    $leaveStmt->close();

    /*$paidSeconds += $leaveHoursSeconds;
    return $paidSeconds;*/

    $paidSeconds += $leaveHoursSeconds;

    // TEMP DEBUG
    error_log("
OT COMPUTATION
User: {$userId}
Date: {$workDateYmd}

Paid Seconds: {$paidSeconds}
Leave Seconds: {$leaveHoursSeconds}
");

    return $paidSeconds;
}

/**
 * Check if user has pending DTR amendments
 * affecting a specific work_date.
 */
function ot_has_pending_amendments(
    mysqli $conn,
    int $userId,
    string $workDate
): bool {

    $stmt = $conn->prepare("
        SELECT da.id
        FROM dtr_amendments da

        LEFT JOIN task_logs tl
            ON tl.id = da.log_id

        LEFT JOIN task_logs_archive tla
            ON tla.id = da.log_id

        WHERE da.user_id = ?
          AND da.status = 'Pending'
          AND (
                tl.work_date = ?
             OR tla.work_date = ?
          )
        LIMIT 1
    ");

    $stmt->bind_param(
        "iss",
        $userId,
        $workDate,
        $workDate
    );

    $stmt->execute();
    $stmt->store_result();

    $hasPending = $stmt->num_rows > 0;

    $stmt->close();

    return $hasPending;
}
