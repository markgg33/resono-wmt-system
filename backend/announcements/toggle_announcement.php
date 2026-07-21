<?php

require_once "../connection_db.php";

$id = $_POST['id'];

$stmt = $conn->prepare("
UPDATE announcements
SET status = IF(status='active','inactive','active')
WHERE id=?
");

$stmt->bind_param("i", $id);
$stmt->execute();

echo json_encode([
    "success" => true
]);
