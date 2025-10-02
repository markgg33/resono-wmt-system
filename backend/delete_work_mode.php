<?php
require_once "connection_db.php"; // this should create $conn = new mysqli(...)

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = intval($_POST["id"] ?? 0);

    if ($id <= 0) {
        echo json_encode(["error" => "Invalid ID"]);
        exit;
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Delete tasks first
        $stmt = $conn->prepare("DELETE FROM task_descriptions WHERE work_mode_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        // Delete work mode
        $stmt = $conn->prepare("DELETE FROM work_modes WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        // Commit transaction
        $conn->commit();

        echo json_encode(["success" => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["error" => $e->getMessage()]);
    }
}
