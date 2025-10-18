<?php

class Auth {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    // Kullanıcı kaydı
    public function register($fullName, $email, $password) {
        // E-posta zaten var mı?
        $stmt = $this->db->prepare("SELECT id FROM User WHERE email = :email");
        $stmt->execute([':email' => $email]);
        
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Bu e-posta adresi zaten kullanılıyor!'];
        }
        
        // Şifreyi hashle
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Yeni kullanıcı oluştur (role = user)
        $stmt = $this->db->prepare("
            INSERT INTO User (full_name, email, password, role, balance) 
            VALUES (:full_name, :email, :password, 'user', 1000.0)
        ");
        
        try {
            $stmt->execute([
                ':full_name' => $fullName,
                ':email' => $email,
                ':password' => $hashedPassword
            ]);
            
            return ['success' => true, 'message' => 'Kayıt başarılı! Giriş yapabilirsiniz.'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Kayıt sırasında bir hata oluştu!'];
        }
    }
    
    // Kullanıcı girişi
    public function login($email, $password) {
        $stmt = $this->db->prepare("SELECT * FROM User WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Oturum bilgilerini ayarla
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role']; // admin, company_admin, user
            $_SESSION['company_id'] = $user['company_id'];
            $_SESSION['balance'] = $user['balance'];
            
            return ['success' => true, 'message' => 'Giriş başarılı!', 'role' => $user['role']];
        }
        
        return ['success' => false, 'message' => 'E-posta veya şifre hatalı!'];
    }
    
    // Çıkış yap
    public function logout() {
        session_destroy();
        return ['success' => true, 'message' => 'Başarıyla çıkış yaptınız!'];
    }
    
    // Mevcut kullanıcı bilgilerini al
    public function getCurrentUser() {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        
        $stmt = $this->db->prepare("SELECT * FROM User WHERE id = :id");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        return $stmt->fetch();
    }
}

$auth = new Auth($db);