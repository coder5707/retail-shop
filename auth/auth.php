<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load config for WEB_BASE constant
if (!defined('WEB_BASE')) {
    require_once __DIR__ . '/../config/config.php';
}

if (!isset($_SESSION['user_id'])) {
    header("Location: " . WEB_BASE . "/auth/login.php");
    exit;
}
