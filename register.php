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
    <title>Kayıt Ol</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #1e3c72 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            width: 100%;
            max-width: 400px;
            animation: fadeIn 0.8s ease-out;
            position: relative;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .home-button {
            position: absolute;
            top: 15px;
            left: 15px;
            background: rgba(102, 126, 234, 0.1);
            border: 2px solid rgba(102, 126, 234, 0.3);
            border-radius: 50px;
            padding: 8px 16px;
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .home-button:hover {
            background: rgba(102, 126, 234, 0.2);
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }
        
        .home-button:active {
            transform: translateY(0);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            margin-top: 20px;
        }
        
        .logo {
            margin-bottom: 25px;
        }
        
        .logo-img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
            border: 3px solid white;
        }
        
        .header h1 {
            color: #2c5aa0;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }
        
        .form-group input:hover {
            border-color: #c4d1f0;
        }
        
        .submit-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #1e3c72 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .submit-btn:active {
            transform: translateY(0);
        }
        
        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
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
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e1e5e9;
        }
        
        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .login-link a:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 30px 20px;
                margin: 10px;
            }
            
            .header h1 {
                font-size: 24px;
            }
            
            .home-button {
                padding: 6px 12px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="home-button">
            🏠 Ana Sayfa
        </a>
        
        <div class="header">
            <div class="logo">
                <img src="konya-logo.png" alt="Konya Belediyesi" class="logo-img">
            </div>
            <h1>Konya Büyükşehir Belediyesi</h1>
            <p>Yeni Hesap Oluştur</p>
        </div>
        
        <?php
        if ($message) {
            echo "<div class='message $messageType'>$message</div>";
        }
        ?>
        
        <form method="POST" id="registerForm">
            <div class="form-group">
                <label for="kullanici_adi">👤 Kullanıcı Adı</label>
                <input type="text" 
                       id="kullanici_adi" 
                       name="kullanici_adi" 
                       required 
                       minlength="3"
                       value="<?php echo isset($_POST['kullanici_adi']) ? htmlspecialchars($_POST['kullanici_adi']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="sifre">🔒 Şifre</label>
                <input type="password" 
                       id="sifre" 
                       name="sifre" 
                       required 
                       minlength="4">
            </div>
            
            <div class="form-group">
                <label for="sifre_tekrar">🔒 Şifre Tekrar</label>
                <input type="password" 
                       id="sifre_tekrar" 
                       name="sifre_tekrar" 
                       required 
                       minlength="4">
            </div>
            
            <button type="submit" class="submit-btn">
                 Kayıt Ol
            </button>
        </form>
        
        <div class="login-link">
            <p>Zaten hesabınız var mı? <a href="index.php">🔑 Giriş Yap</a></p>
        </div>
    </div>
    
    <script>
        // Form gönderildiğinde butonu devre dışı bırak
        document.getElementById('registerForm').addEventListener('submit', function() {
            const btn = document.querySelector('.submit-btn');
            btn.innerHTML = '⏳ İşleniyor...';
            btn.disabled = true;
        });
    </script>
</body>
</html>