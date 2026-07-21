        <?php
        session_start();
        require '../connection_db.php';
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
            echo json_encode(["status" => "error", "message" => "Unauthorized"]);
            exit;
        }

        $user_id = $_SESSION['user_id'];
        $user_role = $_SESSION['role'];

        $data = json_decode(file_get_contents("php://input"), true);
        $request_id = intval($data['request_id'] ?? 0);

        if (!$request_id) {
            echo json_encode(["status" => "error", "message" => "Invalid request"]);
            exit;
        }

        /* =========================================
        1. GET REQUEST
        ========================================= */
        $stmt = $conn->prepare("
            SELECT user_id, status, leave_payment_status
            FROM leave_requests
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $leave = $res->fetch_assoc();
        $stmt->close();

        if (!$leave) {
            echo json_encode(["status" => "error", "message" => "Leave not found"]);
            exit;
        }

        $isOwner = ($leave['user_id'] == $user_id);
        $isAdminLike = in_array($user_role, ['admin', 'hr', 'executive', 'supervisor']);

        /* =========================================
        2. PERMISSION CHECK
        ========================================= */
        if (!$isOwner && !$isAdminLike) {
            echo json_encode(["status" => "error", "message" => "Forbidden"]);
            exit;
        }

        /* =========================================
3. IF APPROVED → RESTORE CREDITS
========================================= */

        $conn->begin_transaction();

        try {

            if ($leave['status'] === 'Approved' && $leave['leave_payment_status'] === 'Paid') {

                // ✅ SAME mapping as approve (IMPORTANT)
                $columnMap = [
                    'Vacation Leave'      => 'vacation_leave',
                    'Sick Leave'          => 'sick_leave',
                    'Emergency Leave'     => 'emergency_leave',
                    'Compassionate Leave' => 'compassionate_leave'
                ];

                $stmt = $conn->prepare("
            SELECT leave_date, leave_type, availment
            FROM leave_request_dates
            WHERE leave_request_id = ?
        ");
                $stmt->bind_param("i", $request_id);
                $stmt->execute();
                $res = $stmt->get_result();

                while ($row = $res->fetch_assoc()) {

                    $leaveDate = $row['leave_date'];

                    // 🔥 CHECK IF RH (DO NOT RESTORE IF RH)
                    $schedStmt = $conn->prepare("
                SELECT schedule_code
                FROM scheduler_days
                WHERE user_id = ?
                AND work_date = ?
                LIMIT 1
            ");
                    $schedStmt->bind_param("is", $leave['user_id'], $leaveDate);
                    $schedStmt->execute();
                    $schedRes = $schedStmt->get_result()->fetch_assoc();
                    $schedStmt->close();

                    $scheduleCode = strtoupper($schedRes['schedule_code'] ?? '');

                    if ($scheduleCode === 'RH') {
                        continue; // ❗ skip restore
                    }

                    // ✅ SAFE mapping instead of string replace
                    $field = $columnMap[$row['leave_type']] ?? null;
                    if (!$field) continue;

                    $upd = $conn->prepare("
                UPDATE users
                SET $field = $field + ?
                WHERE id = ?
            ");
                    $upd->bind_param("di", $row['availment'], $leave['user_id']);
                    $upd->execute();
                    $upd->close();
                }

                $stmt->close();
            }

            /* =========================================
4. UPDATE STATUS → CANCELLED
========================================= */
            $stmt = $conn->prepare("
    UPDATE leave_requests
    SET status = 'Cancelled',
        updated_at = NOW()
    WHERE id = ?
");
            $stmt->bind_param("i", $request_id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();

            /* =========================================
5. RESPONSE
========================================= */
            echo json_encode([
                "status" => "success",
                "message" => "Leave request cancelled successfully."
            ]);
        } catch (Exception $e) {
            $conn->rollback();

            echo json_encode([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
