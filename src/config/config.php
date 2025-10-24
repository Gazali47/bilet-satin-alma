<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('SITE_URL', 'http://localhost:8080');
define('SITE_NAME', 'Bilet Satın Alma Platformu');
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../utils/functions.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);