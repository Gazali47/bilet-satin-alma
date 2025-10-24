#  Bilet Satın Alma Platformu

Otobüs bileti satış ve yönetim sistemi. PHP, SQLite ve Docker kullanılarak geliştirilmiştir.

##  Özellikler

### Ziyaretçi (Giriş Yapmamış Kullanıcı)
- ✅ Sefer arama ve listeleme
- ✅ Sefer detaylarını görüntüleme
- ✅ Kayıt olma ve giriş yapma

### User (Yolcu)
- ✅ Sefer arama ve listeleme
- ✅ Koltuk seçerek bilet satın alma
- ✅ Kupon kodu uygulama
- ✅ Bilet iptal etme (kalkıştan 1 saat öncesine kadar)
- ✅ Biletleri PDF olarak indirme
- ✅ Kredi sistemi ile ödeme
- ✅ Bilet geçmişini görüntüleme

### Firma Admin
- ✅ Kendi firmasına ait seferleri yönetme
- ✅ Yeni sefer ekleme
- ✅ Mevcut seferleri düzenleme/silme
- ✅ Satılan biletleri görüntüleme

### Admin
- ✅ Firma ekleme/düzenleme/silme
- ✅ Firma Admin kullanıcıları oluşturma ve firmaya atama
- ✅ Kupon oluşturma/yönetme
- ✅ Tüm sistem verilerine erişim
- ✅ İstatistik ve raporlama

##  Teknolojiler

- **Backend:** PHP 8.1
- **Veritabanı:** SQLite
- **Frontend:** HTML, CSS, JavaScript
- **Container:** Docker

##  Kurulum

### Gereksinimler
- Docker
- Docker Compose

### Adımlar

1. **Projeyi klonlayın:**
```bash
git clone https://github.com/Gazali47/bilet-satin-alma.git
cd bilet-satin-alma
```

2. **Docker container'ı başlatın:**
```bash
docker-compose up -d
```

3. **Tarayıcıda açın:**
```
http://localhost:8080
```

Veritabanı otomatik olarak oluşturulacak ve örnek veriler eklenecektir.

##  Demo Hesaplar

### Admin Hesabı
- **E-posta:** admin@bilet.com
- **Şifre:** admin123
- **Yetkileri:** Tüm sisteme erişim

### Firma Admin Hesabı
- **E-posta:** firma@metro.com
- **Şifre:** firma123
- **Yetkileri:** Metro Turizm seferlerini yönetme

### Normal Kullanıcı Hesabı
- **E-posta:** user@test.com
- **Şifre:** user123
- **Bakiye:** 1000 ₺

##  Proje Yapısı

```
bilet-satin-alma/
├── database/               # SQLite veritabanı
│   └── database.sqlite
├── public/                 # Web dizini
│   ├── admin/             # Admin paneli
│   ├── firma-admin/       # Firma admin paneli
│   ├── assets/            # CSS, JS, resimler
│   ├── index.php          # Ana sayfa
│   ├── login.php          # Giriş
│   ├── register.php       # Kayıt
│   ├── bilet-satin-al.php # Bilet satın alma
│   └── biletlerim.php     # Biletlerim
├── src/                   # Uygulama kodu
│   ├── config/            # Yapılandırma
│   ├── includes/          # Header, footer, auth
│   └── utils/             # Yardımcı fonksiyonlar
├── Dockerfile
├── docker-compose.yml
└── README.md
```

##  Veritabanı Şeması

- **Bus_Company:** Otobüs firmaları
- **User:** Kullanıcılar (Admin, Firma Admin, User)
- **Trips:** Seferler
- **Tickets:** Biletler
- **Booked_Seats:** Rezerve koltuklar
- **Coupons:** İndirim kuponları
- **User_Coupons:** Kullanıcı-kupon ilişkisi

##  Güvenlik

- ✅ Password hashing (bcrypt)
- ✅ SQL Injection koruması (PDO prepared statements)
- ✅ XSS koruması (htmlspecialchars)
- ✅ Session yönetimi
- ✅ Rol bazlı yetkilendirme

##  Kullanım Senaryoları

### Bilet Satın Alma
1. Kullanıcı kayıt olur veya giriş yapar
2. Ana sayfada kalkış-varış noktası seçer
3. Uygun seferi bulur
4. Koltuk seçer
5. Kupon kodu uygular (opsiyonel)
6. Bilet satın alır (kredi düşer)
7. PDF olarak indirir

### Bilet İptali
1. "Biletlerim" sayfasına gider
2. İptal etmek istediği bileti seçer
3. Kalkışa 1 saatten fazla varsa iptal eder
4. Para hesabına iade edilir

### Firma Admin İşlemleri
1. Giriş yapar
2. "Sefer Yönetimi" paneline gider
3. Yeni sefer ekler veya mevcut seferleri düzenler
4. Satılan biletleri görüntüler

### Admin İşlemleri
1. Giriş yapar
2. Firma ekler
3. Firma Admin kullanıcısı oluşturur ve firmaya atar
4. İndirim kuponları oluşturur
5. Sistem istatistiklerini görüntüler

##  Geliştirme

### Docker'ı yeniden build etme:
```bash
docker-compose down
docker-compose up --build -d
```

### Logları görüntüleme:
```bash
docker-compose logs -f
```

### Container'a bağlanma:
```bash
docker exec -it bilet-platform bash
```

##  Geliştirici

- **İsim:** Gazali KEPENÇ
- **GitHub:** https://github.com/Gazali47
