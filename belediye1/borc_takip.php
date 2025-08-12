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
        $taksit_sayisi = ceil($toplam_borc / $aylik_odeme);
        
        // KÜSURAT DÜZELTME: Normal taksit tutarı
        $normal_taksit_tutari = floor($toplam_borc / $taksit_sayisi);
        
        // Mevcut ödenen tutarı koru
        $current_stmt = $pdo->prepare("SELECT (toplam_borc - kalan_borc) as odenen FROM firmalar WHERE id = ?");
        $current_stmt->execute([$firma_id]);
        $current_data = $current_stmt->fetch();
        $odenen_tutar = $current_data ? $current_data['odenen'] : 0;
        
        $yeni_kalan = $toplam_borc - $odenen_tutar;
        if ($yeni_kalan < 0) $yeni_kalan = 0;
        
        $durum = ($yeni_kalan <= 0) ? 'tamamlandi' : 'aktif';
        $final_aylik_odeme = ($yeni_kalan <= 0) ? 0 : $normal_taksit_tutari;
        
        // Eğer tamamlanmış firmayı yeniden aktif hale getiriyorsak
        if ($yeni_kalan > 0 && $current_data && $current_data['odenen'] >= $toplam_borc) {
            $durum = 'aktif';
            $final_aylik_odeme = $normal_taksit_tutari;
        }
        
        $update_stmt = $pdo->prepare("
            UPDATE firmalar 
            SET firma_adi = ?, sehir = ?, telefon = ?, toplam_borc = ?, kalan_borc = ?, 
                aylik_odeme = ?, baslangic_tarihi = ?, taksit_sayisi = ?, durum = ?
            WHERE id = ?
        ");
        $update_stmt->execute([
            $firma_adi, $sehir, $telefon, $toplam_borc, $yeni_kalan, 
            $final_aylik_odeme, $baslangic_tarihi, $taksit_sayisi, $durum, $firma_id
        ]);
        
        $success_message = "Firma başarıyla güncellendi!";
        
    } catch(PDOException $e) {
        $error_message = "Firma güncellenirken hata oluştu: " . $e->getMessage();
    }
}

// Yeni firma ekleme işlemi - KÜSURAT DÜZELTME
if (isset($_POST['add_firma'])) {
    $firma_adi = $_POST['firma_adi'];
    $sehir = $_POST['sehir'];
    $telefon = $_POST['telefon'];
    $toplam_borc = $_POST['toplam_borc'];
    $aylik_odeme = $_POST['aylik_odeme'];
    $baslangic_tarihi = $_POST['baslangic_tarihi'];
    
    try {
        $taksit_sayisi = ceil($toplam_borc / $aylik_odeme);
        
        // KÜSURAT DÜZELTME: Normal taksit tutarı
        $normal_taksit_tutari = floor($toplam_borc / $taksit_sayisi);
        
        $insert_stmt = $pdo->prepare("
            INSERT INTO firmalar (firma_adi, sehir, telefon, toplam_borc, kalan_borc, aylik_odeme, baslangic_tarihi, taksit_sayisi, durum) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'aktif')
        ");
        $insert_stmt->execute([$firma_adi, $sehir, $telefon, $toplam_borc, $toplam_borc, $normal_taksit_tutari, $baslangic_tarihi, $taksit_sayisi]);
        
        $firma_id = $pdo->lastInsertId();
        
        // Otomatik taksit programı oluştur
        createInstallmentSchedule($pdo, $firma_id, $toplam_borc, $normal_taksit_tutari, $baslangic_tarihi, $taksit_sayisi);
        
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

// KÜSURAT DÜZELTME: Ödeme ekleme işlemi
if (isset($_POST['add_payment'])) {
    $firma_id = $_POST['firma_id'];
    $odeme_tutari = $_POST['odeme_tutari'];
    $odeme_tarihi = $_POST['odeme_tarihi'] ?? date('Y-m-d');
    
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
            
            // KÜSURAT DÜZELTME: Normal ve son taksit hesaplama
            $normal_taksit_tutari = floor($firma['toplam_borc'] / $firma['taksit_sayisi']);
            
            // SON TAKSİT KONTROLÜ
            if ($kalan_taksit_sayisi <= 1) {
                // SON TAKSİT - Kalan borç ne kadarsa onu öde
                $required_payment = $firma['kalan_borc'];
                
                if ($odeme_tutari != $required_payment) {
                    $error_message = "SON TAKSİT: Kalan borç tutarını ödemelisiniz: ₺" . number_format($required_payment, 2, ',', '.') . " TL";
                    $pdo->rollback();
                } else {
                    // Firmayı tamamen bitir
                    $update_stmt = $pdo->prepare("
                        UPDATE firmalar 
                        SET kalan_borc = 0, durum = 'tamamlandi', aylik_odeme = 0
                        WHERE id = ?
                    ");
                    $update_stmt->execute([$firma_id]);
                    
                    // Son taksiti kaydet
                    $taksit_update = $pdo->prepare("
                        UPDATE taksitler 
                        SET durum = 'odendi', odeme_tarihi = ?, tutar = ?
                        WHERE firma_id = ? AND durum IN ('bekliyor', 'gecikme') 
                        ORDER BY vade_tarihi ASC 
                        LIMIT 1
                    ");
                    $taksit_update->execute([$odeme_tarihi, $required_payment, $firma_id]);
                    
                    // Diğer bekleyen taksitleri tamamla
                    $complete_stmt = $pdo->prepare("
                        UPDATE taksitler 
                        SET durum = 'tamamlandi' 
                        WHERE firma_id = ? AND durum IN ('bekliyor', 'gecikme')
                    ");
                    $complete_stmt->execute([$firma_id]);
                    
                    $pdo->commit();
                    $success_message = "🎉 SON TAKSİT ÖDENDİ! Borç tamamen kapandı! Ödenen: ₺" . number_format($required_payment, 2, ',', '.');
                }
                
            } else {
                // NORMAL TAKSİT
                if (abs($odeme_tutari - $normal_taksit_tutari) > 1) {
                    $error_message = "Normal taksit tutarını ödemelisiniz: ₺" . number_format($normal_taksit_tutari, 2, ',', '.') . " TL";
                    $pdo->rollback();
                } else {
                    // Normal taksit ödeme
                    $yeni_kalan = $firma['kalan_borc'] - $normal_taksit_tutari;
                    if ($yeni_kalan < 0) $yeni_kalan = 0;
                    
                    $durum = ($yeni_kalan <= 0) ? 'tamamlandi' : 'aktif';
                    $yeni_aylik_odeme = ($yeni_kalan <= 0) ? 0 : $normal_taksit_tutari;
                    
                    // Firmayı güncelle
                    $update_stmt = $pdo->prepare("
                        UPDATE firmalar 
                        SET kalan_borc = ?, durum = ?, aylik_odeme = ?
                        WHERE id = ?
                    ");
                    $update_stmt->execute([$yeni_kalan, $durum, $yeni_aylik_odeme, $firma_id]);
                    
                    // Taksiti güncelle
                    $taksit_update = $pdo->prepare("
                        UPDATE taksitler 
                        SET durum = 'odendi', odeme_tarihi = ?, tutar = ?
                        WHERE firma_id = ? AND durum IN ('bekliyor', 'gecikme') 
                        ORDER BY vade_tarihi ASC 
                        LIMIT 1
                    ");
                    $taksit_update->execute([$odeme_tarihi, $normal_taksit_tutari, $firma_id]);
                    
                    // Eğer borç bittiyse kalan taksitleri tamamla
                    if ($yeni_kalan <= 0) {
                        $complete_stmt = $pdo->prepare("
                            UPDATE taksitler 
                            SET durum = 'tamamlandi' 
                            WHERE firma_id = ? AND durum IN ('bekliyor', 'gecikme')
                        ");
                        $complete_stmt->execute([$firma_id]);
                        
                        $success_message = "🎉 BORÇ TAMAMLANDI! Ödenen: ₺" . number_format($normal_taksit_tutari, 2, ',', '.');
                    } else {
                        $success_message = "✅ Taksit ödendi! Ödenen: ₺" . number_format($normal_taksit_tutari, 2, ',', '.') . " - Kalan: ₺" . number_format($yeni_kalan, 2, ',', '.');
                    }
                    
                    $pdo->commit();
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

// KÜSURAT DÜZELTME: Taksit programı oluşturma fonksiyonu
function createInstallmentSchedule($pdo, $firma_id, $toplam_borc, $aylik_odeme, $baslangic_tarihi, $taksit_sayisi) {
    $baslangic_date = new DateTime($baslangic_tarihi);
    
    // KÜSURAT ÇÖZÜMÜ
    $normal_taksit_tutari = floor($toplam_borc / $taksit_sayisi);
    $son_taksit_tutari = $toplam_borc - ($normal_taksit_tutari * ($taksit_sayisi - 1));
    
    for ($i = 1; $i <= $taksit_sayisi; $i++) {
        $vade_tarihi = clone $baslangic_date;
        $vade_tarihi->add(new DateInterval('P' . (($i - 1) * 30) . 'D'));
        
        // Son taksit farklı tutarda
        $tutar = ($i == $taksit_sayisi) ? $son_taksit_tutari : $normal_taksit_tutari;
        
        // Gecikme kontrolü
        $today = new DateTime();
        $gecikme_tarihi = clone $vade_tarihi;
        $gecikme_tarihi->add(new DateInterval('P15D'));
        
        $durum = ($today > $gecikme_tarihi) ? 'gecikme' : 'bekliyor';
        
        $insert_taksit = $pdo->prepare("
            INSERT INTO taksitler (firma_id, tutar, vade_tarihi, durum) 
            VALUES (?, ?, ?, ?)
        ");
        $insert_taksit->execute([$firma_id, $tutar, $vade_tarihi->format('Y-m-d'), $durum]);
    }
}

// Yıllık özet hesaplama
function getYearlyBreakdown($firmalar) {
    $yearly_data = [];
    
    foreach ($firmalar as $firma) {
        $baslangic_tarihi = new DateTime($firma['baslangic_tarihi']);
        $start_year = (int)$baslangic_tarihi->format('Y');
        
        $kalan_borc = $firma['toplam_borc'];
        $current_date = clone $baslangic_tarihi;
        $taksit_no = 1;
        
        // KÜSURAT DÜZELTME
        $normal_taksit_tutari = floor($firma['toplam_borc'] / $firma['taksit_sayisi']);
        
        while ($kalan_borc > 0 && $taksit_no <= $firma['taksit_sayisi']) {
            $year = (int)$current_date->format('Y');
            
            if (!isset($yearly_data[$year])) {
                $yearly_data[$year] = ['toplam' => 0, 'odenen' => 0, 'kalan' => 0, 'aylik_ortalama' => 0, 'firma_sayisi' => 0];
            }
            
            // Son taksit kontrolü
            $taksit_tutari = ($taksit_no == $firma['taksit_sayisi']) ? 
                $firma['toplam_borc'] - ($normal_taksit_tutari * ($firma['taksit_sayisi'] - 1)) : 
                $normal_taksit_tutari;
            
            $taksit_tutari = min($taksit_tutari, $kalan_borc);
            
            $yearly_data[$year]['toplam'] += $taksit_tutari;
            $yearly_data[$year]['aylik_ortalama'] += $normal_taksit_tutari;
            $yearly_data[$year]['firma_sayisi']++;
            
            // Ödenen tutarları hesapla
            $odenen_tutar = $firma['toplam_borc'] - $firma['kalan_borc'];
            if ($odenen_tutar >= ($taksit_no * $normal_taksit_tutari)) {
                $yearly_data[$year]['odenen'] += $taksit_tutari;
            }
            
            $kalan_borc -= $taksit_tutari;
            $current_date->add(new DateInterval('P30D'));
            $taksit_no++;
            
            if ($taksit_no > 1000) break;
        }
    }
    
    // Kalan tutarları hesapla
    foreach ($yearly_data as $year => &$data) {
        $data['kalan'] = $data['toplam'] - $data['odenen'];
        $data['aylik_ortalama'] = $data['firma_sayisi'] > 0 ? $data['aylik_ortalama'] / $data['firma_sayisi'] : 0;
    }
    
    ksort($yearly_data);
    
    return array_filter($yearly_data, function($data) {
        return $data['toplam'] > 0;
    });
}

$yearly_breakdown = getYearlyBreakdown($firmalar);
$yearly_breakdown = getYearlyBreakdown($firmalar);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konya Büyükşehir Belediyesi - Borç Takip Sistemi</title>
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
        
        /* Başlangıç animasyonu için gizli container */
        .container {
            width: 100%;
            min-height: 100vh;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            display: flex;
            flex-direction: column;
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
        }
        
        .container.show {
            opacity: 1;
        }
        
        /* Loading ekranı */
        .loading-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.5s ease-in-out;
        }
        
        .loading-screen.hide {
            opacity: 0;
            pointer-events: none;
        }
        
        .loading-content {
            text-align: center;
            color: white;
        }
        
        .finance-gif {
            width: 200px;
            height: 200px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            margin-bottom: 20px;
            animation: floatAnimation 5s ease-in-out infinite;
        }
        
        @keyframes floatAnimation {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        .loading-text {
            font-size: 24px;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            animation: fadeInOut 2s ease-in-out infinite;
        }
        
        @keyframes fadeInOut {
            0%, 100% { opacity: 0.7; }
            50% { opacity: 1; }
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
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header-logo {
            width: 60px;
            height: 60px;
            background: url('konya-logo.png') center/contain no-repeat;
            border-radius: 12px;
            border: 3px solid rgba(255,255,255,0.2);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
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
        
        /* Büyük Yıllık Özet Butonu */
        .btn-yearly-summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #3498db 100%);
            border: none;
            border-radius: 20px;
            padding: 25px 40px;
            cursor: pointer;
            transition: all 0.4s ease;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
            min-width: 400px;
            max-width: 600px;
            animation: pulseGlow 3s ease-in-out infinite;
        }
        
        .btn-yearly-summary::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            transform: rotate(45deg);
            animation: shimmerButton 3s linear infinite;
        }
        
        @keyframes shimmerButton {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }
        
        @keyframes pulseGlow {
            0%, 100% { 
                box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3), 0 0 0 0 rgba(102, 126, 234, 0.4);
            }
            50% { 
                box-shadow: 0 15px 40px rgba(102, 126, 234, 0.5), 0 0 20px 5px rgba(102, 126, 234, 0.2);
            }
        }
        
        .btn-yearly-summary:hover {
            transform: translateY(-8px) scale(1.05);
            box-shadow: 0 20px 50px rgba(102, 126, 234, 0.5);
        }
        
        .yearly-btn-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            z-index: 2;
        }
        
        .yearly-btn-icon {
            font-size: 36px;
            animation: bounce 2s ease-in-out infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }
        
        .yearly-btn-text {
            flex: 1;
            margin-left: 20px;
            text-align: left;
        }
        
        .yearly-btn-title {
            display: block;
            color: white;
            font-size: 22px;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            margin-bottom: 5px;
        }
        
        .yearly-btn-subtitle {
            display: block;
            color: rgba(255,255,255,0.9);
            font-size: 14px;
            font-weight: 400;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
        }
        
        .yearly-btn-arrow {
            font-size: 24px;
            color: white;
            font-weight: bold;
            transition: transform 0.3s ease;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .btn-yearly-summary:hover .yearly-btn-arrow {
            transform: translateX(10px);
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
            align-items: center;
            flex-wrap: wrap;
        }
        
        .stats-bar {
            background: rgba(255,255,255,0.7);
            padding: 25px 5px;
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
        
        /* Yıllık Özet Modal Stili */
        .yearly-summary-modal {
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
        
        .yearly-summary-modal-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            margin: 3% auto;
            padding: 35px;
            border-radius: 15px;
            width: 95%;
            max-width: 1200px;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.2);
            animation: slideIn 0.3s ease;
        }
        
        .yearly-summary {
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.15), rgba(155, 89, 182, 0.1));
            padding: 30px;
            border-radius: 20px;
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
        }
        
        .table-container {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(248, 249, 250, 0.95));
            backdrop-filter: blur(15px);
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.12);
            overflow: hidden;
            border: 2px solid rgba(52, 152, 219, 0.1);
            position: relative;
        }
        
        .table-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #3498db, #9b59b6, #e74c3c, #f39c12, #27ae60);
            background-size: 400% 100%;
            animation: gradientMove 8s ease infinite;
        }
        
        @keyframes gradientMove {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        .table-header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 50%, #3498db 100%);
            color: white;
            padding: 25px;
            font-weight: 700;
            font-size: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            position: relative;
            overflow: hidden;
        }
        
        .table-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            animation: shimmerHeader 4s linear infinite;
        }
        
        @keyframes shimmerHeader {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
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
        
        .btn-info {
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: white;
            box-shadow: 0 4px 15px rgba(23, 162, 184, 0.4);
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
            max-height: 80vh;
            overflow-y: auto;
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
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 15px rgba(52, 152, 219, 0.2);
        }
        
        .calculation-info {
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.1), rgba(155, 89, 182, 0.05));
            padding: 15px;
            border-radius: 10px;
            margin-top: 10px;
            border-left: 4px solid #3498db;
            font-size: 14px;
            color: #2c3e50;
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
        
        /* Taksit Modal Özel Stilleri */
        .taksit-modal-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            margin: 2% auto;
            padding: 35px;
            border-radius: 15px;
            width: 95%;
            max-width: 1200px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.2);
            animation: slideIn 0.3s ease;
        }
        
        .taksit-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            margin-top: 20px;
            background: rgba(255,255,255,0.9);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .taksit-table th {
            background: linear-gradient(135deg, #34495e, #2c3e50);
            color: white;
            padding: 15px 8px;
            text-align: center;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .taksit-table td {
            padding: 12px 8px;
            text-align: center;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            vertical-align: middle;
        }
        
        .taksit-table tbody tr:hover {
            background: rgba(52, 152, 219, 0.1);
        }
        
        .refresh-bottom {
            position: fixed;
            top: 85px;
            right: 0px;
            z-index: 1;
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
            border-radius: 25px;
            pointer-events: auto;
            opacity: 0.8;
            transition: all 0.3s ease;
             transform: scale(0.8); 
        }
        
        .refresh-bottom:hover {
            opacity: 1;
            transform: scale(0.9);
        }
        
        /* Container'a relative position ekle */
        .container {
            width: 100%;
            min-height: 100vh;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            display: flex;
            flex-direction: column;
            position: relative;
        }
        
        /* Tablodaki butonlar için özel z-index */
        .firms-table td {
            padding: 15px 12px;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            vertical-align: middle;
            position: relative;
            z-index: 10;
        }
        
        .firms-table .btn {
            position: relative;
            z-index: 100;
        }
        
        /* Header sağ tarafına margin ekle */
        .header .user-info {
            font-size: 25px;
            opacity: 0.9;
            margin-right: 80px; /* Sayfa yenile butonu için yer aç */
        }
        
        @media (max-width: 1024px) {
            .refresh-bottom {
                top: 70px;
                right: 15px;
                z-index: 1;
                transform: scale(0.85);
                opacity: 0.7;
            }
            
            .header .user-info {
                margin-right: 70px;
            }
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
            
            .refresh-bottom {
                top: 60px;
                right: 10px;
                z-index: 1;
                transform: scale(0.75);
                opacity: 0.6;
            }
            
            .refresh-bottom:hover {
                opacity: 1;
                transform: scale(0.8);
            }
            
            .header .user-info {
                margin-right: 60px;
            }
            
            .btn-yearly-summary {
                min-width: 300px;
                max-width: 90%;
                padding: 20px 30px;
            }
            
        }
        
    </style>
</head>
<body>
    <!-- Başlangıç Animasyonu -->
    <div class="loading-screen" id="loadingScreen">
        <div class="loading-content">
            <img src="Finance.gif" class="finance-gif" alt="Loading">
            <div class="loading-text">Borç Takip Sistemi Yükleniyor...</div>
        </div>
    </div>

    <div class="container" id="mainContainer">
        <div class="header">
            <div class="header-left">
                <div class="header-logo"></div>
                <h1>Konya  Belediyesi - Borç Takip Sistemi</h1>
            </div>
            <div class="user-info">
                <?php echo htmlspecialchars($_SESSION['username']); ?> - <?php echo strtoupper($_SESSION['role']); ?>
                <span style="margin: 0 25px;">|</span>
                <a href="dashboard.php" style="background: linear-gradient(135deg, #0016c0ff, #0016c0ff); color: white; padding: 12px 20px; border-radius: 25px; text-decoration: none; font-weight: 600; box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3); transition: all 0.3s ease;">🏠 Anasayfa</a>
            </div>
        </div>
        
        <!-- Yıllık Borç Özeti Büyük Butonu -->
        <div style="padding: 30px; background: rgba(255,255,255,0.7); border-bottom: 1px solid rgba(0,0,0,0.1);">
            <div style="text-align: center;">
                <button class="btn-yearly-summary" onclick="showYearlyModal()">
                    <div class="yearly-btn-content">
                        <div class="yearly-btn-icon">📅</div>
                        <div class="yearly-btn-text">
                            <span class="yearly-btn-title">Yıllık Borç Özeti</span>
                            <span class="yearly-btn-subtitle">Detaylı yıllık raporlama</span>
                        </div>
                        <div class="yearly-btn-arrow">→</div>
                    </div>
                </button>
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
                
                <!-- Ortalama Kartları -->
                <div class="stat-card average">
                    <h3>₺<?php echo number_format($istatistik['ortalama_aylik_odeme'], 0, ',', '.'); ?></h3>
                    <p>Aylık Ödeme Ortalaması</p>
                </div>
                <div class="stat-card average">
                    <h3>₺<?php echo number_format($istatistik['ortalama_kalan_borc'], 0, ',', '.'); ?></h3>
                    <p>Kalan Borç Ortalaması</p>
                </div>
            </div>
        </div>
        
        <div class="content">
            <?php if ($success_message): ?>
                <div class="message success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>a
            <?php endif; ?>
            
            <style>
        .firms-table tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.05);
            transition: background-color 0.2s ease;
        }
    </style>
    
    <!-- Firma Arama ve Yeni Firma Butonları - Sadece Tablo Üstünde -->
            <div style="background: rgba(255,255,255,0.8); padding: 20px; margin-bottom: 20px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="🔍 Firma adı veya şehir ile ara..." onkeyup="filterTable()">
                    </div>
                    <button class="btn btn-success" onclick="showAddFirmaModal()">➕ Yeni Firma</button>
                </div>
            </div>
            
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
                            <tr onclick="goToDetails(event, <?php echo $firma['id']; ?>)" style="cursor: pointer;">
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
                                        // Eşit taksit tutarını göster
                                        $esit_taksit_tutari = round($firma['toplam_borc'] / $firma['taksit_sayisi']);
                                        echo "₺" . number_format($esit_taksit_tutari, 0, ',', '.');
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
                                    <div style="display: flex; flex-wrap: wrap; gap: 5px; align-items: center; position: relative; z-index: 1;">
                                        <a href="firma_detay.php?id=<?php echo $firma['id']; ?>" class="btn btn-primary">📋 Detay</a>
                                        <button class="btn btn-warning" onclick="showEditFirmaModal(<?php echo $firma['id']; ?>, '<?php echo htmlspecialchars($firma['firma_adi']); ?>', '<?php echo htmlspecialchars($firma['sehir']); ?>', '<?php echo htmlspecialchars($firma['telefon']); ?>', <?php echo $firma['toplam_borc']; ?>, <?php echo $firma['aylik_odeme']; ?>, '<?php echo $firma['baslangic_tarihi']; ?>')">✏️ Düzenle</button>
                                        <button class="btn btn-info" onclick="showTaksitModal(<?php echo $firma['id']; ?>, '<?php echo htmlspecialchars($firma['firma_adi']); ?>')">📅 Taksitler</button>
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

    <!-- Sayfa Yenile Butonu (En Alt Sağ Köşe) -->
    <button class="btn btn-warning refresh-bottom" onclick="refreshAndScrollTop()" title="Sayfa Yenile">🔄 Sayfa Yenile</button>

    <!-- Yıllık Özet Modalı -->
    <div id="yearlyModal" class="yearly-summary-modal">
        <div class="yearly-summary-modal-content">
            <span class="close" onclick="closeYearlyModal()">&times;</span>
            <?php if (!empty($yearly_breakdown)): ?>
            <div class="yearly-summary">
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
            <?php else: ?>
            <div class="empty-state">
                <h3>📅 Yıllık Veri Bulunamadı</h3>
                <p>Henüz yıllık borç verisi bulunmamaktadır.</p>
            </div>
            <?php endif; ?>
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
                    <input type="text" name="firma_adi" required oninput="capitalizeWords(this)">
                </div>
                <div class="form-group">
                    <label>Şehir:</label>
                    <input type="text" name="sehir" required oninput="capitalizeWords(this)">
                </div>
                <div class="form-group">
                    <label>Telefon:</label>
                    <input type="tel" name="telefon">
                </div>
                <div class="form-group">
                    <label>Toplam Borç (₺):</label>
                    <input type="number" name="toplam_borc" id="add_toplam_borc" required min="1" step="0.01" oninput="calculateInstallments('add')">
                </div>
                <div class="form-group">
                    <label>Ödeme Şekli Seçin:</label>
                    <select id="add_payment_type" onchange="handlePaymentTypeChange('add')">
                        <option value="monthly">Aylık Ödeme Tutarı Gir</option>
                        <option value="installments">Taksit Sayısı Seç</option>
                    </select>
                </div>
                <div class="form-group" id="add_monthly_group">
                    <label>Aylık Ödeme (₺):</label>
                    <input type="number" name="aylik_odeme" id="add_aylik_odeme" required min="1" step="0.01" oninput="calculateInstallments('add')">
                </div>
                <div class="form-group" id="add_installments_group" style="display: none;">
                    <label>Taksit Sayısı:</label>
                    <select id="add_taksit_sayisi" onchange="calculateMonthlyPayment('add')">
                        <option value="">Taksit sayısı seçin</option>
                        <?php for($i = 3; $i <= 60; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?> Taksit</option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Başlangıç Tarihi:</label>
                    <input type="date" name="baslangic_tarihi" required>
                </div>
                <div class="calculation-info" id="add_calculation_info">
                    <strong>💡 Hesaplama Bilgisi:</strong><br>
                    <span id="add_calculation_text">Lütfen tutarları girin</span>
                </div>
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addFirmaModal')">İptal</button>
                    <button type="submit" name="add_firma" class="btn btn-success">💾 Firma Ekle</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Firma Düzenleme Modalı -->
    <div id="editFirmaModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editFirmaModal')">&times;</span>
            <h2>✏️ Firma Düzenle</h2>
            <form method="POST">
                <input type="hidden" name="firma_id" id="edit_firma_id">
                <div class="form-group">
                    <label>Firma Adı:</label>
                    <input type="text" name="firma_adi" id="edit_firma_adi" required oninput="capitalizeWords(this)">
                </div>
                <div class="form-group">
                    <label>Şehir:</label>
                    <input type="text" name="sehir" id="edit_sehir" required oninput="capitalizeWords(this)">
                </div>
                <div class="form-group">
                    <label>Telefon:</label>
                    <input type="tel" name="telefon" id="edit_telefon">
                </div>
                <div class="form-group">
                    <label>Toplam Borç (₺):</label>
                    <input type="number" name="toplam_borc" id="edit_toplam_borc" required min="1" step="0.01" oninput="calculateInstallments('edit')">
                </div>
                <div class="form-group">
                    <label>Ödeme Şekli Seçin:</label>
                    <select id="edit_payment_type" onchange="handlePaymentTypeChange('edit')">
                        <option value="monthly">Aylık Ödeme Tutarı Gir</option>
                        <option value="installments">Taksit Sayısı Seç</option>
                    </select>
                </div>
                <div class="form-group" id="edit_monthly_group">
                    <label>Aylık Ödeme (₺):</label>
                    <input type="number" name="aylik_odeme" id="edit_aylik_odeme" required min="1" step="0.01" oninput="calculateInstallments('edit')">
                </div>
                <div class="form-group" id="edit_installments_group" style="display: none;">
                    <label>Taksit Sayısı:</label>
                    <select id="edit_taksit_sayisi" onchange="calculateMonthlyPayment('edit')">
                        <option value="">Taksit sayısı seçin</option>
                        <?php for($i = 3; $i <= 60; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?> Taksit</option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Başlangıç Tarihi:</label>
                    <input type="date" name="baslangic_tarihi" id="edit_baslangic_tarihi" required>
                </div>
                <div class="calculation-info" id="edit_calculation_info">
                    <strong>💡 Hesaplama Bilgisi:</strong><br>
                    <span id="edit_calculation_text">Lütfen tutarları girin</span>
                </div>
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editFirmaModal')">İptal</button>
                    <button type="submit" name="update_firma" class="btn btn-success">💾 Güncelle</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Taksit Modalı -->
    <div id="taksitModal" class="modal">
        <div class="taksit-modal-content">
            <span class="close" onclick="closeModal('taksitModal')">&times;</span>
            <h2>📅 Taksit Takvimi</h2>
            <div id="taksitContent"></div>
        </div>
    </div>

    <!-- Silme Formları (gizli) -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="firma_id" id="delete_firma_id">
        <input type="hidden" name="delete_firma" value="1">
    </form>

    <script>
        // Sayfa yüklendiğinde animasyon başlat
        document.addEventListener('DOMContentLoaded', function() {
            // 1. saniye sonra loading ekranını gizle ve ana içeriği göster
            setTimeout(function() {
                document.getElementById('loadingScreen').classList.add('hide');
                document.getElementById('mainContainer').classList.add('show');
            }, 1000);
            
            // 1. saniye sonra loading ekranını tamamen kaldır
            setTimeout(function() {
                document.getElementById('loadingScreen').style.display = 'none';
            }, 1000);
            
            // Bugünün tarihini varsayılan olarak ayarla
            const today = new Date().toISOString().split('T')[0];
            const dateInputs = document.querySelectorAll('input[type="date"]');
            dateInputs.forEach(input => {
                if (!input.value) {
                    input.value = today;
                }
            });
        });

        function goToDetails(event, firmaId) {
            // Don't trigger if clicking on the actions column (last column)
            const clickedElement = event.target;
            const isLastColumn = clickedElement.closest('td') && clickedElement.closest('td').cellIndex === 11;
            
            // Don't trigger if clicking on a button or link
            if (!isLastColumn && !event.target.closest('button') && !event.target.closest('a')) {
                window.location.href = `firma_detay.php?id=${firmaId}`;
            }
        }

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

       function capitalizeWords(input) {
    const exceptions = ["A.Ş.", "LTD.", "LTD", "AŞ", "AŞ."]; // büyük harf kalması gerekenler

    let words = input.value.split(' ').map(word => {
        if (exceptions.includes(word.toUpperCase())) {
            return word.toUpperCase(); // Özel ifadeyi tamamen büyük yap
        }
        return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase(); // Normal kelime
    });

    input.value = words.join(' ');
}


        // Ödeme tipi değişikliği
        function handlePaymentTypeChange(prefix) {
            const paymentType = document.getElementById(prefix + '_payment_type').value;
            const monthlyGroup = document.getElementById(prefix + '_monthly_group');
            const installmentsGroup = document.getElementById(prefix + '_installments_group');
            const aylikOdemeInput = document.getElementById(prefix + '_aylik_odeme');
            
            if (paymentType === 'monthly') {
                monthlyGroup.style.display = 'block';
                installmentsGroup.style.display = 'none';
                aylikOdemeInput.required = true;
                document.getElementById(prefix + '_taksit_sayisi').required = false;
            } else {
                monthlyGroup.style.display = 'none';
                installmentsGroup.style.display = 'block';
                aylikOdemeInput.required = false;
                document.getElementById(prefix + '_taksit_sayisi').required = true;
            }
            
            calculateInstallments(prefix);
        }

        // Taksit hesaplama
        function calculateInstallments(prefix) {
            const toplamBorc = parseFloat(document.getElementById(prefix + '_toplam_borc').value) || 0;
            const paymentType = document.getElementById(prefix + '_payment_type').value;
            const calculationText = document.getElementById(prefix + '_calculation_text');
            
            if (toplamBorc <= 0) {
                calculationText.innerHTML = 'Lütfen geçerli bir toplam borç miktarı girin';
                return;
            }
            
            if (paymentType === 'monthly') {
                const aylikOdeme = parseFloat(document.getElementById(prefix + '_aylik_odeme').value) || 0;
                
                if (aylikOdeme <= 0) {
                    calculationText.innerHTML = 'Lütfen geçerli bir aylık ödeme tutarı girin';
                    return;
                }
                
                // Tam taksit sayısını hesapla - küsurat fark olmasın
                const taksitSayisi = Math.ceil(toplamBorc / aylikOdeme);
                
                // Toplam tutarı eşit taksitlere böl - hepsi aynı miktarda olsun
                const esitTaksitTutari = Math.round(toplamBorc / taksitSayisi);
                const toplamOdeme = esitTaksitTutari * taksitSayisi;
                const fark = toplamOdeme - toplamBorc;
                
                calculationText.innerHTML = `
                    📊 <strong>Hesaplama:</strong><br>
                    • Toplam Taksit Sayısı: <strong>${taksitSayisi} ay</strong><br>
                    • Tüm Taksitler: <strong>₺${esitTaksitTutari.toLocaleString()}</strong> (eşit miktarda)<br>
                    • Toplam Ödeme: <strong>₺${toplamOdeme.toLocaleString()}</strong><br>
                    ${fark > 0 ? `• Ek Ödeme: <strong style="color: #f39c12;">+₺${fark.toLocaleString()}</strong>` : ''}
                    ${fark < 0 ? `• İndirim: <strong style="color: #27ae60;">₺${Math.abs(fark).toLocaleString()}</strong>` : ''}
                `;
            } else {
                calculationText.innerHTML = 'Taksit sayısını seçin';
            }
        }

        // Aylık ödeme hesaplama
        function calculateMonthlyPayment(prefix) {
            const toplamBorc = parseFloat(document.getElementById(prefix + '_toplam_borc').value) || 0;
            const taksitSayisi = parseInt(document.getElementById(prefix + '_taksit_sayisi').value) || 0;
            const calculationText = document.getElementById(prefix + '_calculation_text');
            const aylikOdemeInput = document.getElementById(prefix + '_aylik_odeme');
            
            if (toplamBorc <= 0 || taksitSayisi <= 0) {
                calculationText.innerHTML = 'Lütfen geçerli değerler girin';
                return;
            }
            
            // Eşit taksitler için hesaplama - küsurat yok
            const esitTaksitTutari = Math.round(toplamBorc / taksitSayisi);
            const toplamOdeme = esitTaksitTutari * taksitSayisi;
            const fark = toplamOdeme - toplamBorc;
            
            // Aylık ödeme alanına otomatik değer yazma
            aylikOdemeInput.value = esitTaksitTutari;
            
            calculationText.innerHTML = `
                📊 <strong>Hesaplama:</strong><br>
                • Toplam Taksit Sayısı: <strong>${taksitSayisi} ay</strong><br>
                • Tüm Taksitler: <strong>₺${esitTaksitTutari.toLocaleString()}</strong> (eşit miktarda)<br>
                • Toplam Ödeme: <strong>₺${toplamOdeme.toLocaleString()}</strong><br>
                ${fark > 0 ? `• Ek Ödeme: <strong style="color: #f39c12;">+₺${fark.toLocaleString()}</strong>` : ''}
                ${fark < 0 ? `• İndirim: <strong style="color: #27ae60;">₺${Math.abs(fark).toLocaleString()}</strong>` : ''}
            `;
        }

        function showAddFirmaModal() {
            document.getElementById('addFirmaModal').style.display = 'block';
        }

        function showYearlyModal() {
            document.getElementById('yearlyModal').style.display = 'block';
        }

        function closeYearlyModal() {
            document.getElementById('yearlyModal').style.display = 'none';
        }

        function refreshAndScrollTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
            setTimeout(() => {
                window.location.reload();
            }, 500);
        }

        // GÜNCELLENMIŞ: Gecikme faizi kaldırılmış showTaksitModal fonksiyonu
        function showTaksitModal(firmaId, firmaAdi) {
            // Loading göster
            document.getElementById('taksitContent').innerHTML = `
                <div style="text-align: center; padding: 50px;">
                    <div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                    <p style="margin-top: 15px; color: #666;">Taksit bilgileri yükleniyor...</p>
                </div>
                <style>
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
                </style>
            `;
            
            // Modal'ı aç
            document.getElementById('taksitModal').style.display = 'block';
            
            // AJAX ile gerçek verileri çek
            fetch(`get_payment_dates.php?firma_id=${firmaId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    
                    const firma = data.firma;
                    const taksitler = data.taksit_takvimi;
                    const ozet = data.ozet;
                    
                    let html = `
                        <div style="margin-bottom: 20px; padding: 20px; background: linear-gradient(135deg, #e3f2fd, #bbdefb); border-radius: 12px; border-left: 5px solid #2196f3;">
                            <h3 style="margin-bottom: 15px; color: #1976d2; font-size: 18px;">📋 ${firma.firma_adi} - Taksit Bilgileri</h3>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                                <div><strong>Toplam Taksit:</strong> ${firma.taksit_sayisi}</div>
                                <div><strong>Normal Taksit Tutarı:</strong> ₺${firma.normal_taksit_tutari.toLocaleString()}</div>
                                <div><strong>Son Taksit Tutarı:</strong> ₺${firma.son_taksit_tutari.toLocaleString()}</div>
                                <div><strong>Kalan Borç:</strong> ₺${firma.kalan_borc.toLocaleString()}</div>
                            </div>`;
                    
                    // SON TAKSİT ÖZEL MESAJI
                    if (ozet.kalan_taksit_sayisi <= 1 && firma.kalan_borc > 0) {
                        html += `
                            <div style="margin-top: 15px; padding: 15px; background: rgba(255, 193, 7, 0.1); border-radius: 8px; border-left: 4px solid #ffc107;">
                                <strong style="color: #856404;">⚠️ SON TAKSİT:</strong> 
                                <span style="color: #856404;">Bu son taksittir. Kalan borç tutarı <strong>₺${firma.kalan_borc.toLocaleString()}</strong> olarak ödenmelidir.</span>
                            </div>
                        `;
                    } else {
                        html += `
                            <div style="margin-top: 15px; padding: 15px; background: rgba(76, 175, 80, 0.1); border-radius: 8px; border-left: 4px solid #4caf50;">
                                <strong style="color: #2e7d32;">💡 Ödeme İpucu:</strong> 
                                <span style="color: #2e7d32;">Normal taksitler için <strong>₺${firma.normal_taksit_tutari.toLocaleString()}</strong>, son taksit için <strong>₺${firma.son_taksit_tutari.toLocaleString()}</strong> tutarında ödeme yapılmalıdır.</span>
                            </div>
                        `;
                    }
                    
                    html += `
                        </div>
                        <div style="overflow-x: auto;">
                        <table class="taksit-table">
                            <thead>
                                <tr>
                                    <th>Taksit No</th>
                                    <th>Vade Tarihi</th>
                                    <th>Taksit Tutarı</th>
                                    <th>Durum</th>
                                    <th>Ödeme Tarihi</th>
                                    <th>Notlar</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;
                    
                    // Taksit satırları - GECIKME FAIZI KALDIRILDI
                    taksitler.forEach(taksit => {
                        const durumStyle = `background: linear-gradient(135deg, ${taksit.durum_renk}, ${taksit.durum_renk}aa); color: white; padding: 8px 15px; border-radius: 20px; font-weight: 600;`;
                        
                        html += `
                            <tr style="transition: all 0.3s ease;">
                                <td style="font-weight: bold; color: #1976d2;">${taksit.taksit_no}</td>
                                <td style="font-weight: 600;">${taksit.vade_tarihi}</td>
                                <td style="color: #2e7d32; font-weight: 600;">₺${taksit.tutar.toLocaleString()}</td>
                                <td><span style="${durumStyle}">${taksit.durum_text}</span></td>
                                <td style="font-weight: 600;">
                                    ${taksit.odeme_tarihi ? 
                                        `<span style="color: #27ae60; background: rgba(39, 174, 96, 0.1); padding: 4px 8px; border-radius: 15px; font-size: 12px;">${formatDate(taksit.odeme_tarihi)}</span>` : 
                                        `<span style="color: #6c757d;">-</span>`
                                    }
                                </td>
                                <td style="font-size: 12px; color: #666; font-style: italic;">${taksit.notlar}</td>
                            </tr>
                        `;
                    });
                    
                    html += '</tbody></table></div>';
                    
                    // Özet bilgiler
                    html += `
                        <div style="margin-top: 20px; padding: 15px; background: linear-gradient(135deg, rgba(52, 152, 219, 0.1), rgba(155, 89, 182, 0.05)); border-radius: 10px; border-left: 4px solid #3498db;">
                            <h4 style="color: #2c3e50; margin-bottom: 10px;">📊 Ödeme Özeti</h4>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; font-size: 14px;">
                                <div><strong>Ödenen Taksit:</strong> ${ozet.odenen_taksit_sayisi}/${firma.taksit_sayisi}</div>
                                <div><strong>Kalan Taksit:</strong> ${ozet.kalan_taksit_sayisi}</div>
                                <div><strong>Ödeme Oranı:</strong> ${ozet.odeme_orani}%</div>
                                <div><strong>Tahmini Bitiş:</strong> ${ozet.tahmini_bitis}</div>
                            </div>
                        </div>
                    `;
                    
                    document.getElementById('taksitContent').innerHTML = html;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('taksitContent').innerHTML = `
                        <div style="text-align: center; padding: 50px; color: #dc3545;">
                            <h4>❌ Hata Oluştu</h4>
                            <p>Taksit bilgileri yüklenirken bir hata oluştu.</p>
                            <p style="font-size: 12px; color: #666;">${error.message}</p>
                            <button class="btn btn-primary" onclick="showTaksitModal(${firmaId}, '${firmaAdi}')" style="margin-top: 15px;">🔄 Tekrar Dene</button>
                        </div>
                    `;
                });
        }

        // Tarih formatlama yardımcı fonksiyonu
        function formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString('tr-TR');
        }

        function showEditFirmaModal(id, nama, sehir, telefon, toplam_borc, aylik_odeme, baslangic_tarihi) {
            document.getElementById('edit_firma_id').value = id;
            document.getElementById('edit_firma_adi').value = nama;
            document.getElementById('edit_sehir').value = sehir;
            document.getElementById('edit_telefon').value = telefon;
            document.getElementById('edit_toplam_borc').value = toplam_borc;
            document.getElementById('edit_aylik_odeme').value = aylik_odeme;
            document.getElementById('edit_baslangic_tarihi').value = baslangic_tarihi;
            document.getElementById('editFirmaModal').style.display = 'block';
            
            // Hesaplamaları güncelle
            calculateInstallments('edit');
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

        // Modal kapatma - ESC tuşu ve X butonu
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                // Tüm açık modalları kapat
                const modals = document.querySelectorAll('.modal, .yearly-summary-modal');
                modals.forEach(modal => {
                    if (modal.style.display === 'block') {
                        modal.style.display = 'none';
                    }
                });
            }
        });

        // Modal dışına tıklamayı engelle (sadece X ve ESC ile kapansın)
        window.onclick = function(event) {
            // Modal dışına tıklama ile kapanmayacak - sadece X ve ESC tuşu ile
        }
</script>
</body>
</html>