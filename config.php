<?php
// config.php - Konya Büyükşehir Belediyesi Veritabanı Bağlantı Ayarları

// Veritabanı bağlantı bilgileri
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "konya_belediye";

// Hata raporlama
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Zaman dilimi ayarı
date_default_timezone_set('Europe/Istanbul');

try {
    // PDO bağlantısı oluştur
    $pdo = new PDO(
        "mysql:host=$servername;dbname=$dbname;charset=utf8mb4", 
        $username, 
        $password,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        )
    );

    // Geliştirme sırasında bağlantı başarılı mesajı
    // echo "Veritabanı bağlantısı başarılı!";

} catch(PDOException $e) {
    // Bağlantı hatası durumunda
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}

// Güvenlik fonksiyonları
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);                
    $data = htmlspecialchars($data);
    return $data;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit();
    }
}

function checkUserRole($required_role) {
    if (!isLoggedIn()) {
        return false;
    }

    $user_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

    if ($required_role === 'admin' && $user_role === 'admin') {
        return true;
    } elseif ($required_role === 'yonetici' && in_array($user_role, array('admin', 'yonetici'))) {
        return true;
    } elseif ($required_role === 'muhasebe' && in_array($user_role, array('admin', 'yonetici', 'muhasebe'))) {
        return true;
    }

    return false;
}

// Sistem sabitleri
define('SITE_NAME', 'Konya Büyükşehir Belediyesi');
define('ADMIN_EMAIL', 'admin@konya.bel.tr');
define('SESSION_TIMEOUT', 3600); // 1 saat

// Session ayarları
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Session timeout kontrolü
if (isLoggedIn() && isset($_SESSION['login_time'])) {
    if (time() - $_SESSION['login_time'] > SESSION_TIMEOUT) {
        session_destroy();
        header('Location: index.php?timeout=1');
        exit();
    }
}
