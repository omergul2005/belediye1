<?php
/**
 * Konya Büyükşehir Belediyesi - Hakkımızda Sayfası
 * Bilgi İşlem Daire Başkanlığı hakkında detaylı bilgiler
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
$page_title = "Hakkımızda - Bilgi İşlem Daire Başkanlığı";
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
        
        /* Professional Manager Cards */
        .managers-grid {
            display: grid;
            gap: 32px;
            margin-bottom: 48px;
        }
        
        .manager-card {
            background: var(--white);
            border-radius: 16px;
            padding: 32px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--gray-200);
            transition: all 0.3s ease;
        }
        
        .manager-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
        }
        
        .manager-content {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 32px;
            align-items: start;
        }
        
        .manager-photo {
            width: 200px;
            height: 240px;
            border-radius: 12px;
            object-fit: cover;
            border: 3px solid var(--gray-200);
            transition: all 0.3s ease;
        }
        
        .manager-card:hover .manager-photo {
            border-color: var(--secondary-blue);
        }
        
        .manager-info {
            flex: 1;
        }
        
        .manager-name {
            font-size: 28px;
            color: var(--text-dark);
            margin-bottom: 8px;
        }
        
        .manager-title {
            font-size: 18px;
            
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 16px; 
        }
        
        .manager-bio {
            font-size: 15px;
            line-height: 1.7;
            color: var(--text-gray);
            margin-bottom: 24px;
        }
        
        .cv-toggle-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--light-blue);
            color: var(--primary-blue);
            border: 2px solid var(--secondary-blue);
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .cv-toggle-btn:hover {
            background: var(--secondary-blue);
            color: var(--white);
        }
        
        .cv-toggle-btn.active {
            background: var(--secondary-blue);
            color: var(--white);
        }
        
        .cv-section {
            margin-top: 24px;
            background: var(--gray-50);
            border-radius: 12px;
            padding: 24px;
            border-left: 4px solid var(--secondary-blue);
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            transition: all 0.4s ease;
        }
        
        .cv-section.active {
            max-height: 500px;
            opacity: 1;
        }
        
        .cv-content h4 {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .cv-content p {
            font-size: 16px;
            line-height: 1.7;
            color: var(--text-gray);
        }
        
        /* Department Responsibilities */
        .responsibilities-section {
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
        
        .responsibilities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
        }
        
        
        
        .responsibility-header {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 12px;
        }
        
        .responsibility-icon {
            background: var(--secondary-blue);
            color: var(--white);
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }
        
        .responsibility-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-dark);
            line-height: 1.4;
        }
        
        
        
        /* Projects Section */
        .projects-section {
            background: var(--white);
            border-radius: 16px;
            padding: 40px;
            margin-bottom: 32px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--gray-200);
        }
        
        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
        }
        
        .project-card {
            background: var(--gray-50);
            border-radius: 12px;
            padding: 24px;
            border: 2px solid transparent;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .project-card:hover {
            border-color: var(--secondary-blue);
            background: var(--white);
            box-shadow: var(--shadow-md);
        }
        
        .project-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
        }
        
        .project-icon {
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
        
        .project-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-dark);
        }
        
        .project-desc {
            font-size: 14px;
            color: var(--text-gray);
            line-height: 1.6;
        }
        
        /* Stats Section */
        .stats-section {
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
            border-radius: 16px;
            padding: 48px;
            text-align: center;
            color: var(--white);
            margin-bottom: 32px;
        }
        
        .stats-title {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 32px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 32px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 42px;
            font-weight: 900;
            margin-bottom: 8px;
            display: block;
        }
        
        .stat-label {
            font-size: 16px;
            opacity: 0.9;
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
            
            .manager-content {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 24px;
            }
            
            .manager-photo {
                width: 160px;
                height: 192px;
                justify-self: center;
            }
            
            .page-title {
                font-size: 28px;
            }
            
            .responsibilities-grid {
                grid-template-columns: 1fr;
            }
            
            .projects-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 24px;
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
        .expandable-card {
    max-height: 80px;
    overflow: hidden;
    transition: all 0.4s ease;
}

.expandable-card.open {
    max-height: 300px; /* Açıldığında genişlik */
}

.expandable-card .project-desc {
    opacity: 0;
    transition: opacity 0.4s ease;
    margin: 0;
}

.expandable-card.open .project-desc {
    opacity: 1;
    margin-top: 10px;
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
                <h1 class="page-title">Bilgi İşlem Daire Başkanlığı</h1>
                <p class="page-subtitle">Teknoloji ve İnovasyon Merkezi</p>
            </div>
            
            <!-- Yöneticiler Bölümü -->
            <div class="managers-grid animate-fade-in">
                <!-- Harun Yiğit - Daire Başkanı -->
                <div class="manager-card">
                    <div class="manager-content">
                        <img src="yigit.jpg" alt="Harun YİĞİT" class="manager-photo">
                        <div class="manager-info">
                            <h2 class="manager-name">Harun YİĞİT</h2>
                            <p class="manager-title">Bilgi İşlem Daire Başkanı</p>
                            <button class="cv-toggle-btn" onclick="toggleCV('harun-cv')">
                                <i class="fas fa-user-circle"></i>
                                Detaylı Özgeçmiş
                            </button>
                            <div id="harun-cv" class="cv-section">
                                <div class="cv-content">
                                    <h4><i class="fas fa-graduation-cap"></i>Özgeçmiş</h4>
                                    <p>
                                        Harun Yiğit, 1980 yılında Konya'da doğdu. İlk, orta ve lise öğrenimini Konya'da tamamladı. 
                                        2003 yılında Mersin Üniversitesi Bilgisayar Mühendisliği bölümünden mezun oldu. 2 yıl özel 
                                        sektörde çalıştıktan sonra 2005 yılında Konya Büyükşehir Belediyesi'nde göreve başladı. 
                                        2016 yılında Yazılım Şube Müdürü, 2018 yılında Akıllı Şehir Yönetimi Şube Müdürü olarak 
                                        görevlendirildi. Halen Bilgi İşlem Dairesi Başkanlığı görevini yürütmektedir. 
                                        Evli ve 3 çocuk babasıdır.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Hasan Arslan - Yazılım Şube Müdürü -->
                <div class="manager-card">
                    <div class="manager-content">
                        <img src="hasan.jpg" alt="Hasan ARSLAN" class="manager-photo">
                        <div class="manager-info">
                            <h2 class="manager-name">Hasan ARSLAN</h2>
                            <p class="manager-title">Yazılım Şube Müdürü</p>
                            <button class="cv-toggle-btn" onclick="toggleCV('hasan-cv')">
                                <i class="fas fa-user-circle"></i>
                                Detaylı Özgeçmiş
                            </button>
                            <div id="hasan-cv" class="cv-section">
                                <div class="cv-content">
                                    <h4><i class="fas fa-graduation-cap"></i>Özgeçmiş</h4>
                                    <p>
                                        Hasan Arslan, 1978 yılında Mersin'in Erdemli ilçesinde doğdu. Erdemli İmam Hatip Lisesini 
                                        bitirdi. Selçuk Üniversitesi Bilgisayar Programcılığını ve Necmettin Erbakan Üniversitesi 
                                        Bilgisayar Mühendisliğini tamamladı. Eylül 2000'de Konya Büyükşehir Belediyesi'nde yazılım 
                                        geliştirici olarak işe başladı. 2014'te Bilgi İşlem Şube Müdürü, 2018'de Yazılım Şube 
                                        Müdürü oldu. Orta derecede İngilizce bilir, evli ve 3 çocuk babasıdır.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
    <!-- Daire Başkanı Görevleri -->
<div class="responsibilities-section animate-fade-in">
    <h3 class="section-title">
        <i class="fas fa-user-tie"></i>
        Başkanın Görevleri
    </h3>
    <div class="responsibilities-grid">
        <div class="project-card expandable-card" onclick="toggleCard(this)">
            <div class="responsibility-header">
                <div class="responsibility-icon">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="responsibility-title">Emir ve Talimatların Yerine Getirilmesi</div>
            </div>
            <div class="responsibility-desc">
                Başkan, Genel Sekreter ve yardımcılarının talimatlarını yerine getirir, Belediye Meclisi ve Encümen kararlarını uygular.
            </div>
        </div>

        <div class="project-card expandable-card" onclick="toggleCard(this)">
            <div class="responsibility-header">
                <div class="responsibility-icon">
                    <i class="fas fa-project-diagram"></i>
                </div>
                <div class="responsibility-title">Koordinasyon ve Denetim</div>
            </div>
            <div class="responsibility-desc">
                Müdürlükler arası koordinasyonu sağlar, çalışmaları denetler, görev dağılımlarını yapar.
            </div>
        </div>

        <div class="project-card expandable-card" onclick="toggleCard(this)">
            <div class="responsibility-header">
                <div class="responsibility-icon">
                    <i class="fas fa-envelope-open-text"></i>
                </div>
                <div class="responsibility-title">Evrak ve Yazışmalar</div>
            </div>
            <div class="responsibility-desc">
                Gelen evrakları havale eder, yazışmaları imzalar ve takibini yapar.
            </div>
        </div>

        <div class="project-card expandable-card" onclick="toggleCard(this)">
            <div class="responsibility-header">
                <div class="responsibility-icon">
                    <i class="fas fa-gavel"></i>
                </div>
                <div class="responsibility-title">Disiplin ve Denetim</div>
            </div>
            <div class="responsibility-desc">
                Disiplin amiri olarak gerekli işlemleri yürütür.
            </div>
        </div>

        <div class="project-card expandable-card" onclick="toggleCard(this)">
            <div class="responsibility-header">
                <div class="responsibility-icon">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="responsibility-title">Bütçe ve Harcama</div>
            </div>
            <div class="responsibility-desc">
                Daire Başkanlığının bütçesini hazırlar ve harcama yetkilisi olarak harcamaları yapar.
            </div>
        </div>

        <div class="project-card expandable-card" onclick="toggleCard(this)">
            <div class="responsibility-header">
                <div class="responsibility-icon">
                    <i class="fas fa-file-contract"></i>
                </div>
                <div class="responsibility-title">İhale Süreçleri</div>
            </div>
            <div class="responsibility-desc">
                İhale yetkilisi olarak ihale onaylarını düzenler ve sözleşmeleri imzalar.
            </div>
        </div>

        <div class="project-card expandable-card" onclick="toggleCard(this)">
            <div class="responsibility-header">
                <div class="responsibility-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="responsibility-title">Faaliyet ve Strateji</div>
            </div>
            <div class="responsibility-desc">
                Faaliyet raporlarını ve stratejik planları hazırlar, uygular.
            </div>
        </div>

        <div class="project-card expandable-card" onclick="toggleCard(this)">
            <div class="responsibility-header">
                <div class="responsibility-icon">
                    <i class="fas fa-archive"></i>
                </div>
                <div class="responsibility-title">Arşiv Yönetimi</div>
            </div>
            <div class="responsibility-desc">
                Arşiv oluşturur, arşiv faaliyetlerini yürütür.
            </div>
        </div>

        <div class="project-card expandable-card" onclick="toggleCard(this)">
            <div class="responsibility-header">
                <div class="responsibility-icon">
                    <i class="fas fa-handshake"></i>
                </div>
                <div class="responsibility-title">Belediye ile İlişkiler</div>
            </div>
            <div class="responsibility-desc">
                Meclis ve Encümene teklif sunar, bilgi edinme başvurularını ilgili birimlere yönlendirir.
            </div>
        </div>

        <div class="project-card expandable-card" onclick="toggleCard(this)">
            <div class="responsibility-header">
                <div class="responsibility-icon">
                    <i class="fas fa-lightbulb"></i>
                </div>
                <div class="responsibility-title">Proje Geliştirme</div>
            </div>
            <div class="responsibility-desc">
                Faaliyet alanına dair projeler üretir, takip eder ve uygular.
            </div>
        </div>
    </div>
</div>
<!-- Daire Başkanlığı Görevleri -->
<div class="responsibilities-section animate-fade-in">
    <h3 class="section-title">
        <i class="fas fa-building"></i>
        Daire Başkanlığı Görevleri
    </h3>
    <div class="responsibilities-grid">
        <div class="project-card expandable-card" onclick="toggleCard(this)">
            <div class="responsibility-header">
                <div class="responsibility-icon">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="responsibility-title">Donanım Temini ve Bakımı</div>
            </div>
            <div class="responsibility-desc">
                Belediye birimlerinin ihtiyacı olan bilgisayar, yazıcı, tarayıcı gibi cihazların temini, bakım ve onarımlarının yapılması.
            </div>
        </div>

        <div class="project-card expandable-card" onclick="toggleCard(this)">
            <div class="responsibility-header">
                <div class="responsibility-icon">
                    <i class="fas fa-network-wired"></i>
                </div>
                <div class="responsibility-title">Ağ ve Sunucu Sistemleri</div>
            </div>
            <div class="responsibility-desc">
                Ağ, internet, sunucu ve yedekleme sistemlerinin kurulması ve işletilmesinin sağlanması.
            </div>
        </div>

        <div class="project-card expandable-card" onclick="toggleCard(this)">
            <div class="responsibility-header">
                <div class="responsibility-icon">
                    <i class="fas fa-bus-alt"></i>
                </div>
                <div class="responsibility-title">Toplu Ulaşım Sistemleri</div>
            </div>
            <div class="responsibility-desc">
                Toplu Ulaşım Elektronik Ücret Toplama Sisteminin kurulması ve işletilmesi.
            </div>
        </div>

        <div class="project-card expandable-card" onclick="toggleCard(this)">
            <div class="responsibility-header">
                <div class="responsibility-icon">
                    <i class="fas fa-laptop-code"></i>
                </div>
                <div class="responsibility-title">Yazılım ve Uygulama Geliştirme</div>
            </div>
            <div class="responsibility-desc">
                Yazılımlar, web sayfaları ve mobil uygulamaların temin edilmesi veya geliştirilmesi ve kurulması.
            </div>
        </div>

        <div class="project-card expandable-card" onclick="toggleCard(this)">
            <div class="responsibility-header">
                <div class="responsibility-icon">
                    <i class="fas fa-video"></i>
                </div>
                <div class="responsibility-title">Güvenlik Sistemleri</div>
            </div>
            <div class="responsibility-desc">
                Güvenlik kamerası ve alarm sistemlerinin kurulması ve işletilmesinin sağlanması.
            </div>
        </div>
    </div>
</div>


            
            <!-- Başarılı Projeler -->
            <div class="projects-section animate-fade-in">
                <h3 class="section-title">
                    <i class="fas fa-rocket"></i>
                    Başarılı Projelerimiz
                </h3>
                <div class="projects-grid">
                    <div class="project-card expandable-card" onclick="toggleCard(this)">
                        <div class="project-header">
                            <div class="project-icon">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <h4 class="project-title">Konya Kart Sistemi</h4>
                        </div>
                        <p class="project-desc">
                            Şehrin tüm toplu ulaşım araçlarında kullanılan entegre elektronik ödeme sistemi. 
                            Günlük 500.000+ işlem kapasitesi ile vatandaşlarımıza hizmet vermektedir.
                        </p>
                    </div>
                    
                    <div class="project-card expandable-card" onclick="toggleCard(this)">
                        <div class="project-header">
                            <div class="project-icon">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <h4 class="project-title">Konya Büyükşehir Mobil</h4>
                        </div>
                        <p class="project-desc">
                            Belediye hizmetlerine mobil erişim sağlayan kapsamlı uygulama. Fatura ödeme, 
                            başvuru yapma ve şehir bilgilerine erişim imkanı sunmaktadır.
                        </p>
                    </div>
                    
                    <div class="project-card expandable-card" onclick="toggleCard(this)">
                        <div class="project-header">
                            <div class="project-icon">
                                <i class="fas fa-traffic-light"></i>
                            </div>
                            <h4 class="project-title">Akıllı Trafik Yönetimi</h4>
                        </div>
                        <p class="project-desc">
                            Yapay zeka destekli trafik optimizasyon sistemi ile şehir trafiğinde %30 
                            verimlilik artışı sağlanmıştır.
                        </p>
                    </div>
                    
                    <div class="project-card expandable-card" onclick="toggleCard(this)">
                        <div class="project-header">
                            <div class="project-icon">
                                <i class="fas fa-leaf"></i>
                            </div>
                            <h4 class="project-title">Çevre İzleme Sistemi</h4>
                        </div>
                        <p class="project-desc">
                            IoT sensörleri ile şehir genelinde hava kalitesi, gürültü ve çevre 
                            parametrelerinin anlık izlenmesi sağlanmaktadır.
                        </p>
                    </div>

                    <div class="project-card expandable-card" onclick="toggleCard(this)">
                  <div class="project-header">
                   <div class="project-icon">
                   <i class="fas fa-globe"></i>
                  </div>
                   <h4 class="project-title">E-Belediye Platformu</h4>
                 </div>
                 <p class="project-desc">
                   Vatandaşların belediye işlemlerini online yapabilecekleri kapsamlı web 
                    platformu. 7/24 erişilebilir hizmet sunmaktadır.
                  </p>
            </div>   
                    <div class="project-card expandable-card" onclick="toggleCard(this)">
                        <div class="project-header">
                            <div class="project-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h4 class="project-title">Veri Analiz Sistemi</h4>
                        </div>
                        <p class="project-desc">
                            Belediye hizmetlerinin performansını ölçen ve karar destek sistemi 
                            olarak kullanılan büyük veri analiz platformu.
                        </p>
                    </div>
                </div>
            </div>
            
            
            
            <!-- Daire Başkanı Yetki ve Sorumlulukları -->
            <div class="responsibilities-section animate-fade-in">
                <h3 class="section-title">
                    <i class="fas fa-user-tie"></i>
                    Daire Başkanın Yetki ve Sorumlulukları
                </h3>
                <div class="responsibilities-grid">
                    <div class="project-card expandable-card" onclick="toggleCard(this)">
                        <div class="responsibility-header">
                            <div class="responsibility-icon">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <div class="responsibility-title">Yönetim ve Koordinasyon</div>
                        </div>
                        <div class="responsibility-desc">
                            Başkan, Genel Sekreter ve Genel Sekreter Yardımcıları tarafından verilen emir ve talimatları 
                            yerine getirmek, müdürlükler arası koordineyi sağlamak.
                        </div>
                    </div>
                    
                    <div class="project-card expandable-card" onclick="toggleCard(this)">
                        <div class="responsibility-header">
                            <div class="responsibility-icon">
                                <i class="fas fa-file-signature"></i>
                            </div>
                            <div class="responsibility-title">Evrak ve Yazışma</div>
                        </div>
                        <div class="responsibility-desc">
                            Daire Başkanlığına gelen evrakları ilgili müdürlüklere havale etmek, takibini yapmak ve 
                            yazışmaları imzalamak.
                        </div>
                    </div>
                    
                    <div class="project-card expandable-card" onclick="toggleCard(this)">
                        <div class="responsibility-header">
                            <div class="responsibility-icon">
                                <i class="fas fa-gavel"></i>
                            </div>
                            <div class="responsibility-title">Disiplin Yetkilisi</div>
                        </div>
                        <div class="responsibility-desc">
                            Disiplin amiri olarak personel hakkında disiplin amirliği görevini yürütmek ve 
                            gerekli işlemleri gerçekleştirmek.
                        </div>
                    </div>
                    
                    <div class="project-card expandable-card" onclick="toggleCard(this)">
                        <div class="responsibility-header">
                            <div class="responsibility-icon">
                                <i class="fas fa-calculator"></i>
                            </div>
                            <div class="responsibility-title">Bütçe Yönetimi</div>
                        </div>
                        <div class="responsibility-desc">
                            Daire Başkanlığının bütçesini hazırlamak, etkin ve verimli kullanmak, 
                            harcama yetkilisi olarak bütçe kontrolünü sağlamak.
                        </div>
                    </div>
                    
                    <div class="project-card expandable-card" onclick="toggleCard(this)">
                        <div class="responsibility-header">
                            <div class="responsibility-icon">
                                <i class="fas fa-handshake"></i>
                            </div>
                            <div class="responsibility-title">İhale ve Sözleşme</div>
                        </div>
                        <div class="responsibility-desc">
                            İhale yetkilisi olarak ihale onay belgesini düzenlemek, ihaleleri onaylamak ve 
                            sözleşmeleri imzalamak.
                        </div>
                    </div>
                    
                    <div class="project-card expandable-card" onclick="toggleCard(this)">
                        <div class="responsibility-header">
                            <div class="responsibility-icon">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                            <div class="responsibility-title">Raporlama</div>
                        </div>
                        <div class="responsibility-desc">
                            Daire Başkanlığı faaliyet raporunu hazırlamak ve Stratejik Plan çalışmalarını 
                            yürütmek.
                        </div>
                    </div>
                    
                    <div class="project-card expandable-card" onclick="toggleCard(this)">
                        <div class="responsibility-header">
                            <div class="responsibility-icon">
                                <i class="fas fa-archive"></i>
                            </div>
                            <div class="responsibility-title">Arşiv Yönetimi</div>
                        </div>
                        <div class="responsibility-desc">
                            Daire Başkanlığı arşivini oluşturmak ve arşiv çalışmalarını organize etmek, 
                            belge yönetimini sağlamak.
                        </div>
                    </div>
                    
                    <div class="project-card expandable-card" onclick="toggleCard(this)">
                        <div class="responsibility-header">
                            <div class="responsibility-icon">
                                <i class="fas fa-lightbulb"></i>
                            </div>
                            <div class="responsibility-title">Proje Geliştirme</div>
                        </div>
                        <div class="responsibility-desc">
                            Faaliyet alanıyla ilgili konularda projeler üretmek, takip etmek ve uygulamaya 
                            geçirmek.
                        </div>
                    </div>
                    
                    <div class="project-card expandable-card" onclick="toggleCard(this)">
                        <div class="responsibility-header">
                            <div class="responsibility-icon">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <div class="responsibility-title">Bilgi Edinme</div>
                        </div>
                        <div class="responsibility-desc">
                            Bilgi edinme başvurularını ilgili müdürlüklere yönlendirmek ve takibini 
                            gerçekleştirmek.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // CV toggle fonksiyonu
        function toggleCV(cvId) {
            const cvSection = document.getElementById(cvId);
            const button = event.target.closest('.cv-toggle-btn');
            
            if (cvSection.classList.contains('active')) {
                cvSection.classList.remove('active');
                button.classList.remove('active');
                button.innerHTML = '<i class="fas fa-user-circle"></i> Detaylı Özgeçmiş';
            } else {
                // Diğer açık CV'leri kapat
                document.querySelectorAll('.cv-section.active').forEach(section => {
                    section.classList.remove('active');
                });
                document.querySelectorAll('.cv-toggle-btn.active').forEach(btn => {
                    btn.classList.remove('active');
                    btn.innerHTML = '<i class="fas fa-user-circle"></i> Detaylı Özgeçmiş';
                });
                
                cvSection.classList.add('active');
                button.classList.add('active');
                button.innerHTML = '<i class="fas fa-eye-slash"></i> Özgeçmişi Gizle';
            }
        }
        
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
            document.querySelectorAll('.responsibility-card, .project-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-4px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
        
        // Responsive menu toggle (if needed for mobile)
        function toggleMobileMenu() {
            const menu = document.querySelector('.mobile-menu');
            if (menu) {
                menu.classList.toggle('active');
            }
        }
    
    
    function toggleCard(card) {
        card.classList.toggle('open');
    }



    </script>
</body>
</html>