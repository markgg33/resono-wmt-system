 <?php
// export_mtd_csv_internal.php
// Provides: function generate_mtd_csv($conn, $userId, $monthStart, $monthEnd)
// Returns: ['csv' => $csvString, 'user_name' => $userName, 'department' => $department]

if (!function_exists('generate_mtd_csv')) {
    function generate_mtd_csv($conn, $userId, $monthStart, $monthEnd)
    {
        // Local helpers as closures to avoid global redeclare issues
        $formatDuration = function ($seconds) {
            $h = floor($seconds / 3600);
            $m = floor(($seconds % 3600) / 60);
            return str_pad($h, 2, "0", STR_PAD_LEFT) . ":" . str_pad($m, 2, "0", STR_PAD_LEFT);
        };

        $getDescription = function ($conn, $descId) {
            static $cache = [];
            if (!$descId) return '';
            if (isset($cache[$descId])) return $cache[$descId];
            $stmt = $conn->prepare("SELECT description FROM task_descriptions WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $descId);
            $stmt->execute();
            $res = $stmt->get_result();
            $desc = $res->fetch_assoc()['description'] ?? '';
            $stmt->close();
            $cache[$descId] = $desc;
            return $desc;
        };

        $allocateAwayBreakDuration = function ($duration, array &$durations, int &$usedPaidBreak, int &$usedUnpaidBreak) {
            $remaining = $duration;

            // Paid break max 30 mins/day (1800s)
            $remainingPaid = max(0, 1800 - $usedPaidBreak);
            if ($remainingPaid > 0) {
                $paid = min($remaining, $remainingPaid);
                $durations['paid_break'] += $paid;
                $usedPaidBreak += $paid;
                $remaining -= $paid;
            }

            // Unpaid break max 60 mins/day (3600s)
            if ($remaining > 0) {
                $remainingUnpaid = max(0, 3600 - $usedUnpaidBreak);
                if ($remainingUnpaid > 0) {
                    $unpaid = min($remaining, $remainingUnpaid);
                    $durations['unpaid_break'] += $unpaid;
                    $usedUnpaidBreak += $unpaid;
                    $remaining -= $unpaid;
                }
            }

            // Excess → personal_time
            if ($remaining > 0) {
                $durations['personal_time'] += $remaining;
            }
        };

        // --- fetch user info (for header) ---
        $userName = "Unknown";
        $department = "N/A";
        $uStmt = $conn->prepare("
            SELECT u.first_name, u.middle_name, u.last_name, d.name AS department 
            FROM users u 
            LEFT JOIN departments d ON u.department_id = d.id 
            WHERE u.id = ?
        ");
        $uStmt->bind_param("i", $userId);
        $uStmt->execute();
        $uInfo = $uStmt->get_result()->fetch_assoc();
        $uStmt->close();
        if ($uInfo) {
            $userName = trim($uInfo['first_name'] . " " . ($uInfo['middle_name'] ?? "") . " " . $uInfo['last_name']);
            $department = $uInfo['department'] ?? "N/A";
        }

        // --- fetch logs (task_logs + task_logs_archive), ordered by date,start_time ---
        $logsQuery = "
          SELECT id, user_id, work_mode_id, task_description_id, date, start_time, end_time, total_duration, remarks
          FROM task_logs
          WHERE user_id = ? AND date BETWEEN ? AND ?
          UNION ALL
          SELECT original_id AS id, user_id, work_mode_id, task_description_id, date, start_time, end_time, total_duration, remarks
          FROM task_logs_archive
          WHERE user_id = ? AND date BETWEEN ? AND ?
          ORDER BY date ASC, start_time ASC
        ";
        $stmt = $conn->prepare($logsQuery);
        if ($stmt === false) {
            return ['csv' => '', 'user_name' => $userName, 'department' => $department];
        }
        $stmt->bind_param("ississ", $userId, $monthStart, $monthEnd, $userId, $monthStart, $monthEnd);
        $stmt->execute();
        $res = $stmt->get_result();

        $dailyLogs = [];
        while ($r = $res->fetch_assoc()) {
            $dailyLogs[$r['date']][] = $r;
        }
        $stmt->close();

        // build rows & totals
        $summary = [];
        $totals = [
            'total' => 0,
            'production' => 0,
            'offphone' => 0,
            'training' => 0,
            'resono' => 0,
            'paid_break' => 0,
            'unpaid_break' => 0,
            'personal_time' => 0,
        ];

        foreach ($dailyLogs as $date => $logs) {
            $login = null;
            $logout = null;
            $usedPaidBreak = 0;
            $usedUnpaidBreak = 0;

            foreach ($logs as $log) {
                if (!$login && $log['start_time']) $login = $log['start_time'];
                if ($log['end_time']) $logout = $log['end_time'];
            }

            $totalTime = ($logout && $login) ? strtotime($logout) - strtotime($login) : 0;

            $durations = [
                'production' => 0,
                'offphone' => 0,
                'training' => 0,
                'resono' => 0,
                'paid_break' => 0,
                'unpaid_break' => 0,
                'personal_time' => 0
            ];

            foreach ($logs as $log) {
                if (!$log['end_time'] || !$log['start_time']) continue;
                $duration = strtotime($log['end_time']) - strtotime($log['start_time']);
                if ($duration <= 0) continue;

                $desc = strtolower($log['task_description_id'] ? $getDescription($conn, $log['task_description_id']) : '');
                $workModeId = (int)$log['work_mode_id'];

                if (strpos($desc, 'resono') !== false) {
                    $durations['resono'] += $duration;
                } elseif (strpos($desc, 'training') !== false) {
                    $durations['training'] += $duration;
                } elseif (strpos($desc, 'offphone') !== false) {
                    $durations['offphone'] += $duration;
                } elseif (strpos($desc, 'away - break') !== false) {
                    $allocateAwayBreakDuration($duration, $durations, $usedPaidBreak, $usedUnpaidBreak);
                } else {
                    if ($workModeId === 1) {
                        $durations['production'] += $duration;
                    }
                }
            }

            // accumulate totals
            $totals['total'] += $totalTime;
            foreach ($durations as $k => $v) $totals[$k] += $v;

            $summary[] = [
                'date' => $date,
                'login' => $login ?? '--',
                'logout' => $logout ?? '--',
                'total' => $formatDuration($totalTime),
                'production' => $formatDuration($durations['production']),
                'offphone' => $formatDuration($durations['offphone']),
                'training' => $formatDuration($durations['training']),
                'resono' => $formatDuration($durations['resono']),
                'paid_break' => $formatDuration($durations['paid_break']),
                'unpaid_break' => $formatDuration($durations['unpaid_break']),
                'personal_time' => $formatDuration($durations['personal_time'])
            ];
        }

        // prepare CSV into memory
        $fh = fopen("php://temp", "r+");
        // header info
        fputcsv($fh, ["User", $userName]);
        fputcsv($fh, ["Department", $department]);
        fputcsv($fh, []); // blank line
        // table header
        fputcsv($fh, ["Date", "Login", "Logout", "Total", "Production", "Offphone", "Training", "Resono", "Paid Break", "Unpaid Break", "Personal Time"]);
        // rows
        foreach ($summary as $row) {
            fputcsv($fh, [$row['date'], $row['login'], $row['logout'], $row['total'], $row['production'], $row['offphone'], $row['training'], $row['resono'], $row['paid_break'], $row['unpaid_break'], $row['personal_time']]);
        }
        // totals row
        fputcsv($fh, [
            "TOTALS",
            "",
            "",
            $formatDuration($totals['total']),
            $formatDuration($totals['production']),
            $formatDuration($totals['offphone']),
            $formatDuration($totals['training']),
            $formatDuration($totals['resono']),
            $formatDuration($totals['paid_break']),
            $formatDuration($totals['unpaid_break']),
            $formatDuration($totals['personal_time'])
        ]);

        rewind($fh);
        $csvContent = stream_get_contents($fh);
        fclose($fh);

        return ['csv' => $csvContent, 'user_name' => $userName, 'department' => $department];
    }
}