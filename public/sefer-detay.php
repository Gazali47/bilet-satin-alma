<?php
require_once __DIR__ . '/../src/config/config.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    setError('Geçersiz sefer!');
    header('Location: /index.php');
    exit();
}

$tripId = (int)$_GET['id'];

$stmt = $db->prepare("
    SELECT t.*, b.name as company_name, b.logo_path 
    FROM Trips t 
    JOIN Bus_Company b ON t.company_id = b.id 
    WHERE t.id = :id
");
$stmt->execute([':id' => $tripId]);
$trip = $stmt->fetch();

if (!$trip) {
    setError('Sefer bulunamadı!');
    header('Location: /index.php');
    exit();
}

$stmtSeats = $db->prepare("
    SELECT bs.seat_number 
    FROM Tickets ti
    JOIN Booked_Seats bs ON ti.id = bs.ticket_id
    WHERE ti.trip_id = :trip_id AND ti.status = 'ACTIVE'
");
$stmtSeats->execute([':trip_id' => $tripId]);
$bookedSeats = $stmtSeats->fetchAll(PDO::FETCH_COLUMN);
$availableSeats = $trip['capacity'] - count($bookedSeats);

$pageTitle = 'Sefer Detayı';
require_once __DIR__ . '/../src/includes/header.php';
?>

<div class="card">
    <h2>🚌 Sefer Detayı</h2>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
        <div>
            <h3><?php echo clean($trip['company_name']); ?></h3>
            
            <table style="box-shadow: none;">
                <tr>
                    <td><strong>Kalkış:</strong></td>
                    <td><?php echo clean($trip['departure_city']); ?></td>
                </tr>
                <tr>
                    <td><strong>Varış:</strong></td>
                    <td><?php echo clean($trip['destination_city']); ?></td>
                </tr>
                <tr>
                    <td><strong>Kalkış Zamanı:</strong></td>
                    <td><?php echo date('d.m.Y H:i', strtotime($trip['departure_time'])); ?></td>
                </tr>
                <tr>
                    <td><strong>Varış Zamanı:</strong></td>
                    <td><?php echo date('d.m.Y H:i', strtotime($trip['arrival_time'])); ?></td>
                </tr>
                <tr>
                    <td><strong>Fiyat:</strong></td>
                    <td><strong style="color: #667eea; font-size: 20px;"><?php echo formatPrice($trip['price']); ?></strong></td>
                </tr>
                <tr>
                    <td><strong>Müsait Koltuk:</strong></td>
                    <td>
                        <span style="color: <?php echo $availableSeats > 0 ? 'green' : 'red'; ?>; font-weight: bold;">
                            <?php echo $availableSeats; ?> / <?php echo $trip['capacity']; ?>
                        </span>
                    </td>
                </tr>
            </table>
            
            <?php if (isLoggedIn() && isUser()): ?>
                <?php if ($availableSeats > 0): ?>
                    <a href="/bilet-satin-al.php?trip_id=<?php echo $trip['id']; ?>" 
                       class="btn btn-success" style="width: 100%; margin-top: 20px;">
                        🎫 Bilet Satın Al
                    </a>
                <?php else: ?>
                    <button class="btn btn-secondary" disabled style="width: 100%; margin-top: 20px;">
                        Bu Sefer Dolu
                    </button>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info" style="margin-top: 20px;">
                    Bilet satın almak için <a href="/login.php">giriş yapmalısınız</a>.
                </div>
            <?php endif; ?>
        </div>
        
        <div>
            <h3>Koltuk Durumu</h3>
            <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                <div style="text-align: center; margin-bottom: 15px;">
                    <span style="display: inline-block; margin: 0 15px;">
                        <span style="display: inline-block; width: 20px; height: 20px; background: white; border: 2px solid #ddd; vertical-align: middle;"></span>
                        Boş
                    </span>
                    <span style="display: inline-block; margin: 0 15px;">
                        <span style="display: inline-block; width: 20px; height: 20px; background: #ddd; vertical-align: middle;"></span>
                        Dolu
                    </span>
                </div>
                
                <div class="seats-container">
                    <?php for ($i = 1; $i <= $trip['capacity']; $i++): ?>
                        <div class="seat <?php echo in_array($i, $bookedSeats) ? 'seat-taken' : ''; ?>">
                            <?php echo $i; ?>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>
    
    <a href="/index.php" class="btn btn-secondary" style="margin-top: 20px;">← Geri Dön</a>
</div>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?>