<?php
require_once __DIR__ . '/../../src/config/config.php';
require_once __DIR__ . '/../../src/includes/auth.php';

global $auth, $db;
requireLogin();
if (!$auth->isFirmaAdmin()) {
    setError('Bu sayfaya erişim yetkiniz yok!');
    header('Location: /index.php');
    exit();
}

$currentUser = $auth->getCurrentUser();

$stmt = $db->prepare("SELECT * FROM Bus_Company WHERE id = :id");
$stmt->execute([':id' => $currentUser['company_id']]);
$company = $stmt->fetch();

$stmt = $db->prepare("
    SELECT 
        t.*,
        (SELECT COUNT(*) FROM Tickets ti 
         JOIN Booked_Seats bs ON ti.id = bs.ticket_id 
         WHERE ti.trip_id = t.id AND ti.status = 'ACTIVE') as booked_seats
    FROM Trips t
    WHERE t.company_id = :company_id
    ORDER BY t.departure_time DESC
");
$stmt->execute([':company_id' => $currentUser['company_id']]);
$trips = $stmt->fetchAll();

if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $tripId = (int)$_GET['delete'];
    $stmtCheck = $db->prepare("SELECT id FROM Trips WHERE id = :id AND company_id = :company_id");
    $stmtCheck->execute([':id' => $tripId, ':company_id' => $currentUser['company_id']]);
    if ($stmtCheck->fetch()) {
        try {
            $stmtDelete = $db->prepare("DELETE FROM Trips WHERE id = :id");
            $stmtDelete->execute([':id' => $tripId]);
            setSuccess('Sefer başarıyla silindi!');
        } catch (Exception $e) {
            setError('Sefer silinirken bir hata oluştu!');
        }
    } else {
        setError('Bu seferi silme yetkiniz yok!');
    }
    header('Location: /firma-admin/index.php');
    exit();
}

$pageTitle = 'Firma Admin Paneli';
require_once __DIR__ . '/../../src/includes/header.php';
?>

<div class="card">
    <h2>🏢 Firma Admin Paneli</h2>
    <div style="background: #f8f9fa; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
        <h3><?php echo clean($company['name']); ?></h3>
        <p style="margin: 5px 0;"><strong>Admin:</strong> <?php echo clean($currentUser['full_name']); ?></p>
    </div>
    <a href="/firma-admin/sefer-ekle.php" class="btn btn-success" style="margin-bottom: 20px;">➕ Yeni Sefer Ekle</a>
</div>

<div class="card">
    <h2>📋 Seferlerim</h2>
    <?php if (count($trips) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Güzergah</th>
                    <th>Kalkış</th>
                    <th>Varış</th>
                    <th>Fiyat</th>
                    <th>Dolu/Kapasite</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($trips as $trip): ?>
                    <?php $availableSeats = $trip['capacity'] - $trip['booked_seats']; ?>
                    <tr>
                        <td><strong><?php echo clean($trip['departure_city']); ?></strong> → <strong><?php echo clean($trip['destination_city']); ?></strong></td>
                        <td><?php echo date('d.m.Y H:i', strtotime($trip['departure_time'])); ?></td>
                        <td><?php echo date('d.m.Y H:i', strtotime($trip['arrival_time'])); ?></td>
                        <td><?php echo formatPrice($trip['price']); ?></td>
                        <td><span style="color: <?php echo $availableSeats > 0 ? 'green' : 'red'; ?>;"><?php echo $trip['booked_seats']; ?> / <?php echo $trip['capacity']; ?></span></td>
                        <td>
                            <a href="/firma-admin/sefer-duzenle.php?id=<?php echo $trip['id']; ?>" class="btn btn-primary" style="padding: 8px 15px; font-size: 12px;">✏️ Düzenle</a>
                            <a href="/firma-admin/index.php?delete=<?php echo $trip['id']; ?>" class="btn btn-danger" style="padding: 8px 15px; font-size: 12px;" onclick="return confirm('Bu seferi silmek istediğinize emin misiniz?');">🗑️ Sil</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info">Henüz sefer eklenmemiş. <a href="/firma-admin/sefer-ekle.php">Yeni sefer ekleyin</a>.</div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../src/includes/footer.php'; ?>