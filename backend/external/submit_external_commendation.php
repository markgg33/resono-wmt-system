<?php
require "../connection_db.php";

$data = json_decode(file_get_contents("php://input"), true);

// ✅ SAFE EXTRACTION
$user_ids = $data['user_ids'] ?? [];
$value = $data['value_id'] ?? null;
$points = $data['points'] ?? null;
$reason = $data['reason'] ?? '';
$email = $data['email'] ?? '';
$name = $data['name'] ?? '';

// ✅ VALIDATION
if (empty($user_ids) || !$value || !$points || !$reason) {
    echo json_encode([
        "status" => "error",
        "message" => "Missing required fields"
    ]);
    exit;
}

// LIMIT (3 per day)
$stmt = $conn->prepare("
SELECT COUNT(*) as total
FROM commendations
WHERE external_email = ?
AND DATE(created_at) = CURDATE()
");

$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if ($res['total'] >= 3) {
    echo json_encode([
        "status" => "error",
        "message" => "Daily limit reached"
    ]);
    exit;
}

$requestCode = "EXT-" . date("YmdHis") . "-" . bin2hex(random_bytes(3));

// ✅ INSERT LOOP
foreach ($user_ids as $to) {

    $stmt = $conn->prepare("
INSERT INTO commendations
(request_code, sender_type, type, from_user_id, to_user_id, value_id, points, reason, external_email, external_name, status)
VALUES (?, 'external','commend',NULL,?,?,?,?,?,?,'pending')
");

    $stmt->bind_param(
        "siiisss",
        $requestCode,
        $to,
        $value,
        $points,
        $reason,
        $email,
        $name
    );

    $stmt->execute();
}

echo json_encode(["status" => "success"]);
