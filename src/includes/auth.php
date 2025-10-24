<?php

class Auth {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function register($fullName, $email, $password) {
        $stmt = $this->db->prepare("SELECT id FROM User WHERE email = :email");
        $stmt->execute([':email' => $email]);
        
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Bu e-posta adresi zaten kullanılıyor!'];
        }        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
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
    
    public function login($email, $password) {
        $stmt = $this->db->prepare("SELECT * FROM User WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['company_id'] = $user['company_id'];
            $_SESSION['balance'] = $user['balance'];
            
            return ['success' => true, 'message' => 'Giriş başarılı!', 'role' => $user['role']];
        }
        
        return ['success' => false, 'message' => 'E-posta veya şifre hatalı!'];
    }
    
    public function logout() {
        session_destroy();
        return ['success' => true, 'message' => 'Başarıyla çıkış yaptınız!'];
    }
    
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