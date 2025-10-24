<?php
require_once __DIR__ . '/../../src/config/config.php';

require_once __DIR__ . '/../../src/includes/auth.php';

requireLogin();
if (!isFirmaAdmin()) {
    setError('Bu sayfaya erişim yetkiniz yok!');
    header('Location: /index.php');
    exit();
}

$currentUser = $auth->getCurrentUser();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $departureCity = trim($_POST['departure_city']);
    $destinationCity = trim($_POST['destination_city']);
    $departureTime = trim($_POST['departure_time']);
    $arrivalTime = trim($_POST['arrival_time']);
    $price = (float)$_POST['price'];
    $capacity = (int)$_POST['capacity'];
    
    
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
                INSERT INTO Trips (company_id, departure_city, destination_city, departure_time, arrival_time, price, capacity) 
                VALUES (:company_id, :departure_city, :destination_city, :departure_time, :arrival_time, :price, :capacity)
            ");
            
            $stmt->execute([
                ':company_id' => $currentUser['company_id'],
                ':departure_city' => $departureCity,
                ':destination_city' => $destinationCity,
                ':departure_time' => $departureTime,
                ':arrival_time' => $arrivalTime,
                ':price' => $price,
                ':capacity' => $capacity
            ]);
            
            setSuccess('Sefer başarıyla eklendi!');
            header('Location: /firma-admin/index.php');
            exit();
            
        } catch (Exception $e) {
            setError('Sefer eklenirken bir hata oluştu!');
        }
    }
}

$pageTitle = 'Yeni Sefer Ekle';
require_once __DIR__ . '/../../src/includes/header.php';
?>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <h2>➕ Yeni Sefer Ekle</h2>
    
    <form method="POST" action="">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label for="departure_city">Kalkış Şehri:</label>
                <input type="text" id="departure_city" name="departure_city" required 
                       value="<?php echo isset($_POST['departure_city']) ? clean($_POST['departure_city']) : ''; ?>"
                       placeholder="Örn: Ankara">
            </div>
            
            <div class="form-group">
                <label for="destination_city">Varış Şehri:</label>
                <input type="text" id="destination_city" name="destination_city" required 
                       value="<?php echo isset($_POST['destination_city']) ? clean($_POST['destination_city']) : ''; ?>"
                       placeholder="Örn: İstanbul">
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label for="departure_time">Kalkış Zamanı:</label>
                <input type="datetime-local" id="departure_time" name="departure_time" required 
                       value="<?php echo isset($_POST['departure_time']) ? clean($_POST['departure_time']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="arrival_time">Varış Zamanı:</label>
                <input type="datetime-local" id="arrival_time" name="arrival_time" required 
                       value="<?php echo isset($_POST['arrival_time']) ? clean($_POST['arrival_time']) : ''; ?>">
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label for="price">Fiyat (₺):</label>
                <input type="number" id="price" name="price" required min="1" step="0.01"
                       value="<?php echo isset($_POST['price']) ? clean($_POST['price']) : ''; ?>"
                       placeholder="Örn: 250.00">
            </div>
            
            <div class="form-group">
                <label for="capacity">Koltuk Kapasitesi:</label>
                <input type="number" id="capacity" name="capacity" required min="1" max="60"
                       value="<?php echo isset($_POST['capacity']) ? clean($_POST['capacity']) : '45'; ?>"
                       placeholder="Örn: 45">
            </div>
        </div>
        
        <button type="submit" class="btn btn-success">Sefer Ekle</button>
        <a href="/firma-admin/index.php" class="btn btn-secondary">İptal</a>
    </form>
</div>

<?php require_once __DIR__ . '/../../src/includes/footer.php'; ?>