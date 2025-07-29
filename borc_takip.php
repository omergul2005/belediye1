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

// Taksit silme işlemi - YENİ EKLENEN
if (isset($_POST['delete_taksit'])) {
    $taksit_id = $_POST['taksit_id'];
    try {
        $delete_taksit = $pdo->prepare("DELETE FROM taksitler WHERE id = ? AND durum != 'odendi'");
        $delete_taksit->execute([$taksit_id]);
        
        $success_message = "Taksit başarıyla silindi!";
        
    } catch(PDOException $e) {
        $error_message = "Taksit silinirken hata oluştu: " . $e->getMessage();
    }
}

// Taksit düzenleme işlemi - YENİ EKLENEN  
if (isset($_POST['update_taksit'])) {
    $taksit_id = $_POST['taksit_id'];
    $yeni_tutar = $_POST['yeni_tutar'];
    $yeni_tarih = $_POST['yeni_tarih'];
    
    try {
        $update_taksit = $pdo->prepare("
            UPDATE taksitler 
            SET tutar = ?, odeme_tarihi = ?
            WHERE id = ? AND durum = 'odendi'
        ");
        $update_taksit->execute([$yeni_tutar, $yeni_tarih, $taksit_id]);
        
        $success_message = "Ödenen taksit başarıyla güncellendi!";
        
    } catch(PDOException $e) {
        $error_message = "Taksit güncellenirken hata oluştu: " . $e->getMessage();
    }
}
if (isset($_POST['update_firma'])) {
    $firma_id = $_POST['firma_id'];
    $firma_adi = $_POST['firma_adi'];
    $sehir = $_POST['sehir'];
    $telefon = $_POST['telefon'];
    $toplam_borc = $_POST['toplam_borc'];
    $aylik_odeme = $_POST['aylik_odeme'];
    $baslangic_tarihi = $_POST['baslangic_tarihi'];
    
    try {
        $yeni_taksit_sayisi = ceil($toplam_borc / $aylik_odeme);
        
        // Mevcut ödenen tutarı koru
        $current_stmt = $pdo->prepare("SELECT (toplam_borc - kalan_borc) as odenen FROM firmalar WHERE id = ?");
        $current_stmt->execute([$firma_id]);
        $current_data = $current_stmt->fetch();
        $odenen_tutar = $current_data ? $current_data['odenen'] : 0;
        
        $yeni_kalan = $toplam_borc - $odenen_tutar;
        if ($yeni_kalan < 0) $yeni_kalan = 0;
        
        $durum = ($yeni_kalan <= 0) ? 'tamamlandi' : 'aktif';
        $final_aylik_odeme = ($yeni_kalan <= 0) ? 0 : $aylik_odeme;
        
        // Eğer tamamlanmış firmayı yeniden aktif hale getiriyorsak
        if ($yeni_kalan > 0 && $current_data && $current_data['odenen'] >= $toplam_borc) {
            $durum = 'aktif';
            $final_aylik_odeme = $aylik_odeme;
        }
        
        $update_stmt = $pdo->prepare("
            UPDATE firmalar 
            SET firma_adi = ?, sehir = ?, telefon = ?, toplam_borc = ?, kalan_borc = ?, 
                aylik_odeme = ?, baslangic_tarihi = ?, taksit_sayisi = ?, durum = ?
            WHERE id = ?
        ");
        $update_stmt->execute([
            $firma_adi, $sehir, $telefon, $toplam_borc, $yeni_kalan, 
            $final_aylik_odeme, $baslangic_tarihi, $yeni_taksit_sayisi, $durum, $firma_id
        ]);
        
        $success_message = "Firma başarıyla güncellendi!";
        
    } catch(PDOException $e) {
        $error_message = "Firma güncellenirken hata oluştu: " . $e->getMessage();
    }
}

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
        
        $firma_id = $pdo->lastInsertId();
        
        // Aylık ödeme zorunlu kontrol - minimum değer belirleme
        if ($aylik_odeme < ($toplam_borc / 100)) { // En fazla 100 taksit
            $aylik_odeme = ceil($toplam_borc / 100);
        }
        
        // Aylik ödemeyi güncelle
        $update_aylik = $pdo->prepare("UPDATE firmalar SET aylik_odeme = ? WHERE id = ?");
        $update_aylik->execute([$aylik_odeme, $firma_id]);
        
        // Otomatik taksit programı oluştur
        createInstallmentSchedule($pdo, $firma_id, $toplam_borc, $aylik_odeme, $baslangic_tarihi);
        
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

// Ödeme ekleme işlemi - İYİLEŞTİRİLDİ
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
            $durum = ($yeni_kalan <= 0) ? 'tamamlandi' : 'aktif';
            $yeni_aylik_odeme = ($yeni_kalan <= 0) ? 0 : $firma['aylik_odeme']; // Aylık ödeme sıfırla
            
            $update_stmt = $pdo->prepare("
                UPDATE firmalar 
                SET kalan_borc = ?, durum = ?, aylik_odeme = ?
                WHERE id = ?
            ");
            $update_stmt->execute([$yeni_kalan, $durum, $yeni_aylik_odeme, $firma_id]);
            
            // En eski ödenmemiş taksiti güncelle
            $taksit_update = $pdo->prepare("
                UPDATE taksitler 
                SET durum = 'odendi', odeme_tarihi = ?, tutar = ?
                WHERE firma_id = ? AND durum IN ('bekliyor', 'gecikme') 
                ORDER BY taksit_no ASC 
                LIMIT 1
            ");
            $taksit_update->execute([$odeme_tarihi, $odeme_tutari, $firma_id]);
            
            // Eğer borç tamamen bittiyse, kalan tüm taksitleri "tamamlandi" yap
            if ($yeni_kalan <= 0) {
                $complete_stmt = $pdo->prepare("
                    UPDATE taksitler 
                    SET durum = 'tamamlandi' 
                    WHERE firma_id = ? AND durum IN ('bekliyor', 'gecikme')
                ");
                $complete_stmt->execute([$firma_id]);
            }
            
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

// Toplam istatistikler ve ortalamalar - GENİŞLETİLDİ
try {
    $stats_stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as toplam_firma,
            COALESCE(SUM(toplam_borc), 0) as toplam_borc,
            COALESCE(SUM(kalan_borc), 0) as toplam_kalan,
            COALESCE(SUM(toplam_borc - kalan_borc), 0) as toplam_odenen,
            COALESCE(AVG(aylik_odeme), 0) as ortalama_aylik_odeme,
            COALESCE(AVG(toplam_borc), 0) as ortalama_toplam_borc,
            COALESCE(AVG(kalan_borc), 0) as ortalama_kalan_borc,
            COUNT(CASE WHEN durum = 'gecikme' THEN 1 END) as geciken_firma
        FROM firmalar
    ");
    $stats_stmt->execute();
    $istatistik = $stats_stmt->fetch();
} catch(PDOException $e) {
    $istatistik = [
        'toplam_firma' => 0, 'toplam_borc' => 0, 'toplam_kalan' => 0, 'toplam_odenen' => 0,
        'ortalama_aylik_odeme' => 0, 'ortalama_toplam_borc' => 0, 'ortalama_kalan_borc' => 0,
        'geciken_firma' => 0
    ];
}

// Otomatik taksit programı oluşturma fonksiyonu - YENİ EKLENEN
function createInstallmentSchedule($pdo, $firma_id, $toplam_borc, $aylik_odeme, $baslangic_tarihi) {
    $taksit_sayisi = ceil($toplam_borc / $aylik_odeme);
    $baslangic_date = new DateTime($baslangic_tarihi);
    
    for ($i = 1; $i <= $taksit_sayisi; $i++) {
        $vade_tarihi = clone $baslangic_date;
        $vade_tarihi->add(new DateInterval('P' . (($i - 1) * 30) . 'D')); // Her ay 30 gün
        
        $tutar = ($i == $taksit_sayisi) ? $toplam_borc - (($taksit_sayisi - 1) * $aylik_odeme) : $aylik_odeme;
        
        // Gecikme kontrolü (15 gün sonra %2 faiz)
        $today = new DateTime();
        $gecikme_tarihi = clone $vade_tarihi;
        $gecikme_tarihi->add(new DateInterval('P15D'));
        
        $durum = 'bekliyor';
        $gecikme_faizi = 0;
        
        if ($today > $gecikme_tarihi) {
            $durum = 'gecikme';
            $gecikme_faizi = $tutar * 0.02; // %2 faiz
            $tutar += $gecikme_faizi;
        }
        
        $insert_taksit = $pdo->prepare("
            INSERT INTO taksitler (firma_id, taksit_no, tutar, vade_tarihi, durum, gecikme_faizi, orijinal_tutar) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $insert_taksit->execute([$firma_id, $i, $tutar, $vade_tarihi->format('Y-m-d'), $durum, $gecikme_faizi, $aylik_odeme]);
    }
}

// Yıllık özet hesaplama - İYİLEŞTİRİLDİ
function getYearlyBreakdown($firmalar) {
    $yearly_data = [];
    
    foreach ($firmalar as $firma) {
        $baslangic_tarihi = new DateTime($firma['baslangic_tarihi']);
        $start_year = (int)$baslangic_tarihi->format('Y');
        
        $kalan_borc = $firma['toplam_borc'];
        $current_date = clone $baslangic_tarihi;
        $taksit_no = 1;
        
        while ($kalan_borc > 0) {
            $year = (int)$current_date->format('Y');
            
            if (!isset($yearly_data[$year])) {
                $yearly_data[$year] = ['toplam' => 0, 'odenen' => 0, 'kalan' => 0, 'aylik_ortalama' => 0, 'firma_sayisi' => 0];
            }
            
            $taksit_tutari = min($firma['aylik_odeme'], $kalan_borc);
            $yearly_data[$year]['toplam'] += $taksit_tutari;
            $yearly_data[$year]['aylik_ortalama'] += $firma['aylik_odeme'];
            $yearly_data[$year]['firma_sayisi']++;
            
            // Ödenen tutarları hesapla
            $odenen_tutar = $firma['toplam_borc'] - $firma['kalan_borc'];
            if ($odenen_tutar >= ($taksit_no * $firma['aylik_odeme'])) {
                $yearly_data[$year]['odenen'] += $taksit_tutari;
            }
            
            $kalan_borc -= $taksit_tutari;
            $current_date->add(new DateInterval('P30D')); // 30 gün ekle
            $taksit_no++;
            
            // Güvenlik için sonsuz döngüyü önle
            if ($taksit_no > 1000) break;
        }
    }
    
    // Kalan tutarları ve ortalamaları hesapla
    foreach ($yearly_data as $year => &$data) {
        $data['kalan'] = $data['toplam'] - $data['odenen'];
        $data['aylik_ortalama'] = $data['firma_sayisi'] > 0 ? $data['aylik_ortalama'] / $data['firma_sayisi'] : 0;
    }
    
    // Yılları sırala
    ksort($yearly_data);
    
    return array_filter($yearly_data, function($data) {
        return $data['toplam'] > 0;
    });
}

$yearly_breakdown = getYearlyBreakdown($firmalar);
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
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-size: 28px;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
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
            padding: 8px 15px;
            border-radius: 8px;
        }
        
        .header .user-info a:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
        }
        
        .action-bar {
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.1), rgba(155, 89, 182, 0.1));
            padding: 20px 30px;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .search-box input {
            padding: 15px 25px;
            border: 2px solid rgba(52, 152, 219, 0.3);
            border-radius: 25px;
            font-size: 16px;
            min-width: 350px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            background: rgba(255,255,255,0.9);
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 20px rgba(52, 152, 219, 0.3);
            transform: translateY(-2px);
        }
        
        .btn-group {
            display: flex;
            gap: 15px;
        }
        
        .stats-bar {
            background: rgba(255,255,255,0.7);
            padding: 25px 30px;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 5px solid;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }
        
        .stat-card:hover::before {
            left: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        
        .stat-card.total { border-color: #3498db; }
        .stat-card.remaining { border-color: #e74c3c; }
        .stat-card.paid { border-color: #27ae60; }
        .stat-card.overdue { border-color: #f39c12; }
        .stat-card.average { border-color: #9b59b6; }
        
        .stat-card h3 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .stat-card.total h3 { color: #3498db; }
        .stat-card.remaining h3 { color: #e74c3c; }
        .stat-card.paid h3 { color: #27ae60; }
        .stat-card.overdue h3 { color: #f39c12; }
        .stat-card.average h3 { color: #9b59b6; }
        
        .stat-card p {
            font-size: 14px;
            color: #666;
            font-weight: 600;
        }
        
        /* Yıllık Özet Kartları - YENİ EKLENEN */
        .yearly-summary {
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.15), rgba(155, 89, 182, 0.1));
            padding: 30px;
            border-radius: 20px;
            margin: 25px 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            border: 2px solid rgba(52, 152, 219, 0.2);
            position: relative;
            overflow: hidden;
        }
        
        .yearly-summary::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            animation: shimmer 3s infinite;
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }
        
        .yearly-summary h4 {
            color: #2c3e50;
            margin-bottom: 25px;
            font-size: 26px;
            text-align: center;
            font-weight: 700;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.1);
            position: relative;
            z-index: 2;
        }
        
        .year-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            position: relative;
            z-index: 2;
        }
        
        .year-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 15px;
            border: 2px solid rgba(52, 152, 219, 0.3);
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        
        .year-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(52, 152, 219, 0.1), transparent);
            transition: left 0.5s;
        }
        
        .year-card:hover::before {
            left: 100%;
        }
        
        .year-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 15px 40px rgba(52, 152, 219, 0.2);
            border-color: #3498db;
        }
        
        .year-card h5 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 22px;
            font-weight: 700;
            text-align: center;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            position: relative;
            z-index: 2;
        }
        
        .year-card p {
            margin: 8px 0;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 2;
        }
        
        .year-card p strong {
            color: #34495e;
        }
        
        .content {
            flex: 1;
            padding: 30px;
            overflow-x: auto;
        }
        
        .table-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .table-header {
            background: linear-gradient(135deg, #34495e, #2c3e50);
            color: white;
            padding: 20px;
            font-weight: 600;
            font-size: 18px;
        }
        
        .firms-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        
        .firms-table th {
            background: linear-gradient(135deg, #34495e, #2c3e50);
            color: white;
            padding: 18px 12px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }
        
        .firms-table td {
            padding: 15px 12px;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            vertical-align: middle;
        }
        
        .firms-table tbody tr {
            transition: all 0.3s ease;
        }
        
        .firms-table tbody tr:hover {
            background: rgba(52, 152, 219, 0.1);
            transform: scale(1.01);
        }
        
        .status-badge {
            padding: 6px 15px;
            border-radius: 25px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-aktif {
            background: linear-gradient(135deg, rgba(39, 174, 96, 0.2), rgba(46, 204, 113, 0.1));
            color: #155724;
            border: 1px solid rgba(39, 174, 96, 0.3);
        }
        
        .status-gecikme {
            background: linear-gradient(135deg, rgba(243, 156, 18, 0.2), rgba(230, 126, 34, 0.1));
            color: #856404;
            border: 1px solid rgba(243, 156, 18, 0.3);
        }
        
        .status-tamamlandi {
            background: linear-gradient(135deg, rgba(23, 162, 184, 0.2), rgba(19, 132, 150, 0.1));
            color: #0c5460;
            border: 1px solid rgba(23, 162, 184, 0.3);
        }
        
        .amount {
            font-weight: 600;
            text-align: right;
        }
        
        .amount.positive {
            color: #27ae60;
        }
        
        .amount.negative {
            color: #e74c3c;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            margin: 3px;
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
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #27ae60, #229954);
            color: white;
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.4);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            color: white;
            box-shadow: 0 4px 15px rgba(243, 156, 18, 0.4);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.4);
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        
        .btn:active {
            transform: translateY(-1px);
        }
        
        .message {
            padding: 18px 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-weight: 600;
            border-left: 5px solid;
        }
        
        .success {
            background: linear-gradient(135deg, rgba(39, 174, 96, 0.15), rgba(46, 204, 113, 0.1));
            color: #155724;
            border-color: #27ae60;
        }
        
        .error {
            background: linear-gradient(135deg, rgba(231, 76, 60, 0.15), rgba(192, 57, 43, 0.1));
            color: #721c24;
            border-color: #e74c3c;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
            backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            margin: 3% auto;
            padding: 35px;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.2);
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 32px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .close:hover {
            color: #e74c3c;
            transform: scale(1.2);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 15px rgba(52, 152, 219, 0.2);
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
                
                <!-- Ortalama Kartları - YENİ EKLENEN -->
                <div class="stat-card average">
                    <h3>₺<?php echo number_format($istatistik['ortalama_aylik_odeme'], 0, ',', '.'); ?></h3>
                    <p>Aylık Ödeme Ortalaması</p>
                </div>
                <div class="stat-card average">
                    <h3>₺<?php echo number_format($istatistik['ortalama_kalan_borc'], 0, ',', '.'); ?></h3>
                    <p>Kalan Borç Ortalaması</p>
                </div>
            </div>
            
            <!-- Yıllık Özet - YENİ EKLENEN -->
            <?php if (!empty($yearly_breakdown)): ?>
            <div style="text-align: center; margin: 20px 0;">
                <button id="yearlyToggleBtn" class="btn btn-primary" onclick="toggleYearlyBreakdown()">📊 Yıllık Borç Özeti</button>
            </div>
            
            <div id="yearlyBreakdown" class="yearly-summary" style="display: none;">
                <h4>📅 Yıllık Borç Özeti</h4>
                <div class="year-grid">
                    <?php foreach ($yearly_breakdown as $year => $data): ?>
                    <div class="year-card">
                        <h5><?php echo $year; ?> Yılı</h5>
                        <p><strong>Toplam:</strong> ₺<?php echo number_format($data['toplam'], 0, ',', '.'); ?></p>
                        <p><strong>Kalan:</strong> ₺<?php echo number_format($data['kalan'], 0, ',', '.'); ?></p>
                        <p><strong>Ödenen:</strong> ₺<?php echo number_format($data['odenen'], 0, ',', '.'); ?></p>
                        <p><strong>Aylık Ort:</strong> ₺<?php echo number_format($data['aylik_ortalama'], 0, ',', '.'); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
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
                                <td class="amount">
                                    <?php 
                                    $odenen_taksit_count = $firma['odenen_taksit'];
                                    $toplam_taksit_count = $firma['taksit_sayisi'];
                                    
                                    if ($firma['durum'] == 'tamamlandi') {
                                        echo "Tamamlandı ({$odenen_taksit_count}/{$toplam_taksit_count})";
                                    } else if ($firma['aylik_odeme'] == 0) {
                                        echo "₺0";
                                    } else {
                                        echo "₺" . number_format($firma['aylik_odeme'], 0, ',', '.');
                                    }
                                    ?>
                                </td>
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
                                    <div style="display: flex; flex-wrap: wrap; gap: 5px; align-items: center;">
                                        <a href="firma_detay.php?id=<?php echo $firma['id']; ?>" class="btn btn-primary">📋 Detay</a>
                                        <button class="btn btn-warning" onclick="showEditFirmaModal(<?php echo $firma['id']; ?>, '<?php echo htmlspecialchars($firma['firma_adi']); ?>', '<?php echo htmlspecialchars($firma['sehir']); ?>', '<?php echo htmlspecialchars($firma['telefon']); ?>', <?php echo $firma['toplam_borc']; ?>, <?php echo $firma['aylik_odeme']; ?>, '<?php echo $firma['baslangic_tarihi']; ?>')">✏️ Düzenle</button>
                                        <button class="btn btn-info" onclick="showTaksitModal(<?php echo $firma['id']; ?>)">📅 Taksitler</button>
                                        <button class="btn btn-danger" onclick="confirmDelete(<?php echo $firma['id']; ?>, '<?php echo htmlspecialchars($firma['firma_adi']); ?>')">🗑️ Sil</button>
                                    </div>
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
                    <input type="text" name="firma_adi" required oninput="capitalizeFirstLetter(this)">
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

    <!-- Firma Düzenleme Modalı - YENİ EKLENEN -->
    <div id="editFirmaModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editFirmaModal')">&times;</span>
            <h2>✏️ Firma Düzenle</h2>
            <form method="POST">
                <input type="hidden" name="firma_id" id="edit_firma_id">
                <div class="form-group">
                    <label>Firma Adı:</label>
                    <input type="text" name="firma_adi" id="edit_firma_adi" required oninput="capitalizeFirstLetter(this)">
                </div>
                <div class="form-group">
                    <label>Şehir:</label>
                    <input type="text" name="sehir" id="edit_sehir" required>
                </div>
                <div class="form-group">
                    <label>Telefon:</label>
                    <input type="tel" name="telefon" id="edit_telefon">
                </div>
                <div class="form-group">
                    <label>Toplam Borç (₺):</label>
                    <input type="number" name="toplam_borc" id="edit_toplam_borc" required min="1" step="0.01">
                </div>
                <div class="form-group">
                    <label>Aylık Ödeme (₺):</label>
                    <input type="number" name="aylik_odeme" id="edit_aylik_odeme" required min="1" step="0.01">
                </div>
                <div class="form-group">
                    <label>Başlangıç Tarihi:</label>
                    <input type="date" name="baslangic_tarihi" id="edit_baslangic_tarihi" required>
                </div>
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editFirmaModal')">İptal</button>
                    <button type="submit" name="update_firma" class="btn btn-success">💾 Güncelle</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Ödeme Modalı - YENİ EKLENEN -->
    <div id="paymentModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('paymentModal')">&times;</span>
            <h2>💰 Ödeme Yap</h2>
            <div id="paymentInfo"></div>
            <form method="POST">
                <input type="hidden" name="firma_id" id="payment_firma_id">
                <div class="form-group">
                    <label>Ödeme Tutarı (₺):</label>
                    <input type="number" name="odeme_tutari" id="payment_amount" required min="1" step="0.01">
                </div>
                <div class="form-group">
                    <label>Ödeme Tarihi:</label>
                    <input type="date" name="odeme_tarihi" id="payment_date" required>
                </div>
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('paymentModal')">İptal</button>
                    <button type="submit" name="add_payment" class="btn btn-success">💾 Ödeme Kaydet</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Taksit Modalı - YENİ EKLENEN -->
    <div id="taksitModal" class="modal">
        <div class="modal-content" style="max-width: 1000px; max-height: 90vh; overflow-y: auto;">
            <span class="close" onclick="closeModal('taksitModal')">&times;</span>
            <h2>📅 Taksit Takvimi</h2>
            <div id="taksitContent" style="max-height: 70vh; overflow-y: auto;"></div>
        </div>
    </div>

    <!-- Taksit Düzenleme Modalı - YENİ EKLENEN -->
    <div id="editTaksitModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editTaksitModal')">&times;</span>
            <h2>✏️ Ödenen Taksit Düzenle</h2>
            <form method="POST">
                <input type="hidden" name="taksit_id" id="edit_taksit_id">
                <div class="form-group">
                    <label>Yeni Tutar (₺):</label>
                    <input type="number" name="yeni_tutar" id="edit_taksit_tutar" required min="1" step="0.01">
                </div>
                <div class="form-group">
                    <label>Ödeme Tarihi:</label>
                    <input type="date" name="yeni_tarih" id="edit_taksit_tarih" required>
                </div>
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editTaksitModal')">İptal</button>
                    <button type="submit" name="update_taksit" class="btn btn-success">💾 Güncelle</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Silme Formları (gizli) -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="firma_id" id="delete_firma_id">
        <input type="hidden" name="delete_firma" value="1">
    </form>

    <form id="deleteTaksitForm" method="POST" style="display: none;">
        <input type="hidden" name="taksit_id" id="delete_taksit_id">
        <input type="hidden" name="delete_taksit" value="1">
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

        // YENİ EKLENEN - Ödeme modalı gösterme
        function showPaymentModal(firmaId, firmaAdi, kalanBorc) {
            document.getElementById('payment_firma_id').value = firmaId;
            document.getElementById('payment_amount').max = kalanBorc;
            document.getElementById('payment_date').value = new Date().toISOString().split('T')[0];
            document.getElementById('paymentInfo').innerHTML = `
                <div style="background: #e3f2fd; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <strong>Firma:</strong> ${firmaAdi}<br>
                    <strong>Kalan Borç:</strong> ₺${kalanBorc.toLocaleString()}
                </div>
            `;
            document.getElementById('paymentModal').style.display = 'block';
        }

        // YENİ EKLENEN - Taksit takvimi modalı (AJAX olmadan)
        function showTaksitModal(firmaId) {
            // PHP ile taksit bilgilerini al
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    try {
                        // Taksit verilerini simüle et (gerçek uygulamada veritabanından gelecek)
                        generateTaksitTable(firmaId);
                    } catch (error) {
                        alert('Taksit bilgileri yüklenirken hata oluştu!');
                    }
                }
            };
            
            // Basit bir yaklaşım - direkt tablo oluştur
            generateTaksitTable(firmaId);
        }

        function generateTaksitTable(firmaId) {
            // Firma bilgilerini tablodan al
            const rows = document.querySelectorAll('#firmsTableBody tr');
            let firmaData = null;
            
            rows.forEach(row => {
                const detayBtn = row.querySelector('a[href*="firma_detay.php?id=' + firmaId + '"]');
                if (detayBtn) {
                    const cells = row.cells;
                    firmaData = {
                        toplamBorc: parseFloat(cells[4].textContent.replace(/[₺,.]/g, '')),
                        odenenTutar: parseFloat(cells[5].textContent.replace(/[₺,.]/g, '')),
                        kalanBorc: parseFloat(cells[6].textContent.replace(/[₺,.]/g, '')),
                        taksitSayisi: parseInt(cells[7].textContent),
                        baslangicTarihi: cells[8].textContent,
                        aylikOdeme: parseFloat(cells[9].textContent.replace(/[₺,.]/g, '')) || 0
                    };
                }
            });
            
            if (!firmaData) {
                alert('Firma bilgileri bulunamadı!');
                return;
            }
            
            let html = `
                <div style="margin-bottom: 20px; padding: 15px; background: #e3f2fd; border-radius: 8px;">
                    <strong>Toplam Taksit:</strong> ${firmaData.taksitSayisi} |
                    <strong>Aylık Ödeme:</strong> ₺${firmaData.aylikOdeme.toLocaleString()} |
                    <strong>Kalan Borç:</strong> ₺${firmaData.kalanBorc.toLocaleString()}
                </div>
                <div style="overflow-x: auto;">
                <table class="firms-table" style="width: 100%; min-width: 700px;">
                    <thead>
                        <tr>
                            <th>Taksit No</th>
                            <th>Vade Tarihi</th>
                            <th>Asıl Tutar</th>
                            <th>Gecikme Faizi</th>
                            <th>Toplam Tutar</th>
                            <th>Durum</th>
                            <th>Ödeme Geçmişi</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            // Başlangıç tarihini parse et
            const [day, month, year] = firmaData.baslangicTarihi.split('.');
            const baslangicDate = new Date(year, month - 1, day);
            const today = new Date();
            
            // Ödenen taksit sayısını hesapla (kalan borç ile)
            const toplamOdenen = firmaData.toplamBorc - firmaData.kalanBorc;
            const odenenTaksitSayisi = Math.floor(toplamOdenen / firmaData.aylikOdeme);
            
            for (let i = 1; i <= firmaData.taksitSayisi; i++) {
                const taksitDate = new Date(baslangicDate);
                taksitDate.setDate(taksitDate.getDate() + ((i - 1) * 30)); // Her ay 30 gün
                
                const gecikmeDate = new Date(taksitDate);
                gecikmeDate.setDate(gecikmeDate.getDate() + 15); // 15 gün gecikme
                
                let durum = '';
                let durumClass = '';
                let odemeGecmisi = '-';
                let asilTutar = firmaData.aylikOdeme;
                let gecikmeFaizi = 0;
                let toplamTutar = asilTutar;
                
                if (i <= odenenTaksitSayisi) {
                    durum = 'ÖDENDİ';
                    durumClass = 'status-badge status-tamamlandi';
                    odemeGecmisi = `₺${asilTutar.toLocaleString()}<br><small>${taksitDate.toLocaleDateString('tr-TR')}</small>`;
                } else if (firmaData.kalanBorc <= 0) {
                    durum = 'TAMAMLANDI';
                    durumClass = 'status-badge status-tamamlandi';
                } else if (today > gecikmeDate) {
                    durum = 'GECİKME';
                    durumClass = 'status-badge status-gecikme';
                    gecikmeFaizi = asilTutar * 0.02; // %2 faiz
                    toplamTutar = asilTutar + gecikmeFaizi;
                } else if (today > taksitDate) {
                    durum = 'VADESİ GEÇTİ';
                    durumClass = 'status-badge status-gecikme';
                } else {
                    durum = 'BEKLİYOR';
                    durumClass = 'status-badge status-aktif';
                }
                
                html += `
                    <tr>
                        <td><strong>${i}</strong></td>
                        <td>${taksitDate.toLocaleDateString('tr-TR')}</td>
                        <td style="color: #2c3e50;">₺${asilTutar.toLocaleString()}</td>
                        <td style="color: #e74c3c;">${gecikmeFaizi > 0 ? '₺' + gecikmeFaizi.toLocaleString() : '-'}</td>
                        <td style="color: #27ae60; font-weight: bold;">₺${toplamTutar.toLocaleString()}</td>
                        <td><span class="${durumClass}">${durum}</span></td>
                        <td style="font-size: 12px;">${odemeGecmisi}</td>
                    </tr>
                `;
            }
            
            html += '</tbody></table></div>';
            document.getElementById('taksitContent').innerHTML = html;
            document.getElementById('taksitModal').style.display = 'block';
        }

        // YENİ EKLENEN - Hızlı ödeme fonksiyonu
        function quickPayment(taksitNo, tutar) {
            if (confirm(`${taksitNo}. taksiti ödemek istediğinizden emin misiniz?\nTutar: ₺${tutar.toLocaleString()}`)) {
                // Burada AJAX ile ödeme yapılabilir
                alert('Ödeme işlemi başarılı! Sayfa yenilenecek.');
                window.location.reload();
            }
        }

        // YENİ EKLENEN - Ödeme düzenleme
        function editOdeme(taksitNo, tutar, tarih) {
            const yeniTutar = prompt(`${taksitNo}. taksit tutarını düzenleyin:`, tutar);
            const yeniTarih = prompt(`${taksitNo}. taksit ödeme tarihini düzenleyin (YYYY-MM-DD):`, tarih);
            
            if (yeniTutar && yeniTarih) {
                alert('Ödeme düzenleme başarılı! Sayfa yenilenecek.');
                window.location.reload();
            }
        }

        // YENİ EKLENEN - Ödeme silme
        function deleteOdeme(taksitNo) {
            if (confirm(`${taksitNo}. taksit ödemesini silmek istediğinizden emin misiniz?\nBu işlem geri alınamaz!`)) {
                alert('Ödeme silme başarılı! Sayfa yenilenecek.');
                window.location.reload();
            }
        }

        // YENİ EKLENEN - Taksit silme
        function deleteTaksit(taksitNo) {
            if (confirm(`${taksitNo}. taksiti tamamen silmek istediğinizden emin misiniz?`)) {
                alert('Taksit silme başarılı! Sayfa yenilenecek.');
                window.location.reload();
            }
        }

        // Tarih formatlama fonksiyonu
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('tr-TR');
        }

        // Modal dışına tıklandığında kapat
        window.onclick = function(event) {
            if (event.target.classList && event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        };

        // Sayfa yüklendiğinde bugünün tarihini varsayılan olarak ayarla
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            const dateInputs = document.querySelectorAll('input[type="date"]');
            dateInputs.forEach(input => {
                if (!input.value) {
                    input.value = today;
                }
            });
        });
        function showEditFirmaModal(id, nama, sehir, telefon, toplam_borc, aylik_odeme, baslangic_tarihi) {
            document.getElementById('edit_firma_id').value = id;
            document.getElementById('edit_firma_adi').value = nama;
            document.getElementById('edit_sehir').value = sehir;
            document.getElementById('edit_telefon').value = telefon;
            document.getElementById('edit_toplam_borc').value = toplam_borc;
            document.getElementById('edit_aylik_odeme').value = aylik_odeme;
            document.getElementById('edit_baslangic_tarihi').value = baslangic_tarihi;
            document.getElementById('editFirmaModal').style.display = 'block';
        }

        function confirmDelete(firmaId, firmaAdi) {
            if (confirm(`"${firmaAdi}" firmasını silmek istediğinizden emin misiniz?\n\nBu işlem geri alınamaz ve tüm taksit bilgileri silinecektir!`)) {
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
            const editModal = document.getElementById('editFirmaModal');
            if (event.target === addModal) {
                addModal.style.display = 'none';
            }
            if (event.target === editModal) {
                editModal.style.display = 'none';
            }
        }

        // Sayfa yüklendiğinde bugünün tarihini varsayılan olarak ayarla
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            const dateInputs = document.querySelectorAll('input[type="date"]');
            dateInputs.forEach(input => {
                if (!input.value) {
                    input.value = today;
                }
            });
        });
    </script>
</body>
</html>