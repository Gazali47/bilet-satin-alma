<?php
require_once __DIR__ . '/../../src/config/config.php';

// Sadece admin rolü erişebilir
requireLogin();
if (!isAdmin()) {
    setError('Bu sayfaya erişim yetkiniz yok!');
    header('Location: /index.php');
    exit();
}

// İstatistikler
$stats = [];

// Toplam firma sayısı
$stmt = $db->query("SELECT COUNT(*) as count FROM Bus_Company");
$stats['companies'] = $stmt->fetch()['count'];

// Toplam kullanıcı sayısı
$stmt = $db->query("SELECT COUNT(*) as count FROM User WHERE role = 'user'");
$stats['users'] = $stmt->fetch()['count'];

// Toplam sefer sayısı
$stmt = $db->query("SELECT COUNT(*) as count FROM Trips");
$stats['trips'] = $stmt->fetch()['count'];

// Toplam bilet satışı
$stmt = $db->query("SELECT COUNT(*) as count FROM Tickets WHERE status = 'ACTIVE'");
$stats['tickets'] = $stmt->fetch()['count'];

// Toplam gelir
$stmt = $db->query("SELECT SUM(total_price) as total FROM Tickets WHERE status = 'ACTIVE'");
$stats['revenue'] = $stmt->fetch()['total'] ?? 0;

// Son biletler
$stmt = $db->query("
    SELECT 
        ti.*,
        u.full_name,
        u.email,
        tr.departure_city,
        tr.destination_city,
        bc.name as company_name
    FROM Tickets ti
    JOIN User u ON ti.user_id = u.id
    JOIN Trips tr ON ti.trip_id = tr.id
    JOIN Bus_Company bc ON tr.company_id = bc.id
    ORDER BY ti.created_at DESC
    LIMIT 10
");
$recentTickets = $stmt->fetchAll();

$pageTitle = 'Admin Paneli';
require_once __DIR__ . '/../../src/includes/header.php';
?>

<div class="card">
    <h2>👨‍💼 Admin Paneli</h2>
    <p>Hoş geldiniz, <strong><?php echo clean($_SESSION['full_name']); ?></strong></p>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px;">
    <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <h3 style="margin: 0; font-size: 36px;"><?php echo $stats['companies']; ?></h3>
        <p style="margin: 5px 0;">Toplam Firma</p>
    </div>
    
    <div class="card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
        <h3 style="margin: 0; font-size: 36px;"><?php echo $stats['users']; ?></h3>
        <p style="margin: 5px 0;">Toplam Kullanıcı</p>
    </div>
    
    <div class="card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
        <h3 style="margin: 0; font-size: 36px;"><?php echo $stats['trips']; ?></h3>
        <p style="margin: 5px 0;">Toplam Sefer</p>
    </div>
    
    <div class="card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white;">
        <h3 style="margin: 0; font-size: 36px;"><?php echo $stats['tickets']; ?></h3>
        <p style="margin: 5px 0;">Satılan Bilet</p>
    </div>
    
    <div class="card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white;">
        <h3 style="margin: 0; font-size: 28px;"><?php echo formatPrice($stats['revenue']); ?></h3>
        <p style="margin: 5px 0;">Toplam Gelir</p>
    </div>
</div>

<div class="card">
    <h3>🔧 Yönetim Panelleri</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-top: 20px;">
        <a href="/admin/firma-yonetimi.php" class="btn btn-primary" style="text-align: center; padding: 20px;">
            🏢 Firma Yönetimi
        </a>
        <a href="/admin/kullanici-yonetimi.php" class="btn btn-success" style="text-align: center; padding: 20px;">
            👥 Kullanıcı Yönetimi
        </a>
        <a href="/admin/kupon-yonetimi.php" class="btn btn-secondary" style="text-align: center; padding: 20px;">
            🎫 Kupon Yönetimi
        </a>
    </div>
</div>

<div class="card">
    <h3>🎫 Son Bilet Satışları</h3>
    
    <?php if (count($recentTickets) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Bilet No</th>
                    <th>Yolcu</th>
                    <th>Firma</th>
                    <th>Güzergah</th>
                    <th>Tutar</th>
                    <th>Durum</th>
                    <th>Tarih</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentTickets as $ticket): ?>
                    <tr>
                        <td>#<?php echo str_pad($ticket['id'], 6, '0', STR_PAD_LEFT); ?></td>
                        <td><?php echo clean($ticket['full_name']); ?></td>
                        <td><?php echo clean($ticket['company_name']); ?></td>
                        <td>
                            <?php echo clean($ticket['departure_city']); ?> → 
                            <?php echo clean($ticket['destination_city']); ?>
                        </td>
                        <td><?php echo formatPrice($ticket['total_price']); ?></td>
                        <td>
                            <span style="padding: 5px 10px; border-radius: 5px; background: <?php echo $ticket['status'] === 'ACTIVE' ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $ticket['status'] === 'ACTIVE' ? '#155724' : '#721c24'; ?>; font-size: 12px;">
                                <?php echo $ticket['status'] === 'ACTIVE' ? 'Aktif' : 'İptal'; ?>
                            </span>
                        </td>
                        <td><?php echo date('d.m.Y H:i', strtotime($ticket['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info">Henüz bilet satışı bulunmamaktadır.</div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../src/includes/footer.php'; ?>