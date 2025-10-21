<?php
// Oturum başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Temel ayarlar
define('SITE_URL', 'http://localhost:8080');
define('SITE_NAME', 'Bilet Satın Alma Platformu');

// Veritabanını dahil et
require_once __DIR__ . '/database.php';

// Yardımcı fonksiyonları dahil et
require_once __DIR__ . '/../utils/functions.php';

// Hata ayıklama (production'da kapatılmalı)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);