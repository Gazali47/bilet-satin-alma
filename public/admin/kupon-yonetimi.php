<?php
require_once __DIR__ . '/../../src/config/config.php';

// Sadece admin rolü erişebilir
requireLogin();
if (!isAdmin()) {
    setError('Bu sayfaya erişim yetkiniz yok!');
    header('Location: /index.php');
    exit();
}

// Kupon ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_coupon'])) {
    $code = strtoupper(trim($_POST['code']));
    $discount = (float)$_POST['discount'];
    $usageLimit = (int)$_POST['usage_limit'];
    $expiryDate = trim($_POST['expire_date']);
    
    if (empty($code) || $discount <= 0 || $usageLimit <= 0 || empty($expiryDate)) {
        setError('Tüm alanları doğru şekilde doldurmanız gerekmektedir!');
    } elseif ($discount > 1) {
        setError('İndirim oranı 0-1 arasında olmalıdır! (Örn: 0.10 = %10)');
    } else {
        try {
            $stmt = $db->prepare("
                INSERT INTO Coupons (code, discount, usage_limit, expire_date) 
                VALUES (:code, :discount, :usage_limit, :expire_date)
            ");
            $stmt->execute([
                ':code' => $code,
                ':discount' => $discount,
                ':usage_limit' => $usageLimit,
                ':expire_date' => $expiryDate
            ]);
            setSuccess('Kupon başarıyla eklendi!');
        } catch (Exception $e) {
            setError('Bu kupon kodu zaten kullanılıyor!');
        }
    }
    
    header('Location: /admin/kupon-yonetimi.php');
    exit();
}

// Kupon silme
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $couponId = (int)$_GET['delete'];
    
    try {
        $stmt = $db->prepare("DELETE FROM Coupons WHERE id = :id");
        $stmt->execute([':id' => $couponId]);
        setSuccess('Kupon başarıyla silindi!');
    } catch (Exception $e) {
        setError('Kupon silinirken bir hata oluştu!');
    }
    
    header('Location: /admin/kupon-yonetimi.php');
    exit();
}

// Kuponları al
$stmt = $db->query("
    SELECT 
        c.*,
        (c.usage_limit - 
            (SELECT COUNT(*) FROM User_Coupons WHERE coupon_id = c.id)
        ) as remaining_usage
    FROM Coupons c
    ORDER BY c.created_at DESC
");
$coupons = $stmt->fetchAll();

$pageTitle = 'Kupon Yönetimi';
require_once __DIR__ . '/../../src/includes/header.php';
?>

<div class="card">
    <h2>🎫 Kupon Yönetimi</h2>
    
    <form method="POST" action="" style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
        <h3>Yeni Kupon Oluştur</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 15px;">
            <div class="form-group">
                <label for="code">Kupon Kodu:</label>
                <input type="text" id="code" name="code" required placeholder="WELCOME10" style="text-transform: uppercase;">
                <small style="color: #666;">Otomatik büyük harfe çevrilir</small>
            </div>
            
            <div class="form-group">
                <label for="discount">İndirim Oranı (0-1):</label>
                <input type="number" id="discount" name="discount" required min="0" max="1" step="0.01" placeholder="0.10">
                <small style="color: #666;">0.10 = %10 indirim</small>
            </div>
            
            <div class="form-group">
                <label for="usage_limit">Kullanım Limiti:</label>
                <input type="number" id="usage_limit" name="usage_limit" required min="1" placeholder="100">
            </div>
            
            <div class="form-group">
                <label for="expire_date">Son Kullanma Tarihi:</label>
                <input type="datetime-local" id="expire_date" name="expire_date" required>
            </div>
        </div>
        <button type="submit" name="add_coupon" class="btn btn-success">➕ Kupon Ekle</button>
    </form>
    
    <h3>Mevcut Kuponlar</h3>
    
    <?php if (count($coupons) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Kupon Kodu</th>
                    <th>İndirim</th>
                    <th>Kullanım Limiti</th>
                    <th>Kalan Kullanım</th>
                    <th>Son Kullanma</th>
                    <th>Durum</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($coupons as $coupon): ?>
                    <?php
                    $isExpired = strtotime($coupon['expire_date']) < time();
                    $isActive = !$isExpired && $coupon['remaining_usage'] > 0;
                    ?>
                    <tr style="<?php echo !$isActive ? 'opacity: 0.6;' : ''; ?>">
                        <td><?php echo $coupon['id']; ?></td>
                        <td>
                            <strong style="font-family: monospace; background: #f0f0f0; padding: 5px 10px; border-radius: 5px;">
                                <?php echo clean($coupon['code']); ?>
                            </strong>
                        </td>
                        <td>
                            <span style="color: #28a745; font-weight: bold;">
                                %<?php echo ($coupon['discount'] * 100); ?>
                            </span>
                        </td>
                        <td><?php echo $coupon['usage_limit']; ?></td>
                        <td>
                            <span style="color: <?php echo $coupon['remaining_usage'] > 0 ? 'green' : 'red'; ?>; font-weight: bold;">
                                <?php echo $coupon['remaining_usage']; ?>
                            </span>
                        </td>
                        <td><?php echo date('d.m.Y H:i', strtotime($coupon['expire_date'])); ?></td>
                        <td>
                            <?php if ($isActive): ?>
                                <span style="padding: 5px 10px; border-radius: 5px; background: #d4edda; color: #155724; font-size: 12px;">
                                    ✓ Aktif
                                </span>
                            <?php elseif ($isExpired): ?>
                                <span style="padding: 5px 10px; border-radius: 5px; background: #f8d7da; color: #721c24; font-size: 12px;">
                                    ✗ Süresi Dolmuş
                                </span>
                            <?php else: ?>
                                <span style="padding: 5px 10px; border-radius: 5px; background: #fff3cd; color: #856404; font-size: 12px;">
                                    ⚠ Tükendi
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="/admin/kupon-yonetimi.php?delete=<?php echo $coupon['id']; ?>" 
                               class="btn btn-danger" style="padding: 8px 15px; font-size: 12px;"
                               onclick="return confirm('Bu kuponu silmek istediğinize emin misiniz?');">
                                🗑️ Sil
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info">Henüz kupon oluşturulmamış.</div>
    <?php endif; ?>
</div>

<a href="/admin/index.php" class="btn btn-secondary">← Geri Dön</a>

<?php require_once __DIR__ . '/../../src/includes/footer.php'; ?>