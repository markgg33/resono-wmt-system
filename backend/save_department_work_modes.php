<?php
include 'connection_db.php';

$data = json_decode(file_get_contents("php://input"), true);
$department_id = $data['department_id'];
$work_modes = $data['work_modes'];

if (!$department_id) {
  echo json_encode(["error" => "No department ID provided"]);
  exit;
}

// delete old assignments for this department only
$stmt = $conn->prepare("DELETE FROM department_work_modes WHERE department_id = ?");
$stmt->bind_param("i", $department_id);
$stmt->execute();
$stmt->close();

// insert new selected modes
if (!empty($work_modes)) {
  $stmt = $conn->prepare("INSERT INTO department_work_modes (department_id, work_mode_id) VALUES (?, ?)");
  foreach ($work_modes as $mode_id) {
    $stmt->bind_param("ii", $department_id, $mode_id);
    $stmt->execute();
  }
  $stmt->close();
}

echo json_encode(["success" => true]);
$conn->close();
?>
