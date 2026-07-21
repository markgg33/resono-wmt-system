<?php
require_once "../connection_db.php";
header('Content-Type: application/json');

$sql = "
SELECT 
  u.id,
  u.first_name,
  u.last_name,
  u.role
FROM users u
WHERE u.status = 'active'
AND NOT EXISTS (
  SELECT 1 
  FROM team_members tm 
  WHERE tm.user_id = u.id
)
ORDER BY u.first_name
";

$result = $conn->query($sql);
echo json_encode($result->fetch_all(MYSQLI_ASSOC));
