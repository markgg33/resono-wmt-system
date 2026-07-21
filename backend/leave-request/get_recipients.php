<?php
session_start();
require '../connection_db.php';

header('Content-Type: application/json');

$userId   = $_SESSION['user_id'] ?? null;
$requestId = intval($_GET['leave_id'] ?? 0);

try {
    $sql = "
        SELECT id, role, CONCAT(first_name, ' ', last_name) AS username
FROM users
WHERE role IN ('admin', 'executive', 'hr', 'supervisor')
  AND (
      id != ?
      OR id = (
          SELECT recipient_id
          FROM leave_requests
          WHERE id = ?
      )
  )
ORDER BY role, first_name

    ";

    $result = $conn->query($sql);
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $userId, $requestId);

    $recipients = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $recipients[] = $row;
        }
    }

    echo json_encode([
        "recipients" => $recipients
    ]);
} catch (Exception $e) {
    echo json_encode([
        "error" => $e->getMessage()
    ]);
}
