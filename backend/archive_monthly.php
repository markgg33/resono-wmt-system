<?php
// archive_monthly.php
// ---------------------------------------
// Run this manually (http://localhost/archive_monthly.php) 
// to test archiving of the previous month's task_logs
// ---------------------------------------

require_once __DIR__ . '/connection_db.php';
header('Content-Type: application/json');

date_default_timezone_set('Asia/Manila');

// Define last month range
$firstDayPrev = date('Y-m-01', strtotime('first day of previous month'));
$firstDayCurr = date('Y-m-01'); // start of current month

// Check if already archived this month (idempotency)
$checkSql = "SELECT 1 FROM task_logs_archive WHERE archived_month = ? LIMIT 1";
$chk = $conn->prepare($checkSql);
$chk->bind_param('s', $firstDayPrev);
$chk->execute();
$already = $chk->get_result()->fetch_row();
$chk->close();

if ($already) {
    echo json_encode([
        'status' => 'ok',
        'message' => "Already archived for $firstDayPrev"
    ]);
    exit;
}

$conn->begin_transaction();
try {
    // 1) Copy rows into archive
    $insSql = "
    INSERT INTO task_logs_archive
    (original_id, user_id, work_mode_id, task_description_id, date, start_time, end_time, total_duration, remarks, volume_remark, archived_month, archived_at)
    SELECT 
        tl.id, tl.user_id, tl.work_mode_id, tl.task_description_id, tl.date, tl.start_time, tl.end_time, tl.total_duration, tl.remarks, tl.volume_remark,
        ?, NOW()
    FROM task_logs tl
    WHERE tl.date >= ? AND tl.date < ?
";

    $ins = $conn->prepare($insSql);
    $ins->bind_param('sss', $firstDayPrev, $firstDayPrev, $firstDayCurr);
    $ins->execute();
    $rowsInserted = $ins->affected_rows;
    $ins->close();

    // 2) Delete those rows from main table
    $delSql = "DELETE FROM task_logs WHERE date >= ? AND date < ?";
    $del = $conn->prepare($delSql);
    $del->bind_param('ss', $firstDayPrev, $firstDayCurr);
    $del->execute();
    $rowsDeleted = $del->affected_rows;
    $del->close();

    $conn->commit();

    echo json_encode([
        'status' => 'ok',
        'archived_month' => $firstDayPrev,
        'inserted' => $rowsInserted,
        'deleted' => $rowsDeleted,
        'message' => "Successfully archived $rowsDeleted rows for $firstDayPrev"
    ]);
} catch (Throwable $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
