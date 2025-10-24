<?php
require_once __DIR__ . '/../../src/config/config.php';

require_once __DIR__ . '/../../src/includes/auth.php';

requireLogin();
if (!isAdmin()) {
    setError('Bu sayfaya erişim yetkiniz yok!');
    header('Location: /index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_company'])) {
    $companyName = trim($_POST['company_name']);
    
    if (empty($companyName)) {
        setError('Firma adı boş olamaz!');
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO Bus_Company (name) VALUES (:name)");
            $stmt->execute([':name' => $companyName]);
            setSuccess('Firma başarıyla eklendi!');
        } catch (Exception $e) {
            setError('Bu firma adı zaten kullanılıyor!');
        }
    }
    
    header('Location: /admin/firma-yonetimi.php');
    exit();
}

if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $companyId = (int)$_GET['delete'];
    
    try {
        $stmt = $db->prepare("DELETE FROM Bus_Company WHERE id = :id");
        $stmt->execute([':id' => $companyId]);
        setSuccess('Firma başarıyla silindi!');
    } catch (Exception $e) {
        setError('Firma silinirken bir hata oluştu!');
    }
    
    header('Location: /admin/firma-yonetimi.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_company'])) {
    $companyId = (int)$_POST['company_id'];
    $companyName = trim($_POST['company_name']);
    
    if (empty($companyName)) {
        setError('Firma adı boş olamaz!');
    } else {
        try {
            $stmt = $db->prepare("UPDATE Bus_Company SET name = :name WHERE id = :id");
            $stmt->execute([':name' => $companyName, ':id' => $companyId]);
            setSuccess('Firma başarıyla güncellendi!');
        } catch (Exception $e) {
            setError('Bu firma adı zaten kullanılıyor!');
        }
    }
    
    header('Location: /admin/firma-yonetimi.php');
    exit();
}

$stmt = $db->query("
    SELECT 
        bc.*,
        (SELECT COUNT(*) FROM Trips WHERE company_id = bc.id) as trip_count,
        (SELECT COUNT(*) FROM User WHERE company_id = bc.id AND role = 'company_admin') as admin_count
    FROM Bus_Company bc
    ORDER BY bc.name ASC
");
$companies = $stmt->fetchAll();

$pageTitle = 'Firma Yönetimi';
require_once __DIR__ . '/../../src/includes/header.php';
?>

<div class="card">
    <h2>🏢 Firma Yönetimi</h2>
    
    <form method="POST" action="" style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
        <h3>Yeni Firma Ekle</h3>
        <div style="display: flex; gap: 15px; align-items: flex-end;">
            <div class="form-group" style="flex: 1; margin-bottom: 0;">
                <label for="company_name">Firma Adı:</label>
                <input type="text" id="company_name" name="company_name" required placeholder="Örn: Metro Turizm">
            </div>
            <button type="submit" name="add_company" class="btn btn-success">➕ Ekle</button>
        </div>
    </form>
    
    <h3>Mevcut Firmalar</h3>
    
    <?php if (count($companies) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Firma Adı</th>
                    <th>Sefer Sayısı</th>
                    <th>Admin Sayısı</th>
                    <th>Oluşturulma</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($companies as $company): ?>
                    <tr>
                        <td><?php echo $company['id']; ?></td>
                        <td><strong><?php echo clean($company['name']); ?></strong></td>
                        <td><?php echo $company['trip_count']; ?></td>
                        <td><?php echo $company['admin_count']; ?></td>
                        <td><?php echo date('d.m.Y', strtotime($company['created_at'])); ?></td>
                        <td>
                            <button onclick="editCompany(<?php echo $company['id']; ?>, '<?php echo addslashes($company['name']); ?>')" 
                                    class="btn btn-primary" style="padding: 8px 15px; font-size: 12px;">
                                ✏️ Düzenle
                            </button>
                            <a href="/admin/firma-yonetimi.php?delete=<?php echo $company['id']; ?>" 
                               class="btn btn-danger" style="padding: 8px 15px; font-size: 12px;"
                               onclick="return confirm('Bu firmayı silmek istediğinize emin misiniz? Tüm seferleri ve biletleri de silinecektir!');">
                                🗑️ Sil
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info">Henüz firma eklenmemiş.</div>
    <?php endif; ?>
</div>

<div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div class="card" style="max-width: 500px; margin: 100px auto; position: relative;">
        <h3>Firma Düzenle</h3>
        <form method="POST" action="">
            <input type="hidden" name="company_id" id="edit_company_id">
            <div class="form-group">
                <label for="edit_company_name">Firma Adı:</label>
                <input type="text" id="edit_company_name" name="company_name" required>
            </div>
            <button type="submit" name="edit_company" class="btn btn-success">Güncelle</button>
            <button type="button" onclick="closeEditModal()" class="btn btn-secondary">İptal</button>
        </form>
    </div>
</div>

<script>
function editCompany(id, name) {
    document.getElementById('edit_company_id').value = id;
    document.getElementById('edit_company_name').value = name;
    document.getElementById('editModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}
</script>

<a href="/admin/index.php" class="btn btn-secondary">← Geri Dön</a>

<?php require_once __DIR__ . '/../../src/includes/footer.php'; ?>