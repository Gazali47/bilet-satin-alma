<?php

function clean($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

function isAdmin() {
    return hasRole('admin');
}

function isFirmaAdmin() {
    return hasRole('company_admin'); 
}

function isUser() {
    return hasRole('user');
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit();
    }
}

function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header('Location: /index.php');
        exit();
    }
}

function setSuccess($message) {
    $_SESSION['success_message'] = $message;
}

function setError($message) {
    $_SESSION['error_message'] = $message;
}

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

function formatDate($date) {
    return date('d.m.Y', strtotime($date));
}

function formatTime($time) {
    return date('H:i', strtotime($time));
}

function formatPrice($price) {
    return number_format($price, 2, ',', '.') . ' ₺';
}

function canCancelTicket($date, $time) {
    $departureTime = strtotime($date . ' ' . $time);
    $currentTime = time();
    $timeDiff = $departureTime - $currentTime;
    return $timeDiff > 3600;
}