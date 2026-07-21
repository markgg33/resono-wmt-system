<?php
session_start();
require_once "../connection_db.php";
header('Content-Type: application/json');

$from = (int)($_SESSION['user_id'] ?? 0);
$role = $_SESSION['role'] ?? null;

$type    = $_POST['type'] ?? null;
$userIds = $_POST['user_ids'] ?? [];
$value   = isset($_POST['value_id']) ? (int)$_POST['value_id'] : 0;
$points  = isset($_POST['points']) ? (int)$_POST['points'] : 0;
$reason  = trim($_POST['reason'] ?? '');

// In case user_ids arrives as JSON string
if (is_string($userIds)) {
    $decoded = json_decode($userIds, true);
    if (is_array($decoded)) $userIds = $decoded;
}

if (
    !$from ||
    !$type ||
    empty($userIds) ||
    !$value ||
    !$points ||
    $points < 1 ||
    empty($reason)
) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

// Only admin/executive/hr can deduct
if ($type === 'deduct' && !in_array($role, ['admin', 'executive', 'hr'], true)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

/* ---------- FILE UPLOAD ---------- */
$attachmentPath = null;

if (!empty($_FILES['attachment']['name'])) {
    $uploadDir = "../../uploads/commendations/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $filename = time() . "_" . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($_FILES['attachment']['name']));
    $target = $uploadDir . $filename;

    if (move_uploaded_file($_FILES['attachment']['tmp_name'], $target)) {
        $attachmentPath = $filename;
    }
}

/* ---------- REQUEST CODE (ONE PER SUBMISSION) ---------- */
$requestCode = "CMD-" . date("YmdHis") . "-" . bin2hex(random_bytes(3));

/* ---------- INSERT (TRANSACTION) ---------- */
$conn->begin_transaction();

try {
    $stmt = $conn->prepare("
      INSERT INTO commendations
        (request_code, type, from_user_id, to_user_id, value_id, points, reason, attachment)
      VALUES
        (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $inserted = 0;

    /*
    foreach ($userIds as $to) {
        $to = (int)$to;

        // skip self
        if ($to === $from) continue;

        $stmt->bind_param(
            "ssiiiiss",
            $requestCode,
            $type,
            $from,
            $to,
            $value,
            $points,
            $reason,
            $attachmentPath
        );

        if ($stmt->execute()) $inserted++;
    }*/

    //CANNOT DEDUCT POINTS FROM SELF LOGIC (WORKING)
    /*
    foreach ($userIds as $to) {
        $to = (int)$to;

        // ✅ New rule:
        // - Commend: allow self
        // - Deduct: disallow self (even if admin/executive/hr)
        if ($type === 'deduct' && $to === $from) {
            continue;
        }

        $stmt->bind_param(
            "ssiiiiss",
            $requestCode,
            $type,
            $from,
            $to,
            $value,
            $points,
            $reason,
            $attachmentPath
        );

        if ($stmt->execute()) $inserted++;
    }*/

    //REMOVE SELF DEDUCT LOGIC IF NOT APPROVED
    foreach ($userIds as $to) {
        $to = (int)$to;

        $stmt->bind_param(
            "ssiiiiss",
            $requestCode,   
            $type,
            $from,
            $to,
            $value,
            $points,
            $reason,
            $attachmentPath
        );

        if ($stmt->execute()) {
            $inserted++;
        }
    }


    $stmt->close();

    /*
    if ($inserted === 0) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'No commendation was created']);
        exit;
    }*/

    //CANNOT DEDUCT POINTS FROM SELF LOGIC
    /*
    if ($inserted === 0) {
        $conn->rollback();

        if ($type === 'deduct') {
            echo json_encode([
                'status' => 'error',
                'message' => 'You cannot deduct points from yourself.'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'No commendation was created'
            ]);
        }
        exit;
    }*/

    //REMOVE SELF DEDUCT LOGIC IF NOT APPROVED
    if ($inserted === 0) {
        $conn->rollback();
        echo json_encode([
            'status' => 'error',
            'message' => 'No commendation was created'
        ]);
        exit;
    }


    $conn->commit();
    echo json_encode(['status' => 'success', 'request_code' => $requestCode]);
} catch (Throwable $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => 'Insert failed: ' . $e->getMessage()]);
}
