<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_GET['firma_id'])) {
    echo json_encode(['error' => 'Firma ID bulunamadı']);
    exit;
}

$firma_id = (int)$_GET['firma_id'];

try {
    // Firma bilgilerini al
    $firma_stmt = $pdo->prepare("
        SELECT 
            f.*,
            (f.toplam_borc - f.kalan_borc) as odenen_tutar
        FROM firmalar f 
        WHERE f.id = ?
    ");
    $firma_stmt->execute([$firma_id]);
    $firma = $firma_stmt->fetch();
    
    if (!$firma) {
        echo json_encode(['error' => 'Firma bulunamadı']);
        exit;
    }
    
    // Ödeme geçmişini al
    $odeme_stmt = $pdo->prepare("
        SELECT * FROM taksitler 
        WHERE firma_id = ? AND durum = 'odendi'
        ORDER BY odeme_tarihi ASC
    ");
    $odeme_stmt->execute([$firma_id]);
    $odemeler = $odeme_stmt->fetchAll();
    
    // KÜSURAT DÜZELTME: Normal ve son taksit hesaplama
    $normal_taksit_tutari = floor($firma['toplam_borc'] / $firma['taksit_sayisi']);
    $son_taksit_tutari = $firma['toplam_borc'] - ($normal_taksit_tutari * ($firma['taksit_sayisi'] - 1));
    
    $firma_data = [
        'firma_adi' => $firma['firma_adi'],
        'taksit_sayisi' => $firma['taksit_sayisi'],
        'normal_taksit_tutari' => $normal_taksit_tutari,
        'son_taksit_tutari' => $son_taksit_tutari,
        'kalan_borc' => $firma['kalan_borc']
    ];
    
    // Taksit takvimi oluştur
    $taksit_takvimi = [];
    $baslangic_date = new DateTime($firma['baslangic_tarihi']);
    $today = new DateTime();
    
    for ($i = 1; $i <= $firma['taksit_sayisi']; $i++) {
        $vade_tarihi = clone $baslangic_date;
        $vade_tarihi->add(new DateInterval('P' . (($i - 1) * 30) . 'D'));
        
        // 15 GÜN TOLERANS - DÜZELTME
        $tolerans_tarihi = clone $vade_tarihi;
        $tolerans_tarihi->add(new DateInterval('P15D')); // 15 gün sonra
        
        // Son taksit farklı tutarda
        $tutar = ($i == $firma['taksit_sayisi']) ? $son_taksit_tutari : $normal_taksit_tutari;
        
        // Durum ve ödeme tarihi belirleme
        $durum = 'bekliyor';
        $odeme_tarihi = null;
        $durum_renk = '#6c757d';
        $durum_text = 'Bekliyor';
        $gecikme_faizi = 0;
        $notlar = '';
        
        // Ödenen taksitler için ödeme tarihi bul
        if ($i <= count($odemeler)) {
            $odeme = $odemeler[$i - 1];
            $durum = 'odendi';
            $odeme_tarihi = $odeme['odeme_tarihi'];
            $durum_renk = '#28a745';
            $durum_text = 'Ödendi';
            $tutar = $odeme['tutar']; // Gerçek ödenen tutarı kullan
            
            // ÖNEMLİ DÜZELTME: 15 GÜN TOLERANS KONTROLÜ
            $odeme_date = new DateTime($odeme_tarihi);
            
            if ($odeme_date > $tolerans_tarihi) {
                // 15 günden fazla gecikme - FAİZ UYGULA
                $gecikme_gun = $vade_tarihi->diff($odeme_date)->days;
                $gecikme_faizi = $tutar * 0.02; // %2 faiz
                $durum_text = 'Gecikmeli Ödendi';
                $durum_renk = '#fd7e14';
                $notlar = "Gecikmeli ödendi ({$gecikme_gun} gün)";
            } else if ($odeme_date > $vade_tarihi) {
                // 15 günden az gecikme - FAİZ YOK
                $gecikme_gun = $vade_tarihi->diff($odeme_date)->days;
                $gecikme_faizi = 0; // FAİZ UYGULANMAZ
                $durum_text = 'Ödendi';
                $durum_renk = '#28a745';
                $notlar = "Zamanında ödendi ({$gecikme_gun} gün gecikme - tolerans içi)";
            } else {
                // Zamanında ödeme
                $notlar = 'Zamanında ödendi';
            }
        } else if ($firma['kalan_borc'] <= 0) {
            $durum = 'tamamlandi';
            $durum_renk = '#007bff';
            $durum_text = 'Tamamlandı';
            $notlar = 'Borç tamamlandı';
        } else if ($today > $tolerans_tarihi) {
            // 15 günden fazla gecikme
            $durum = 'gecikme';
            $durum_renk = '#dc3545';
            $durum_text = 'Gecikme';
            $gecikme_gun = $tolerans_tarihi->diff($today)->days;
            $gecikme_faizi = $tutar * 0.02; // %2 faiz
            $notlar = "Gecikme faizi uygulanacak ({$gecikme_gun} gün)";
        } else if ($today > $vade_tarihi) {
            // 15 gün içinde gecikme - tolerans süresi
            $durum = 'vade_gecti';
            $durum_renk = '#fd7e14';
            $durum_text = 'Vadesi Geçti (Tolerans)';
            $gecikme_gun = $vade_tarihi->diff($today)->days;
            $gecikme_faizi = 0; // Henüz faiz yok
            $notlar = "Vadesi geçti ({$gecikme_gun} gün - tolerans süresi)";
        }
        
        $toplam_tutar = $tutar + $gecikme_faizi;
        
        $taksit_takvimi[] = [
            'taksit_no' => $i,
            'vade_tarihi' => $vade_tarihi->format('d.m.Y'),
            'tutar' => $tutar,
            'gecikme_faizi' => $gecikme_faizi,
            'toplam_tutar' => $toplam_tutar,
            'durum' => $durum,
            'durum_text' => $durum_text,
            'durum_renk' => $durum_renk,
            'odeme_tarihi' => $odeme_tarihi,
            'notlar' => $notlar
        ];
    }
    
    // Özet bilgiler
    $odenen_taksit_sayisi = count($odemeler);
    $kalan_taksit_sayisi = $firma['taksit_sayisi'] - $odenen_taksit_sayisi;
    $odeme_orani = $firma['toplam_borc'] > 0 ? round(($firma['odenen_tutar'] / $firma['toplam_borc']) * 100, 1) : 0;
    
    // Tahmini bitiş tarihi
    if ($kalan_taksit_sayisi > 0) {
        $tahmini_bitis = clone $baslangic_date;
        $tahmini_bitis->add(new DateInterval('P' . (($firma['taksit_sayisi'] - 1) * 30) . 'D'));
        $tahmini_bitis_str = $tahmini_bitis->format('d.m.Y');
    } else {
        $tahmini_bitis_str = 'Tamamlandı';
    }
    
    $ozet = [
        'odenen_taksit_sayisi' => $odenen_taksit_sayisi,
        'kalan_taksit_sayisi' => $kalan_taksit_sayisi,
        'odeme_orani' => $odeme_orani,
        'tahmini_bitis' => $tahmini_bitis_str
    ];
    
    echo json_encode([
        'firma' => $firma_data,
        'taksit_takvimi' => $taksit_takvimi,
        'ozet' => $ozet
    ], JSON_UNESCAPED_UNICODE);
    
} catch(PDOException $e) {
    echo json_encode(['error' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>