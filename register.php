
          <?php
/**
 * Konya Büyükşehir Belediyesi - Kullanıcı Kayıt Sistemi
 * Bu dosya yeni kullanıcı kayıt işlemlerini yönetir
 * Responsive tasarım ile mobil ve masaüstü uyumlu
 * 
 * @author Konya Büyükşehir Belediyesi IT Departmanı
 * @version 2.0
 * @date 2025
 */

// Veritabanı bağlantı dosyasını dahil et
include "config.php";

// Mesaj değişkenlerini başlat
$message = '';
$messageType = '';

// Form gönderildi mi kontrol et
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Form verilerini al ve temizle
    $kullanici_adi = cleanInput($_POST['kullanici_adi']);
    $sifre = $_POST['sifre'];
    $sifre_tekrar = $_POST['sifre_tekrar'];
    
    // Form doğrulama işlemleri
    if (empty($kullanici_adi) || empty($sifre) || empty($sifre_tekrar)) {
        $message = "Tüm alanlar doldurulmalıdır!";
        $messageType = 'error';
    } elseif (strlen($kullanici_adi) < 3) {
        $message = "Kullanıcı adı en az 3 karakter olmalıdır!";
        $messageType = 'error';
    } elseif (strlen($sifre) < 4) {
        $message = "Şifre en az 4 karakter olmalıdır!";
        $messageType = 'error';
    } elseif ($sifre !== $sifre_tekrar) {
        $message = "Şifreler eşleşmiyor!";
        $messageType = 'error';
    } else {
        try {
            // Kullanıcı adının daha önce kullanılıp kullanılmadığını kontrol et
            $kontrol_sorgu = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $kontrol_sorgu->execute([$kullanici_adi]);
            $kullanici_sayisi = $kontrol_sorgu->fetchColumn();
            
            if ($kullanici_sayisi > 0) {
                $message = "Bu kullanıcı adı zaten kullanımda!";
                $messageType = 'error';
            } else {
                // Güvenlik notu: Gerçek projede şifre hash'lenmelidir (password_hash() kullanın)
                
                // Yeni kullanıcıyı veritabanına ekle (onay bekliyor statüsünde)
                $sorgu = $pdo->prepare("INSERT INTO users (username, password, status, role) VALUES (?, ?, 'onay_bekliyor', 'user')");
                $basarili = $sorgu->execute([$kullanici_adi, $sifre]);
                
                if ($basarili) {
                    $message = "🎉 Kayıt başarılı! Yönetici onayı bekleniyor.";
                    $messageType = 'success';
                } else {
                    $message = "Bir hata oluştu, lütfen tekrar deneyin.";
                    $messageType = 'error';
                }
            }
        } catch (PDOException $e) {
            // Veritabanı hatası durumunda kullanıcıya genel mesaj göster
            $message = "Bir hata oluştu, lütfen tekrar deneyin.";
            $messageType = 'error';
            // Gerçek hatayı log dosyasına kaydet
            error_log("Register hatası: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <!-- Sayfa meta bilgileri -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes, minimum-scale=1.0, maximum-scale=3.0">
    <title>Konya Büyükşehir Belediyesi - Kayıt Ol</title>
    <link rel="icon" type="image/png" href="konya-logo.png">
    
    <!-- Font Awesome ikonları için CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        /* Google Fonts - Inter font ailesi */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        /* Tüm elementler için temel sıfırlama */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent; /* Mobilde dokunma vurgu rengini kaldır */
        }

        /* CSS değişkenleri - Renk paleti ve tema tanımları */
        :root {
            --primary-color: #2c5282;        /* Ana renk - Konya mavisi */
            --secondary-color: #3182ce;      /* İkincil renk - Açık mavi */
            --accent-color: #e53e3e;         /* Vurgu rengi - Kırmızı (hatalar için) */
            --success-color: #38a169;        /* Başarı rengi - Yeşil */
            --warning-color: #dd6b20;        /* Uyarı rengi - Turuncu */
            --bg-gradient: linear-gradient(135deg, #4facfe 0%, #1243b3ff -50%, #667eea 70%, #764ba2 100%); /* Arka plan gradyanı */
            --card-bg: rgba(255, 255, 255, 0.95); /* Kart arka planı */
            --text-primary: #2d3748;         /* Ana metin rengi */
            --text-secondary: #4a5568;       /* İkincil metin rengi */
            --border-color: #e2e8f0;         /* Kenarlık rengi */
            --input-bg: #ffffff;             /* Input arka plan rengi */
            --shadow-light: 0 2px 8px rgba(0, 0, 0, 0.08);   /* Hafif gölge */
            --shadow-medium: 0 8px 24px rgba(0, 0, 0, 0.12); /* Orta gölge */
            --shadow-heavy: 0 16px 48px rgba(0, 0, 0, 0.15); /* Ağır gölge */
        }

        /* Ana sayfa gövdesi stilleri */
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            position: relative;
            overflow-x: hidden; /* Yatay kaydırmayı engelle */
            font-size: 16px;
        }

        /* ===== DEKORATIF ARKA PLAN ÖĞELERİ ===== */
        /* Masaüstü için geometrik şekiller */
        .geometric-elements {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            pointer-events: none; /* Mouse etkileşimini engelle */
            z-index: 0;
        }

        /* Animasyonlu geometrik şekiller */
        .geometric-shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.08);
            animation: floatGeometric 20s infinite ease-in-out;
        }

        /* Birinci şekil - Dönen kare */
        .geometric-shape:nth-child(1) {
            width: 120px;
            height: 120px;
            top: 10%;
            left: 8%;
            border-radius: 15px;
            transform: rotate(45deg);
            animation-delay: 0s;
            background: linear-gradient(45deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.03));
        }

        /* İkinci şekil - Yüzen daire */
        .geometric-shape:nth-child(2) {
            width: 80px;
            height: 80px;
            top: 65%;
            right: 12%;
            border-radius: 50%;
            animation-delay: -8s;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.04));
        }

        /* Üçüncü şekil - Üçgen */
        .geometric-shape:nth-child(3) {
            width: 100px;
            height: 100px;
            bottom: 20%;
            left: 70%;
            clip-path: polygon(50% 0%, 0% 100%, 100% 100%);
            animation-delay: -15s;
            background: linear-gradient(90deg, rgba(255, 255, 255, 0.07), rgba(255, 255, 255, 0.12));
        }

        /* Dördüncü şekil - Küçük kare */
        .geometric-shape:nth-child(4) {
            width: 60px;
            height: 60px;
            top: 35%;
            right: 25%;
            border-radius: 10px;
            animation-delay: -5s;
            background: linear-gradient(225deg, rgba(255, 255, 255, 0.09), rgba(255, 255, 255, 0.05));
        }

        /* Geometrik şekillerin yüzme animasyonu */
        @keyframes floatGeometric {
            0%, 100% { 
                transform: translateY(0px) translateX(0px) rotate(0deg); 
                opacity: 0.08;
            }
            33% { 
                transform: translateY(-15px) translateX(8px) rotate(120deg); 
                opacity: 0.12;
            }
            66% { 
                transform: translateY(8px) translateX(-10px) rotate(240deg); 
                opacity: 0.06;
            }
        }

        /* ===== ANA KAPSAYICI KONTEYNER ===== */
        .container {
            background: var(--card-bg);
            backdrop-filter: blur(25px); /* Cam efekti */
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            box-shadow: var(--shadow-heavy);
            padding: 2.5rem;
            width: 100%;
            max-width: 480px;
            position: relative;
            z-index: 1;
            animation: slideUpFade 0.9s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            margin: auto;
            max-height: 95vh;
            overflow-y: auto; /* Uzun içerik için kaydırma */
        }

        /* Konteyner giriş animasyonu */
        @keyframes slideUpFade {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* ===== ÜST NAVIGASYON ===== */
        /* Geri dön butonu */
        .back-button {
            position: absolute;
            top: 1.2rem;
            left: 1.2rem;
            background: rgba(44, 82, 130, 0.08);
            border: 1px solid rgba(44, 82, 130, 0.15);
            border-radius: 50px;
            padding: 0.7rem 1.2rem;
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.25s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            z-index: 2;
            min-height: 44px; /* Dokunma hedefi minimum boyutu */
        }

        /* Geri dön butonu hover efekti */
        .back-button:hover {
            background: rgba(44, 82, 130, 0.12);
            border-color: var(--primary-color);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(44, 82, 130, 0.15);
        }

        /* ===== SAYFA BAŞLIĞI VE LOGO ===== */
        .header {
            text-align: center;
            margin-bottom: 2rem;
            margin-top: 1rem;
        }

        /* Logo konteyneri */
        .logo-container {
            position: relative;
            display: inline-block;
            margin-bottom: 1.2rem;
        }

        /* Logo resmi */
        .logo-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: var(--shadow-medium);
            position: relative;
            z-index: 1;
        }

        /* Ana başlık */
        .header h1 {
            color: var(--text-primary);
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 0.4rem;
            letter-spacing: -0.025em;
            line-height: 1.2;
        }

        /* Alt başlık */
        .header p {
            color: var(--text-secondary);
            font-size: 1rem;
            font-weight: 500;
            opacity: 0.85;
        }

        /* ===== MESAJ KUTUSU ===== */
        .message {
            padding: 1rem 1.2rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.7rem;
            animation: fadeInDown 0.6s ease-out;
        }

        /* Mesaj giriş animasyonu */
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Hata mesajı stilleri */
        .message.error {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            color: var(--accent-color);
            border: 1px solid #fecaca;
        }

        /* Başarı mesajı stilleri */
        .message.success {
            background: linear-gradient(135deg, #f0fff4 0%, #dcfce7 100%);
            color: var(--success-color);
            border: 1px solid #bbf7d0;
        }

        /* ===== FORM ÖĞELERİ ===== */
        /* Form grubu konteyneri */
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        /* Form etiketleri */
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Input alanları */
        .form-group input {
            width: 100%;
            padding: 1rem 1.2rem;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 1rem;
            font-family: inherit;
            background: var(--input-bg);
            transition: all 0.2s ease;
            position: relative;
            outline: none;
            min-height: 48px; /* Dokunma hedefi minimum boyutu */
        }

        /* Input odaklanma efekti */
        .form-group input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(44, 82, 130, 0.1);
            background: #ffffff;
        }

        /* Input hover efekti */
        .form-group input:hover {
            border-color: #cbd5e0;
            background: #ffffff;
        }

        /* ===== ŞİFRE GÜÇ GÖSTERGESİ ===== */
        .password-strength {
            margin-top: 0.5rem;
            display: flex;
            gap: 0.3rem;
        }

        /* Şifre gücü çubukları */
        .strength-bar {
            height: 3px;
            background: #e2e8f0;
            border-radius: 2px;
            flex: 1;
            transition: all 0.3s ease;
        }

        /* Güçlü şifre göstergesi */
        .strength-bar.active {
            background: var(--success-color);
        }

        /* Orta güçte şifre göstergesi */
        .strength-bar.medium {
            background: var(--warning-color);
        }

        /* ===== GÖNDER BUTONU ===== */
        .submit-btn {
            width: 100%;
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.25s ease;
            position: relative;
            overflow: hidden;
            margin-top: 1rem;
            min-height: 48px; /* Dokunma hedefi minimum boyutu */
        }

        /* Buton parlama efekti */
        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.15), transparent);
            transition: left 0.6s;
        }

        /* Hover'da parlama efekti */
        .submit-btn:hover::before {
            left: 100%;
        }

        /* Buton hover efekti */
        .submit-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(44, 82, 130, 0.25);
        }

        /* Buton aktif durumu */
        .submit-btn:active {
            transform: translateY(0);
        }

        /* Devre dışı buton */
        .submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        /* ===== GİRİŞ LİNKİ BÖLÜMÜ ===== */
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }

        .login-link p {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        /* Giriş yap linki */
        .login-link a {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            padding: 0.7rem 1.5rem;
            border-radius: 20px;
            background: rgba(44, 82, 130, 0.04);
            transition: all 0.25s ease;
            border: 1px solid transparent;
            min-height: 44px; /* Dokunma hedefi minimum boyutu */
        }

        /* Giriş linki hover efekti */
        .login-link a:hover {
            background: rgba(44, 82, 130, 0.08);
            transform: translateY(-1px);
            border-color: rgba(44, 82, 130, 0.15);
        }

        /* ===== GEREKSİNİMLER KUTUSU ===== */
        .requirements {
            margin-top: 1.2rem;
            padding: 1.2rem;
            background: rgba(44, 82, 130, 0.02);
            border-radius: 12px;
            border: 1px solid rgba(44, 82, 130, 0.08);
        }

        /* Gereksinimler başlığı */
        .requirements h4 {
            color: var(--text-primary);
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Gereksinimler listesi */
        .requirements ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        /* Gereksinimler liste öğeleri */
        .requirements li {
            color: var(--text-secondary);
            font-size: 0.8rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            line-height: 1.4;
        }

        /* Liste öğesi işaretçisi */
        .requirements li::before {
            content: '•';
            color: var(--primary-color);
            font-weight: bold;
        }

        /* ===== YÜKLEME ANİMASYONU ===== */
        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        /* Dönen yükleme animasyonu */
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* ===== RESPONSİVE TASARIM ===== */

        /* Büyük Masaüstü Ekranları (1200px+) */
        @media (min-width: 1200px) {
            .container {
                max-width: 520px;
                padding: 3rem;
            }
            
            .header h1 {
                font-size: 1.8rem;
            }
            
            .logo-img {
                width: 90px;
                height: 90px;
            }
        }

        /* Standart Masaüstü (992px - 1199px) */
        @media (min-width: 992px) and (max-width: 1199px) {
            .container {
                max-width: 500px;
                padding: 2.5rem;
            }
        }

        /* Tablet Yatay (768px - 991px) */
        @media (min-width: 768px) and (max-width: 991px) {
            body {
                padding: 1.5rem;
            }
            
            .container {
                max-width: 450px;
                padding: 2rem;
            }
            
            .header h1 {
                font-size: 1.5rem;
            }
            
            .logo-img {
                width: 75px;
                height: 75px;
            }
        }

        /* Tablet Dikey & Büyük Mobil (576px - 767px) */
        @media (min-width: 576px) and (max-width: 767px) {
            body {
                padding: 1rem;
            }
            
            .container {
                max-width: 400px;
                padding: 1.8rem;
                border-radius: 18px;
            }
            
            .header h1 {
                font-size: 1.4rem;
            }
            
            .header p {
                font-size: 0.9rem;
            }
            
            .logo-img {
                width: 70px;
                height: 70px;
            }
            
            .back-button {
                top: 1rem;
                left: 1rem;
                padding: 0.6rem 1rem;
                font-size: 0.8rem;
            }
        }

        /* Mobil Cihazlar (575px'e kadar) */
        @media (max-width: 575px) {
            body {
                padding: 0.5rem;
                font-size: 14px; /* Mobil için küçük font */
            }
            
            .container {
                max-width: 100%;
                padding: 1.5rem;
                margin: 0.5rem;
                border-radius: 16px;
                max-height: calc(100vh - 1rem);
            }
            
            .header {
                margin-bottom: 1.5rem;
                margin-top: 0.5rem;
            }
            
            .header h1 {
                font-size: 1.3rem;
                margin-bottom: 0.3rem;
            }
            
            .header p {
                font-size: 0.85rem;
            }
            
            .logo-container {
                margin-bottom: 1rem;
            }
            
            .logo-img {
                width: 65px;
                height: 65px;
            }
            
            .back-button {
                top: 0.8rem;
                left: 0.8rem;
                padding: 0.5rem 0.8rem;
                font-size: 0.75rem;
                min-height: 40px;
            }
            
            .form-group {
                margin-bottom: 1.2rem;
            }
            
            .form-group input {
                padding: 0.9rem 1rem;
                font-size: 16px; /* iOS zoom'unu önler */
                border-radius: 10px;
                min-height: 44px;
            }
            
            .submit-btn {
                padding: 0.9rem 1.2rem;
                font-size: 0.95rem;
                min-height: 44px;
            }
            
            .message {
                padding: 0.9rem 1rem;
                font-size: 0.85rem;
                margin-bottom: 1.2rem;
            }
            
            .requirements {
                padding: 1rem;
                margin-top: 1rem;
            }
            
            .requirements h4 {
                font-size: 0.85rem;
                margin-bottom: 0.6rem;
            }
            
            .requirements li {
                font-size: 0.75rem;
                margin-bottom: 0.4rem;
            }
            
            .login-link {
                margin-top: 1.2rem;
                padding-top: 1.2rem;
            }
            
            .login-link p {
                font-size: 0.85rem;
                margin-bottom: 0.8rem;
            }
            
            .login-link a {
                font-size: 0.85rem;
                padding: 0.6rem 1.2rem;
                min-height: 40px;
            }
        }

        /* Küçük Mobil Cihazlar (400px'e kadar) */
        @media (max-width: 400px) {
            body {
                padding: 0.25rem;
            }
            
            .container {
                padding: 1.2rem;
                margin: 0.25rem;
                border-radius: 14px;
            }
            
            .header h1 {
                font-size: 1.2rem;
            }
            
            .logo-img {
                width: 60px;
                height: 60px;
            }
            
            .form-group input {
                padding: 0.8rem 0.9rem;
            }
            
            .submit-btn {
                padding: 0.8rem 1rem;
            }
            
            .back-button {
                padding: 0.4rem 0.7rem;
                font-size: 0.7rem;
                min-height: 36px;
            }
        }

        /* Mobil Cihazlarda Yatay Konum */
        @media (max-height: 600px) and (orientation: landscape) {
            body {
                padding: 0.5rem;
            }
            
            .container {
                padding: 1rem;
                max-height: calc(100vh - 1rem);
                margin: 0.5rem auto;
            }
            
            .header {
                margin-bottom: 1rem;
                margin-top: 0.3rem;
            }
            
            .logo-container {
                margin-bottom: 0.5rem;
            }
            
            .logo-img {
                width: 50px;
                height: 50px;
            }
            
            .header h1 {
                font-size: 1.1rem;
                margin-bottom: 0.2rem;
            }
            
            .header p {
                font-size: 0.8rem;
            }
            
            .form-group {
                margin-bottom: 0.8rem;
            }
            
            .requirements {
                margin-top: 0.8rem;
                padding: 0.8rem;
            }
            
            
            .requirements li {
                font-size: 0.7rem;
                margin-bottom: 0.2rem;
            }
            
            .geometric-elements {
                display: none; /* Hide background elements in landscape */
            }
        }

        /* High DPI displays */
        @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
            .logo-img {
                image-rendering: -webkit-optimize-contrast;
                image-rendering: crisp-edges;
            }
        }

        /* Enhanced accessibility */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
            
            .geometric-elements {
                display: none;
            }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            :root {
                --card-bg: rgba(255, 255, 255, 0.98);
                --text-primary: #1a202c;
                --text-secondary: #2d3748;
            }
        }

        /* Print styles */
        @media print {
            .geometric-elements,
            .back-button {
                display: none;
            }
            
            body {
                background: white;
            }
            
            .container {
                box-shadow: none;
                border: 1px solid #ccc;
                background: white;
            }
        }
    </style>
</head>
<body>
    <div class="geometric-elements">
        <div class="geometric-shape"></div>
        <div class="geometric-shape"></div>
        <div class="geometric-shape"></div>
        <div class="geometric-shape"></div>
    </div>

    <div class="container">
        <a href="index.php" class="back-button">
            <i class="fas fa-arrow-left"></i>
            Ana Sayfa
        </a>
        
        <div class="header">
            <div class="logo-container">
                <img src="konya-logo.png" alt="Konya Belediyesi" class="logo-img">
            </div>
            <h1>Konya Büyükşehir Belediyesi</h1>
            <p>Yeni Hesap Oluşturma</p>
        </div>
        
        <?php
        if ($message) {
            $icon = $messageType === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
            echo "<div class='message $messageType'>";
            echo "<i class='$icon'></i>";
            echo $message;
            echo "</div>";
        }
        ?>
        
        <form method="POST" id="registerForm">
            <div class="form-group">
                <label for="kullanici_adi">
                    <i class="fas fa-user"></i>
                    Kullanıcı Adı
                </label>
                <input type="text" 
                    id="kullanici_adi" 
                    name="kullanici_adi" 
                    placeholder="Kullanıcı adınızı girin"
                    required 
                    minlength="3"
                    autocomplete="username"
                    value="<?php echo isset($_POST['kullanici_adi']) ? htmlspecialchars($_POST['kullanici_adi']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="sifre">
                    <i class="fas fa-lock"></i>
                    Şifre
                </label>
                <input type="password" 
                    id="sifre" 
                    name="sifre" 
                    placeholder="Şifrenizi girin"
                    required 
                    minlength="4"
                    autocomplete="new-password">
                <div class="password-strength" id="passwordStrength">
                    <div class="strength-bar"></div>
                    <div class="strength-bar"></div>
                    <div class="strength-bar"></div>
                    <div class="strength-bar"></div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="sifre_tekrar">
                    <i class="fas fa-lock"></i>
                    Şifre Tekrar
                </label>
                <input type="password" 
                    id="sifre_tekrar" 
                    name="sifre_tekrar" 
                    placeholder="Şifrenizi tekrar girin"
                    required 
                    minlength="4"
                    autocomplete="new-password">
            </div>
            
            <button type="submit" class="submit-btn" id="submitButton">
                <span id="buttonText">
                    <i class="fas fa-user-plus"></i>
                    Kayıt Ol
                </span>
            </button>
        </form>
        
        <div class="requirements">
            <h4>
                <i class="fas fa-info-circle"></i>
                Kayıt Gereksinimleri
            </h4>
            <ul>
                <li>Kullanıcı adı en az 3 karakter olmalıdır</li>
                <li>Şifre en az 4 karakter olmalıdır</li>
                <li>Şifreler eşleşmelidir</li>
                <li>Kayıt sonrası yönetici onayı gereklidir</li>
            </ul>
        </div>
        
        <div class="login-link">
            <p>Zaten hesabınız var mı?</p>
            <a href="index.php">
                <i class="fas fa-sign-in-alt"></i>
                Giriş Yap
            </a>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registerForm');
            const submitButton = document.getElementById('submitButton');
            const buttonText = document.getElementById('buttonText');
            const passwordInput = document.getElementById('sifre');
            const passwordConfirmInput = document.getElementById('sifre_tekrar');
            const strengthBars = document.querySelectorAll('.strength-bar');
            
            // Password strength indicator
            function updatePasswordStrength(password) {
                let strength = 0;
                
                if (password.length >= 4) strength++;
                if (password.length >= 6) strength++;
                if (/[A-Z]/.test(password)) strength++;
                if (/[0-9]/.test(password)) strength++;
                
                strengthBars.forEach((bar, index) => {
                    bar.classList.remove('active', 'medium');
                    if (index < strength) {
                        if (strength <= 2) {
                            bar.classList.add('medium');
                        } else {
                            bar.classList.add('active');
                        }
                    }
                });
            }
            
            // Password input event listener
            passwordInput.addEventListener('input', function() {
                updatePasswordStrength(this.value);
            });
            
            // Password confirmation validation
            function validatePasswordMatch() {
                const password = passwordInput.value;
                const confirmPassword = passwordConfirmInput.value;
                
                if (confirmPassword && password !== confirmPassword) {
                    passwordConfirmInput.setCustomValidity('Şifreler eşleşmiyor');
                } else {
                    passwordConfirmInput.setCustomValidity('');
                }
            }
            
            passwordInput.addEventListener('input', validatePasswordMatch);
            passwordConfirmInput.addEventListener('input', validatePasswordMatch);
            
            // Form submission
            form.addEventListener('submit', function(e) {
                // Loading animation
                submitButton.disabled = true;
                buttonText.innerHTML = '<span class="loading"></span> İşleniyor...';
                submitButton.style.opacity = '0.8';
                
                // Remove any existing error messages temporarily
                const existingMessage = document.querySelector('.message');
                if (existingMessage) {
                    existingMessage.style.opacity = '0.5';
                }
            });
            
            // Auto-focus on username field
            const usernameInput = document.getElementById('kullanici_adi');
            if (usernameInput && !usernameInput.value) {
                setTimeout(() => usernameInput.focus(), 100);
            }
            
            // Real-time username validation
            usernameInput.addEventListener('input', function() {
                const value = this.value.trim();
                if (value.length > 0 && value.length < 3) {
                    this.setCustomValidity('Kullanıcı adı en az 3 karakter olmalıdır');
                } else {
                    this.setCustomValidity('');
                }
            });
            
            // Enhanced keyboard navigation
            const inputs = form.querySelectorAll('input');
            inputs.forEach((input, index) => {
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        const nextInput = inputs[index + 1];
                        if (nextInput) {
                            nextInput.focus();
                        } else {
                            form.requestSubmit();
                        }
                    }
                });
            });
            
            // Mobile optimizations
            if (window.innerWidth <= 768) {
                // Prevent iOS zoom on input focus
                const inputs = document.querySelectorAll('input');
                inputs.forEach(input => {
                    input.addEventListener('focus', function() {
                        if (this.style.fontSize !== '16px') {
                            this.style.fontSize = '16px';
                        }
                    });
                });
                
                // Add haptic feedback for mobile
                if ('vibrate' in navigator) {
                    submitButton.addEventListener('click', function() {
                        navigator.vibrate(50);
                    });
                }
            }
            
            // Touch optimizations
            if ('ontouchstart' in window) {
                // Add touch feedback
                const touchElements = document.querySelectorAll('.submit-btn, .back-button, .login-link a');
                touchElements.forEach(element => {
                    element.addEventListener('touchstart', function() {
                        this.style.transform = 'scale(0.98)';
                    });
                    
                    element.addEventListener('touchend', function() {
                        setTimeout(() => {
                            this.style.transform = '';
                        }, 100);
                    });
                });
            }
            
            // Viewport height fix for mobile browsers
            function setViewportHeight() {
                const vh = window.innerHeight * 0.01;
                document.documentElement.style.setProperty('--vh', `${vh}px`);
            }
            
            setViewportHeight();
            window.addEventListener('resize', setViewportHeight);
            window.addEventListener('orientationchange', setViewportHeight);
        });
        
        // Enhanced error handling and user feedback
        window.addEventListener('error', function(e) {
            console.error('JavaScript Error:', e.error);
        });
        
        // Performance optimization: Lazy load background animations
        if (window.innerWidth > 768 && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            const geometricElements = document.querySelector('.geometric-elements');
            if (geometricElements) {
                geometricElements.style.display = 'block';
            }
        }
        
        // Security: Console warning
        console.log('%cKonya Büyükşehir Belediyesi - Güvenlik Uyarısı', 'color: #2c5282; font-size: 18px; font-weight: bold;');
        console.log('%cBu konsola kod yapıştırmayınız! Hesabınızın güvenliği için önemlidir.', 'color: #e53e3e; font-size: 14px;');
    </script>
</body>
</html>