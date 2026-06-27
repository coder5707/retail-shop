<?php
// ============================================================
// DATABASE CONFIGURATION
// ============================================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // Change for production
define('DB_PASS', '');           // Change for production
define('DB_NAME', 'clothes_retail_db');  // New database name

// ============================================================
// BASE PATH — auto-detected so it works in any folder name
// ============================================================
define('BASE_PATH', rtrim(str_replace('\\', '/', dirname(dirname(__FILE__))), '/'));

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
// Walk up from current script to find project root (contains 'assets')
$parts = explode('/', trim($scriptDir, '/'));
$base  = '';
$root  = $_SERVER['DOCUMENT_ROOT'];
for ($i = count($parts); $i >= 0; $i--) {
    $try  = '/' . implode('/', array_slice($parts, 0, $i));
    $phys = $root . $try;
    if (file_exists($phys . '/assets/css/style.css')) {
        $base = $try;
        break;
    }
}
define('WEB_BASE', rtrim($base, '/'));
