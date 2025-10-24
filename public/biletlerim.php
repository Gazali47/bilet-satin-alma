<?php
require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/includes/auth.php';

requireLogin();
if (!isUser()) {
    setError('Bu sayfaya erişim yetkiniz yok!');
    header('Location: /index.php');
    exit();
}

$currentUser = $auth->getCurrentUser();

if (isset($_GET['cancel']) && !empty($_GET['cancel'])) {
    $ticketId = (int)$_GET['cancel'];
    
    $stmt = $db->prepare("
        SELECT ti.*, tr.departure_time, tr.price 
        FROM Tickets ti
        JOIN Trips tr ON ti.trip_id = tr.id
        WHERE ti.id = :ticket_id AND ti.user_id = :user_id AND ti.status = 'ACTIVE'
    ");
    $stmt->execute([':ticket_id' => $ticketId, ':user_id' => $currentUser['id']]);
    $ticket = $stmt->fetch();
    
    if ($ticket) {
        if (canCancelTicket(date('Y-m-d', strtotime($ticket['departure_time'])), date('H:i:s', strtotime($ticket['departure_time'])))) {
            try {
                $db->beginTransaction();
                
                $stmtCancel = $db->prepare("UPDATE Tickets SET status = 'CANCELLED' WHERE id = :id");
                $stmtCancel->execute([':id' => $ticketId]);
                
                $stmtRefund = $db->prepare("UPDATE User SET balance = balance + :amount WHERE id = :user_id");
                $stmtRefund->execute([':amount' => $ticket['total_price'], ':user_id' => $currentUser['id']]);
                
                $db->commit();
                
                $_SESSION['balance'] = $currentUser['balance'] + $ticket['total_price'];
                
                setSuccess('Bilet başarıyla iptal edildi. ' . formatPrice($ticket['total_price']) . ' hesabınıza iade edildi.');
            } catch (Exception $e) {
                $db->rollBack();
                setError('İptal işlemi sırasında bir hata oluştu!');
            }
        } else {
            setError('Kalkışa 1 saatten az kaldığı için bilet iptal edilemez!');
        }
    } else {
        setError('Bilet bulunamadı!');
    }
    
    header('Location: /biletlerim.php');
    exit();
}

$stmt = $db->prepare("
    SELECT 
        ti.*,
        tr.departure_city,
        tr.destination_city,
        tr.departure_time,
        tr.arrival_time,
        tr.price as seat_price,
        bc.name as company_name,
        GROUP_CONCAT(bs.seat_number, ', ') as seat_numbers
    FROM Tickets ti
    JOIN Trips tr ON ti.trip_id = tr.id
    JOIN Bus_Company bc ON tr.company_id = bc.id
    LEFT JOIN Booked_Seats bs ON ti.id = bs.ticket_id
    WHERE ti.user_id = :user_id
    GROUP BY ti.id
    ORDER BY ti.created_at DESC
");
$stmt->execute([':user_id' => $currentUser['id']]);
$tickets = $stmt->fetchAll();

$pageTitle = 'Biletlerim';
require_once __DIR__ . '/../src/includes/header.php';
?>

<div class="card">
    <h2>🎫 Biletlerim</h2>
    
    <div style="background: #f8f9fa; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
        <p style="margin: 5px 0;"><strong>Ad Soyad:</strong> <?php echo clean($currentUser['full_name']); ?></p>
        <p style="margin: 5px 0;"><strong>E-posta:</strong> <?php echo clean($currentUser['email']); ?></p>
        <p style="margin: 5px 0;"><strong>Bakiye:</strong> <span style="color: green; font-weight: bold;"><?php echo formatPrice($currentUser['balance']); ?></span></p>
    </div>
    
    <?php if (count($tickets) > 0): ?>
        <?php foreach ($tickets as $ticket): ?>
            <?php
            $canCancel = $ticket['status'] === 'ACTIVE' && 
                         canCancelTicket(date('Y-m-d', strtotime($ticket['departure_time'])), 
                                       date('H:i:s', strtotime($ticket['departure_time'])));
            ?>
            <div class="card" style="background: <?php echo $ticket['status'] === 'ACTIVE' ? '#fff' : '#f8f9fa'; ?>; border-left: 5px solid <?php echo $ticket['status'] === 'ACTIVE' ? '#28a745' : '#dc3545'; ?>;">
                <div style="display: grid; grid-template-columns: 1fr auto; gap: 20px;">
                    <div>
                        <h3 style="margin-bottom: 10px;">
                            <?php echo clean($ticket['company_name']); ?>
                            <span style="font-size: 14px; padding: 5px 10px; border-radius: 5px; background: <?php echo $ticket['status'] === 'ACTIVE' ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $ticket['status'] === 'ACTIVE' ? '#155724' : '#721c24'; ?>;">
                                <?php echo $ticket['status'] === 'ACTIVE' ? 'Aktif' : 'İptal Edildi'; ?>
                            </span>
                        </h3>
                        
                        <p style="margin: 5px 0; font-size: 16px;">
                            <strong><?php echo clean($ticket['departure_city']); ?></strong> 
                            → 
                            <strong><?php echo clean($ticket['destination_city']); ?></strong>
                        </p>
                        
                        <p style="margin: 5px 0;">
                            📅 Kalkış: <?php echo date('d.m.Y H:i', strtotime($ticket['departure_time'])); ?>
                        </p>
                        
                        <p style="margin: 5px 0;">
                            📅 Varış: <?php echo date('d.m.Y H:i', strtotime($ticket['arrival_time'])); ?>
                        </p>
                        
                        <p style="margin: 5px 0;">
                            💺 Koltuk No: <strong><?php echo clean($ticket['seat_numbers']); ?></strong>
                        </p>
                        
                        <p style="margin: 5px 0;">
                            💰 Ödenen: <strong><?php echo formatPrice($ticket['total_price']); ?></strong>
                        </p>
                        
                        <p style="margin: 5px 0; font-size: 12px; color: #666;">
                            Satın Alma Tarihi: <?php echo date('d.m.Y H:i', strtotime($ticket['created_at'])); ?>
                        </p>
                    </div>
                    
                    <div style="text-align: right;">
                        <?php if ($ticket['status'] === 'ACTIVE'): ?>
                            <a href="/bilet-pdf.php?id=<?php echo $ticket['id']; ?>" 
                               class="btn btn-primary" style="margin-bottom: 10px; display: block;">
                                📄 PDF İndir
                            </a>
                            
                            <?php if ($canCancel): ?>
                                <a href="/biletlerim.php?cancel=<?php echo $ticket['id']; ?>" 
                                   class="btn btn-danger"
                                   onclick="return confirm('Bu bileti iptal etmek istediğinize emin misiniz? Para iadeniz yapılacaktır.');">
                                    ❌ İptal Et
                                </a>
                            <?php else: ?>
                                <button class="btn btn-secondary" disabled>
                                    ⏰ İptal Süresi Geçti
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="alert alert-info">
            Henüz biletiniz bulunmamaktadır. <a href="/index.php">Sefer araması yapın</a>.
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?>