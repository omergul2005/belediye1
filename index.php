<?php
require_once 'config.php';

// Zaten giriş yapmışsa dashboard'a yönlendir
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$info = '';

// Timeout mesajı
if (isset($_GET['timeout'])) {
    $info = 'Oturumunuz zaman aşımına uğradı. Lütfen tekrar giriş yapın.';
}

// Çıkış mesajı
if (isset($_GET['logout'])) {
    $info = 'Başarıyla çıkış yaptınız.';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = cleanInput($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Kullanıcı adı ve şifre gereklidir.";
    } else {
        try {
            // Özel kullanıcılar için basit kontrol
            if (($username == 'admin' && ($password == '12345' || $password == '2025')) ||
                ($username == 'yonetici' && $password == 'panel')) {
                // Session oluştur
                $_SESSION['user_id'] = ($username == 'admin') ? 1 : 2;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = ($username == 'admin') ? 'admin' : 'yonetici';
                $_SESSION['full_name'] = ($username == 'admin') ? 'Sistem Yöneticisi' : 'Panel Yöneticisi';
                $_SESSION['login_time'] = time();
                $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];

                // Dashboard'a yönlendir
                header('Location: dashboard.php');
                exit();
            }

            // Yeni veritabanı kontrolü - users tablosundan
            $sorgu = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $sorgu->execute([$username]);
            $kullanici = $sorgu->fetch(PDO::FETCH_ASSOC);

            if ($kullanici && $password == $kullanici["password"]) {
                if ($kullanici["status"] == "onaylandi") {
                    // Giriş başarılı - Session oluştur
                    $_SESSION["kullanici_id"] = $kullanici["id"];
                    $_SESSION["user_id"] = $kullanici["id"];
                    $_SESSION["username"] = $kullanici["username"];
                    $_SESSION["role"] = $kullanici["role"];
                    $_SESSION['login_time'] = time();
                    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
                    
                    // Dashboard'a yönlendir
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error = "Hesabınız henüz onaylanmadı.";
                }
            } else {
                $error = "Hatalı kullanıcı adı veya şifre!";
            }

        } catch(PDOException $e) {
            $error = "Hatalı kullanıcı adı veya şifre!";
            error_log("Login hatası: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Giriş</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="konya-logo.png">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <img src="konya-logo.png" alt="<?php echo SITE_NAME; ?>" class="logo-img">
            </div>
            <h1><?php echo SITE_NAME; ?></h1>
            <h2>Yönetici Paneli Girişi</h2>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($info)): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($info); ?>
            </div>
        <?php endif; ?>

        <div class="login-form">
            <form method="POST" autocomplete="on">
                <div class="form-group">
                    <label for="username">Kullanıcı Adı:</label>
                    <input type="text" id="username" name="username" required autocomplete="username" 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                           placeholder="Kullanıcı adınızı girin">
                </div>

                <div class="form-group">
                    <label for="password">Şifre:</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password"
                           placeholder="Şifrenizi girin">
                </div>

                <button type="submit" class="login-btn">Güvenli Giriş</button>
            </form>

            <div class="form-links">
                <a href="register.php" class="link">Hesap Oluştur</a>
            </div>

            <!-- Demo Bilgileri -->
            <div style="margin-top: 20px; padding: 15px; background: #e9ecef; border-radius: 8px; font-size: 12px; color: #666;">
                <strong>Demo Giriş Bilgileri:</strong><br>
                Admin: <code>admin</code> = <code>12345</code> veya <code>2025</code><br>
                User: <code>omer</code> = <code>2005</code>
            </div>
        </div>

        <div class="footer">
            <p>&copy; <?php echo date('Y') . ' ' . SITE_NAME; ?> - Tüm Hakları Saklıdır</p>
        </div>
    </div>
</body>
</html>