<?php
require_once __DIR__ . '/../src/config/config.php';

requireLogin();
if (!isUser()) {
    setError('Bu sayfaya erişim yetkiniz yok!');
    header('Location: /index.php');
    exit();
}

if (!isset($_GET['trip_id']) || empty($_GET['trip_id'])) {
    setError('Geçersiz sefer!');
    header('Location: /index.php');
    exit();
}

$tripId = (int)$_GET['trip_id'];

$stmt = $db->prepare("
    SELECT t.*, b.name as company_name 
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

$currentUser = $auth->getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedSeats = isset($_POST['seats']) ? $_POST['seats'] : [];
    $couponCode = isset($_POST['coupon_code']) ? trim($_POST['coupon_code']) : '';
    
    if (empty($selectedSeats)) {
        setError('Lütfen en az bir koltuk seçiniz!');
    } else {
        $valid = true;
        foreach ($selectedSeats as $seat) {
            if (in_array($seat, $bookedSeats)) {
                setError('Seçtiğiniz koltuklar müsait değil!');
                $valid = false;
                break;
            }
        }
        
        if ($valid) {
            $totalPrice = $trip['price'] * count($selectedSeats);
            $discount = 0;
            
            if (!empty($couponCode)) {
                $stmtCoupon = $db->prepare("
                    SELECT * FROM Coupons 
                    WHERE code = :code 
                    AND expire_date >= datetime('now')
                    AND usage_limit > 0
                ");
                $stmtCoupon->execute([':code' => $couponCode]);
                $coupon = $stmtCoupon->fetch();
                
                if ($coupon) {
                    $discount = $totalPrice * $coupon['discount'];
                    $totalPrice -= $discount;
                }
            }
            
            if ($currentUser['balance'] < $totalPrice) {
                setError('Yetersiz bakiye! Bakiyeniz: ' . formatPrice($currentUser['balance']));
            } else {
                try {
                    $db->beginTransaction();
                    
                    $stmtTicket = $db->prepare("
                        INSERT INTO Tickets (trip_id, user_id, status, total_price) 
                        VALUES (:trip_id, :user_id, 'ACTIVE', :total_price)
                    ");
                    $stmtTicket->execute([
                        ':trip_id' => $tripId,
                        ':user_id' => $currentUser['id'],
                        ':total_price' => $totalPrice
                    ]);
                    
                    $ticketId = $db->lastInsertId();
                    
                    $stmtSeat = $db->prepare("
                        INSERT INTO Booked_Seats (ticket_id, seat_number) 
                        VALUES (:ticket_id, :seat_number)
                    ");
                    
                    foreach ($selectedSeats as $seat) {
                        $stmtSeat->execute([
                            ':ticket_id' => $ticketId,
                            ':seat_number' => $seat
                        ]);
                    }
                    
                    $stmtBalance = $db->prepare("
                        UPDATE User SET balance = balance - :amount WHERE id = :user_id
                    ");
                    $stmtBalance->execute([
                        ':amount' => $totalPrice,
                        ':user_id' => $currentUser['id']
                    ]);
                    
                    if (!empty($couponCode) && isset($coupon)) {
                        $stmtUpdateCoupon = $db->prepare("
                            UPDATE Coupons SET usage_limit = usage_limit - 1 WHERE id = :id
                        ");
                        $stmtUpdateCoupon->execute([':id' => $coupon['id']]);
                    }
                    
                    $db->commit();
                    
                    $_SESSION['balance'] = $currentUser['balance'] - $totalPrice;
                    
                    setSuccess('Biletiniz başarıyla satın alındı!');
                    header('Location: /biletlerim.php');
                    exit();
                    
                } catch (Exception $e) {
                    $db->rollBack();
                    setError('Bir hata oluştu: ' . $e->getMessage());
                }
            }
        }
    }
}

$pageTitle = 'Bilet Satın Al';
require_once __DIR__ . '/../src/includes/header.php';
?>

<div class="card">
    <h2>Bilet Satın Al</h2>
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
        <h3><?php echo clean($trip['company_name']); ?></h3>
        <p style="margin: 5px 0;">
            <strong><?php echo clean($trip['departure_city']); ?></strong> → 
            <strong><?php echo clean($trip['destination_city']); ?></strong>
        </p>
        <p style="margin: 5px 0;">
            <?php echo date('d.m.Y H:i', strtotime($trip['departure_time'])); ?>
        </p>
        <p style="margin: 5px 0;">
            Koltuk Başı: <strong><?php echo formatPrice($trip['price']); ?></strong>
        </p>
        <p style="margin: 5px 0;">
            Bakiyeniz: <strong><?php echo formatPrice($currentUser['balance']); ?></strong>
        </p>
    </div>
    
    <form method="POST" action="" id="ticketForm">
        <h3>Koltuk Seçimi</h3>
        <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
            <div style="text-align: center; margin-bottom: 15px;">
                <span style="display: inline-block; margin: 0 15px;">
                    <span style="display: inline-block; width: 20px; height: 20px; background: white; border: 2px solid #ddd; vertical-align: middle;"></span>
                    Boş
                </span>
                <span style="display: inline-block; margin: 0 15px;">
                    <span style="display: inline-block; width: 20px; height: 20px; background: #667eea; vertical-align: middle;"></span>
                    Seçili
                </span>
                <span style="display: inline-block; margin: 0 15px;">
                    <span style="display: inline-block; width: 20px; height: 20px; background: #ddd; vertical-align: middle;"></span>
                    Dolu
                </span>
            </div>
            
            <div class="seats-container">
                <?php for ($i = 1; $i <= $trip['capacity']; $i++): ?>
                    <?php $isTaken = in_array($i, $bookedSeats); ?>
                    <div class="seat <?php echo $isTaken ? 'seat-taken' : ''; ?>" 
                         onclick="<?php echo !$isTaken ? 'toggleSeat(this, ' . $i . ')' : ''; ?>"
                         style="<?php echo !$isTaken ? 'cursor: pointer;' : ''; ?>">
                        <?php echo $i; ?>
                        <?php if (!$isTaken): ?>
                            <input type="checkbox" name="seats[]" value="<?php echo $i; ?>" 
                                   style="display: none;" class="seat-checkbox">
                        <?php endif; ?>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
        
        <div class="form-group">
            <label for="coupon_code">İndirim Kuponu (İsteğe Bağlı):</label>
            <input type="text" id="coupon_code" name="coupon_code" 
                   placeholder="WELCOME10">
            <small style="color: #666;">Kupon kodunuz varsa giriniz.</small>
        </div>
        
        <div style="background: #e8f5e9; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            <strong>Seçilen Koltuklar:</strong> <span id="selectedSeatsText">Henüz koltuk seçilmedi</span><br>
            <strong>Toplam Fiyat:</strong> <span id="totalPrice">0 ₺</span>
        </div>
        
        <button type="submit" class="btn btn-success">Satın Al</button>
        <a href="/sefer-detay.php?id=<?php echo $trip['id']; ?>" class="btn btn-secondary">İptal</a>
    </form>
</div>

<script>
const pricePerSeat = <?php echo $trip['price']; ?>;
let selectedSeats = [];

function toggleSeat(element, seatNumber) {
    const checkbox = element.querySelector('.seat-checkbox');
    
    if (element.classList.contains('seat-selected')) {
        element.classList.remove('seat-selected');
        checkbox.checked = false;
        selectedSeats = selectedSeats.filter(s => s !== seatNumber);
    } else {
        element.classList.add('seat-selected');
        checkbox.checked = true;
        selectedSeats.push(seatNumber);
    }
    
    updateSummary();
}

function updateSummary() {
    const seatsText = selectedSeats.length > 0 ? selectedSeats.join(', ') : 'Henüz koltuk seçilmedi';
    const total = selectedSeats.length * pricePerSeat;
    
    document.getElementById('selectedSeatsText').textContent = seatsText;
    document.getElementById('totalPrice').textContent = total.toFixed(2).replace('.', ',') + ' ₺';
}
</script>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?>