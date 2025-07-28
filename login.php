<?php
session_start();
include "config.php";

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
                echo "Hesabınız henüz onaylanmadı.";
            }
        } else {
            echo "Kullanıcı adı veya şifre yanlış!";
        }
    } catch (PDOException $e) {
        echo "Bir hata oluştu. Lütfen tekrar deneyiniz.";
        error_log("Login hatası: " . $e->getMessage());
    }
}
?>

<form method="POST">
    <input type="text" name="kullanici_adi" placeholder="Kullanıcı Adı" required>
    <input type="password" name="sifre" placeholder="Şifre" required>
    <input type="submit" value="Giriş Yap">
</form>