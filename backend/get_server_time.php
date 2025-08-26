<?php
header('Content-Type: application/json');
date_default_timezone_set("Asia/Manila");

echo json_encode([
    "server_time" => date("c")
]);
