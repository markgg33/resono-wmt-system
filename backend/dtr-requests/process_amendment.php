<?php
session_start();
require '../connection_db.php';
header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['role'] ?? null;

if (!$userId || !in_array($userRole, ['admin', 'executive', 'hr'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$requestId = $data['request_id'] ?? null;
$decision = $data['decision'] ?? null; // "Approved" or "Rejected"

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

/*if ($decision === "Approved") {
    $logId = $amendment['log_id'];
    $field = $amendment['field'];
    $newValue = $amendment['new_value'];

    // Apply change
    $update = $conn->prepare("UPDATE task_logs SET {$field} = ? WHERE id = ?");
    $update->bind_param("si", $newValue, $logId);
    $update->execute();
    $update->close();

    // Recalculate duration if start/end time changed
    if (in_array($field, ['start_time', 'end_time'])) {
        $dur = $conn->prepare("UPDATE task_logs SET total_duration = TIMEDIFF(end_time, start_time) WHERE id = ?");
        $dur->bind_param("i", $logId);
        $dur->execute();
        $dur->close();
    }
}*/

if ($decision === "Approved") {
    $logId = $amendment['log_id'];
    $field = $amendment['field'];
    $newValue = $amendment['new_value'];

    // Apply change to the amended field
    $update = $conn->prepare("UPDATE task_logs SET {$field} = ? WHERE id = ?");
    $update->bind_param("si", $newValue, $logId);
    $update->execute();
    $update->close();

    // Recalculate Task A duration if start/end time changed
    if (in_array($field, ['start_time', 'end_time'])) {
        $dur = $conn->prepare("UPDATE task_logs SET total_duration = TIMEDIFF(end_time, start_time) WHERE id = ?");
        $dur->bind_param("i", $logId);
        $dur->execute();
        $dur->close();
    }

    // 🔹 If Task A's end_time was changed, update Task B's start_time
    if ($field === 'end_time') {
        // Get Task A's new end_time and user_id
        $getTaskA = $conn->prepare("SELECT user_id, end_time FROM task_logs WHERE id = ?");
        $getTaskA->bind_param("i", $logId);
        $getTaskA->execute();
        $taskA = $getTaskA->get_result()->fetch_assoc();
        $getTaskA->close();

        if ($taskA) {
            $userIdTask = $taskA['user_id'];
            $newEndTime = $taskA['end_time'];

            // Find Task B (the very next task for the same user, later id)
            $getTaskB = $conn->prepare("SELECT id FROM task_logs WHERE user_id = ? AND id > ? ORDER BY id ASC LIMIT 1");
            $getTaskB->bind_param("ii", $userIdTask, $logId);
            $getTaskB->execute();
            $taskB = $getTaskB->get_result()->fetch_assoc();
            $getTaskB->close();

            if ($taskB) {
                $taskBId = $taskB['id'];

                // Update Task B's start_time
                $updB = $conn->prepare("UPDATE task_logs SET start_time = ? WHERE id = ?");
                $updB->bind_param("si", $newEndTime, $taskBId);
                $updB->execute();
                $updB->close();

                // Recalculate Task B's duration too
                $durB = $conn->prepare("UPDATE task_logs SET total_duration = TIMEDIFF(end_time, start_time) WHERE id = ?");
                $durB->bind_param("i", $taskBId);
                $durB->execute();
                $durB->close();
            }
        }
    }
}


// Update amendment status
$stmt = $conn->prepare("UPDATE dtr_amendments 
                        SET status = ?, processed_by = ?, processed_at = NOW() 
                        WHERE id = ?");
$stmt->bind_param("sii", $decision, $userId, $requestId);
$stmt->execute();
$stmt->close();

echo json_encode(["status" => "success", "message" => "Request $decision"]);
