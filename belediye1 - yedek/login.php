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
                // Giriş başarılı
                $_SESSION["kullanici_id"] = $kullanici["id"];
                $_SESSION["username"] = $kullanici["username"];
                $_SESSION["role"] = $kullanici["role"];
                
                // Kullanıcı rolüne göre yönlendir
                if ($kullanici["role"] == "admin") {
                    header("Location: dashboard.php");
                } else {
                    header("Location: dashboard.php");
                }
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
    <title>Giriş Yap</title>
</head>
<body>
    <?php if ($success_message): ?>
        <div id="success-message" style="color: green; background: #d4edda; padding: 10px; margin: 10px 0; border-radius: 5px;">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div style="color: red; background: #f8d7da; padding: 10px; margin: 10px 0; border-radius: 5px;">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="kullanici_adi" placeholder="Kullanıcı Adı" required>
        <input type="password" name="sifre" placeholder="Şifre" required>
        <input type="submit" value="Giriş Yap">
    </form>

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
                });
            }
        });
    </script>
</body>
</html>