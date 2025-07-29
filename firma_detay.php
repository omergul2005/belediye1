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

// Firma ID kontrolü
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: borc_takip.php');
    exit;
}

$firma_id = (int)$_GET['id'];
$success_message = '';
$error_message = '';

// Ödeme ekleme işlemi
if (isset($_POST['add_payment'])) {
    $odeme_tutari = (float)$_POST['odeme_tutari'];
    
    try {
        $pdo->beginTransaction();
        
        // Firma bilgilerini al
        $firma_stmt = $pdo->prepare("SELECT * FROM firmalar WHERE id = ?");
        $firma_stmt->execute([$firma_id]);
        $firma = $firma_stmt->fetch();
        
        if ($firma && $odeme_tutari <= $firma['kalan_borc'] && $odeme_tutari > 0) {
            // Kalan borcu güncelle
            $yeni_kalan = $firma['kalan_borc'] - $odeme_tutari;
            $durum = ($yeni_kalan <= 0) ? 'tamamlandi' : 'aktif';
            
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
            
            // Sayfayı yenile
            header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $firma_id);
            exit;
            
        } else {
            $error_message = "Geçersiz ödeme tutarı!";
        }
        
    } catch(PDOException $e) {
        $pdo->rollback();
        $error_message = "Ödeme kaydedilirken hata oluştu!";
    }
}

// Firma bilgilerini al
try {
    $stmt = $pdo->prepare("
        SELECT 
            f.*,
            (f.toplam_borc - f.kalan_borc) as odenen_tutar
        FROM firmalar f 
        WHERE f.id = ?
    ");
    $stmt->execute([$firma_id]);
    $firma = $stmt->fetch();
    
    if (!$firma) {
        header('Location: borc_takip.php');
        exit;
    }
} catch(PDOException $e) {
    $error_message = "Firma bilgileri bulunamadı!";
    $firma = null;
}

// Ödeme geçmişini al
try {
    $odeme_stmt = $pdo->prepare("
        SELECT * FROM taksitler 
        WHERE firma_id = ? AND durum = 'odendi'
        ORDER BY odeme_tarihi DESC
    ");
    $odeme_stmt->execute([$firma_id]);
    $odemeler = $odeme_stmt->fetchAll();
} catch(PDOException $e) {
    $odemeler = [];
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firma Detay - <?php echo $firma ? htmlspecialchars($firma['firma_adi']) : 'Firma'; ?></title>
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
        
        .header .nav-links a {
            color: white;
            text-decoration: none;
            margin: 0 10px;
            transition: all 0.3s ease;
        }
        
        .header .nav-links a:hover {
            text-decoration: underline;
        }
        
        .content {
            flex: 1;
            padding: 30px;
        }
        
        .firma-info {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .firma-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .firma-title h2 {
            color: #2c5aa0;
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .firma-title p {
            color: #666;
            font-size: 16px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .info-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        
        .info-card h4 {
            color: #333;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .info-card .value {
            font-size: 24px;
            font-weight: 700;
            color: #007bff;
        }
        
        .info-card.negative .value {
            color: #dc3545;
        }
        
        .info-card.positive .value {
            color: #28a745;
        }
        
        .progress-section {
            margin-top: 20px;
            padding: 20px;
            background: #f1f3f4;
            border-radius: 8px;
        }
        
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
            transition: width 0.3s ease;
        }
        
        .taksit-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .taksit-header {
            background: #2c5aa0;
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .taksit-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .taksit-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
        }
        
        .taksit-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        
        .taksit-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-odendi {
            background: #d4edda;
            color: #155724;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
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
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .amount {
            font-weight: 600;
            text-align: right;
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
            margin: 10% auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 400px;
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
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .firma-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .content {
                padding: 15px;
            }
            
            .taksit-table {
                font-size: 12px;
            }
            
            .taksit-table th,
            .taksit-table td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Firma Detay Sayfası</h1>
            <div class="nav-links">
                <a href="borc_takip.php">← Borç Takip</a>
                <a href="dashboard.php">🏠 Anasayfa</a>
            </div>
        </div>
        
        <div class="content">
            <?php if ($success_message): ?>
                <div class="message success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="message error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <?php if ($firma): ?>
                <div class="firma-info">
                    <div class="firma-header">
                        <div class="firma-title">
                            <h2><?php echo htmlspecialchars($firma['firma_adi']); ?></h2>
                            <p><?php echo htmlspecialchars($firma['sehir']); ?> • <?php echo htmlspecialchars($firma['telefon']); ?></p>
                        </div>
                        <?php if ($firma['kalan_borc'] > 0): ?>
                            <button class="btn btn-success" onclick="showPaymentModal()">💰 Ödeme Ekle</button>
                        <?php endif; ?>
                    </div>
                    
                    <div class="info-grid">
                        <div class="info-card">
                            <h4>Toplam Borç</h4>
                            <div class="value">₺<?php echo number_format($firma['toplam_borc'], 0, ',', '.'); ?></div>
                        </div>
                        <div class="info-card positive">
                            <h4>Ödenen Tutar</h4>
                            <div class="value">₺<?php echo number_format($firma['odenen_tutar'], 0, ',', '.'); ?></div>
                        </div>
                        <div class="info-card negative">
                            <h4>Kalan Borç</h4>
                            <div class="value">₺<?php echo number_format($firma['kalan_borc'], 0, ',', '.'); ?></div>
                        </div>
                        <div class="info-card">
                            <h4>Aylık Ödeme</h4>
                            <div class="value">₺<?php echo number_format($firma['aylik_odeme'], 0, ',', '.'); ?></div>
                        </div>
                        <div class="info-card">
                            <h4>Toplam Taksit</h4>
                            <div class="value"><?php echo $firma['taksit_sayisi']; ?> Ay</div>
                        </div>
                        <div class="info-card">
                            <h4>Ödenen Taksit</h4>
                            <div class="value"><?php echo count($odemeler); ?> / <?php echo $firma['taksit_sayisi']; ?></div>
                        </div>
                    </div>
                    
                    <div class="progress-section">
                        <h4>Ödeme İlerlemesi</h4>
                        <?php 
                        $progress = $firma['toplam_borc'] > 0 ? (($firma['odenen_tutar'] / $firma['toplam_borc']) * 100) : 0;
                        ?>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                        </div>
                        <p><strong><?php echo number_format($progress, 1); ?>%</strong> tamamlandı</p>
                    </div>
                </div>
                
                <!-- Ödeme Geçmişi -->
                <?php if (!empty($odemeler)): ?>
                <div class="taksit-container">
                    <div class="taksit-header">
                        <h3>💳 Ödeme Geçmişi</h3>
                        <span><?php echo count($odemeler); ?> Ödeme</span>
                    </div>
                    
                    <table class="taksit-table">
                        <thead>
                            <tr>
                                <th>Sıra</th>
                                <th>Ödeme Tarihi</th>
                                <th>Tutar</th>
                                <th>Durum</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($odemeler as $index => $odeme): ?>
                            <tr>
                                <td><strong><?php echo $index + 1; ?>. Ödeme</strong></td>
                                <td><?php echo date('d.m.Y', strtotime($odeme['odeme_tarihi'])); ?></td>
                                <td class="amount">₺<?php echo number_format($odeme['tutar'], 0, ',', '.'); ?></td>
                                <td>
                                    <span class="status-badge status-odendi">
                                        ✅ Ödendi
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="taksit-container">
                    <div class="taksit-header">
                        <h3>💳 Ödeme Geçmişi</h3>
                        <span>0 Ödeme</span>
                    </div>
                    <div style="padding: 40px; text-align: center; color: #666;">
                        <h4>Henüz ödeme yapılmamış</h4>
                        <p>Bu firma için henüz ödeme kaydı bulunmamaktadır.</p>
                    </div>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="message error">Firma bilgileri bulunamadı!</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Ödeme Modalı -->
    <?php if ($firma && $firma['kalan_borc'] > 0): ?>
    <div id="paymentModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('paymentModal')">&times;</span>
            <h2>💰 Ödeme Ekle</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Firma:</label>
                    <input type="text" value="<?php echo htmlspecialchars($firma['firma_adi']); ?>" readonly style="background: #f8f9fa;">
                </div>
                <div class="form-group">
                    <label>Kalan Borç:</label>
                    <input type="text" value="₺<?php echo number_format($firma['kalan_borc'], 0, ',', '.'); ?>" readonly style="background: #f8f9fa;">
                </div>
                <div class="form-group">
                    <label>Ödeme Tutarı (₺):</label>
                    <input type="number" name="odeme_tutari" required min="1" max="<?php echo $firma['kalan_borc']; ?>" step="0.01" placeholder="Ödeme tutarını girin">
                </div>
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('paymentModal')">İptal</button>
                    <button type="submit" name="add_payment" class="btn btn-success">💾 Ödeme Kaydet</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
        function showPaymentModal() {
            document.getElementById('paymentModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Modal dışına tıklandığında kapat
        window.onclick = function(event) {
            const paymentModal = document.getElementById('paymentModal');
            if (event.target === paymentModal) {
                paymentModal.style.display = 'none';
            }
        }
    </script>
</body>
</html>