<?php
require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/includes/auth.php';

if (isLoggedIn()) {
    header('Location: /index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if (empty($fullName) || empty($email) || empty($password) || empty($confirmPassword)) {
        setError('Tüm alanları doldurmanız gerekmektedir!');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setError('Geçerli bir e-posta adresi giriniz!');
    } elseif (strlen($password) < 6) {
        setError('Şifre en az 6 karakter olmalıdır!');
    } elseif ($password !== $confirmPassword) {
        setError('Şifreler eşleşmiyor!');
    } else {
        $result = $auth->register($fullName, $email, $password);
        
        if ($result['success']) {
            setSuccess($result['message']);
            header('Location: /login.php');
            exit();
        } else {
            setError($result['message']);
        }
    }
}

$pageTitle = 'Kayıt Ol';
require_once __DIR__ . '/../src/includes/header.php';
?>

<div class="card" style="max-width: 500px; margin: 50px auto;">
    <h2>Kayıt Ol</h2>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="email">E-posta:</label>
            <input type="email" id="email" name="email" required 
                   value="<?php echo isset($_POST['email']) ? clean($_POST['email']) : ''; ?>">
        </div>
        
        <div class="form-group">
            <label for="password">Şifre:</label>
            <input type="password" id="password" name="password" required minlength="6">
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Şifre Tekrar:</label>
            <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%;">Kayıt Ol</button>
        
        <p style="text-align: center; margin-top: 20px;">
            Zaten hesabınız var mı? <a href="/login.php">Giriş Yap</a>
        </p>
    </form>
</div>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?>