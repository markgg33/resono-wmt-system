<?php

require_once "../connection_db.php";

$id = $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM announcements WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

echo json_encode($result->fetch_assoc());
