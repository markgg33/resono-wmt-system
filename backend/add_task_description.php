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
$inserted = [];

$stmtCheck = $conn->prepare("SELECT id FROM task_descriptions WHERE work_mode_id = ? AND LOWER(description) = LOWER(?)");
$stmtInsert = $conn->prepare("INSERT INTO task_descriptions (work_mode_id, description) VALUES (?, ?)");

foreach ($descriptions as $desc) {
    $desc = trim($desc);
    if (empty($desc)) continue;

    // Check if duplicate (case-insensitive)
    $stmtCheck->bind_param("is", $workModeId, $desc);
    $stmtCheck->execute();
    $stmtCheck->store_result();

    if ($stmtCheck->num_rows > 0) {
        $duplicates[] = $desc;
    } else {
        $stmtInsert->bind_param("is", $workModeId, $desc);
        $stmtInsert->execute();
        $inserted[] = $desc;
    }
}

echo json_encode([
    'success' => true,
    'inserted' => $inserted,
    'duplicates' => $duplicates
]);

$stmtCheck->close();
$stmtInsert->close();
$conn->close();
