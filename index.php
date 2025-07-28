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
    <title>Konya Büyükşehir Belediyesi - Giriş</title>
    <link rel="icon" type="image/png" href="konya-logo.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #2c5aa0 0%, #1e3c72 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 50px;
            width: 100%;
            max-width: 450px;
            text-align: center;
            animation: fadeInUp 0.8s ease-out;
        }
        
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
        
        .login-header {
            margin-bottom: 40px;
        }
        
        .logo {
            margin-bottom: 25px;
        }
        
        .logo-img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 10px 25px rgba(44, 90, 160, 0.3);
            border: 3px solid white;
        }
        
        .login-header h1 {
            color: #2c5aa0;
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .login-header h2 {
            color: #666;
            font-size: 16px;
            font-weight: 400;
            line-height: 1.5;
        }
        
        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 14px;
            font-weight: 600;
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .success {
            background: #efe;
            color: #363;
            border: 1px solid #cfc;
        }
        
        .login-form {
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #2c5aa0;
            background: white;
            box-shadow: 0 0 0 3px rgba(44, 90, 160, 0.1);
            transform: translateY(-2px);
        }
        
        .form-group input:hover {
            border-color: #c4d1f0;
        }
        
        .login-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #2c5aa0 0%, #1e3c72 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(44, 90, 160, 0.3);
        }
        
        .login-btn:active {
            transform: translateY(0);
        }
        
        .form-links {
            display: flex;
            justify-content: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #e1e5e9;
        }
        
        .test-accounts {
            margin-top: 25px;
            padding: 20px;
            background: rgba(44, 90, 160, 0.05);
            border-radius: 10px;
            border: 1px solid rgba(44, 90, 160, 0.1);
        }
        
        .test-accounts h3 {
            color: #2c5aa0;
            font-size: 16px;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .account-info p {
            font-size: 13px;
            color: #555;
            margin-bottom: 8px;
            text-align: left;
        }
        
        .account-info code {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            color: #2c5aa0;
            font-weight: 600;
        }
        
        .link {
            color: #2c5aa0;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            padding: 8px 16px;
            border-radius: 20px;
            background: rgba(44, 90, 160, 0.1);
        }
        
        .link:hover {
            color: #1e3c72;
            background: rgba(44, 90, 160, 0.2);
            transform: translateY(-1px);
        }
        
        .footer {
            color: #999;
            font-size: 12px;
            margin-top: 30px;
            line-height: 1.4;
        }
        
            @media (max-width: 480px) {
            .login-container {
                padding: 30px 25px;
                margin: 15px;
            }
            
            .login-header h1 {
                font-size: 24px;
            }
            
            .logo-img {
                width: 80px;
                height: 80px;
            }
            
            .test-accounts {
                padding: 15px;
            }
            
            .account-info p {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <img src="konya-logo.png" alt="Konya Belediyesi" class="logo-img">
            </div>
            <h1>Konya Büyükşehir Belediyesi</h1>
            <h2>Yönetim Paneli<br>Güvenli Giriş</h2>
        </div>
        
        <?php if ($success_message): ?>
            <div class="message success" id="success-message">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="message error">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="login-form" id="loginForm">
            <div class="form-group">
                <label for="kullanici_adi">👤 Kullanıcı Adı</label>
                <input type="text" 
                       id="kullanici_adi" 
                       name="kullanici_adi" 
                       placeholder="Kullanıcı adınızı girin" 
                       required
                       value="<?php echo isset($_POST['kullanici_adi']) ? htmlspecialchars($_POST['kullanici_adi']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="sifre">🔒 Şifre</label>
                <input type="password" 
                       id="sifre" 
                       name="sifre" 
                       placeholder="Şifrenizi girin" 
                       required>
            </div>
            
            <button type="submit" class="login-btn">
                Giriş Yap
            </button>
        </form>
        
        <div class="form-links">
            <a href="register.php" class="link">📝 Kayıt Ol</a>
        </div>
        
        <div class="test-accounts">
            <h3>Test Hesapları</h3>
            <div class="account-info">
                <p><strong>Yönetici:</strong> kullanıcı: <code>admin</code> şifre: <code>2025</code></p>
                <p><strong>User:</strong> kullanıcı: <code>omer</code> şifre: <code>2005</code></p>
            </div>
        </div>
        
        <div class="footer">
            © 2025 Konya Büyükşehir Belediyesi<br>
            Tüm hakları saklıdır.
        </div>
    </div>

    <script>
        // Sayfa yüklendiğinde URL'yi temizle
        if (window.location.search.includes('logout=1')) {
            // 3 saniye sonra mesajı gizle ve URL'yi temizle
            setTimeout(function() {
                const successMsg = document.getElementById('success-message');
                if (successMsg) {
                    successMsg.style.display = 'none';
                }
                // URL'yi temizle
                window.history.replaceState({}, document.title, window.location.pathname);
            }, 3000);
        }
        
        // Form gönderildiğinde logout mesajını gizle
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function() {
                    const successMsg = document.getElementById('success-message');
                    if (successMsg) {
                        successMsg.style.display = 'none';
                    }
                    
                    // Butonu loading yap
                    const btn = document.querySelector('.login-btn');
                    btn.innerHTML = '⏳ Giriş yapılıyor...';
                    btn.disabled = true;
                });
            }
        });
    </script>
</body>
</html>