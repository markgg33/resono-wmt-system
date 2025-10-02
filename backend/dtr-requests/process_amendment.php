<?php
session_start();
require '../connection_db.php';
header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['role'] ?? null;

if (!$userId || !in_array($userRole, ['admin', 'executive', 'hr', 'supervisor'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$requestId = $data['request_id'] ?? null;
$decision  = $data['decision'] ?? null; // "Approved" or "Rejected"

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

echo json_encode(["status" => "success", "message" => "Request $decision"]);
