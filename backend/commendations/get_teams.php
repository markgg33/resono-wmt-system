<?php
session_start();
require_once "../connection_db.php";
header('Content-Type: application/json');

$allowedRoles = ['admin', 'executive', 'hr'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowedRoles)) {
  http_response_code(403);
  exit;
}

/**
 * 1️⃣ Get teams
 */
$sql = "
  SELECT
    t.id,
    t.name,
    CONCAT(u.first_name, ' ', u.last_name) AS created_by
  FROM teams t
  JOIN users u ON t.created_by = u.id
  ORDER BY t.created_at DESC
";

$result = $conn->query($sql);

$teams = [];

while ($team = $result->fetch_assoc()) {

  /**
   * 2️⃣ Get members PER TEAM
   */
  $membersSql = "
      SELECT
        CONCAT(m.first_name, ' ', m.last_name) AS name,
        m.role,
        m.status
      FROM team_members tm
      JOIN users m ON tm.user_id = m.id
      WHERE tm.team_id = ?
      ORDER BY m.first_name
    ";

  $stmt = $conn->prepare($membersSql);
  $stmt->bind_param("i", $team['id']);
  $stmt->execute();
  $membersResult = $stmt->get_result();

  $members = [];
  while ($row = $membersResult->fetch_assoc()) {
    $members[] = $row;
  }

  /**
   * 3️⃣ Attach members array
   */
  $team['members'] = $members;
  $teams[] = $team;
}

echo json_encode($teams);
