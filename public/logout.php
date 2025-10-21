<?php
require_once __DIR__ . '/../src/config/config.php';

// Oturumu sonlandır
session_destroy();

// Başarı mesajı için yeni oturum başlat
session_start();
setSuccess('Başarıyla çıkış yaptınız!');

header('Location: /index.php');
exit();
?>