<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Giriş kontrolü
if (!isset($_SESSION['kullanici_id'])) {
    header('Location: index.php');
    exit;
}

$success_message = '';
$error_message = '';

// Yeni firma ekleme işlemi
if (isset($_POST['add_firma'])) {
    $firma_adi = $_POST['firma_adi'];
    $sehir = $_POST['sehir'];
    $telefon = $_POST['telefon'];
    $toplam_borc = $_POST['toplam_borc'];
    $aylik_odeme = $_POST['aylik_odeme'];
    $baslangic_tarihi = $_POST['baslangic_tarihi'];
    
    try {
        $taksit_sayisi = ceil($toplam_borc / $aylik_odeme);
        
        $insert_stmt = $pdo->prepare("
            INSERT INTO firmalar (firma_adi, sehir, telefon, toplam_borc, kalan_borc, aylik_odeme, baslangic_tarihi, taksit_sayisi, durum) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'aktif')
        ");
        $insert_stmt->execute([$firma_adi, $sehir, $telefon, $toplam_borc, $toplam_borc, $aylik_odeme, $baslangic_tarihi, $taksit_sayisi]);
        
        $success_message = "Yeni firma başarıyla eklendi!";
        
    } catch(PDOException $e) {
        $error_message = "Firma eklenirken hata oluştu: " . $e->getMessage();
    }
}

// Firma silme işlemi
if (isset($_POST['delete_firma'])) {
    $firma_id = $_POST['firma_id'];
    try {
        $pdo->beginTransaction();
        
        // Önce taksitleri sil
        $delete_taksit = $pdo->prepare("DELETE FROM taksitler WHERE firma_id = ?");
        $delete_taksit->execute([$firma_id]);
        
        // Sonra firmayı sil
        $delete_firma = $pdo->prepare("DELETE FROM firmalar WHERE id = ?");
        $delete_firma->execute([$firma_id]);
        
        $pdo->commit();
        $success_message = "Firma başarıyla silindi!";
        
    } catch(PDOException $e) {
        $pdo->rollback();
        $error_message = "Firma silinirken hata oluştu: " . $e->getMessage();
    }
}

// Ödeme ekleme işlemi
if (isset($_POST['add_payment'])) {
    $firma_id = $_POST['firma_id'];
    $odeme_tutari = $_POST['odeme_tutari'];
    
    try {
        $pdo->beginTransaction();
        
        // Firma bilgilerini al
        $firma_stmt = $pdo->prepare("SELECT * FROM firmalar WHERE id = ?");
        $firma_stmt->execute([$firma_id]);
        $firma = $firma_stmt->fetch();
        
        if ($firma && $odeme_tutari <= $firma['kalan_borc'] && $odeme_tutari > 0) {
            // Kalan borcu güncelle
            $yeni_kalan = $firma['kalan_borc'] - $odeme_tutari;
            $durum = ($yeni_kalan == 0) ? 'tamamlandi' : 'aktif';
            
            $update_stmt = $pdo->prepare("
                UPDATE firmalar 
                SET kalan_borc = ?, durum = ? 
                WHERE id = ?
            ");
            $update_stmt->execute([$yeni_kalan, $durum, $firma_id]);
            
            // Ödeme kaydını taksitler tablosuna ekle
            $taksit_stmt = $pdo->prepare("
                INSERT INTO taksitler (firma_id, tutar, vade_tarihi, durum, odeme_tarihi) 
                VALUES (?, ?, CURDATE(), 'odendi', CURDATE())
            ");
            $taksit_stmt->execute([$firma_id, $odeme_tutari]);
            
            $pdo->commit();
            $success_message = "Ödeme başarıyla kaydedildi!";
            
        } else {
            $error_message = "Geçersiz ödeme tutarı!";
        }
        
    } catch(PDOException $e) {
        $pdo->rollback();
        $error_message = "Ödeme kaydedilirken hata oluştu: " . $e->getMessage();
    }
}

// Firmalar listesi
try {
    $stmt = $pdo->prepare("
        SELECT 
            f.*,
            (f.toplam_borc - f.kalan_borc) as odenen_tutar,
            (SELECT COUNT(*) FROM taksitler t WHERE t.firma_id = f.id AND t.durum = 'odendi') as odenen_taksit,
            (SELECT COUNT(*) FROM taksitler t WHERE t.firma_id = f.id AND t.durum != 'odendi') as kalan_taksit,
            (SELECT COUNT(*) FROM taksitler t WHERE t.firma_id = f.id AND t.vade_tarihi < CURDATE() AND t.durum != 'odendi') as geciken_taksit
        FROM firmalar f 
        ORDER BY f.id ASC
    ");
    $stmt->execute();
    $firmalar = $stmt->fetchAll();
} catch(PDOException $e) {
    $error_message = "Firmalar yüklenirken hata oluştu: " . $e->getMessage();
    $firmalar = [];
}

// Toplam istatistikler
try {
    $stats_stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as toplam_firma,
            COALESCE(SUM(toplam_borc), 0) as toplam_borc,
            COALESCE(SUM(kalan_borc), 0) as toplam_kalan,
            COALESCE(SUM(toplam_borc - kalan_borc), 0) as toplam_odenen,
            COUNT(CASE WHEN durum = 'gecikme' THEN 1 END) as geciken_firma
        FROM firmalar
    ");
    $stats_stmt->execute();
    $istatistik = $stats_stmt->fetch();
} catch(PDOException $e) {
    $istatistik = ['toplam_firma' => 0, 'toplam_borc' => 0, 'toplam_kalan' => 0, 'toplam_odenen' => 0, 'geciken_firma' => 0];
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konya Belediyesi - Borç Takip Sistemi</title>
    <link rel="icon" type="image/png" href="konya-logo.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #2c5aa0 0%, #1e3c72 100%);
            min-height: 100vh;
        }
        
        .container {
            width: 100vw;
            min-height: 100vh;
            background: white;
            display: flex;
            flex-direction: column;
        }
        
        .header {
            background: linear-gradient(135deg, #2c5aa0 0%, #1e3c72 100%);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 24px;
            font-weight: 700;
        }
        
        .header .user-info {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .header .user-info a {
            color: white;
            text-decoration: none;
            margin: 0 5px;
            transition: all 0.3s ease;
        }
        
        .header .user-info a:hover {
            text-decoration: underline;
            transform: translateY(-1px);
        }
        
        .action-bar {
            background: #f8f9fa;
            padding: 15px 30px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .search-box input {
            padding: 12px 20px;
            border: 2px solid #ddd;
            border-radius: 25px;
            font-size: 16px;
            min-width: 350px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 10px rgba(0,123,255,0.3);
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
        }
        
        .stats-bar {
            background: #f8f9fa;
            padding: 20px 30px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
        }
        
        .stat-card.total { border-color: #007bff; }
        .stat-card.remaining { border-color: #dc3545; }
        .stat-card.paid { border-color: #28a745; }
        .stat-card.overdue { border-color: #ffc107; }
        
        .stat-card h3 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-card.total h3 { color: #007bff; }
        .stat-card.remaining h3 { color: #dc3545; }
        .stat-card.paid h3 { color: #28a745; }
        .stat-card.overdue h3 { color: #ffc107; }
        
        .stat-card p {
            font-size: 14px;
            color: #666;
            font-weight: 600;
        }
        
        .content {
            flex: 1;
            padding: 30px;
            overflow-x: auto;
        }
        
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-header {
            background: #2c5aa0;
            color: white;
            padding: 15px 20px;
            font-weight: 600;
        }
        
        .firms-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        
        .firms-table th {
            background: #f8f9fa;
            padding: 15px 10px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
            white-space: nowrap;
        }
        
        .firms-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        
        .firms-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-aktif {
            background: #d4edda;
            color: #155724;
        }
        
        .status-gecikme {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-tamamlandi {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .amount {
            font-weight: 600;
            text-align: right;
        }
        
        .amount.positive {
            color: #28a745;
        }
        
        .amount.negative {
            color: #dc3545;
        }
        
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            margin: 2px;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: black;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .empty-state h3 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #333;
        }
        
        .empty-state p {
            font-size: 16px;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .action-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box input {
                min-width: 100%;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .content {
                padding: 15px;
            }
            
            .firms-table {
                font-size: 12px;
            }
            
            .firms-table th,
            .firms-table td {
                padding: 8px 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Konya Belediyesi - Borç Takip Sistemi</h1>
            <div class="user-info">
                <?php echo htmlspecialchars($_SESSION['username']); ?> - <?php echo strtoupper($_SESSION['role']); ?>
                | <a href="dashboard.php">🏠 Anasayfa</a>
            </div>
        </div>
        
        <div class="action-bar">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="🔍 Firma adı veya şehir ile ara..." onkeyup="filterTable()">
            </div>
            <div class="btn-group">
                <button class="btn btn-success" onclick="showAddFirmaModal()">➕ Yeni Firma</button>
                <button class="btn btn-warning" onclick="window.location.reload()">🔄 Sayfa Yenile</button>
            </div>
        </div>
        
        <div class="stats-bar">
            <div class="stats-grid">
                <div class="stat-card total">
                    <h3><?php echo number_format($istatistik['toplam_firma']); ?></h3>
                    <p>Toplam Firma</p>
                </div>
                <div class="stat-card total">
                    <h3>₺<?php echo number_format($istatistik['toplam_borc'], 0, ',', '.'); ?></h3>
                    <p>Toplam Borç</p>
                </div>
                <div class="stat-card remaining">
                    <h3>₺<?php echo number_format($istatistik['toplam_kalan'], 0, ',', '.'); ?></h3>
                    <p>Kalan Borç</p>
                </div>
                <div class="stat-card paid">
                    <h3>₺<?php echo number_format($istatistik['toplam_odenen'], 0, ',', '.'); ?></h3>
                    <p>Ödenen Tutar</p>
                </div>
                <div class="stat-card overdue">
                    <h3><?php echo $istatistik['geciken_firma']; ?></h3>
                    <p>Gecikme Var</p>
                </div>
            </div>
        </div>
        
        <div class="content">
            <?php if ($success_message): ?>
                <div class="message success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="message error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="table-container">
                <div class="table-header">
                    💼 Firma Borç Listesi
                </div>
                
                <?php if (empty($firmalar)): ?>
                    <div class="empty-state">
                        <h3>📋 Henüz Firma Eklenmemiş</h3>
                        <p>Sisteme firma eklemek için "Yeni Firma" butonuna tıklayın.</p>
                    </div>
                <?php else: ?>
                    <table class="firms-table">
                        <thead>
                            <tr>
                                <th>Sıra</th>
                                <th>Firma Adı</th>
                                <th>Şehir</th>
                                <th>Telefon</th>
                                <th>Toplam Borç</th>
                                <th>Ödenen</th>
                                <th>Kalan Borç</th>
                                <th>Toplam Taksit</th>
                                <th>Başlangıç</th>
                                <th>Aylık Ödeme</th>
                                <th>Durum</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody id="firmsTableBody">
                            <?php foreach ($firmalar as $index => $firma): ?>
                            <tr>
                                <td><strong><?php echo $index + 1; ?></strong></td>
                                <td><strong><?php echo htmlspecialchars($firma['firma_adi']); ?></strong></td>
                                <td><?php echo htmlspecialchars($firma['sehir']); ?></td>
                                <td><?php echo htmlspecialchars($firma['telefon']); ?></td>
                                <td class="amount">₺<?php echo number_format($firma['toplam_borc'], 0, ',', '.'); ?></td>
                                <td class="amount positive">₺<?php echo number_format($firma['odenen_tutar'], 0, ',', '.'); ?></td>
                                <td class="amount negative">₺<?php echo number_format($firma['kalan_borc'], 0, ',', '.'); ?></td>
                                <td class="amount"><?php echo $firma['taksit_sayisi']; ?></td>
                                <td><?php echo date('d.m.Y', strtotime($firma['baslangic_tarihi'])); ?></td>
                                <td class="amount">₺<?php echo number_format($firma['aylik_odeme'], 0, ',', '.'); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $firma['durum']; ?>">
                                        <?php 
                                        switch($firma['durum']) {
                                            case 'aktif': echo 'Aktif'; break;
                                            case 'gecikme': echo 'Gecikme'; break;
                                            case 'tamamlandi': echo 'Tamamlandı'; break;
                                            default: echo 'Aktif';
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="firma_detay.php?id=<?php echo $firma['id']; ?>" class="btn btn-primary">📋 Detay</a>
                                    <button class="btn btn-danger" onclick="confirmDelete(<?php echo $firma['id']; ?>, '<?php echo htmlspecialchars($firma['firma_adi']); ?>')">🗑️ Sil</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Yeni Firma Ekleme Modalı -->
    <div id="addFirmaModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addFirmaModal')">&times;</span>
            <h2>🏢 Yeni Firma Ekle</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Firma Adı:</label>
                    <input type="text" name="firma_adi" required>
                </div>
                <div class="form-group">
                    <label>Şehir:</label>
                    <input type="text" name="sehir" required>
                </div>
                <div class="form-group">
                    <label>Telefon:</label>
                    <input type="tel" name="telefon">
                </div>
                <div class="form-group">
                    <label>Toplam Borç (₺):</label>
                    <input type="number" name="toplam_borc" required min="1" step="0.01">
                </div>
                <div class="form-group">
                    <label>Aylık Ödeme (₺):</label>
                    <input type="number" name="aylik_odeme" required min="1" step="0.01">
                </div>
                <div class="form-group">
                    <label>Başlangıç Tarihi:</label>
                    <input type="date" name="baslangic_tarihi" required>
                </div>
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addFirmaModal')">İptal</button>
                    <button type="submit" name="add_firma" class="btn btn-success">💾 Firma Ekle</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Silme Formu (gizli) -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="firma_id" id="delete_firma_id">
        <input type="hidden" name="delete_firma" value="1">
    </form>

    <script>
        function filterTable() {
            const searchInput = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#firmsTableBody tr');
            
            let visibleIndex = 1;
            rows.forEach(row => {
                const firmaAdi = row.cells[1].textContent.toLowerCase();
                const sehir = row.cells[2].textContent.toLowerCase();
                
                const matchesSearch = firmaAdi.includes(searchInput) || sehir.includes(searchInput);
                
                if (matchesSearch) {
                    row.style.display = '';
                    row.cells[0].innerHTML = `<strong>${visibleIndex}</strong>`;
                    visibleIndex++;
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function showAddFirmaModal() {
            document.getElementById('addFirmaModal').style.display = 'block';
        }

        function confirmDelete(firmaId, firmaAdi) {
            if (confirm(`"${firmaAdi}" firmasını silmek istediğinizden emin misiniz?\n\nBu işlem geri alınamaz!`)) {
                document.getElementById('delete_firma_id').value = firmaId;
                document.getElementById('deleteForm').submit();
            }
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Modal dışına tıklandığında kapat
        window.onclick = function(event) {
            const addModal = document.getElementById('addFirmaModal');
            if (event.target === addModal) {
                addModal.style.display = 'none';
            }
        }
    </script>
</body>
</html>