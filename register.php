<?php
include "config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kullanici_adi = cleanInput($_POST['kullanici_adi']);
    $sifre = $_POST['sifre'];
    $sifre_tekrar = $_POST['sifre_tekrar'];

    if ($sifre !== $sifre_tekrar) {
        echo "Şifreler eşleşmiyor!";
        exit;
    }

    try {
        // Kullanıcı adının zaten var olup olmadığını kontrol et
        $kontrol_sorgu = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $kontrol_sorgu->execute([$kullanici_adi]);
        $kullanici_sayisi = $kontrol_sorgu->fetchColumn();

        if ($kullanici_sayisi > 0) {
            echo "Bu kullanıcı adı zaten kullanımda!";
            exit;
        }

        // Kullanıcıyı onay bekliyor olarak ekle
        $sorgu = $pdo->prepare("INSERT INTO users (username, password, status, role) VALUES (?, ?, 'onay_bekliyor', 'user')");
        $basarili = $sorgu->execute([$kullanici_adi, $sifre]);

        if ($basarili) {
            echo "Kayıt başarılı. Yönetici onayı bekleniyor.";
        } else {
            echo "Bir hata oluştu.";
        }
    } catch (PDOException $e) {
        echo "Bir hata oluştu.";
        error_log("Register hatası: " . $e->getMessage());
    }
}
?>

<form method="POST">
    <label>Kullanıcı Adı:</label><br>
    <input type="text" name="kullanici_adi" required><br>

    <label>Şifre:</label><br>
    <input type="password" name="sifre" required><br>

    <label>Şifre Tekrar:</label><br>
    <input type="password" name="sifre_tekrar" required><br><br>

    <button type="submit">Kayıt Ol</button>
</form>