<?php
$data = json_decode(file_get_contents("php://input"), true);

$token = $data['token'];

$response = file_get_contents("https://oauth2.googleapis.com/tokeninfo?id_token=" . $token);
$payload = json_decode($response, true);

if (isset($payload['email'])) {
    echo json_encode([
        "status" => "success",
        "email" => $payload['email'],
        "name" => $payload['name']
    ]);
} else {
    echo json_encode(["status" => "error"]);
}
