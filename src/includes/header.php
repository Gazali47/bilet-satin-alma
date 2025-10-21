<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Bilet Satın Alma Platformu</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>🚌 Bilet Satın Alma Platformu</h1>
            <nav>
                <a href="/index.php">Ana Sayfa</a>
                
                <?php if (isLoggedIn()): ?>
                    
                    <?php if (isUser()): ?>
                        <a href="/biletlerim.php">Biletlerim</a>
                    <?php endif; ?>
                    
                    <?php if (isFirmaAdmin()): ?>
                        <a href="/firma-admin/index.php">Sefer Yönetimi</a>
                    <?php endif; ?>
                    
                    <?php if (isAdmin()): ?>
                        <a href="/admin/index.php">Admin Paneli</a>
                    <?php endif; ?>
                    
                    <a href="/logout.php">Çıkış Yap (<?php echo clean($_SESSION['email']); ?>)</a>
                    
                <?php else: ?>
                    <a href="/login.php">Giriş Yap</a>
                    <a href="/register.php">Kayıt Ol</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <?php echo displayMessages(); ?>