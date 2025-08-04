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

// DÜZELTME: Ödeme ekleme işlemi - KÜSURAT ÇÖZÜMLÜ
if (isset($_POST['add_payment'])) {
    $odeme_tutari = (float)$_POST['odeme_tutari'];
    
    try {
        $pdo->beginTransaction();
        
        // Firma bilgilerini al
        $firma_stmt = $pdo->prepare("SELECT * FROM firmalar WHERE id = ?");
        $firma_stmt->execute([$firma_id]);
        $firma = $firma_stmt->fetch();
        
        if ($firma && $odeme_tutari > 0 && $odeme_tutari <= $firma['kalan_borc']) {
            
            // Ödenen taksit sayısını hesapla
            $odenen_taksit_stmt = $pdo->prepare("SELECT COUNT(*) as odenen_sayisi FROM taksitler WHERE firma_id = ? AND durum = 'odendi'");
            $odenen_taksit_stmt->execute([$firma_id]);
            $odenen_taksit_sayisi = $odenen_taksit_stmt->fetch()['odenen_sayisi'];
            
            // Kalan taksit sayısını hesapla
            $kalan_taksit_sayisi = $firma['taksit_sayisi'] - $odenen_taksit_sayisi;
            
            // KÜSURAT ÇÖZÜMÜ: Normal ve son taksit tutarlarını hesapla
            $normal_taksit_tutari = floor($firma['toplam_borc'] / $firma['taksit_sayisi']);
            $son_taksit_tutari = $firma['toplam_borc'] - ($normal_taksit_tutari * ($firma['taksit_sayisi'] - 1));
            
            // ======= SON TAKSİT KONTROLÜ =======
            if ($kalan_taksit_sayisi <= 1) {
                // SON TAKSİT - KALAN BORÇ NE KADARSA O TUTARI ÖDE
                $required_payment = $firma['kalan_borc'];
                
                if ($odeme_tutari != $required_payment) {
                    $error_message = "SON TAKSİT: Kalan borç tutarını tam olarak ödemelisiniz: ₺" . number_format($required_payment, 2, ',', '.') . " TL";
                    $pdo->rollback();
                } else {
                    // SON TAKSİT ÖDEMESİ - BORÇ TAMAMEN BİTİYOR
                    
                    // 1. Firmayı tamamen bitir
                    $update_stmt = $pdo->prepare("
                        UPDATE firmalar 
                        SET kalan_borc = 0, durum = 'tamamlandi', aylik_odeme = 0
                        WHERE id = ?
                    ");
                    $update_stmt->execute([$firma_id]);
                    
                    // 2. Son taksiti gerçek kalan borç tutarıyla güncelle
                    $taksit_stmt = $pdo->prepare("
                        INSERT INTO taksitler (firma_id, tutar, vade_tarihi, durum, odeme_tarihi) 
                        VALUES (?, ?, CURDATE(), 'odendi', CURDATE())
                    ");
                    $taksit_stmt->execute([$firma_id, $required_payment]);
                    
                    $pdo->commit();
                    $success_message = "🎉 SON TAKSİT ÖDENDİ! Borç tamamen kapandı! Ödenen: ₺" . number_format($required_payment, 2, ',', '.') . " - KALAN BORÇ: ₺0";
                    
                    // Sayfayı yenile
                    header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $firma_id);
                    exit;
                }
                
            } else {
                // NORMAL TAKSİT - NORMAL TAKSİT TUTARI KURALI
                
                if (abs($odeme_tutari - $normal_taksit_tutari) > 1) { // 1 TL tolerans
                    $error_message = "Normal taksit için belirlenen tutarı ödemelisiniz: ₺" . number_format($normal_taksit_tutari, 2, ',', '.') . " TL";
                    $pdo->rollback();
                } else {
                    // Normal taksit ödeme işlemi
                    $yeni_kalan = $firma['kalan_borc'] - $normal_taksit_tutari;
                    
                    // Negatif olursa sıfırla
                    if ($yeni_kalan < 0) $yeni_kalan = 0;
                    
                    $durum = ($yeni_kalan <= 0) ? 'tamamlandi' : 'aktif';
                    
                    // Firmayı güncelle
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
                    $taksit_stmt->execute([$firma_id, $normal_taksit_tutari]);
                    
                    $pdo->commit();
                    
                    if ($yeni_kalan <= 0) {
                        $success_message = "🎉 BORÇ TAMAMLANDI! Ödenen: ₺" . number_format($normal_taksit_tutari, 2, ',', '.') . " - KALAN BORÇ: ₺0";
                    } else {
                        $success_message = "✅ Taksit ödendi! Ödenen: ₺" . number_format($normal_taksit_tutari, 2, ',', '.') . " - Kalan: ₺" . number_format($yeni_kalan, 2, ',', '.');
                    }
                    
                    // Sayfayı yenile
                    header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $firma_id);
                    exit;
                }
            }
            
        } else {
            if ($odeme_tutari > $firma['kalan_borc']) {
                $error_message = "Ödeme tutarı kalan borçtan fazla olamaz! Maksimum: ₺" . number_format($firma['kalan_borc'], 2, ',', '.');
            } else {
                $error_message = "Geçersiz ödeme tutarı!";
            }
        }
        
    } catch(PDOException $e) {
        $pdo->rollback();
        $error_message = "Ödeme kaydedilirken hata oluştu!";
    }
}

// Taksit silme işlemi
if (isset($_POST['delete_taksit'])) {
    $taksit_id = (int)$_POST['taksit_id'];
    
    try {
        $pdo->beginTransaction();
        
        // Taksit bilgilerini al
        $taksit_stmt = $pdo->prepare("SELECT * FROM taksitler WHERE id = ? AND firma_id = ?");
        $taksit_stmt->execute([$taksit_id, $firma_id]);
        $taksit = $taksit_stmt->fetch();
        
        if ($taksit) {
            // Taksiti sil
            $delete_stmt = $pdo->prepare("DELETE FROM taksitler WHERE id = ?");
            $delete_stmt->execute([$taksit_id]);
            
            // Firma kalan borcunu güncelle (geri ekle)
            $update_stmt = $pdo->prepare("
                UPDATE firmalar 
                SET kalan_borc = kalan_borc + ?, durum = 'aktif'
                WHERE id = ?
            ");
            $update_stmt->execute([$taksit['tutar'], $firma_id]);
            
            $pdo->commit();
            $success_message = "Taksit başarıyla silindi!";
        }
        
    } catch(PDOException $e) {
        $pdo->rollback();
        $error_message = "Taksit silinirken hata oluştu!";
    }
}

// Taksit düzenleme işlemi
if (isset($_POST['edit_taksit'])) {
    $taksit_id = (int)$_POST['taksit_id'];
    $yeni_tutar = (float)$_POST['yeni_tutar'];
    $yeni_tarih = $_POST['yeni_tarih'];
    
    try {
        $pdo->beginTransaction();
        
        // Eski taksit bilgilerini al
        $old_taksit_stmt = $pdo->prepare("SELECT * FROM taksitler WHERE id = ? AND firma_id = ?");
        $old_taksit_stmt->execute([$taksit_id, $firma_id]);
        $old_taksit = $old_taksit_stmt->fetch();
        
        if ($old_taksit) {
            // Taksiti güncelle
            $update_taksit_stmt = $pdo->prepare("
                UPDATE taksitler 
                SET tutar = ?, odeme_tarihi = ?
                WHERE id = ?
            ");
            $update_taksit_stmt->execute([$yeni_tutar, $yeni_tarih, $taksit_id]);
            
            // Firma kalan borcunu güncelle
            $fark = $yeni_tutar - $old_taksit['tutar'];
            $update_firma_stmt = $pdo->prepare("
                UPDATE firmalar 
                SET kalan_borc = kalan_borc - ?
                WHERE id = ?
            ");
            $update_firma_stmt->execute([$fark, $firma_id]);
            
            $pdo->commit();
            $success_message = "Taksit başarıyla güncellendi!";
        }
        
    } catch(PDOException $e) {
        $pdo->rollback();
        $error_message = "Taksit güncellenirken hata oluştu!";
    }
}

// Ödenmedi yap işlemi
if (isset($_POST['mark_unpaid'])) {
    $taksit_id = (int)$_POST['taksit_id'];
    
    try {
        $pdo->beginTransaction();
        
        // Taksit bilgilerini al
        $taksit_stmt = $pdo->prepare("SELECT * FROM taksitler WHERE id = ? AND firma_id = ?");
        $taksit_stmt->execute([$taksit_id, $firma_id]);
        $taksit = $taksit_stmt->fetch();
        
        if ($taksit) {
            // Taksiti ödenmedi olarak işaretle
            $update_stmt = $pdo->prepare("
                UPDATE taksitler 
                SET durum = 'bekliyor', odeme_tarihi = NULL
                WHERE id = ?
            ");
            $update_stmt->execute([$taksit_id]);
            
            // Firma kalan borcunu güncelle (geri ekle)
            $update_firma_stmt = $pdo->prepare("
                UPDATE firmalar 
                SET kalan_borc = kalan_borc + ?, durum = 'aktif'
                WHERE id = ?
            ");
            $update_firma_stmt->execute([$taksit['tutar'], $firma_id]);
            
            $pdo->commit();
            $success_message = "Taksit ödenmedi olarak işaretlendi!";
        }
        
    } catch(PDOException $e) {
        $pdo->rollback();
        $error_message = "İşlem sırasında hata oluştu!";
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
    <!-- CSS kodu aynı kalacak - kısaltma için dahil etmiyorum -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .container {
            width: 100vw;
            min-height: 100vh;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            display: flex;
            flex-direction: column;
        }
        
        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 25px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 8px 32px rgba(0,0,0,0.15);
        }
        
        .header h1 {
            font-size: 28px;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .header .nav-links a {
            color: white;
            text-decoration: none;
            margin: 0 10px;
            transition: all 0.3s ease;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
        }
        
        .header .nav-links a:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
        }
        
        .content {
            flex: 1;
            padding: 30px;
        }
        
        .firma-info {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
            border: 2px solid rgba(255,255,255,0.3);
        }
        
        .firma-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 3px solid #3498db;
        }
        
        .firma-title h2 {
            color: #2c3e50;
            font-size: 32px;
            margin-bottom: 5px;
            background: linear-gradient(135deg, #2c3e50, #3498db);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .firma-title p {
            color: #666;
            font-size: 18px;
            font-weight: 600;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }
        
        .info-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(15px);
            padding: 25px;
            border-radius: 15px;
            border-left: 5px solid #3498db;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .info-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }
        
        .info-card:hover::before {
            left: 100%;
        }
        
        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 50px rgba(0,0,0,0.15);
        }
        
        .info-card h4 {
            color: #333;
            margin-bottom: 10px;
            font-size: 16px;
            position: relative;
            z-index: 2;
        }
        
        .info-card .value {
            font-size: 28px;
            font-weight: 700;
            color: #3498db;
            position: relative;
            z-index: 2;
        }
        
        .info-card.negative {
            border-left-color: #e74c3c;
        }
        
        .info-card.negative .value {
            color: #e74c3c;
        }
        
        .info-card.positive {
            border-left-color: #27ae60;
        }
        
        .info-card.positive .value {
            color: #27ae60;
        }
        
        .progress-section {
            margin-top: 25px;
            padding: 25px;
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.1), rgba(155, 89, 182, 0.05));
            border-radius: 15px;
            border: 2px solid rgba(52, 152, 219, 0.2);
        }
        
        .progress-bar {
            width: 100%;
            height: 25px;
            background: rgba(233, 236, 239, 0.8);
            border-radius: 15px;
            overflow: hidden;
            margin: 15px 0;
            box-shadow: inset 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #27ae60 0%, #2ecc71 50%, #58d68d 100%);
            transition: width 1s ease;
            position: relative;
            overflow: hidden;
        }
        
        .progress-fill::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            animation: progressShine 2s infinite;
        }
        
        @keyframes progressShine {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        .taksit-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.1);
            overflow: hidden;
            border: 2px solid rgba(255,255,255,0.3);
        }
        
        .taksit-header {
            background: linear-gradient(135deg, #34495e, #2c3e50);
            color: white;
            padding: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .taksit-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .taksit-table th {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 18px 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .taksit-table td {
            padding: 18px 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
            transition: all 0.3s ease;
        }
        
        .taksit-table tbody tr {
            transition: all 0.3s ease;
        }
        
        .taksit-table tbody tr:hover {
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.05), rgba(155, 89, 182, 0.02));
            transform: scale(1.01);
        }
        
        .status-badge {
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .status-odendi {
            background: linear-gradient(135deg, rgba(39, 174, 96, 0.2), rgba(46, 204, 113, 0.1));
            color: #155724;
            border: 2px solid rgba(39, 174, 96, 0.3);
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            margin: 2px;
            position: relative;
            overflow: hidden;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #27ae60, #229954);
            color: white;
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.4);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            color: white;
            box-shadow: 0 5px 15px rgba(243, 156, 18, 0.4);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.4);
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        .amount {
            font-weight: 600;
            text-align: right;
            font-family: 'Courier New', monospace;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
            backdrop-filter: blur(8px);
        }
        
        .modal-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            margin: 5% auto;
            padding: 30px;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 30px 80px rgba(0,0,0,0.3);
            border: 2px solid rgba(255,255,255,0.3);
            position: relative;
            overflow: hidden;
        }
        
        .modal-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.05), rgba(155, 89, 182, 0.05));
            pointer-events: none;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 32px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            z-index: 2;
        }
        
        .close:hover {
            color: #e74c3c;
            transform: scale(1.2) rotate(90deg);
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: rgba(255,255,255,0.9);
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 15px rgba(52, 152, 219, 0.2);
            transform: translateY(-2px);
        }
        
        .form-group input:read-only {
            background: #f8f9fa;
            color: #666;
        }
        
        .message {
            padding: 18px 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-weight: 600;
            border-left: 5px solid;
            backdrop-filter: blur(10px);
        }
        
        .success {
            background: linear-gradient(135deg, rgba(39, 174, 96, 0.15), rgba(46, 204, 113, 0.1));
            color: #155724;
            border-color: #27ae60;
            box-shadow: 0 8px 25px rgba(39, 174, 96, 0.2);
        }
        
        .error {
            background: linear-gradient(135deg, rgba(231, 76, 60, 0.15), rgba(192, 57, 43, 0.1));
            color: #721c24;
            border-color: #e74c3c;
            box-shadow: 0 8px 25px rgba(231, 76, 60, 0.2);
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
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
                padding: 10px 8px;
            }
            
            .btn {
                padding: 8px 12px;
                font-size: 12px;
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
                <?php
                // KÜSURAT ÇÖZÜMÜ: Hesaplamalar
                $normal_taksit_tutari = floor($firma['toplam_borc'] / $firma['taksit_sayisi']);
                $son_taksit_tutari = $firma['toplam_borc'] - ($normal_taksit_tutari * ($firma['taksit_sayisi'] - 1));
                $odenen_taksit_sayisi = count($odemeler);
                $kalan_taksit_sayisi = $firma['taksit_sayisi'] - $odenen_taksit_sayisi;
                ?>
                
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
                            <h4>Normal Taksit (İlk <?php echo $firma['taksit_sayisi'] - 1; ?>)</h4>
                            <div class="value">₺<?php echo number_format($normal_taksit_tutari, 0, ',', '.'); ?></div>
                        </div>
                        <div class="info-card">
                            <h4>Son Taksit (<?php echo $firma['taksit_sayisi']; ?>.)</h4>
                            <div class="value">₺<?php echo number_format($son_taksit_tutari, 0, ',', '.'); ?></div>
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
                        
                        <!-- KÜSURAT BİLGİ KUTUSU -->
                        <?php if ($kalan_taksit_sayisi <= 1 && $firma['kalan_borc'] > 0): ?>
                        <div style="margin-top: 15px; padding: 15px; background: rgba(255, 193, 7, 0.1); border-radius: 8px; border-left: 4px solid #ffc107;">
                            <strong style="color: #856404;">⚠️ SON TAKSİT:</strong> 
                            <span style="color: #856404;">Bu son taksittir. Kalan borç tutarı <strong>₺<?php echo number_format($firma['kalan_borc'], 0, ',', '.'); ?></strong> olarak ödenmelidir.</span>
                        </div>
                        <?php else: ?>
                        <div style="margin-top: 15px; padding: 15px; background: rgba(76, 175, 80, 0.1); border-radius: 8px; border-left: 4px solid #4caf50;">
                            <strong style="color: #2e7d32;">💡 Ödeme İpucu:</strong> 
                            <span style="color: #2e7d32;">Normal taksit için <strong>₺<?php echo number_format($normal_taksit_tutari, 0, ',', '.'); ?></strong> tutarında ödeme yapılmalıdır.</span>
                        </div>
                        <?php endif; ?>
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
                                <th>İşlemler</th>
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
                                <td>
                                    <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                        <button class="btn btn-warning" onclick="editTaksit(<?php echo $odeme['id']; ?>, <?php echo $odeme['tutar']; ?>, '<?php echo $odeme['odeme_tarihi']; ?>')">
                                            ✏️ Düzenle
                                        </button>
                                        <button class="btn btn-secondary" onclick="markUnpaid(<?php echo $odeme['id']; ?>)">
                                            ↩️ Ödenmedi Yap
                                        </button>
                                        <button class="btn btn-danger" onclick="deleteTaksit(<?php echo $odeme['id']; ?>)">
                                            🗑️ Sil
                                        </button>
                                    </div>
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
                    <div style="padding: 60px; text-align: center; color: #666;">
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

    <!-- DÜZELTME: Ödeme Modalı - KÜSURAT ÇÖZÜMLÜ -->
    <?php if ($firma && $firma['kalan_borc'] > 0): ?>
    <div id="paymentModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('paymentModal')">&times;</span>
            <h2>💰 Ödeme Ekle (KÜSURAT ÇÖZÜMLÜ)</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Firma:</label>
                    <input type="text" value="<?php echo htmlspecialchars($firma['firma_adi']); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Kalan Borç:</label>
                    <input type="text" value="₺<?php echo number_format($firma['kalan_borc'], 0, ',', '.'); ?>" readonly>
                </div>
                
                <?php if ($kalan_taksit_sayisi <= 1): ?>
                <!-- SON TAKSİT -->
                <div class="form-group">
                    <label>SON TAKSİT - Kalan Borç Tutarı (Zorunlu):</label>
                    <input type="number" name="odeme_tutari" value="<?php echo $firma['kalan_borc']; ?>" readonly style="background: #fff3cd; font-weight: bold; font-size: 16px; color: #856404; border: 2px solid #ffc107;">
                    <small style="color: #856404; font-size: 12px; margin-top: 5px; display: block;">
                        * SON TAKSİT: Kalan borç tutarının tamamı ödenmelidir (₺<?php echo number_format($firma['kalan_borc'], 0, ',', '.'); ?> TL)
                    </small>
                </div>
                <?php else: ?>
                <!-- NORMAL TAKSİT -->
                <div class="form-group">
                    <label>Normal Taksit Tutarı (Zorunlu):</label>
                    <input type="number" name="odeme_tutari" value="<?php echo $normal_taksit_tutari; ?>" readonly style="background: #d4edda; font-weight: bold; font-size: 16px; color: #155724; border: 2px solid #28a745;">
                    <small style="color: #155724; font-size: 12px; margin-top: 5px; display: block;">
                        * Normal taksit için belirlenen tutar ödenmelidir (₺<?php echo number_format($normal_taksit_tutari, 0, ',', '.'); ?> TL)
                    </small>
                </div>
                <?php endif; ?>
                
                <div style="text-align: right; margin-top: 25px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('paymentModal')">İptal</button>
                    <button type="submit" name="add_payment" class="btn btn-success">💾 Ödeme Kaydet</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Taksit Düzenleme Modalı -->
    <div id="editTaksitModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editTaksitModal')">&times;</span>
            <h2>✏️ Tarih Düzenle</h2>
            <form method="POST">
                <input type="hidden" name="taksit_id" id="edit_taksit_id">
                <input type="hidden" name="yeni_tutar" id="edit_taksit_tutar">
                <div class="form-group">
                    <label>Ödeme Tarihi:</label>
                    <input type="date" name="yeni_tarih" id="edit_taksit_tarih" required>
                </div>
                <div style="text-align: right; margin-top: 25px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editTaksitModal')">İptal</button>
                    <button type="submit" name="edit_taksit" class="btn btn-success">💾 Tarihi Güncelle</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Gizli Formlar -->
    <form id="deleteTaksitForm" method="POST" style="display: none;">
        <input type="hidden" name="taksit_id" id="delete_taksit_id">
        <input type="hidden" name="delete_taksit" value="1">
    </form>

    <form id="markUnpaidForm" method="POST" style="display: none;">
        <input type="hidden" name="taksit_id" id="unpaid_taksit_id">
        <input type="hidden" name="mark_unpaid" value="1">
    </form>

    <script>
        // Modal açma fonksiyonları
        function showPaymentModal() {
            document.getElementById('paymentModal').style.display = 'block';
        }

        function editTaksit(taksitId, tutar, tarih) {
            document.getElementById('edit_taksit_id').value = taksitId;
            document.getElementById('edit_taksit_tutar').value = tutar;
            document.getElementById('edit_taksit_tarih').value = tarih;
            document.getElementById('editTaksitModal').style.display = 'block';
        }

        // Taksit silme
        function deleteTaksit(taksitId) {
            if (confirm('Bu taksiti silmek istediğinizden emin misiniz?\n\nBu işlem geri alınamaz!')) {
                document.getElementById('delete_taksit_id').value = taksitId;
                document.getElementById('deleteTaksitForm').submit();
            }
        }

        // Ödenmedi yap
        function markUnpaid(taksitId) {
            if (confirm('Bu taksiti ödenmedi olarak işaretlemek istediğinizden emin misiniz?\n\nKalan borç tekrar artacaktır!')) {
                document.getElementById('unpaid_taksit_id').value = taksitId;
                document.getElementById('markUnpaidForm').submit();
            }
        }

        // Modal kapatma
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // ESC tuşu ile modal kapatma
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modals = document.querySelectorAll('.modal');
                modals.forEach(modal => {
                    if (modal.style.display === 'block') {
                        modal.style.display = 'none';
                    }
                });
            }
        });

        // Modal dışına tıklandığında kapat
        window.onclick = function(event) {
            if (event.target.classList && event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        };

        // Sayfa yüklendiğinde animasyonlar
        document.addEventListener('DOMContentLoaded', function() {
            // Progress bar animasyonu
            const progressBar = document.querySelector('.progress-fill');
            if (progressBar) {
                const width = progressBar.style.width;
                progressBar.style.width = '0%';
                setTimeout(() => {
                    progressBar.style.width = width;
                }, 500);
            }

            // Kartların sırayla animasyonu
            const cards = document.querySelectorAll('.info-card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'all 0.5s ease';
                    
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 100);
                }, index * 100);
            });

            // Tablo satırlarının animasyonu
            const rows = document.querySelectorAll('.taksit-table tbody tr');
            rows.forEach((row, index) => {
                setTimeout(() => {
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(-20px)';
                    row.style.transition = 'all 0.3s ease';
                    
                    setTimeout(() => {
                        row.style.opacity = '1';
                        row.style.transform = 'translateX(0)';
                    }, 50);
                }, index * 100);
            });
        });

        // Bildirim sistemi
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 25px;
                border-radius: 10px;
                color: white;
                font-weight: 600;
                z-index: 10000;
                transform: translateX(300px);
                transition: all 0.3s ease;
                max-width: 300px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            `;
            
            if (type === 'success') {
                notification.style.background = 'linear-gradient(135deg, #27ae60, #229954)';
            } else if (type === 'error') {
                notification.style.background = 'linear-gradient(135deg, #e74c3c, #c0392b)';
            }
            
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);
            
            setTimeout(() => {
                notification.style.transform = 'translateX(300px)';
                setTimeout(() => {
                    if (document.body.contains(notification)) {
                        document.body.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }
    </script>
</body>
</html>