<?php
// PHP kodlarını en başa taşıyoruz
include "config.php";

$message = '';
$messageType = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kullanici_adi = cleanInput($_POST['kullanici_adi']);
    $sifre = $_POST['sifre'];
    $sifre_tekrar = $_POST['sifre_tekrar'];
    
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
            // Kullanıcı adının zaten var olup olmadığını kontrol et
            $kontrol_sorgu = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $kontrol_sorgu->execute([$kullanici_adi]);
            $kullanici_sayisi = $kontrol_sorgu->fetchColumn();
            
            if ($kullanici_sayisi > 0) {
                $message = "Bu kullanıcı adı zaten kullanımda!";
                $messageType = 'error';
            } else {
                // Şifreyi düz metin olarak kaydet (hash'leme)
                
                // Kullanıcıyı onay bekliyor olarak ekle
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
            $message = "Bir hata oluştu, lütfen tekrar deneyin.";
            $messageType = 'error';
            error_log("Register hatası: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konya Büyükşehir Belediyesi - Kayıt Ol</title>
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
            padding: 10px;
            position: relative;
            overflow: hidden;
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
            width: 120px;
            height: 120px;
            top: 10%;
            left: 8%;
            border-radius: 15px;
            transform: rotate(45deg);
            animation-delay: 0s;
            background: linear-gradient(45deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.03));
        }

        .geometric-shape:nth-child(2) {
            width: 80px;
            height: 80px;
            top: 65%;
            right: 12%;
            border-radius: 50%;
            animation-delay: -8s;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.04));
        }

        .geometric-shape:nth-child(3) {
            width: 100px;
            height: 100px;
            bottom: 20%;
            left: 70%;
            clip-path: polygon(50% 0%, 0% 100%, 100% 100%);
            animation-delay: -15s;
            background: linear-gradient(90deg, rgba(255, 255, 255, 0.07), rgba(255, 255, 255, 0.12));
        }

        .geometric-shape:nth-child(4) {
            width: 60px;
            height: 60px;
            top: 35%;
            right: 25%;
            border-radius: 10px;
            animation-delay: -5s;
            background: linear-gradient(225deg, rgba(255, 255, 255, 0.09), rgba(255, 255, 255, 0.05));
        }

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

        .container {
            background: var(--card-bg);
            backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 18px;
            box-shadow: var(--shadow-heavy);
            padding: 2rem;
            width: 100%;
            max-width: 460px;
            position: relative;
            z-index: 1;
            animation: slideUpFade 0.9s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            margin: auto;
            max-height: 95vh;
            overflow-y: auto;
        }

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

        .back-button {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: rgba(44, 82, 130, 0.08);
            border: 1px solid rgba(44, 82, 130, 0.15);
            border-radius: 50px;
            padding: 0.6rem 1rem;
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 600;
            transition: all 0.25s ease;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            z-index: 2;
        }

        .back-button:hover {
            background: rgba(44, 82, 130, 0.12);
            border-color: var(--primary-color);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(44, 82, 130, 0.15);
        }

        .header {
            text-align: center;
            margin-bottom: 1.5rem;
            margin-top: 0.8rem;
        }

        .logo-container {
            position: relative;
            display: inline-block;
            margin-bottom: 1rem;
        }

        .logo-img {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: var(--shadow-medium);
            position: relative;
            z-index: 1;
        }

        .header h1 {
            color: var(--text-primary);
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.3rem;
            letter-spacing: -0.025em;
            line-height: 1.2;
        }

        .header p {
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 500;
            opacity: 0.85;
        }

        .message {
            padding: 0.8rem 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            font-size: 0.8rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            animation: fadeInDown 0.6s ease-out;
        }

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
            margin-bottom: 1.2rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.4rem;
            color: var(--text-primary);
            font-weight: 600;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.9rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            font-size: 0.9rem;
            font-family: inherit;
            background: var(--input-bg);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            position: relative;
            outline: none;
        }

        .form-group input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(44, 82, 130, 0.08);
            background: #ffffff;
        }

        .form-group input:hover {
            border-color: #cbd5e0;
            background: #ffffff;
        }

        /* Password strength indicator */
        .password-strength {
            margin-top: 0.4rem;
            display: flex;
            gap: 0.2rem;
        }

        .strength-bar {
            height: 2.5px;
            background: #e2e8f0;
            border-radius: 2px;
            flex: 1;
            transition: all 0.3s ease;
        }

        .strength-bar.active {
            background: var(--success-color);
        }

        .strength-bar.medium {
            background: var(--warning-color);
        }

        .submit-btn {
            width: 100%;
            padding: 0.9rem 1.2rem;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.25s ease;
            position: relative;
            overflow: hidden;
            margin-top: 0.8rem;
        }

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

        .submit-btn:hover::before {
            left: 100%;
        }

        .submit-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(44, 82, 130, 0.25);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .login-link {
            text-align: center;
            margin-top: 1.2rem;
            padding-top: 1.2rem;
            border-top: 1px solid var(--border-color);
        }

        .login-link p {
            color: var(--text-secondary);
            font-size: 0.8rem;
            margin-bottom: 0.8rem;
        }

        .login-link a {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 0.6rem 1.2rem;
            border-radius: 15px;
            background: rgba(44, 82, 130, 0.04);
            transition: all 0.25s ease;
            border: 1px solid transparent;
        }

        .login-link a:hover {
            background: rgba(44, 82, 130, 0.08);
            transform: translateY(-1px);
            border-color: rgba(44, 82, 130, 0.15);
        }

        .requirements {
            margin-top: 1rem;
            padding: 1rem;
            background: rgba(44, 82, 130, 0.02);
            border-radius: 10px;
            border: 1px solid rgba(44, 82, 130, 0.08);
        }

        .requirements h4 {
            color: var(--text-primary);
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 0.6rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .requirements ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .requirements li {
            color: var(--text-secondary);
            font-size: 0.75rem;
            margin-bottom: 0.4rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            line-height: 1.3;
        }

        .requirements li::before {
            content: '•';
            color: var(--primary-color);
            font-weight: bold;
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

        /* Laptop ve küçük ekranlar için özel responsive ayarlar */
        @media (max-height: 800px) {
            body {
                padding: 8px;
            }
            
            .container {
                padding: 1.2rem;
                max-width: 400px;
            }
            
            .logo-img {
                width: 50px;
                height: 50px;
            }
            
            .header h1 {
                font-size: 1.1rem;
            }
            
            .header p {
                font-size: 0.75rem;
            }
            
            .form-group {
                margin-bottom: 0.7rem;
            }
            
            .requirements {
                margin-top: 0.5rem;
                padding: 0.6rem;
            }
            
            .requirements li {
                font-size: 0.65rem;
                margin-bottom: 0.15rem;
            }
            
            .login-link {
                margin-top: 0.6rem;
                padding-top: 0.6rem;
            }
        }

        /* Çok küçük laptop ekranları için (13-14 inch) */
        @media (max-height: 700px) {
            body {
                padding: 5px;
            }
            
            .container {
                padding: 1rem;
                max-width: 380px;
            }
            
            .header {
                margin-bottom: 0.8rem;
                margin-top: 0.3rem;
            }
            
            .logo-container {
                margin-bottom: 0.4rem;
            }
            
            .logo-img {
                width: 45px;
                height: 45px;
            }
            
            .header h1 {
                font-size: 1rem;
                margin-bottom: 0.1rem;
            }
            
            .form-group {
                margin-bottom: 0.6rem;
            }
            
            .form-group input {
                padding: 0.6rem 0.7rem;
                font-size: 0.8rem;
            }
            
            .submit-btn {
                padding: 0.6rem 0.8rem;
                font-size: 0.8rem;
            }
            
            .requirements {
                margin-top: 0.4rem;
                padding: 0.5rem;
            }
            
            .requirements h4 {
                font-size: 0.7rem;
                margin-bottom: 0.3rem;
            }
            
            .requirements li {
                font-size: 0.6rem;
                margin-bottom: 0.1rem;
            }
            
            .login-link {
                margin-top: 0.5rem;
                padding-top: 0.5rem;
            }
            
            .login-link p {
                font-size: 0.7rem;
                margin-bottom: 0.4rem;
            }
        }

        /* Tablet ve mobil cihazlar */
        @media (max-width: 640px) {
            .container {
                padding: 1.5rem;
                margin: 0.5rem;
                border-radius: 15px;
                max-width: calc(100vw - 1rem);
            }
            
            .header h1 {
                font-size: 1.3rem;
            }
            
            .logo-img {
                width: 60px;
                height: 60px;
            }
            
            .back-button {
                top: 0.8rem;
                left: 0.8rem;
                padding: 0.5rem 0.8rem;
                font-size: 0.75rem;
            }
        }

        /* Çok küçük mobil ekranlar */
        @media (max-width: 480px) and (max-height: 700px) {
            body {
                padding: 2px;
                padding-top: 10px;
            }
            
            .container {
                padding: 1rem;
                margin: 0.25rem;
                max-height: calc(100vh - 20px);
            }
            
            .geometric-elements {
                display: none;
            }
            
            .requirements {
                padding: 0.8rem;
            }
            
            .requirements li {
                font-size: 0.7rem;
            }
        }

        /* Enhanced focus states for accessibility */
        .form-group input:focus-visible {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 1px var(--primary-color);
        }

        .submit-btn:focus-visible {
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
        });
        
        // Security: Console warning
        console.log('%cKonya Büyükşehir Belediyesi - Güvenlik Uyarısı', 'color: #2c5282; font-size: 18px; font-weight: bold;');
        console.log('%cBu konsola kod yapıştırmayınız! Hesabınızın güvenliği için önemlidir.', 'color: #e53e3e; font-size: 14px;');
    </script>
</body>
</html>