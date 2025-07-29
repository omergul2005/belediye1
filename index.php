<?php
session_start();
include "config.php";

$error_message = '';
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kullanici_adi = $_POST["kullanici_adi"];
    $sifre = $_POST["sifre"];

    try {
        $sorgu = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $sorgu->execute([$kullanici_adi]);
        $kullanici = $sorgu->fetch(PDO::FETCH_ASSOC);

        if ($kullanici && $sifre == $kullanici["password"]) {
            if ($kullanici["status"] == "onaylandi") {
                // Giriş başarılı - session değişkenlerini ayarla
                $_SESSION["kullanici_id"] = $kullanici["id"];
                $_SESSION["user_id"] = $kullanici["id"]; // Ek uyumluluk için
                $_SESSION["username"] = $kullanici["username"];
                $_SESSION["role"] = $kullanici["role"];
                $_SESSION["login_time"] = time();
                
                // Debug için
                error_log("Giriş başarılı: " . $kullanici["username"] . " - Role: " . $kullanici["role"]);
                
                // Kullanıcı rolüne göre yönlendir
                header("Location: dashboard.php");
                exit();
            } else {
                $error_message = "Hesabınız henüz onaylanmadı.";
            }
        } else {
            $error_message = "Hatalı kullanıcı adı veya şifre!";
        }
    } catch (PDOException $e) {
        $error_message = "Bir hata oluştu. Lütfen tekrar deneyiniz.";
        error_log("Login hatası: " . $e->getMessage());
    }
} else {
    // Sadece GET isteğinde ve logout parametresi varsa mesaj göster
    if (isset($_GET['logout']) && $_GET['logout'] == '1') {
        $success_message = "Başarıyla çıkış yaptınız.";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konya Büyükşehir Belediyesi - Yönetim Paneli</title>
    <link rel="icon" type="image/png" href="konya-logo.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #1a365d;
            --secondary-color: #2d5a87;
            --accent-color: #e53e3e;
            --success-color: #38a169;
            --warning-color: #dd6b20;
            --bg-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --card-bg: rgba(255, 255, 255, 0.95);
            --text-primary: #2d3748;
            --text-secondary: #4a5568;
            --border-color: #e2e8f0;
            --shadow-light: 0 4px 6px rgba(0, 0, 0, 0.07);
            --shadow-medium: 0 10px 25px rgba(0, 0, 0, 0.15);
            --shadow-heavy: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated Background Elements */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="8" height="8" patternUnits="userSpaceOnUse"><path d="M 8 0 L 0 0 0 8" fill="none" stroke="%23ffffff" stroke-width="0.5" opacity="0.08"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            pointer-events: none;
        }

        .floating-elements {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            pointer-events: none;
            z-index: 0;
        }

        .floating-element {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: floatAnimation 15s infinite ease-in-out;
        }

        .floating-element:nth-child(1) {
            width: 100px;
            height: 100px;
            top: 10%;
            left: 15%;
            animation-delay: 0s;
        }

        .floating-element:nth-child(2) {
            width: 150px;
            height: 150px;
            top: 70%;
            right: 10%;
            animation-delay: -5s;
        }

        .floating-element:nth-child(3) {
            width: 80px;
            height: 80px;
            bottom: 20%;
            left: 70%;
            animation-delay: -10s;
        }

        .floating-element:nth-child(4) {
            width: 60px;
            height: 60px;
            top: 40%;
            right: 20%;
            animation-delay: -7s;
        }

        @keyframes floatAnimation {
            0%, 100% { 
                transform: translateY(0px) translateX(0px) rotate(0deg); 
                opacity: 0.1;
            }
            25% { 
                transform: translateY(-30px) translateX(15px) rotate(90deg); 
                opacity: 0.2;
            }
            50% { 
                transform: translateY(-20px) translateX(-10px) rotate(180deg); 
                opacity: 0.15;
            }
            75% { 
                transform: translateY(10px) translateX(20px) rotate(270deg); 
                opacity: 0.25;
            }
        }

        .login-container {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 24px;
            box-shadow: var(--shadow-heavy);
            padding: 3rem;
            width: 100%;
            max-width: 500px;
            position: relative;
            z-index: 1;
            animation: slideUpScale 0.8s cubic-bezier(0.23, 1, 0.320, 1);
        }

        @keyframes slideUpScale {
            from {
                opacity: 0;
                transform: translateY(60px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .logo-container {
            position: relative;
            display: inline-block;
            margin-bottom: 1.5rem;
        }

        .logo-container::before {
            content: '';
            position: absolute;
            top: -12px;
            left: -12px;
            right: -12px;
            bottom: -12px;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            opacity: 0.1;
            animation: pulseLogo 3s infinite;
        }

        @keyframes pulseLogo {
            0%, 100% { transform: scale(1); opacity: 0.1; }
            50% { transform: scale(1.05); opacity: 0.2; }
        }

        .logo-img {
            width: 85px;
            height: 85px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: var(--shadow-medium);
            position: relative;
            z-index: 1;
        }

        .login-header h1 {
            color: var(--text-primary);
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            letter-spacing: -0.025em;
        }

        .login-header h2 {
            color: var(--text-secondary);
            font-size: 1rem;
            font-weight: 500;
            opacity: 0.8;
        }

        .message {
            padding: 1rem 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideInLeft 0.5s ease-out;
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .message.error {
            background: linear-gradient(135deg, #fed7d7 0%, #feb2b2 100%);
            color: var(--accent-color);
            border: 1px solid #fbb6ce;
        }

        .message.success {
            background: linear-gradient(135deg, #c6f6d5 0%, #9ae6b4 100%);
            color: var(--success-color);
            border: 1px solid #9ae6b4;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            font-weight: 600;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-group input {
            width: 100%;
            padding: 1rem 1.25rem;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 1rem;
            font-family: inherit;
            background: white;
            transition: all 0.3s cubic-bezier(0.23, 1, 0.320, 1);
            position: relative;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(45, 90, 135, 0.1);
            transform: translateY(-2px);
        }

        .form-group input:hover {
            border-color: #cbd5e0;
            transform: translateY(-1px);
        }

        .login-btn {
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
            transition: all 0.3s cubic-bezier(0.23, 1, 0.320, 1);
            position: relative;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .login-btn:hover::before {
            left: 100%;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(45, 90, 135, 0.3);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .login-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .form-links {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }

        .link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--secondary-color);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 20px;
            background: rgba(45, 90, 135, 0.05);
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }

        .link:hover {
            background: rgba(45, 90, 135, 0.1);
            transform: translateY(-1px);
            border-color: rgba(45, 90, 135, 0.2);
        }

        .test-accounts {
            margin-top: 2rem;
            padding: 1.5rem;
            background: rgba(45, 90, 135, 0.03);
            border-radius: 12px;
            border: 1px solid rgba(45, 90, 135, 0.1);
        }

        .test-accounts h3 {
            color: var(--text-primary);
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .account-info {
            display: grid;
            gap: 0.75rem;
        }

        .account-info p {
            font-size: 0.8125rem;
            color: var(--text-secondary);
            padding: 0.75rem;
            background: white;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .account-info code {
            background: rgba(45, 90, 135, 0.1);
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            color: var(--secondary-color);
            font-weight: 600;
            font-size: 0.75rem;
        }

        .footer {
            text-align: center;
            color: var(--text-secondary);
            font-size: 0.75rem;
            margin-top: 2rem;
            opacity: 0.7;
            line-height: 1.5;
        }

        /* Loading animation */
        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive Design */
        @media (max-width: 640px) {
            .login-container {
                padding: 2rem 1.5rem;
                margin: 1rem;
                border-radius: 20px;
            }
            
            .login-header h1 {
                font-size: 1.5rem;
            }
            
            .logo-img {
                width: 70px;
                height: 70px;
            }
            
            .test-accounts {
                padding: 1rem;
            }
            
            .account-info p {
                font-size: 0.75rem;
                flex-direction: column;
                gap: 0.5rem;
                align-items: flex-start;
            }
        }

        /* Enhanced accessibility */
        .form-group input:focus-visible {
            outline: 2px solid var(--secondary-color);
            outline-offset: 2px;
        }

        .login-btn:focus-visible {
            outline: 2px solid var(--secondary-color);
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    <div class="floating-elements">
        <div class="floating-element"></div>
        <div class="floating-element"></div>
        <div class="floating-element"></div>
        <div class="floating-element"></div>
    </div>

    <div class="login-container">
        <div class="login-header">
            <div class="logo-container">
                <img src="konya-logo.png" alt="Konya Büyükşehir Belediyesi" class="logo-img">
            </div>
            <h1>Konya Büyükşehir Belediyesi</h1>
            <h2>Yönetim Paneli Giriş</h2>
        </div>
        
        <?php if ($success_message): ?>
            <div class="message success" id="success-message">
                <i class="fas fa-check-circle"></i>
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="login-form" id="loginForm">
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
                       autocomplete="current-password"
                       required>
            </div>
            
            <button type="submit" class="login-btn" id="loginButton">
                <span id="buttonText">
                    <i class="fas fa-sign-in-alt"></i>
                    Giriş Yap
                </span>
            </button>
        </form>
        
        <div class="form-links">
            <a href="register.php" class="link">
                <i class="fas fa-user-plus"></i>
                Yeni Hesap Oluştur
            </a>
        </div>
        
        <div class="test-accounts">
            <h3>
                <i class="fas fa-key"></i>
                Test Hesapları
            </h3>
            <div class="account-info">
                <p>
                    <span><strong>Yönetici Girişi:</strong></span>
                    <span>kullanıcı: <code>admin</code> şifre: <code>2025</code></span>
                </p>
                <p>
                    <span><strong>Kullanıcı Girişi:</strong></span>
                    <span>kullanıcı: <code>omer</code> şifre: <code>2005</code></span>
                </p>
            </div>
        </div>
        
        <div class="footer">
            <i class="fas fa-shield-alt"></i>
            © 2025 Konya Büyükşehir Belediyesi<br>
            Tüm hakları saklıdır. | Güvenli Giriş Sistemi
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Logout mesajı otomatik gizleme
            if (window.location.search.includes('logout=1')) {
                setTimeout(function() {
                    const successMsg = document.getElementById('success-message');
                    if (successMsg) {
                        successMsg.style.opacity = '0';
                        setTimeout(() => {
                            successMsg.style.display = 'none';
                        }, 300);
                    }
                    window.history.replaceState({}, document.title, window.location.pathname);
                }, 3000);
            }
            
            // Form gönderme işlemi
            const form = document.getElementById('loginForm');
            const loginButton = document.getElementById('loginButton');
            const buttonText = document.getElementById('buttonText');
            
            if (form && loginButton) {
                form.addEventListener('submit', function(e) {
                    loginButton.disabled = true;
                    buttonText.innerHTML = '<span class="loading"></span> Giriş yapılıyor...';
                });
            }

            // Otomatik odaklanma
            const usernameInput = document.getElementById('kullanici_adi');
            if (usernameInput && !usernameInput.value) {
                usernameInput.focus();
            }

            // Enter tuşu desteği
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
        });

        // Güvenlik bildirimi
        console.log('%cKonya Büyükşehir Belediyesi - Güvenlik Uyarısı', 'color: #1a365d; font-size: 18px; font-weight: bold;');
        console.log('%cBu konsola kod yapıştırmayınız! Hesabınızın güvenliği için önemlidir.', 'color: #e53e3e; font-size: 14px;');
    </script>
</body>
</html>