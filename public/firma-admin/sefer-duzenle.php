<?php
require_once __DIR__ . '/../../src/config/config.php';

// Sadece firma admin rolü erişebilir
requireLogin();
if (!isFirmaAdmin()) {
    setError('Bu sayfaya erişim yetkiniz yok!');
    header('Location: /index.php');
    exit();
}

$currentUser = $auth->getCurrentUser();

// Sefer ID kontrolü
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setError('Geçersiz sefer!');
    header('Location: /firma-admin/index.php');
    exit();
}

$tripId = (int)$_GET['id'];

// Seferi al ve firmaya ait mi kontrol et
$stmt = $db->prepare("SELECT * FROM Trips WHERE id = :id AND company_id = :company_id");
$stmt->execute([':id' => $tripId, ':company_id' => $currentUser['company_id']]);
$trip = $stmt->fetch();

if (!$trip) {
    setError('Sefer bulunamadı veya bu seferi düzenleme yetkiniz yok!');
    header('Location: /firma-admin/index.php');
    exit();
}

// Form gönderimi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $departureCity = trim($_POST['departure_city']);
    $destinationCity = trim($_POST['destination_city']);
    $departureTime = trim($_POST['departure_time']);
    $arrivalTime = trim($_POST['arrival_time']);
    $price = (float)$_POST['price'];
    $capacity = (int)$_POST['capacity'];
    
    // Doğrulama
    if (empty($departureCity) || empty($destinationCity) || empty($departureTime) || empty($arrivalTime)) {
        setError('Tüm alanları doldurmanız gerekmektedir!');
    } elseif ($price <= 0) {
        setError('Fiyat 0\'dan büyük olmalıdır!');
    } elseif ($capacity <= 0 || $capacity > 60) {
        setError('Kapasite 1-60 arasında olmalıdır!');
    } elseif (strtotime($arrivalTime) <= strtotime($departureTime)) {
        setError('Varış zamanı kalkış zamanından sonra olmalıdır!');
    } else {
        try {
            $stmt = $db->prepare("
                UPDATE Trips SET 
                    departure_city = :departure_city,
                    destination_city = :destination_city,
                    departure_time = :departure_time,
                    arrival_time = :arrival_time,
                    price = :price,
                    capacity = :capacity
                WHERE id = :id AND company_id = :company_id
            ");
            
            $stmt->execute([
                ':departure_city' => $departureCity,
                ':destination_city' => $destinationCity,
                ':departure_time' => $departureTime,
                ':arrival_time' => $arrivalTime,
                ':price' => $price,
                ':capacity' => $capacity,
                ':id' => $tripId,
                ':company_id' => $currentUser['company_id']
            ]);
            
            setSuccess('Sefer başarıyla güncellendi!');
            header('Location: /firma-admin/index.php');
            exit();
            
        } catch (Exception $e) {
            setError('Sefer güncellenirken bir hata oluştu!');
        }
    }
}

$pageTitle = 'Sefer Düzenle';
require_once __DIR__ . '/../../src/includes/header.php';
?>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <h2>✏️ Sefer Düzenle</h2>
    
    <form method="POST" action="">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label for="departure_city">Kalkış Şehri:</label>
                <input type="text" id="departure_city" name="departure_city" required 
                       value="<?php echo clean($trip['departure_city']); ?>">
            </div>
            
            <div class="form-group">
                <label for="destination_city">Varış Şehri:</label>
                <input type="text" id="destination_city" name="destination_city" required 
                       value="<?php echo clean($trip['destination_city']); ?>">
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label for="departure_time">Kalkış Zamanı:</label>
                <input type="datetime-local" id="departure_time" name="departure_time" required 
                       value="<?php echo date('Y-m-d\TH:i', strtotime($trip['departure_time'])); ?>">
            </div>
            
            <div class="form-group">
                <label for="arrival_time">Varış Zamanı:</label>
                <input type="datetime-local" id="arrival_time" name="arrival_time" required 
                       value="<?php echo date('Y-m-d\TH:i', strtotime($trip['arrival_time'])); ?>">
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label for="price">Fiyat (₺):</label>
                <input type="number" id="price" name="price" required min="1" step="0.01"
                       value="<?php echo $trip['price']; ?>">
            </div>
            
            <div class="form-group">
                <label for="capacity">Koltuk Kapasitesi:</label>
                <input type="number" id="capacity" name="capacity" required min="1" max="60"
                       value="<?php echo $trip['capacity']; ?>">
            </div>
        </div>
        
        <button type="submit" class="btn btn-success">Güncelle</button>
        <a href="/firma-admin/index.php" class="btn btn-secondary">İptal</a>
    </form>
</div>

<?php require_once __DIR__ . '/../../src/includes/footer.php'; ?>