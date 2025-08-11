<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İletişim | Konya Büyükşehir Belediyesi - Bilgi İşlem Dairesi Başkanlığı</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
            padding: 0;
            position: relative;
        }

        /* Animated background */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            animation: gradientShift 10s ease infinite;
            z-index: -1;
        }

        @keyframes gradientShift {
            0%, 100% { filter: hue-rotate(0deg) brightness(1); }
            50% { filter: hue-rotate(15deg) brightness(1.1); }
        }

        /* Navigation */
        .navbar {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .logo {
            display: flex;
            align-items: center;
            color: white;
            font-size: 20px;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-size: 20px;
        }

        .nav-links {
            display: flex;
            gap: 30px;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
            padding: 8px 15px;
            border-radius: 20px;
        }

        .nav-link:first-child {
            background: rgba(40, 167, 69, 0.8);
            font-weight: 600;
        }

        .nav-link:first-child:hover {
            background: rgba(40, 167, 69, 1);
            transform: translateY(-2px);
        }

        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -5px;
            left: 0;
            background: white;
            transition: width 0.3s ease;
        }

        .nav-link:hover::after {
            width: 100%;
        }

        /* Banner Section */
        .banner-section {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            margin: 20px auto;
            max-width: 1200px;
            border-radius: 25px;
            padding: 30px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: slideDown 0.8s ease-out;
        }

        .banner-logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            color: white;
        }

        .banner-title {
            color: white;
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .banner-subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 18px;
            margin-bottom: 20px;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            padding: 20px;
        }

        .contact-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.8s ease-out;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .contact-card:nth-child(1) { animation-delay: 0.2s; }
        .contact-card:nth-child(2) { animation-delay: 0.4s; }
        .contact-card:nth-child(3) { animation-delay: 0.6s; }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .contact-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 50px rgba(0,0,0,0.2);
        }

        .contact-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .contact-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.6s ease;
        }

        .contact-card:hover::after {
            left: 100%;
        }

        .card-title {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 600;
            position: relative;
        }

        .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background: rgba(102, 126, 234, 0.05);
            border-radius: 12px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .info-item:hover {
            background: rgba(102, 126, 234, 0.1);
            transform: translateX(5px);
        }

        .info-item i {
            font-size: 20px;
            color: #667eea;
            width: 40px;
            text-align: center;
            margin-right: 15px;
            transition: transform 0.3s ease;
        }

        .info-item:hover i {
            transform: scale(1.2);
        }

        .info-item span {
            color: #2c3e50;
            font-weight: 500;
            font-size: 15px;
            line-height: 1.4;
        }

        .department-info {
            grid-column: span 2;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            text-align: center;
            animation: fadeInUp 0.8s ease-out 0.6s both;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .department-info h2 {
            color: #2c3e50;
            font-size: 28px;
            margin-bottom: 20px;
        }

        .department-info p {
            color: #6c757d;
            font-size: 16px;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto 30px;
        }

        .quick-contact-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .contact-button {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 25px;
            border-radius: 15px;
            text-decoration: none;
            color: white;
            font-weight: 600;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        .contact-button::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transition: all 0.6s ease;
            transform: translate(-50%, -50%);
        }

        .contact-button:hover::before {
            width: 300px;
            height: 300px;
        }

        .contact-button:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 15px 30px rgba(0,0,0,0.3);
        }

        .phone-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
        }

        .email-btn {
            background: linear-gradient(135deg, #dc3545, #e83e8c);
        }

        .website-btn {
            background: linear-gradient(135deg, #007bff, #6f42c1);
        }

        .map-btn {
            background: linear-gradient(135deg, #fd7e14, #ffc107);
        }

        .contact-button i {
            font-size: 28px;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
            transition: transform 0.3s ease;
        }

        .contact-button:hover i {
            transform: scale(1.2) rotate(5deg);
        }

        .contact-button span {
            font-size: 16px;
            margin-bottom: 5px;
            position: relative;
            z-index: 1;
        }

        .contact-button small {
            font-size: 12px;
            opacity: 0.9;
            font-weight: 400;
            position: relative;
            z-index: 1;
        }

        .working-hours {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-top: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .working-hours h3 {
            margin-bottom: 20px;
            text-align: center;
            font-size: 18px;
        }

        .hours-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }

        .hours-item:hover {
            background: rgba(255,255,255,0.05);
            padding-left: 10px;
            border-radius: 8px;
        }

        .map-container {
            margin-top: 20px;
            border-radius: 15px;
            overflow: hidden;
            height: 250px;
            background: #f8f9fa;
            position: relative;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .map-container iframe {
            transition: all 0.3s ease;
            filter: grayscale(20%);
        }

        .map-container:hover iframe {
            filter: grayscale(0%) brightness(1.1);
            transform: scale(1.02);
        }

        /* Status indicator */
        .status-indicator {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(40, 167, 69, 0.9);
            color: white;
            padding: 10px 15px;
            border-radius: 25px;
            font-size: 12px;
            font-weight: 600;
            backdrop-filter: blur(10px);
            animation: slideInRight 1s ease-out 2s both;
            z-index: 1000;
        }

        .status-indicator::before {
            content: '';
            display: inline-block;
            width: 8px;
            height: 8px;
            background: #fff;
            border-radius: 50%;
            margin-right: 8px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(100px); }
            to { opacity: 1; transform: translateX(0); }
        }

        /* Notification styles */
        .notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(102, 126, 234, 0.9);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            backdrop-filter: blur(10px);
            transform: translateX(400px);
            transition: transform 0.3s ease;
            z-index: 1000;
            max-width: 300px;
            font-size: 14px;
        }

        .notification.show {
            transform: translateX(0);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-links { display: none; }
            .main-container {
                grid-template-columns: 1fr;
                padding: 0 15px;
            }
            .department-info {
                grid-column: span 1;
            }
            .banner-title {
                font-size: 24px;
            }
            .contact-card {
                padding: 25px;
            }
            .quick-contact-buttons {
                grid-template-columns: 1fr;
            }
            .status-indicator {
                position: relative;
                top: auto;
                right: auto;
                margin: 20px auto;
                text-align: center;
                display: block;
                width: fit-content;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                <div class="logo-icon">🏛️</div>
                Konya Belediyesi
            </div>
            <div class="nav-links">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i> Ana Panel
                </a>
            
            </div>
        </div>
    </nav>

    <div class="banner-section">
        <div class="banner-logo">🏛️</div>
        <h1 class="banner-title">Konya Büyükşehir Belediyesi</h1>
        <p class="banner-subtitle">Bilgi İşlem Dairesi Başkanlığı - İletişim</p>
    </div>

    <div class="status-indicator">
        <i class="fas fa-wifi"></i> Sistem Aktif - 7/24 Hizmet
    </div>

    <div class="main-container">
        <div class="contact-card loading">
            <h2 class="card-title">İletişim Bilgileri</h2>
            <div class="info-item clickable" onclick="openMap()">
                <i class="fas fa-map-marker-alt"></i>
                <span>Ferhuniye Mah. Şehit Nazım Bey Cad. No:35 Kat:3 Selçuklu / KONYA</span>
            </div>
            <div class="info-item clickable" onclick="callNumber('03322211100')">
                <i class="fas fa-phone"></i>
                <span>0332 221 11 00</span>
            </div>
            <div class="info-item clickable" onclick="callNumber('03322211282')">
                <i class="fas fa-fax"></i>
                <span>0332 221 12 82</span>
            </div>
            <div class="info-item clickable" onclick="sendEmail('bilgiislem@konya.bel.tr')">
                <i class="fas fa-envelope"></i>
                <span>bilgiislem@konya.bel.tr</span>
            </div>
            <div class="info-item clickable" onclick="openWebsite('https://www.konya.bel.tr')">
                <i class="fas fa-globe"></i>
                <span>www.konya.bel.tr</span>
            </div>
            
            <div class="working-hours">
                <h3><i class="fas fa-clock"></i> Çalışma Saatleri</h3>
                <div class="hours-item">
                    <span>Pazartesi - Cuma:</span>
                    <span>08:00 - 17:00</span>
                </div>
                <div class="hours-item">
                    <span>Öğle Molası:</span>
                    <span>12:00 - 13:00</span>
                </div>
                <div class="hours-item">
                    <span>Hafta Sonu:</span>
                    <span>Kapalı</span>
                </div>
            </div>
        </div>

        <div class="contact-card loading">
            <h2 class="card-title">Acil Durum İletişim</h2>
            <div class="info-item clickable" onclick="callNumber('03322211111')">
                <i class="fas fa-phone-alt"></i>
                <span>7/24 Teknik Destek: 0332 221 11 11</span>
            </div>
            <div class="info-item clickable" onclick="callNumber('03322211234')">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Acil Arıza Bildirimi: 0332 221 12 34</span>
            </div>
            <div class="info-item clickable" onclick="callNumber('4444267')">
                <i class="fas fa-headset"></i>
                <span>Çağrı Merkezi: 444 42 67</span>
            </div>
            <div class="info-item clickable" onclick="openWhatsApp('905330426742')">
                <i class="fab fa-whatsapp"></i>
                <span>WhatsApp Destek: 0533 042 67 42</span>
            </div>
            
            <div class="map-container">
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3148.5847668987657!2d32.4844286!3d37.8748!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x14d085b2e7b7b7b7%3A0x123456789!2sKonya%20B%C3%BCy%C3%BCk%C5%9Fehir%20Belediyesi!5e0!3m2!1str!2str!4v1234567890123"
                    width="100%" 
                    height="100%" 
                    style="border:0; border-radius: 15px;" 
                    allowfullscreen="" 
                    loading="lazy" 
                    referrerpolicy="no-referrer-when-downgrade"
                    title="Konya Büyükşehir Belediyesi Konum Haritası">
                </iframe>
            </div>
        </div>

        <div class="department-info loading">
            <h2><i class="fas fa-phone-volume"></i> Hızlı İletişim</h2>
            <p>
                Konya Büyükşehir Belediyesi ile hızlıca iletişime geçmek için aşağıdaki butonları kullanabilirsiniz.
            </p>
            
            <div class="quick-contact-buttons">
                <a href="tel:03322211100" class="contact-button phone-btn">
                    <i class="fas fa-phone"></i>
                    <span>Ara</span>
                    <small>0332 221 11 00</small>
                </a>
                <a href="mailto:bilgiislem@konya.bel.tr" class="contact-button email-btn">
                    <i class="fas fa-envelope"></i>
                    <span>E-posta Gönder</span>
                    <small>bilgiislem@konya.bel.tr</small>
                </a>
                <a href="https://www.konya.bel.tr" target="_blank" class="contact-button website-btn">
                    <i class="fas fa-globe"></i>
                    <span>Web Sitesi</span>
                    <small>www.konya.bel.tr</small>
                </a>
                <a href="https://www.google.com/maps?q=Ferhuniye+Mah.+Şehit+Nazım+Bey+Cad.+No:35+Selçuklu+KONYA" target="_blank" class="contact-button map-btn">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>Haritada Gör</span>
                    <small>Yol Tarifi Al</small>
                </a>
            </div>
        </div>
    </div>

    <script>
        // Tüm tıklama fonksiyonları çalışıyor
        function callNumber(number) {
            window.open(`tel:${number}`, '_self');
            showNotification(`${number} aranıyor...`, 'success');
        }

        function sendEmail(email) {
            window.open(`mailto:${email}`, '_self');
            showNotification(`${email} adresine e-posta gönderiliyor...`, 'info');
        }

        function openWebsite(url) {
            window.open(url, '_blank');
            showNotification('Web sitesi açılıyor...', 'info');
        }

        function openMap() {
            const mapUrl = 'https://www.google.com/maps?q=Ferhuniye+Mah.+Şehit+Nazım+Bey+Cad.+No:35+Selçuklu+KONYA';
            window.open(mapUrl, '_blank');
            showNotification('Haritada konum gösteriliyor...', 'info');
        }

        function openWhatsApp(number) {
            window.open(`https://wa.me/${number}`, '_blank');
            showNotification('WhatsApp açılıyor...', 'success');
        }

        // Bildirim sistemi
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = 'notification';
            notification.textContent = message;
            
            if (type === 'success') {
                notification.style.background = 'rgba(40, 167, 69, 0.9)';
            } else if (type === 'error') {
                notification.style.background = 'rgba(220, 53, 69, 0.9)';
            }
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.add('show');
            }, 100);
            
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }

        // Sayfa yüklendiğinde
        document.addEventListener('DOMContentLoaded', function() {
            // Loading animasyonları
            const loadingElements = document.querySelectorAll('.loading');
            loadingElements.forEach((el, index) => {
                el.style.animationDelay = `${index * 0.2}s`;
            });

            // Hoş geldin mesajı
            setTimeout(() => {
                showNotification('Konya Büyükşehir Belediyesi iletişim sayfasına hoş geldiniz!', 'success');
            }, 1500);

            // Saat güncelleme
            function updateTime() {
                const now = new Date();
                const timeString = now.toLocaleTimeString('tr-TR');
                const statusIndicator = document.querySelector('.status-indicator');
                if (statusIndicator && now.getHours() >= 8 && now.getHours() < 17) {
                    statusIndicator.innerHTML = `<i class="fas fa-clock"></i> Mesai Saati - ${timeString}`;
                }
            }

            updateTime();
            setInterval(updateTime, 60000);

            console.log('📞 Konya Belediyesi İletişim Sistemi Aktif!');
        });
    </script>
</body>
</html>