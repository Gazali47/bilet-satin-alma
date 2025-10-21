<?php

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        // Veritabanı dizinini oluştur
        $dbDir = __DIR__ . '/../../database';
        if (!file_exists($dbDir)) {
            mkdir($dbDir, 0777, true);
        }
        
        $dbPath = $dbDir . '/database.sqlite';
        
        try {
            $this->connection = new PDO('sqlite:' . $dbPath);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Veritabanını başlat
            $this->initDatabase();
        } catch(PDOException $e) {
            die("Veritabanı bağlantı hatası: " . $e->getMessage() . "<br>Yol: " . $dbPath);
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    private function initDatabase() {
        // 1. Bus_Company Tablosu
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS Bus_Company (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                logo_path TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // 2. User Tablosu
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS User (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                full_name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                role TEXT NOT NULL,
                password TEXT NOT NULL,
                company_id INTEGER,
                balance REAL DEFAULT 800.0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (company_id) REFERENCES Bus_Company(id)
            )
        ");
        
        // 3. Trips Tablosu
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS Trips (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL,
                destination_city TEXT NOT NULL,
                arrival_time DATETIME NOT NULL,
                departure_time DATETIME NOT NULL,
                departure_city TEXT NOT NULL,
                price INTEGER NOT NULL,
                capacity INTEGER NOT NULL,
                created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (company_id) REFERENCES Bus_Company(id)
            )
        ");
        
        // 4. Tickets Tablosu
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS Tickets (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                trip_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                status TEXT DEFAULT 'ACTIVE',
                total_price INTEGER NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (trip_id) REFERENCES Trips(id),
                FOREIGN KEY (user_id) REFERENCES User(id)
            )
        ");
        
        // 5. Booked_Seats Tablosu
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS Booked_Seats (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                ticket_id INTEGER NOT NULL,
                seat_number INTEGER NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (ticket_id) REFERENCES Tickets(id),
                UNIQUE (ticket_id, seat_number)
            )
        ");
        
        // 6. Coupons Tablosu
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS Coupons (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                code TEXT NOT NULL UNIQUE,
                discount REAL NOT NULL,
                usage_limit INTEGER NOT NULL,
                expire_date DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // 7. User_Coupons Tablosu
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS User_Coupons (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                coupon_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (coupon_id) REFERENCES Coupons(id),
                FOREIGN KEY (user_id) REFERENCES User(id)
            )
        ");
        
        // Varsayılan verileri ekle
        $this->insertDefaultData();
    }
    
    private function insertDefaultData() {
        // Admin kullanıcısı var mı kontrol et
        $stmt = $this->connection->prepare("SELECT COUNT(*) as count FROM User WHERE role = 'admin'");
        $stmt->execute();
        $result = $stmt->fetch();
        
        if ($result['count'] == 0) {
            // Admin ekle (şifre: admin123)
            $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $this->connection->exec("
                INSERT INTO User (full_name, email, role, password, company_id, balance) 
                VALUES ('Admin', 'admin@bilet.com', 'admin', '$hashedPassword', NULL, 0)
            ");
            
            // Örnek firmalar ekle
            $this->connection->exec("
                INSERT INTO Bus_Company (name) VALUES 
                ('Metro Turizm'),
                ('Pamukkale Turizm'),
                ('Kamil Koç')
            ");
            
            // Firma admin ekle (şifre: firma123)
            $hashedPassword2 = password_hash('firma123', PASSWORD_DEFAULT);
            $this->connection->exec("
                INSERT INTO User (full_name, email, role, password, company_id, balance) 
                VALUES ('Firma Admin', 'firma@metro.com', 'company_admin', '$hashedPassword2', 1, 0)
            ");
            
            // Normal kullanıcı ekle (şifre: user123)
            $hashedPassword3 = password_hash('user123', PASSWORD_DEFAULT);
            $this->connection->exec("
                INSERT INTO User (full_name, email, role, password, company_id, balance) 
                VALUES ('Test User', 'user@test.com', 'user', '$hashedPassword3', NULL, 1000.0)
            ");
            
            // Örnek seferler ekle
            $this->connection->exec("
                INSERT INTO Trips (company_id, destination_city, arrival_time, departure_time, departure_city, price, capacity) 
                VALUES 
                (1, 'İstanbul', '2025-10-20 14:00:00', '2025-10-20 08:00:00', 'Ankara', 250, 45),
                (1, 'İzmir', '2025-10-20 18:00:00', '2025-10-20 12:00:00', 'Ankara', 200, 45),
                (2, 'Antalya', '2025-10-21 15:00:00', '2025-10-21 07:00:00', 'İstanbul', 300, 45),
                (3, 'Konya', '2025-10-20 12:00:00', '2025-10-20 08:00:00', 'Ankara', 150, 45)
            ");
            
            // Örnek kuponlar ekle
            $this->connection->exec("
                INSERT INTO Coupons (code, discount, usage_limit, expire_date) 
                VALUES 
                ('WELCOME10', 0.10, 100, '2025-12-31 23:59:59'),
                ('SUMMER20', 0.20, 50, '2025-12-31 23:59:59'),
                ('VIP30', 0.30, 20, '2025-12-31 23:59:59')
            ");
        }
    }
}

// Veritabanı bağlantısını başlat
$db = Database::getInstance()->getConnection();