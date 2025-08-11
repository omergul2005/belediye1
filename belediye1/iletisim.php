<?php
/**
 * Konya Büyükşehir Belediyesi - İletişim Sayfası
 * Bilgi İşlem Daire Başkanlığı iletişim bilgileri
 */

// Güvenlik kontrolleri
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Oturum kontrolü
if (!isset($_SESSION['kullanici_id']) || empty($_SESSION['kullanici_id'])) {
    header("Location: login.php");
    exit;
}

// Sayfa title'ı
$page_title = "İletişim - Bilgi İşlem Daire Başkanlığı";
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Konya Büyükşehir Belediyesi</title>
    <link rel="icon" type="image/png" href="konya-logo.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary-blue: #1e3a8a;
            --secondary-blue: #3b82f6;
            --light-blue: #dbeafe;
            --dark-blue: #1e40af;
            --accent-gold: #f59e0b;
            --text-dark: #1f2937;
            --text-gray: #6b7280;
            --white: #ffffff;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --success-green: #059669;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%);
            min-height: 100vh;
            color: var(--text-dark);
            line-height: 1.6;
            font-size: 16px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
        }
        
        /* Modern Header */
        .header {
            background: var(--white);
            border-bottom: 1px solid var(--gray-200);
            padding: 20px 0;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow-sm);
        }
        
        .header-content {
            display: flex;
            justify-content: between;
            align-items: center;
            gap: 32px;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 250px;
            flex-shrink: 0;
        }
        
        .header-logo {
            height: 64px;
            width: auto;
        }
        
        .logo-text {
            display: flex;
            flex-direction: column;
        }
        
        .logo-title {
            font-size: 30px;
            font-weight: 700;
            color: var(--primary-blue);
            line-height: 1.2;
        }
        
        .logo-subtitle {
            text-align: center;
            font-size: 14px;
            color: var(--text-gray);
            font-weight: 500;
        }
        
        .nav-section {
            margin-left: auto;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            background: var(--primary-blue);
            color: var(--white);
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
        }
        
        .back-btn:hover {
            background: var(--dark-blue);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
            color: var(--white);
            text-decoration: none;
        }
        
        /* Main Layout */
        .main-wrapper {
            padding: 40px 0;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 48px;
        }
        
        .page-title {
            font-size: 32px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 12px;
        }
        
        .page-subtitle {
            font-size: 18px;
            color: var(--text-gray);
            font-weight: 500;
        }

        /* İletişim Kartları */
        .contact-grid {
            display: grid;
            gap: 32px;
            margin-bottom: 48px;
        }
        
        .contact-card {
            background: var(--white);
            border-radius: 16px;
            padding: 32px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--gray-200);
            transition: all 0.3s ease;
        }
        
        .contact-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
        }
        
        .contact-content {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 32px;
            align-items: start;
        }
        
        .contact-photo {
            width: 200px;
            height: 240px;
            border-radius: 12px;
            object-fit: cover;
            border: 3px solid var(--gray-200);
            transition: all 0.3s ease;
        }
        
        .contact-card:hover .contact-photo {
            border-color: var(--secondary-blue);
        }
        
        .contact-info {
            flex: 1;
        }
        
        .contact-name {
            font-size: 28px;
            color: var(--text-dark);
            margin-bottom: 8px;
        }
        
        .contact-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 16px;
            color: var(--secondary-blue);
        }
        
        .contact-details {
            display: grid;
            gap: 16px;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background: var(--gray-50);
            border-radius: 8px;
            border-left: 4px solid var(--secondary-blue);
        }
        
        .contact-icon {
            color: var(--secondary-blue);
            font-size: 18px;
            width: 20px;
            text-align: center;
        }
        
        .contact-text {
            font-size: 15px;
            color: var(--text-dark);
            font-weight: 500;
        }

        /* Departman İletişim Bilgileri */
        .department-section {
            background: var(--white);
            border-radius: 16px;
            padding: 40px;
            margin-bottom: 32px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--gray-200);
        }
        
        .section-title {
            font-size: 24px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .section-title i {
            color: var(--secondary-blue);
        }
        
        .department-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
        }
        
        .department-card {
            background: var(--gray-50);
            border-radius: 12px;
            padding: 24px;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .department-card:hover {
            border-color: var(--secondary-blue);
            background: var(--white);
            box-shadow: var(--shadow-md);
        }
        
        .department-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
        }
        
        .department-icon {
            background: linear-gradient(135deg, var(--secondary-blue), var(--primary-blue));
            color: var(--white);
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .department-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-dark);
        }
        
        .department-info {
            display: grid;
            gap: 12px;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: var(--text-gray);
        }
        
        .info-item i {
            color: var(--secondary-blue);
            width: 16px;
        }

        /* Adres ve Harita Bölümü */
        .address-section {
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
            border-radius: 16px;
            padding: 48px;
            text-align: center;
            color: var(--white);
            margin-bottom: 32px;
        }
        
        .address-title {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 32px;
        }
        
        .address-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 32px;
            align-items: center;
        }
        
        .address-info {
            text-align: left;
        }
        
        .address-item {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 20px;
            padding: 16px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            backdrop-filter: blur(10px);
        }
        
        .address-item i {
            font-size: 20px;
            margin-top: 4px;
            opacity: 0.9;
        }
        
        .address-text {
            font-size: 16px;
            line-height: 1.6;
        }
        
        .map-placeholder {
            background: var(--white);
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            color: var(--text-gray);
            border: 2px dashed var(--gray-200);
        }
        
        .map-placeholder i {
            font-size: 48px;
            margin-bottom: 16px;
            color: var(--secondary-blue);
        }

        /* Çalışma Saatleri */
        .working-hours {
            background: var(--white);
            border-radius: 16px;
            padding: 40px;
            margin-bottom: 32px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--gray-200);
        }
        
        .hours-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 24px;
        }
        
        .hours-item {
            text-align: center;
            padding: 24px;
            background: var(--gray-50);
            border-radius: 12px;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .hours-item:hover {
            border-color: var(--secondary-blue);
            background: var(--light-blue);
        }
        
        .hours-day {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 8px;
        }
        
        .hours-time {
            font-size: 14px;
            color: var(--text-gray);
            font-weight: 500;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 0 16px;
            }
            
            .header-content {
                flex-direction: column;
                gap: 16px;
                text-align: center;
            }
            
            .contact-content {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 24px;
            }
            
            .contact-photo {
                width: 160px;
                height: 192px;
                justify-self: center;
            }
            
            .page-title {
                font-size: 28px;
            }
            
            .department-grid {
                grid-template-columns: 1fr;
            }
            
            .address-content {
                grid-template-columns: 1fr;
            }
            
            .hours-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 16px;
            }
        }
        
        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fade-in {
            animation: fadeInUp 0.6s ease-out;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo-section">
                    <img src="konya-logo.png" alt="Konya Büyükşehir Belediyesi" class="header-logo">
                    <div class="logo-text">
                        <div class="logo-title">Konya Büyükşehir Belediyesi</div>
                        <div class="logo-subtitle">Bilgi İşlem Daire Başkanlığı</div>
                    </div>
                </div>
                <div class="nav-section">
                    <a href="dashboard.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i>
                        Ana Panel
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="main-wrapper">
        <div class="container">
            <div class="page-header">
                <h1 class="page-title">İletişim Bilgileri</h1>
                <p class="page-subtitle">Bilgi İşlem Daire Başkanlığı ile İletişime Geçin</p>
            </div>
            
            <!-- Ana İletişim Bilgileri -->
            <div class="contact-grid animate-fade-in">
                <!-- Genel İletişim Bilgileri -->
                <div class="contact-card">
                    <div class="contact-content">
                        <img src="konya-logo.png" alt="Konya Büyükşehir Belediyesi" class="contact-photo">
                        <div class="contact-info">
                            <h2 class="contact-name">Genel İletişim</h2>
                            <p class="contact-title">Bilgi İşlem Daire Başkanlığı</p>
                            <div class="contact-details">
                                <div class="contact-item">
                                    <i class="fas fa-phone contact-icon"></i>
                                    <span class="contact-text">0332 221 11 00</span>
                                </div>
                                <div class="contact-item">
                                    <i class="fas fa-fax contact-icon"></i>
                                    <span class="contact-text">0332 221 12 82</span>
                                </div>
                                <div class="contact-item">
                                    <i class="fas fa-envelope contact-icon"></i>
                                    <span class="contact-text">bilgiislem@konya.bel.tr</span>
                                </div>
                                <div class="contact-item">
                                    <i class="fas fa-map-marker-alt contact-icon"></i>
                                    <span class="contact-text">Ferhuniye Mah. Şehit Nazım Bey Cad. No:35 Kat:3 Selçuklu/KONYA</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Acil Durum İletişim -->
                <div class="contact-card">
                    <div class="contact-content">
                        <img src="konya-logo.png" alt="Acil Durum" class="contact-photo">
                        <div class="contact-info">
                            <h2 class="contact-name">Acil Durum İletişim</h2>
                            <p class="contact-title">7/24 Destek Hizmetleri</p>
                            <div class="contact-details">
                                <div class="contact-item">
                                    <i class="fas fa-phone-alt contact-icon"></i>
                                    <span class="contact-text">0332 221 11 11 (7/24 Teknik Destek)</span>
                                </div>
                                <div class="contact-item">
                                    <i class="fas fa-exclamation-triangle contact-icon"></i>
                                    <span class="contact-text">0332 221 12 34 (Acil Arıza Bildirimi)</span>
                                </div>
                                <div class="contact-item">
                                    <i class="fas fa-headset contact-icon"></i>
                                    <span class="contact-text">444 42 67 (Çağrı Merkezi)</span>
                                </div>
                                <div class="contact-item">
                                    <i class="fab fa-whatsapp contact-icon"></i>
                                    <span class="contact-text">0533 042 67 42 (WhatsApp Destek)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Hızlı İletişim Butonları -->
            <div class="department-section animate-fade-in">
                <h3 class="section-title">
                    <i class="fas fa-phone-volume"></i>
                    Hızlı İletişim
                </h3>
                <p style="text-align: center; color: var(--text-gray); margin-bottom: 32px; font-size: 16px;">
                    Konya Büyükşehir Belediyesi ile hızlıca iletişime geçmek için aşağıdaki butonları kullanabilirsiniz.
                </p>
                <div class="department-grid">
                    <div class="department-card" onclick="callPhone('03322211100')" style="cursor: pointer;">
                        <div class="department-header">
                            <div class="department-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <h4 class="department-title">Telefon</h4>
                        </div>
                        <div class="department-info">
                            <div class="info-item">
                                <i class="fas fa-phone"></i>
                                <span>0332 221 11 00</span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-clock"></i>
                                <span>Pazartesi - Cuma: 08:00 - 17:00</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="department-card" onclick="sendEmail('bilgiislem@konya.bel.tr')" style="cursor: pointer;">
                        <div class="department-header">
                            <div class="department-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <h4 class="department-title">E-Posta</h4>
                        </div>
                        <div class="department-info">
                            <div class="info-item">
                                <i class="fas fa-envelope"></i>
                                <span>bilgiislem@konya.bel.tr</span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-reply"></i>
                                <span>24 saat içinde yanıt</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="department-card" onclick="openWebsite('https://www.konya.bel.tr')" style="cursor: pointer;">
                        <div class="department-header">
                            <div class="department-icon">
                                <i class="fas fa-globe"></i>
                            </div>
                            <h4 class="department-title">Web Sitesi</h4>
                        </div>
                        <div class="department-info">
                            <div class="info-item">
                                <i class="fas fa-globe"></i>
                                <span>www.konya.bel.tr</span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-info-circle"></i>
                                <span>Online hizmetler</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="department-card" onclick="openWhatsApp('905330426742')" style="cursor: pointer;">
                        <div class="department-header">
                            <div class="department-icon">
                                <i class="fab fa-whatsapp"></i>
                            </div>
                            <h4 class="department-title">WhatsApp</h4>
                        </div>
                        <div class="department-info">
                            <div class="info-item">
                                <i class="fab fa-whatsapp"></i>
                                <span>0533 042 67 42</span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-clock"></i>
                                <span>Anlık destek</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Adres ve Konum -->
            <div class="address-section animate-fade-in">
                <h3 class="address-title">Adres ve Konum Bilgileri</h3>
                <div class="address-content">
                    <div class="address-info">
                        
                        <div class="address-item">
                            <i class="fas fa-building"></i>
                            <div class="address-text">
                                <strong>Konum Adres:</strong><br>
                                Konevi Mahallesi<br>
                                Kazım Karabekir Caddesi No: 33<br>
                                42040 Meram/KONYA
                            </div>
                        </div>
                        <div class="address-item">
                            <i class="fas fa-phone"></i>
                            <div class="address-text">
                                <strong>Genel Hat:</strong><br>
                                0332 221 11 00
                            </div>
                        </div>
                        <div class="address-item">
                            <i class="fas fa-fax"></i>
                            <div class="address-text">
                                <strong>Faks:</strong><br>
                                0332 221 12 82
                            </div>
                        </div>
                        <div class="address-item">
                            <i class="fas fa-globe"></i>
                            <div class="address-text">
                                <strong>Web Sitesi:</strong><br>
                                www.konya.bel.tr
                            </div>
                        </div>
                    </div>
                    <div class="map-placeholder">
                        <i class="fas fa-map"></i>
                        <h4>Harita Konumları</h4>
                        <p><strong>Konum:</strong> Konevi Mah., Kazım Karabekir Cd. No: 33</p>
                        <p style="margin-top: 5px; color: var(--accent-gold); cursor: pointer;" onclick="openBranchMap()">
                            <i class="fas fa-map-marked-alt"></i> Harita konumu için tıklayın
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Çalışma Saatleri -->
            <div class="working-hours animate-fade-in">
                <h3 class="section-title">
                    <i class="fas fa-clock"></i>
                    Çalışma Saatleri
                </h3>
                <div class="hours-grid">
                    <div class="hours-item">
                        <div class="hours-day">Pazartesi - Cuma</div>
                        <div class="hours-time">08:00 - 17:00</div>
                    </div>
                    <div class="hours-item">
                        <div class="hours-day">Öğle Arası</div>
                        <div class="hours-time">12:00 - 13:00</div>
                    </div>
                    <div class="hours-item">
                        <div class="hours-day">Cumartesi</div>
                        <div class="hours-time">Kapalı</div>
                    </div>
                    <div class="hours-item">
                        <div class="hours-day">Pazar</div>
                        <div class="hours-time">Kapalı</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Smooth scroll ve page loading animations
        document.addEventListener('DOMContentLoaded', function() {
            // Intersection Observer for fade-in animations
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);
            
            // Initialize animation elements
            document.querySelectorAll('.animate-fade-in').forEach((el, index) => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(30px)';
                el.style.transition = `opacity 0.6s ease ${index * 0.1}s, transform 0.6s ease ${index * 0.1}s`;
                observer.observe(el);
            });
            
            // Add hover effects for cards
            document.querySelectorAll('.contact-card, .department-card, .hours-item').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-4px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
        
        // Telefon numaralarına tıklandığında arama yapma
        document.querySelectorAll('.contact-text').forEach(item => {
            if (item.textContent.includes('0332') || item.textContent.includes('444') || item.textContent.includes('0533')) {
                item.style.cursor = 'pointer';
                item.addEventListener('click', function() {
                    window.location.href = 'tel:' + this.textContent.replace(/\s+/g, '').replace(/[()]/g, '');
                });
            }
        });
        
        // Email adreslerine tıklandığında mail gönderme
        document.querySelectorAll('.contact-text').forEach(item => {
            if (item.textContent.includes('@')) {
                item.style.cursor = 'pointer';
                item.addEventListener('click', function() {
                    window.location.href = 'mailto:' + this.textContent;
                });
            }
        });

        // WhatsApp linkleri için
        document.querySelectorAll('.contact-text').forEach(item => {
            if (item.textContent.includes('0533 042 67 42')) {
                item.style.cursor = 'pointer';
                item.addEventListener('click', function() {
                    window.open('https://wa.me/905330426742', '_blank');
                });
            }
        });

       

        // harita konumu için harita açma
        function openBranchMap() {
            const branchMapUrl = 'https://maps.app.goo.gl/ufHPerV52JyNpYNh6';
            window.open(branchMapUrl, '_blank');
        }

        // Hızlı İletişim Buton Fonksiyonları
        function callPhone(number) {
            window.location.href = 'tel:' + number;
        }

        function sendEmail(email) {
            window.location.href = 'mailto:' + email;
        }

        function openWebsite(url) {
            window.open(url, '_blank');
        }

        function openWhatsApp(number) {
            window.open('https://wa.me/' + number, '_blank');
        }

        // Global fonksiyonlar olarak tanımla
        window.callPhone = callPhone;
        window.sendEmail = sendEmail;
        window.openWebsite = openWebsite;
        window.openWhatsApp = openWhatsApp;

        // Web sitesi linklerine tıklandığında site açma
        document.querySelectorAll('.contact-text').forEach(item => {
            if (item.textContent.includes('www.konya.bel.tr')) {
                item.style.cursor = 'pointer';
                item.addEventListener('click', function() {
                    window.open('https://www.konya.bel.tr', '_blank');
                });
            }
        });
    </script>
</body>
</html>