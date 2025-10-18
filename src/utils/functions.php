<?php

// XSS koruması için
function clean($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Oturum kontrolü
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Kullanıcı rolü kontrolü (admin, company_admin, user)
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Admin mi?
function isAdmin() {
    return hasRole('admin');
}

// Firma Admin mi?
function isFirmaAdmin() {
    return hasRole('company_admin');
}

// User mi?
function isUser() {
    return hasRole('user');
}

// Yetkisiz erişimi engelle
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit();
    }
}

// Rol gerektir
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header('Location: /index.php');
        exit();
    }
}

// Başarı mesajı
function setSuccess($message) {
    $_SESSION['success_message'] = $message;
}

// Hata mesajı
function setError($message) {
    $_SESSION['error_message'] = $message;
}

// Mesaj göster ve temizle
function displayMessages() {
    $output = '';
    
    if (isset($_SESSION['success_message'])) {
        $output .= '<div class="alert alert-success">' . clean($_SESSION['success_message']) . '</div>';
        unset($_SESSION['success_message']);
    }
    
    if (isset($_SESSION['error_message'])) {
        $output .= '<div class="alert alert-danger">' . clean($_SESSION['error_message']) . '</div>';
        unset($_SESSION['error_message']);
    }
    
    return $output;
}

// Tarih formatla
function formatDate($date) {
    return date('d.m.Y', strtotime($date));
}

// Saat formatla
function formatTime($time) {
    return date('H:i', strtotime($time));
}

// Para formatla
function formatPrice($price) {
    return number_format($price, 2, ',', '.') . ' ₺';
}

// Sefer kalkış zamanına 1 saatten az kaldı mı?
function canCancelTicket($date, $time) {
    $departureTime = strtotime($date . ' ' . $time);
    $currentTime = time();
    $timeDiff = $departureTime - $currentTime;
    
    // 1 saat = 3600 saniye
    return $timeDiff > 3600;
}