<?php
header('Content-Type: application/json');
date_default_timezone_set("Asia/Manila");

// 🕐 TIMEZONE FIX: Always return PH timezone regardless of device timezone
$current_time = new DateTime("now", new DateTimeZone("Asia/Manila"));
$server_timestamp = time(); // Unix timestamp (timezone-independent)

echo json_encode([
    "server_time" => $current_time->format("c"), // ISO 8601 with timezone
    "server_timestamp" => $server_timestamp, // Unix timestamp (timezone-independent)
    "timezone" => "Asia/Manila",
    "timezone_offset" => $current_time->format("P"), // +08:00
    "formatted_time" => $current_time->format("Y-m-d H:i:s"), // YYYY-MM-DD HH:MM:SS in PH timezone
    "time_parts" => [
      "date" => $current_time->format("Y-m-d"),
      "time" => $current_time->format("H:i:s")
    ]
]);

