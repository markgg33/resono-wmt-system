<?php
require_once "../connection_db.php";
header('Content-Type: application/json');

$teamId = intval($_GET['team_id'] ?? 0);

$sql = "
SELECT 
  u.id,
  u.first_name,
  u.last_name,
  u.role
FROM users u
WHERE u.status = 'active'
AND (
  NOT EXISTS (
    SELECT 1 FROM team_members tm
    WHERE tm.user_id = u.id
  )
  OR EXISTS (
    SELECT 1 FROM team_members tm
    WHERE tm.user_id = u.id AND tm.team_id = ?
  )
)
ORDER BY u.first_name
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teamId);
$stmt->execute();

echo json_encode(
    $stmt->get_result()->fetch_all(MYSQLI_ASSOC)
);
