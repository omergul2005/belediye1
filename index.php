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
            --primary-color: #2c5282;
            --secondary-color: #3182ce;
            --accent-color: #e53e3e;
            --success-color: #38a169;
            --warning-color: #dd6b20;
            --bg-gradient: linear-gradient(135deg, #4facfe 0%, #1243b3ff -50%, #667eea 70%, #764ba2 100%);
            --card-bg: rgba(255, 255, 255, 0.95);
            --text-primary: #2d3748;
            --text-secondary: #4a5568;
            --border-color: #e2e8f0;
            --input-bg: #ffffff;
            --shadow-light: 0 2px 8px rgba(0, 0, 0, 0.08);
            --shadow-medium: 0 8px 24px rgba(0, 0, 0, 0.12);
            --shadow-heavy: 0 16px 48px rgba(0, 0, 0, 0.15);
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

        /* Güzel çizgiler ve desenler */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="beautifulLines" width="50" height="50" patternUnits="userSpaceOnUse"><path d="M0 25 Q12.5 12.5 25 25 T50 25" stroke="%23ffffff" stroke-width="0.5" fill="none" opacity="0.1"/><path d="M25 0 Q37.5 12.5 50 25 T100 25" stroke="%23ffffff" stroke-width="0.3" fill="none" opacity="0.08"/><circle cx="25" cy="25" r="2" fill="%23ffffff" opacity="0.06"/><circle cx="12.5" cy="12.5" r="1" fill="%23ffffff" opacity="0.08"/><circle cx="37.5" cy="37.5" r="1.5" fill="%23ffffff" opacity="0.05"/><path d="M10 10 L15 15 M35 10 L40 15 M10 40 L15 35 M35 40 L40 35" stroke="%23ffffff" stroke-width="0.8" opacity="0.07"/></pattern></defs><rect width="100" height="100" fill="url(%23beautifulLines)"/></svg>');
            pointer-events: none;
        }

        .geometric-elements {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            pointer-events: none;
            z-index: 0;
        }

        .geometric-shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.08);
            animation: floatGeometric 20s infinite ease-in-out;
        }

        .geometric-shape:nth-child(1) {
            width: 150px;
            height: 150px;
            top: 10%;
            left: 8%;
            border-radius: 20px;
            transform: rotate(45deg);
            animation-delay: 0s;
            background: linear-gradient(45deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.03));
        }

        .geometric-shape:nth-child(2) {
            width: 100px;
            height: 100px;
            top: 65%;
            right: 12%;
            border-radius: 50%;
            animation-delay: -8s;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.04));
        }

        .geometric-shape:nth-child(3) {
            width: 120px;
            height: 120px;
            bottom: 20%;
            left: 70%;
            clip-path: polygon(50% 0%, 0% 100%, 100% 100%);
            animation-delay: -15s;
            background: linear-gradient(90deg, rgba(255, 255, 255, 0.07), rgba(255, 255, 255, 0.12));
        }

        .geometric-shape:nth-child(4) {
            width: 80px;
            height: 80px;
            top: 35%;
            right: 25%;
            border-radius: 15px;
            animation-delay: -5s;
            background: linear-gradient(225deg, rgba(255, 255, 255, 0.09), rgba(255, 255, 255, 0.05));
        }

        @keyframes floatGeometric {
            0%, 100% { 
                transform: translateY(0px) translateX(0px) rotate(0deg); 
                opacity: 0.08;
            }
            33% { 
                transform: translateY(-20px) translateX(10px) rotate(120deg); 
                opacity: 0.12;
            }
            66% { 
                transform: translateY(10px) translateX(-15px) rotate(240deg); 
                opacity: 0.06;
            }
        }

        .login-container {
            background: var(--card-bg);
            backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            box-shadow: var(--shadow-heavy);
            padding: 3rem;
            width: 100%;
            max-width: 480px;
            position: relative;
            z-index: 1;
            animation: slideUpFade 0.9s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        @keyframes slideUpFade {
            from {
                opacity: 0;
                transform: translateY(40px) scale(0.95);
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

        .logo-img {
            width: 85px;
            height: 85px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
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
            opacity: 0.85;
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
            animation: fadeInDown 0.6s ease-out;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message.error {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            color: var(--accent-color);
            border: 1px solid #fecaca;
        }

        .message.success {
            background: linear-gradient(135deg, #f0fff4 0%, #dcfce7 100%);
            color: var(--success-color);
            border: 1px solid #bbf7d0;
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
            border: 1px solid var(--border-color);
            border-radius: 12px;
            font-size: 1rem;
            font-family: inherit;
            background: var(--input-bg);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            position: relative;
            outline: none;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(44, 82, 130, 0.08);
            background: #ffffff;
        }

        .form-group input:hover {
            border-color: #c1c9d0;
            background: #ffffff;
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
            transition: all 0.25s ease;
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
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.15), transparent);
            transition: left 0.6s;
        }

        .login-btn:hover::before {
            left: 100%;
        }

        .login-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(44, 82, 130, 0.25);
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
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 18px;
            background: rgba(44, 82, 130, 0.04);
            transition: all 0.25s ease;
            border: 1px solid transparent;
        }

        .link:hover {
            background: rgba(44, 82, 130, 0.08);
            transform: translateY(-1px);
            border-color: rgba(44, 82, 130, 0.15);
        }

        .test-accounts {
            margin-top: 2rem;
            padding: 1.5rem;
            background: rgba(44, 82, 130, 0.02);
            border-radius: 12px;
            border: 1px solid rgba(44, 82, 130, 0.08);
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
            background: rgba(44, 82, 130, 0.08);
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            color: var(--primary-color);
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
                border-radius: 18px;
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
            border-color: var(--primary-color);
            box-shadow: 0 0 0 1px var(--primary-color);
        }

        .login-btn:focus-visible {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
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
        console.log('%cKonya Büyükşehir Belediyesi - Güvenlik Uyarısı', 'color: #2c5282; font-size: 18px; font-weight: bold;');
        console.log('%cBu konsola kod yapıştırmayınız! Hesabınızın güvenliği için önemlidir.', 'color: #e53e3e; font-size: 14px;');
    </script>
</body>
</html>