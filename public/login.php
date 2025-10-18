<?php
require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/includes/auth.php';

// Zaten giriş yapmışsa ana sayfaya yönlendir
if (isLoggedIn()) {
    header('Location: /index.php');
    exit();
}

// Form gönderildiyse
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        setError('E-posta ve şifre zorunludur!');
    } else {
        $result = $auth->login($email, $password);
        
        if ($result['success']) {
            setSuccess($result['message']);
            
            // Rol bazlı yönlendirme
            if ($result['role'] == 'admin') { // Admin
                header('Location: /admin/index.php');
            } elseif ($result['role'] == 'company_admin') { // Firma Admin
                header('Location: /firma-admin/index.php');
            } else { // User
                header('Location: /index.php');
            }
            exit();
        } else {
            setError($result['message']);
        }
    }
}

$pageTitle = 'Giriş Yap';
require_once __DIR__ . '/../src/includes/header.php';
?>

<div class="card" style="max-width: 500px; margin: 50px auto;">
    <h2>Giriş Yap</h2>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="email">E-posta:</label>
            <input type="email" id="email" name="email" required 
                   value="<?php echo isset($_POST['email']) ? clean($_POST['email']) : ''; ?>">
        </div>
        
        <div class="form-group">
            <label for="password">Şifre:</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%;">Giriş Yap</button>
        
        <p style="text-align: center; margin-top: 20px;">
            Hesabınız yok mu? <a href="/register.php">Kayıt Ol</a>
        </p>
        
        <div class="alert alert-info" style="margin-top: 20px;">
            <strong>Demo Hesap:</strong><br>
            E-posta: admin@bilet.com<br>
            Şifre: admin123
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?>