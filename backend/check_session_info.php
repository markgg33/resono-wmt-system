<?php
require_once __DIR__ . '/session_config.php';
session_start();

// Optional: create or update a session variable for testing
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = date('Y-m-d H:i:s');
}

// Display relevant session info
echo "<pre>";
echo "🧠 SESSION INFO\n";
echo "----------------------------\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Name: " . session_name() . "\n";
echo "Session Save Path: " . session_save_path() . "\n";
echo "Session Status: " . session_status() . " (2 = Active)\n";
echo "Session Created At: " . $_SESSION['created'] . "\n\n";

echo "⚙️ CONFIGURATION\n";
echo "----------------------------\n";
echo "session.gc_maxlifetime: " . ini_get('session.gc_maxlifetime') . " seconds\n";
echo "session.cookie_lifetime: " . ini_get('session.cookie_lifetime') . " seconds\n";
echo "session.cookie_secure: " . (ini_get('session.cookie_secure') ? 'true' : 'false') . "\n";
echo "session.cookie_httponly: " . (ini_get('session.cookie_httponly') ? 'true' : 'false') . "\n";
echo "session.cookie_samesite: " . ini_get('session.cookie_samesite') . "\n";

echo "\n💾 CURRENT SESSION DATA\n";
print_r($_SESSION);

echo "</pre>";
