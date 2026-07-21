<?php
// =======================================================
// 🕒 SESSION SETTINGS (12-hour absolute, 9-hour idle)
// =======================================================

// Absolute session lifetime = 12 * 60 * 60 = 43,200 seconds
// Idle timeout (handled in JS) = 9 * 60 * 60 = 32,400 seconds
$lifetime = 43200;

ini_set('session.gc_maxlifetime', $lifetime);
ini_set('session.cookie_lifetime', $lifetime);

session_set_cookie_params([
    'lifetime' => $lifetime,
    'path' => '/',
    'httponly' => true,
    'secure' => isset($_SERVER['HTTPS']), // use secure flag if using HTTPS
    'samesite' => 'Lax'
]);
