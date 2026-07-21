<?php

require_once "../connection_db.php";

$id = $_POST['id'];

$stmt = $conn->prepare("SELECT image FROM announcements WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row && $row['image']) {
    $file = "../../uploads/announcements/" . $row['image'];
    if (file_exists($file)) {
        unlink($file);
    }
}

$stmt = $conn->prepare("DELETE FROM announcements WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();

echo json_encode([
    "success" => true
]);
