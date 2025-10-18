<?php
require_once __DIR__ . '/../src/config/config.php';

// Sefer arama
$trips = [];
$searchPerformed = false;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && (isset($_GET['departure']) || isset($_GET['destination']))) {
    $departure = isset($_GET['departure']) ? trim($_GET['departure']) : '';
    $destination = isset($_GET['destination']) ? trim($_GET['destination']) : '';
    $date = isset($_GET['date']) ? trim($_GET['date']) : '';
    
    $searchPerformed = true;
    
    $query = "SELECT t.*, b.name as company_name 
              FROM Trips t 
              JOIN Bus_Company b ON t.company_id = b.id 
              WHERE 1=1";
    
    $params = [];
    
    if (!empty($departure)) {
        $query .= " AND t.departure_city LIKE :departure";
        $params[':departure'] = "%$departure%";
    }
    
    if (!empty($destination)) {
        $query .= " AND t.destination_city LIKE :destination";
        $params[':destination'] = "%$destination%";
    }
    
    if (!empty($date)) {
        $query .= " AND DATE(t.departure_time) = :date";
        $params[':date'] = $date;
    }
    
    $query .= " ORDER BY t.departure_time ASC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $trips = $stmt->fetchAll();
}

$pageTitle = 'Ana Sayfa';
require_once __DIR__ . '/../src/includes/header.php';
?>

<div class="card">
    <h2>🔍 Sefer Ara</h2>
    <form method="GET" action="">
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 15px;">
            <div class="form-group" style="margin-bottom: 0;">
                <label for="departure">Kalkış Şehri:</label>
                <input type="text" id="departure" name="departure" 
                       value="<?php echo isset($_GET['departure']) ? clean($_GET['departure']) : ''; ?>"
                       placeholder="Örn: Ankara">
            </div>
            
            <div class="form-group" style="margin-bottom: 0;">
                <label for="destination">Varış Şehri:</label>
                <input type="text" id="destination" name="destination" 
                       value="<?php echo isset($_GET['destination']) ? clean($_GET['destination']) : ''; ?>"
                       placeholder="Örn: İstanbul">
            </div>
            
            <div class="form-group" style="margin-bottom: 0;">
                <label for="date">Tarih:</label>
                <input type="date" id="date" name="date" 
                       value="<?php echo isset($_GET['date']) ? clean($_GET['date']) : ''; ?>">
            </div>
            
            <div class="form-group" style="margin-bottom: 0;">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary">Ara</button>
            </div>
        </div>
    </form>
</div>

<?php if ($searchPerformed): ?>
    <div class="card">
        <h2>📋 Sefer Sonuçları</h2>
        
        <?php if (count($trips) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Firma</th>
                        <th>Güzergah</th>
                        <th>Kalkış</th>
                        <th>Varış</th>
                        <th>Fiyat</th>
                        <th>Koltuk</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($trips as $trip): ?>
                        <?php
                        // Dolu koltuk sayısını hesapla
                        $stmtSeats = $db->prepare("
                            SELECT COUNT(*) as booked_count 
                            FROM Tickets ti
                            JOIN Booked_Seats bs ON ti.id = bs.ticket_id
                            WHERE ti.trip_id = :trip_id AND ti.status = 'ACTIVE'
                        ");
                        $stmtSeats->execute([':trip_id' => $trip['id']]);
                        $seatInfo = $stmtSeats->fetch();
                        $bookedSeats = $seatInfo['booked_count'];
                        $availableSeats = $trip['capacity'] - $bookedSeats;
                        ?>
                        <tr>
                            <td><?php echo clean($trip['company_name']); ?></td>
                            <td>
                                <strong><?php echo clean($trip['departure_city']); ?></strong> 
                                → 
                                <strong><?php echo clean($trip['destination_city']); ?></strong>
                            </td>
                            <td><?php echo date('d.m.Y H:i', strtotime($trip['departure_time'])); ?></td>
                            <td><?php echo date('d.m.Y H:i', strtotime($trip['arrival_time'])); ?></td>
                            <td><strong><?php echo formatPrice($trip['price']); ?></strong></td>
                            <td>
                                <span style="color: <?php echo $availableSeats > 0 ? 'green' : 'red'; ?>">
                                    <?php echo $availableSeats; ?> / <?php echo $trip['capacity']; ?>
                                </span>
                            </td>
                            <td>
                                <a href="/sefer-detay.php?id=<?php echo $trip['id']; ?>" 
                                   class="btn btn-primary">Detay</a>
                                
                                <?php if (isLoggedIn() && isUser()): ?>
                                    <?php if ($availableSeats > 0): ?>
                                        <a href="/bilet-satin-al.php?trip_id=<?php echo $trip['id']; ?>" 
                                           class="btn btn-success">Bilet Al</a>
                                    <?php else: ?>
                                        <button class="btn btn-secondary" disabled>Dolu</button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info">
                Aramanıza uygun sefer bulunamadı.
            </div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="card">
        <h2>🚌 Hoş Geldiniz</h2>
        <p>Yukarıdaki formu kullanarak sefer arayabilirsiniz.</p>
        
        <?php if (!isLoggedIn()): ?>
            <div class="alert alert-info">
                <strong>Bilgi:</strong> Bilet satın almak için <a href="/login.php">giriş yapmalısınız</a>.
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?>