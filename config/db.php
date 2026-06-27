<?php
require_once __DIR__ . '/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("<div style='font-family:Arial;padding:20px;color:#c0392b;'>
         <strong>Database Connection Failed:</strong> " . htmlspecialchars($conn->connect_error) . "
         <br><small>Check config.php and ensure MySQL is running.</small>
         </div>");
}

$conn->set_charset('utf8mb4');
