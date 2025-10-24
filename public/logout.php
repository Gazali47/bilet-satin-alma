<?php
require_once __DIR__ . '/../src/config/config.php';

session_destroy();
session_start();
setSuccess('Başarıyla çıkış yaptınız!');
header('Location: /index.php');
exit();
?>