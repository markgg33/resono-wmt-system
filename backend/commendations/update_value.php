<?php
session_start();
require_once "../connection_db.php";
header('Content-Type: application/json');

$allowedRoles = ['admin','executive','hr'];
if (!in_array($_SESSION['role'] ?? '', $allowedRoles)) {
  http_response_code(403);
  exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$id   = intval($data['id'] ?? 0);
$name = trim($data['name'] ?? '');
$desc = trim($data['description'] ?? '');

if ($id === 0 || $name === '') {
  echo json_encode(['status'=>'error']);
  exit;
}

$stmt = $conn->prepare(
  "UPDATE values_list SET name = ?, description = ? WHERE id = ?"
);
$stmt->bind_param("ssi", $name, $desc, $id);
$stmt->execute();

echo json_encode(['status'=>'success']);
