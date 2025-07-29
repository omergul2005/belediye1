<?php
require_once 'config.php';

// Giriş kontrolü
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['kullanici_id'])) {
    header('Location: index.php');
    exit;
}

$success_message = '';
$error_message = '';

// Admin ise kullanıcı işlemleri
if ($_SESSION['role'] == 'admin') {
    // Kullanıcı silme işlemi
    if (isset($_POST['delete_user'])) {
        $user_id = $_POST['user_id'];
        
        // Kendi hesabını silmeye çalışıyor mu kontrol et
        if ($user_id == $_SESSION['kullanici_id']) {
            $error_message = "Kendi hesabınızı silemezsiniz!";
        } else {
            // Admin kullanıcıların silinmesini engelle
            $check_stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
            $check_stmt->execute([$user_id]);
            $user_role = $check_stmt->fetchColumn();
            
            if ($user_role == 'admin') {
                $error_message = "Admin kullanıcılar silinemez.";
            } else {
                try {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $success_message = "Kullanıcı başarıyla silindi.";
                } catch(PDOException $e) {
                    $error_message = "Kullanıcı silinirken hata oluştu.";
                }
            }
        }
    }
    
    // Kullanıcı durumu güncelleme
    if (isset($_POST['update_status'])) {
        $user_id = $_POST['user_id'];
        $new_status = $_POST['new_status'];
        
        // Admin kullanıcıların durumunu değiştirmeyi engelle
        $check_stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $check_stmt->execute([$user_id]);
        $user_role = $check_stmt->fetchColumn();
        
        if ($user_role == 'admin') {
            $error_message = "Admin kullanıcıların durumu değiştirilemez.";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
                $stmt->execute([$new_status, $user_id]);
                $success_message = "Kullanıcı durumu başarıyla güncellendi.";
            } catch(PDOException $e) {
                $error_message = "Durum güncellenirken hata oluştu.";
            }
        }
    }
    
    // Şifre güncelleme
    if (isset($_POST['update_password'])) {
        $user_id = $_POST['user_id'];
        $new_password = $_POST['new_password'];
        
        // Admin kullanıcıların şifresini değiştirmeyi engelle
        $check_stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $check_stmt->execute([$user_id]);
        $user_role = $check_stmt->fetchColumn();
        
        if ($user_role == 'admin') {
            $error_message = "Admin kullanıcıların şifresi değiştirilemez.";
        } elseif (!empty($new_password)) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$new_password, $user_id]);
                $success_message = "Kullanıcı şifresi başarıyla güncellendi.";
            } catch(PDOException $e) {
                $error_message = "Şifre güncellenirken hata oluştu.";
            }
        } else {
            $error_message = "Şifre boş olamaz.";
        }
    }
    
    // Tüm kullanıcıları getir (şifreler dahil)
    try {
        $stmt = $pdo->prepare("SELECT id, username, password, status, role, created_at FROM users ORDER BY created_at ASC");
        $stmt->execute();
        $users = $stmt->fetchAll();
    } catch(PDOException $e) {
        $error_message = "Kullanıcılar yüklenirken hata oluştu.";
        $users = array();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Ana Panel</title>
    <link rel="icon" type="image/png" href="konya-logo.png">
    <style>
        /* Tamamen sıfırdan tam ekran CSS */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html {
            height: 100%;
            width: 100%;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            height: 100vh;
            width: 100vw;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            overflow-x: hidden;
        }
        
        .dashboard-container {
            width: 100vw;
            height: 100vh;
            background: white;
            display: flex;
            flex-direction: column;
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, #2c5aa0 0%, #1e3c72 100%);
            color: white;
            padding: 20px;
            text-align: center;
            flex-shrink: 0;
        }
        
        .dashboard-header h1 {
            font-size: 24px;
            margin: 10px 0;
        }
        
        .dashboard-header h2 {
            font-size: 16px;
            font-weight: 400;
            opacity: 0.9;
        }
        
        .logo-img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin-bottom: 10px;
        }
        
        .welcome-section {
            flex: 1;
            padding: 30px;
            background: white;
            overflow-y: auto;
        }
        
        .welcome-info {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
        }
        
        .welcome-info h3 {
            color: #2c5aa0;
            font-size: 32px;
            margin-bottom: 20px;
            font-weight: 700;
        }
        
        .welcome-info p {
            color: #666;
            font-size: 18px;
            line-height: 1.6;
        }
        
        .logout-section {
            padding: 20px;
            background: #f8f9fa;
            text-align: center;
            border-top: 1px solid #e9ecef;
            flex-shrink: 0;
        }
        
        .logout-btn {
            background: #dc3545;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block;
        }
        
        .logout-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        /* Borç takip menüsü CSS */
        .borc-takip-menu {
            margin: 30px 0;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .borc-takip-menu h4 {
            color: #2c5aa0;
            font-size: 20px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .menu-card {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            text-decoration: none;
            color: #333;
            border: 2px solid #e1e5e9;
            transition: all 0.3s ease;
            text-align: center;
            display: block;
        }
        
        .menu-card:hover {
            transform: translateY(-5px);
            border-color: #2c5aa0;
            box-shadow: 0 10px 20px rgba(44, 90, 160, 0.1);
            text-decoration: none;
            color: #333;
        }
        
        .menu-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .menu-card h5 {
            color: #2c5aa0;
            margin-bottom: 10px;
            font-size: 18px;
        }
        
        .menu-card p {
            color: #666;
            font-size: 14px;
            line-height: 1.4;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .users-management {
            margin-top: 30px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .users-table th,
        .users-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .users-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-badge.onaylandi {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-badge.onay_bekliyor {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-badge.onaylanmadi {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .role-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .role-badge.admin {
            background-color: #e1ecf4;
            color: #0c5460;
        }
        
        .role-badge.user {
            background-color: #f1f1f1;
            color: #333;
        }
        
        .user-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-small {
            padding: 5px 10px;
            font-size: 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            background-color: #007bff;
            color: white;
        }
        
        .btn-small:hover {
            background-color: #0056b3;
        }
        
        .btn-delete {
            padding: 5px 10px;
            font-size: 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            background-color: #dc3545;
            color: white;
            transition: all 0.3s ease;
        }
        
        .btn-delete:hover {
            background-color: #c82333;
            transform: translateY(-1px);
        }
        
        .btn-delete:active {
            transform: translateY(0);
        }
        
        /* Şifre görüntüleme için CSS */
        .password-container {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .password-hidden,
        .password-visible {
            font-family: monospace;
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            border: 1px solid #dee2e6;
        }
        
        .password-visible code {
            background: none;
            padding: 0;
            color: #e83e8c;
            font-weight: 600;
        }
        
        .btn-eye {
            padding: 2px 6px;
            font-size: 12px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            background-color: #6c757d;
            color: white;
            transition: all 0.2s ease;
        }
        
        .btn-eye:hover {
            background-color: #5a6268;
            transform: scale(1.1);
        }
        
        .btn-eye:active {
            transform: scale(0.95);
        }
        
        select {
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .success-message {
            padding: 12px;
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .error-message {
            padding: 12px;
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        /* Responsive için mobil ayarları */
        @media (max-width: 768px) {
            .welcome-section {
                padding: 20px;
            }
            
            .dashboard-header {
                padding: 15px;
            }
            
            .users-table {
                font-size: 12px;
            }
            
            .users-table th,
            .users-table td {
                padding: 10px 8px;
            }
            
            .user-actions {
                flex-direction: column;
                gap: 5px;
            }
            
            .menu-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .menu-card {
                padding: 20px;
            }
            
            .menu-icon {
                font-size: 36px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <div class="logo">
                <img src="konya-logo.png" alt="<?php echo SITE_NAME; ?>" class="logo-img">
            </div>
            <h1><?php echo SITE_NAME; ?></h1>
            <h2>Yönetim Paneli</h2>
        </div>
        
        <div class="welcome-section">
            <div class="welcome-info">
                <h3>Hoşgeldiniz, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h3>
                <p>
                    <strong>Rol:</strong> <?php echo strtoupper($_SESSION['role']); ?> | 
                    <strong>Giriş Zamanı:</strong> <?php echo date('d.m.Y H:i', $_SESSION['login_time']); ?>
                </p>
            </div>
            
            <!-- Borç Takip Sistemi Menüsü -->
            <div class="borc-takip-menu">
                <h4>💼 Borç Takip Sistemi</h4>
                <div class="menu-grid">
                    <a href="borc_takip.php" class="menu-card">
                        <div class="menu-icon">📊</div>
                        <h5>Firma Borçları</h5>
                        <p>Tüm firmaların borç durumunu görüntüle</p>
                    </a>
                    <a href="borc_takip.php" class="menu-card">
                        <div class="menu-icon">💰</div>
                        <h5>Taksit Takibi</h5>
                        <p>Aylık taksit ödemelerini takip et</p>
                    </a>
                    <a href="borc_takip.php" class="menu-card">
                        <div class="menu-icon">📈</div>
                        <h5>Raporlar</h5>
                        <p>Borç durumu raporlarını incele</p>
                    </a>
                </div>
            </div>

            <?php if ($_SESSION['role'] == 'admin'): ?>
                
                <?php if (!empty($success_message)): ?>
                    <div class="success-message">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <div class="users-management">
                    <h4>👥 Kullanıcı Yönetimi</h4>
                    
                    <?php if (!empty($users)): ?>
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Kullanıcı Adı</th>
                                <th>Şifre</th>
                                <th>Rol</th>
                                <th>Durum</th>
                                <th>Kayıt Tarihi</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $key=>$user): ?>
                            <tr>
                                <td><?php echo $key+1; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                    <?php if ($user['id'] == $_SESSION['kullanici_id']): ?>
                                        <span style="color: #007bff; font-size: 12px;">(Siz)</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="password-container">
                                        <span class="password-hidden" id="password-hidden-<?php echo $user['id']; ?>">
                                            ••••••••
                                        </span>
                                        <span class="password-visible" id="password-visible-<?php echo $user['id']; ?>" style="display: none;">
                                            <code><?php echo htmlspecialchars($user['password']); ?></code>
                                        </span>
                                        <button type="button" class="btn-eye" onclick="togglePassword(<?php echo $user['id']; ?>)" id="eye-btn-<?php echo $user['id']; ?>">
                                            👁️
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <span class="role-badge <?php echo $user['role']; ?>">
                                        <?php echo strtoupper($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $user['status']; ?>">
                                        <?php 
                                        switch($user['status']) {
                                            case 'onaylandi': echo 'Onaylandı'; break;
                                            case 'onay_bekliyor': echo 'Onay Bekliyor'; break;
                                            case 'onaylanmadi': echo 'Onaylanmadı'; break;
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <?php if ($user['role'] == 'admin'): ?>
                                        <span style="color: #666; font-style: italic;">🔒 Korumalı Hesap</span>
                                    <?php else: ?>
                                    <div class="user-actions">
                                        <!-- Durum Güncelleme -->
                                        <form method="POST" style="display: inline-block; margin-right: 10px;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <select name="new_status" onchange="this.form.submit()">
                                                <option value="">Durum Değiştir</option>
                                                <option value="onaylandi" <?php echo ($user['status'] == 'onaylandi') ? 'selected' : ''; ?>>Onayla</option>
                                                <option value="onay_bekliyor" <?php echo ($user['status'] == 'onay_bekliyor') ? 'selected' : ''; ?>>Bekletme</option>
                                                <option value="onaylanmadi" <?php echo ($user['status'] == 'onaylanmadi') ? 'selected' : ''; ?>>Reddet</option>
                                            </select>
                                            <input type="hidden" name="update_status" value="1">
                                        </form>
                                        
                                        <!-- Şifre Güncelleme -->
                                        <button onclick="showPasswordForm(<?php echo $user['id']; ?>)" class="btn-small">
                                            🔑 Şifre
                                        </button>
                                        
                                        <!-- Kullanıcı Silme -->
                                        <?php if ($user['id'] != $_SESSION['kullanici_id']): ?>
                                        <form method="POST" style="display: inline-block; margin-left: 10px;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="delete_user" class="btn-delete" 
                                                    onclick="return confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz!')">
                                                🗑️ Sil
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Şifre güncelleme formu (gizli) -->
                                    <div id="password-form-<?php echo $user['id']; ?>" style="display: none; margin-top: 10px;">
                                        <form method="POST" style="display: inline-block;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="text" name="new_password" placeholder="Yeni şifre" required style="width: 100px; margin-right: 5px;">
                                            <input type="submit" name="update_password" value="Güncelle" class="btn-small">
                                            <button type="button" onclick="hidePasswordForm(<?php echo $user['id']; ?>)" class="btn-small">İptal</button>
                                        </form>
                                    </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: #666;">
                        <h4>👥 Henüz kullanıcı bulunmuyor</h4>
                        <p>Sistem henüz kullanıcı kaydı içermiyor.</p>
                    </div>
                    <?php endif; ?>
                </div>
                
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <div style="font-size: 48px; margin-bottom: 20px;">🏢</div>
                    <h4 style="color: #2c5aa0; margin-bottom: 15px;">Hoş Geldiniz</h4>
                    <p>Sisteme başarıyla giriş yaptınız. Yetkiniz dahilindeki işlemleri gerçekleştirebilirsiniz.</p>
                </div>
            <?php endif; ?>
            
        </div>
        
        <div class="logout-section">
            <a href="logout.php" class="logout-btn">🚪 Güvenli Çıkış</a>
        </div>
    </div>

    <script>
        function showPasswordForm(userId) {
            document.getElementById('password-form-' + userId).style.display = 'block';
        }
        
        function hidePasswordForm(userId) {
            document.getElementById('password-form-' + userId).style.display = 'none';
        }
        
        function togglePassword(userId) {
            const hiddenSpan = document.getElementById('password-hidden-' + userId);
            const visibleSpan = document.getElementById('password-visible-' + userId);
            const eyeBtn = document.getElementById('eye-btn-' + userId);
            
            if (hiddenSpan.style.display === 'none') {
                // Şifreyi gizle
                hiddenSpan.style.display = 'inline';
                visibleSpan.style.display = 'none';
                eyeBtn.innerHTML = '👁️';
                eyeBtn.title = 'Şifreyi Göster';
            } else {
                // Şifreyi göster
                hiddenSpan.style.display = 'none';
                visibleSpan.style.display = 'inline';
                eyeBtn.innerHTML = '🙈';
                eyeBtn.title = 'Şifreyi Gizle';
            }
        }
        
        // Sayfa yüklendiğinde hoş geldin animasyonu
        window.onload = function() {
            document.querySelector('.welcome-section h3').style.animation = 'fadeInUp 0.6s ease-out';
        };
        
        // Otomatik çıkış uyarısı (30 dakika)
        setTimeout(function() {
            if(confirm('Oturumunuz yakında sona erecek. Devam etmek istiyor musunuz?')) {
                location.reload();
            }
        }, 1800000); // 30 dakika
    </script>
</body>
</html>