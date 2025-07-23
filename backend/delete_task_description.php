<?php
require 'connection_db.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    http_response_code(400);
    exit("Invalid ID.");
}

$stmt = $conn->prepare("DELETE FROM task_descriptions WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$stmt->close();
$conn->close();
