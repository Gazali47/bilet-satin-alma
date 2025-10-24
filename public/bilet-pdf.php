<?php
require_once __DIR__ . '/../src/config/config.php';

requireLogin();
if (!isUser()) {
    die('Yetkisiz erişim!');
}

$currentUser = $auth->getCurrentUser();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('Geçersiz bilet!');
}

$ticketId = (int)$_GET['id'];

$stmt = $db->prepare(" 
    SELECT 
        ti.*,
        tr.departure_city,
        tr.destination_city,
        tr.departure_time,
        tr.arrival_time,
        bc.name as company_name,
        u.full_name,
        u.email,
        GROUP_CONCAT(bs.seat_number, ', ') as seat_numbers
    FROM Tickets ti
    JOIN Trips tr ON ti.trip_id = tr.id
    JOIN Bus_Company bc ON tr.company_id = bc.id
    JOIN User u ON ti.user_id = u.id
    LEFT JOIN Booked_Seats bs ON ti.id = bs.ticket_id
    WHERE ti.id = :ticket_id AND ti.user_id = :user_id AND ti.status = 'ACTIVE'
    GROUP BY ti.id
");
$stmt->execute([':ticket_id' => $ticketId, ':user_id' => $currentUser['id']]);
$ticket = $stmt->fetch();

if (!$ticket) {
    die('Bilet bulunamadı veya iptal edilmiş!');
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="bilet_' . $ticketId . '.pdf"');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 40px;
            background: white;
        }
        .ticket {
            border: 3px solid #667eea;
            border-radius: 15px;
            padding: 30px;
            max-width: 600px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            border-bottom: 2px dashed #667eea;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #667eea;
            margin: 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .info-label {
            font-weight: bold;
            color: #555;
        }
        .info-value {
            color: #333;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px dashed #667eea;
            color: #666;
            font-size: 12px;
        }
        .qr-code {
            text-align: center;
            margin: 20px 0;
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="header">
            <h1>🚌 OTOBÜS BİLETİ</h1>
            <p style="margin: 5px 0; color: #666;">Bilet No: #<?php echo str_pad($ticket['id'], 6, '0', STR_PAD_LEFT); ?></p>
        </div>
        
        <div class="info-row">
            <span class="info-label">Firma:</span>
            <span class="info-value"><?php echo clean($ticket['company_name']); ?></span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Yolcu:</span>
            <span class="info-value"><?php echo clean($ticket['full_name']); ?></span>
        </div>
        
        <div class="info-row">
            <span class="info-label">E-posta:</span>
            <span class="info-value"><?php echo clean($ticket['email']); ?></span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Kalkış:</span>
            <span class="info-value"><?php echo clean($ticket['departure_city']); ?></span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Varış:</span>
            <span class="info-value"><?php echo clean($ticket['destination_city']); ?></span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Kalkış Zamanı:</span>
            <span class="info-value"><?php echo date('d.m.Y H:i', strtotime($ticket['departure_time'])); ?></span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Varış Zamanı:</span>
            <span class="info-value"><?php echo date('d.m.Y H:i', strtotime($ticket['arrival_time'])); ?></span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Koltuk No:</span>
            <span class="info-value" style="font-weight: bold; color: #667eea;"><?php echo clean($ticket['seat_numbers']); ?></span>
        </div>
        
        <div class="info-row" style="border-bottom: none;">
            <span class="info-label">Toplam Ücret:</span>
            <span class="info-value" style="font-weight: bold; color: #28a745; font-size: 18px;"><?php echo formatPrice($ticket['total_price']); ?></span>
        </div>
        
        <div class="qr-code">
            ▣ TICKET-<?php echo str_pad($ticket['id'], 6, '0', STR_PAD_LEFT); ?>
        </div>
        
        <div class="footer">
            <p>Bu bilet <?php echo date('d.m.Y H:i', strtotime($ticket['created_at'])); ?> tarihinde oluşturulmuştur.</p>
            <p>İyi yolculuklar dileriz!</p>
        </div>
    </div>
    
    <script>        
        window.onload = function() {
            window.print(); 
        }
    </script>
</body>
</html>