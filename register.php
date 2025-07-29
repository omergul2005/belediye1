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

        .container {
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

        .back-button {
            position: absolute;
            top: 1.5rem;
            left: 1.5rem;
            background: rgba(45, 90, 135, 0.1);
            border: 2px solid rgba(45, 90, 135, 0.2);
            border-radius: 50px;
            padding: 0.75rem 1.25rem;
            color: var(--secondary-color);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.23, 1, 0.320, 1);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            z-index: 2;
        }

        .back-button:hover {
            background: rgba(45, 90, 135, 0.15);
            border-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(45, 90, 135, 0.2);
        }

        .back-button:active {
            transform: translateY(0);
        }

        .header {
            text-align: center;
            margin-bottom: 2rem;
            margin-top: 1rem;
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

        .header h1 {
            color: var(--text-primary);
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            letter-spacing: -0.025em;
        }

        .header p {
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

        /* Password strength indicator */
        .password-strength {
            margin-top: 0.5rem;
            display: flex;
            gap: 0.25rem;
        }

        .strength-bar {
            height: 3px;
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
            margin-top: 1rem;
        }

        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .submit-btn:hover::before {
            left: 100%;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(45, 90, 135, 0.3);
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
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
        }

        .login-link p {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        .login-link a {
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

        .login-link a:hover {
            background: rgba(45, 90, 135, 0.1);
            transform: translateY(-1px);
            border-color: rgba(45, 90, 135, 0.2);
        }

        .requirements {
            margin-top: 1.5rem;
            padding: 1rem;
            background: rgba(45, 90, 135, 0.03);
            border-radius: 12px;
            border: 1px solid rgba(45, 90, 135, 0.1);
        }

        .requirements h4 {
            color: var(--text-primary);
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .requirements ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .requirements li {
            color: var(--text-secondary);
            font-size: 0.8125rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .requirements li::before {
            content: '•';
            color: var(--secondary-color);
            font-weight: bold;
        }

        /* Loading animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive Design */
        @media (max-width: 640px) {
            .container {
                padding: 2rem 1.5rem;
                margin: 1rem;
                border-radius: 20px;
            }
            
            .header h1 {
                font-size: 1.5rem;
            }
            
            .logo-img {
                width: 70px;
                height: 70px;
            }
            
            .back-button {
                top: 1rem;
                left: 1rem;
                padding: 0.5rem 1rem;
                font-size: 0.8125rem;
            }
            
            .requirements {
                padding: 0.75rem;
            }
        }

        /* Enhanced focus states for accessibility */
        .form-group input:focus-visible {
            outline: 2px solid var(--secondary-color);
            outline-offset: 2px;
        }

        .submit-btn:focus-visible {
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
        console.log('%cDikkat!', 'color: red; font-size: 40px; font-weight: bold;');
        console.log('%cBu bir güvenlik özelliğidir. Bu konsola kod yapıştırmayın!', 'color: red; font-size: 16px;');
    </script>
</body>
</html>