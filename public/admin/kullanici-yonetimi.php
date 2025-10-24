<?php
require_once __DIR__ . '/../../src/config/config.php';
require_once __DIR__ . '/../../src/includes/auth.php';

requireLogin();
if (!isAdmin()) {
    setError('Bu sayfaya erişim yetkiniz yok!');
    header('Location: /index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_admin'])) {
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $companyId = (int)$_POST['company_id'];
    
    if (empty($fullName) || empty($email) || empty($password)) {
        setError('Tüm alanları doldurmanız gerekmektedir!');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setError('Geçerli bir e-posta adresi giriniz!');
    } else {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("
                INSERT INTO User (full_name, email, password, role, company_id, balance) 
                VALUES (:full_name, :email, :password, 'company_admin', :company_id, 0)
            ");
            $stmt->execute([
                ':full_name' => $fullName,
                ':email' => $email,
                ':password' => $hashedPassword,
                ':company_id' => $companyId
            ]);
            setSuccess('Firma Admin başarıyla eklendi!');
        } catch (Exception $e) {
            setError('Bu e-posta adresi zaten kullanılıyor!');
        }
    }
    
    header('Location: /admin/kullanici-yonetimi.php');
    exit();
}

if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $userId = (int)$_GET['delete'];
    
    try {
        $stmt = $db->prepare("DELETE FROM User WHERE id = :id AND role != 'admin'");
        $stmt->execute([':id' => $userId]);
        setSuccess('Kullanıcı başarıyla silindi!');
    } catch (Exception $e) {
        setError('Kullanıcı silinirken bir hata oluştu!');
    }
    
    header('Location: /admin/kullanici-yonetimi.php');
    exit();
}

$stmtCompanies = $db->query("SELECT * FROM Bus_Company ORDER BY name ASC");
$companies = $stmtCompanies->fetchAll();

$stmt = $db->query("
    SELECT 
        u.*,
        bc.name as company_name
    FROM User u
    LEFT JOIN Bus_Company bc ON u.company_id = bc.id
    ORDER BY u.created_at DESC
");
$users = $stmt->fetchAll();

$pageTitle = 'Kullanıcı Yönetimi';
require_once __DIR__ . '/../../src/includes/header.php';
?>

<div class="card">
    <h2>👥 Kullanıcı Yönetimi</h2>
    
    <form method="POST" action="" style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
        <h3>Yeni Firma Admin Ekle</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div class="form-group">
                <label for="full_name">Ad Soyad:</label>
                <input type="text" id="full_name" name="full_name" required>
            </div>
            
            <div class="form-group">
                <label for="email">E-posta:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Şifre:</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>
            
            <div class="form-group">
                <label for="company_id">Firma:</label>
                <select id="company_id" name="company_id" required>
                    <option value="">Firma Seçiniz</option>
                    <?php foreach ($companies as $company): ?>
                        <option value="<?php echo $company['id']; ?>">
                            <?php echo clean($company['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <button type="submit" name="add_admin" class="btn btn-success">➕ Firma Admin Ekle</button>
    </form>
    
    <h3>Tüm Kullanıcılar</h3>
    
    <?php if (count($users) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Ad Soyad</th>
                    <th>E-posta</th>
                    <th>Rol</th>
                    <th>Firma</th>
                    <th>Bakiye</th>
                    <th>Kayıt Tarihi</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo clean($user['full_name']); ?></td>
                        <td><?php echo clean($user['email']); ?></td>
                        <td>
                            <span style="padding: 5px 10px; border-radius: 5px; background: <?php 
                                echo $user['role'] === 'admin' ? '#667eea' : ($user['role'] === 'company_admin' ? '#28a745' : '#ffc107'); 
                            ?>; color: white; font-size: 12px;">
                                <?php 
                                    echo $user['role'] === 'admin' ? 'Admin' : ($user['role'] === 'company_admin' ? 'Firma Admin' : 'Kullanıcı'); 
                                ?>
                            </span>
                        </td>
                        <td><?php echo $user['company_name'] ? clean($user['company_name']) : '-'; ?></td>
                        <td><?php echo formatPrice($user['balance']); ?></td>
                        <td><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></td>
                        <td>
                            <?php if ($user['role'] !== 'admin'): ?>
                                <a href="/admin/kullanici-yonetimi.php?delete=<?php echo $user['id']; ?>" 
                                   class="btn btn-danger" style="padding: 8px 15px; font-size: 12px;"
                                   onclick="return confirm('Bu kullanıcıyı silmek istediğinize emin misiniz?');">
                                    🗑️ Sil
                                </a>
                            <?php else: ?>
                                <span style="color: #999; font-size: 12px;">Silinemez</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info">Henüz kullanıcı bulunmamaktadır.</div>
    <?php endif; ?>
</div>

<a href="/admin/index.php" class="btn btn-secondary">← Geri Dön</a>

<?php require_once __DIR__ . '/../../src/includes/footer.php'; ?>