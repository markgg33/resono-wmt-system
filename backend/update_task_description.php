<?php
require 'connection_db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$workModeId = $data['work_mode_id'] ?? null;
$descriptions = $data['tasks'] ?? [];

if (!$workModeId || !is_array($descriptions)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}

$duplicates = [];
$stmt = $conn->prepare("SELECT id FROM task_descriptions WHERE work_mode_id = ? AND LOWER(description) = LOWER(?)");
$insert = $conn->prepare("INSERT INTO task_descriptions (work_mode_id, description) VALUES (?, ?)");

foreach ($descriptions as $desc) {
    $desc = trim($desc);
    if (!empty($desc)) {
        $stmt->bind_param("is", $workModeId, $desc);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $duplicates[] = $desc;
        } else {
            $insert->bind_param("is", $workModeId, $desc);
            $insert->execute();
        }
    }
}

$response = ['success' => true, 'message' => 'Tasks saved.'];
if (!empty($duplicates)) {
    $response['duplicates'] = $duplicates;
    $response['message'] = 'Some tasks were not added due to duplication.';
}

echo json_encode($response);
$stmt->close();
$insert->close();
$conn->close();

//NOW REJECTS DUPLICATES
