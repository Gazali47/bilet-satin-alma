<?php
require_once __DIR__ . '/../src/config/config.php';

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