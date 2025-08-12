<?php
/**
 * Belediye Borç Takip Sistemi - Ana Panel
 * Bu dosya sistemin ana kontrol panelini içerir.
 * Kullanıcı yönetimi ve borç takip işlemleri buradan yapılır.
 */

require_once 'config.php';

// Oturum ve güvenlik kontrolleri
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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: #f5f5f5;
            line-height: 1.6;
            color: #333;
        }

        /* Header */
        .header {
            background: #2c3e50;
            color: white;
            padding: 20px 0;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 225px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .logo {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            margin-bottom: 10px;
        }

        .logo-img {
            width: 180px;
            height: 120px;
            border: 3px solid white;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 16px;
            opacity: 0.9;
        }

        /* Main Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        /* Welcome Card */
        .welcome-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            text-align: center;
        }

        .welcome-card h2 {
            color: #2c3e50;
            font-size: 32px;
            margin-bottom: 15px;
        }

        .welcome-card p {
            font-size: 18px;
            color: #666;
            margin-bottom: 20px;
        }

        .user-info {
            background: #ecf0f1;
            padding: 15px;
            border-radius: 8px;
            display: inline-block;
        }

        .user-info strong {
            color: #2c3e50;
        }

        /* Messages */
        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Menu Grid */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .menu-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            text-decoration: none;
            color: #333;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border-left: 5px solid #3498db;
        }

        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            text-decoration: none;
            color: #333;
        }

        .menu-icon {
            font-size: 60px;
            text-align: center;
            margin-bottom: 20px;
        }

        .menu-card h3 {
            font-size: 22px;
            margin-bottom: 15px;
            color: #2c3e50;
            text-align: center;
        }

        .menu-card p {
            color: #666;
            text-align: center;
            line-height: 1.6;
        }

        /* Admin Panel */
        .admin-panel {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .admin-header {
            background: #34495e;
            color: white;
            padding: 20px;
            font-size: 24px;
            font-weight: 600;
        }

        .table-container {
            padding: 20px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }

        tr:hover {
            background: #f8f9fa;
        }

        /* Badges */
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-admin {
            background: #d1ecf1;
            color: #0c5460;
        }

        .badge-user {
            background: #e2e3e5;
            color: #495057;
        }

        /* Buttons */
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin: 2px;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
        }

        .btn-secondary:hover {
            background: #7f8c8d;
        }

        .btn-small {
            padding: 5px 10px;
            font-size: 12px;
        }

        /* Password Display */
        .password-container {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .password-display {
            background: #f8f9fa;
            padding: 4px 8px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 14px;
            border: 1px solid #dee2e6;
        }

        .password-hidden {
            color: #666;
        }

        .password-visible {
            color: #e74c3c;
            font-weight: bold;
        }

        /* Forms */
        .password-form {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .password-form input {
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        select {
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
            cursor: pointer;
        }

        /* User Actions */
        .user-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .protected-text {
            color: #666;
            font-style: italic;
            font-size: 14px;
        }

        /* Logout Section */
        .logout-section {
            text-align: center;
            margin-top: 40px;
        }

        .logout-btn {
            background: #e74c3c;
            color: white;
            padding: 15px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 18px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .logout-btn:hover {
            background: #c0392b;
            transform: translateY(-2px);
            text-decoration: none;
            color: white;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 20px 15px;
            }

            .menu-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .welcome-card h2 {
                font-size: 24px;
            }

            .header h1 {
                font-size: 22px;
            }

            .logo {
                flex-direction: column;
            }

            .logo-img {
                width: 60px;
                height: 60px;
            }

            table {
                font-size: 14px;
            }

            th, td {
                padding: 8px 6px;
            }

            .user-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .password-form {
                flex-direction: column;
                align-items: stretch;
            }
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state h4 {
            font-size: 20px;
            margin-bottom: 10px;
            color: #2c3e50;
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div class="logo">
                <img src="konya-logo1.png" alt="Konya Büyükşehir Belediyesi" class="logo-img">
            </div>
        </div>
    </div>

    <!-- Main Container -->
    <div class="container">
        <!-- Welcome Card -->
        <div class="welcome-card fade-in">
            <h2>Hoşgeldiniz!</h2>
            <p>Sisteme başarıyla giriş yaptınız. Aşağıdaki menülerden istediğiniz işlemi seçebilirsiniz.</p>
            <div class="user-info">
                <strong>Kullanıcı:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?> | 
                <strong>Rol:</strong> <?php echo strtoupper($_SESSION['role']); ?> | 
                <strong>Giriş:</strong> <?php echo date('d.m.Y H:i', $_SESSION['login_time']); ?>
            </div>
        </div>

        

        <!-- Main Menu -->
        <div class="menu-grid">
            <a href="borc_takip.php" class="menu-card fade-in">
                <div class="menu-icon">📊</div>
                <h3>Borç Takip Sistemi</h3>
                <p>Tüm firmaların borç durumunu görüntüleyin, takip edin ve raporlayın</p>
            </a>
            
            <a href="hakkimizda.php" class="menu-card fade-in">
                <div class="menu-icon">👥</div>
                <h3>Hakkımızda</h3>
                <p>Konya Büyükşehir Belediyesi ve ekibimiz hakkında detaylı bilgi</p>
            </a>
            
            <a href="iletisim.php" class="menu-card fade-in">
                <div class="menu-icon">📞</div>
                <h3>İletişim</h3>
                <p>Bizimle iletişime geçmek için iletişim bilgilerimizi görüntüleyebilirsiniz</p>
            </a>
        </div>
        <!-- Messages -->
        <?php if (!empty($success_message)): ?>
            <div class="message success">
                ✅ <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="message error">
                ❌ <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Admin Panel -->
        <?php if ($_SESSION['role'] == 'admin'): ?>
            <div class="admin-panel fade-in">
                <div class="admin-header">
                    👥 Kullanıcı Yönetimi
                </div>
                
                <div class="table-container">
                    <?php if (!empty($users)): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kullanıcı Adı</th>
                                    <th>Şifre</th>
                                    <th>Rol</th>
                                    <th>Durum</th>
                                    <th>Kayıt Tarihi</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $key => $user): ?>
                                <tr>
                                    <td><?php echo $key + 1; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                        <?php if ($user['id'] == $_SESSION['kullanici_id']): ?>
                                            <span class="badge badge-admin">Siz</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="password-container">
                                            <span class="password-display password-hidden" id="password-hidden-<?php echo $user['id']; ?>">
                                                ••••••••
                                            </span>
                                            <span class="password-display password-visible" id="password-visible-<?php echo $user['id']; ?>" style="display: none;">
                                                <?php echo htmlspecialchars($user['password']); ?>
                                            </span>
                                            <button type="button" class="btn btn-secondary btn-small" onclick="togglePassword(<?php echo $user['id']; ?>)" id="eye-btn-<?php echo $user['id']; ?>">
                                                🔒
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $user['role']; ?>">
                                            <?php echo strtoupper($user['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        $status_text = '';
                                        switch($user['status']) {
                                            case 'onaylandi':
                                                $status_class = 'badge-success';
                                                $status_text = 'Onaylandı';
                                                break;
                                            case 'onay_bekliyor':
                                                $status_class = 'badge-warning';
                                                $status_text = 'Onay Bekliyor';
                                                break;
                                            case 'onaylanmadi':
                                                $status_class = 'badge-danger';
                                                $status_text = 'Onaylanmadı';
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <?php if ($user['role'] == 'admin'): ?>
                                            <span class="protected-text">🔒 Korumalı Hesap</span>
                                        <?php else: ?>
                                            <div class="user-actions">
                                                <!-- Durum Güncelleme -->
                                                <form method="POST" style="display: inline-block;">
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
                                                <button onclick="showPasswordForm(<?php echo $user['id']; ?>)" class="btn btn-primary btn-small">
                                                    🔑 Şifre
                                                </button>
                                                
                                                <!-- Kullanıcı Silme -->
                                                <?php if ($user['id'] != $_SESSION['kullanici_id']): ?>
                                                <form method="POST" style="display: inline-block;">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" name="delete_user" class="btn btn-danger btn-small" 
                                                            onclick="return confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?')">
                                                        🗑️ Sil
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- Şifre güncelleme formu -->
                                            <div id="password-form-<?php echo $user['id']; ?>" class="password-form" style="display: none;">
                                                <form method="POST" style="display: flex; align-items: center; gap: 10px; width: 100%;">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="text" name="new_password" placeholder="Yeni şifre" required>
                                                    <button type="submit" name="update_password" class="btn btn-primary btn-small">
                                                        Güncelle
                                                    </button>
                                                    <button type="button" onclick="hidePasswordForm(<?php echo $user['id']; ?>)" class="btn btn-secondary btn-small">
                                                        İptal
                                                    </button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <h4>Henüz kullanıcı bulunmuyor</h4>
                            <p>Sistem henüz kullanıcı kaydı içermiyor.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Logout Section -->
        <div class="logout-section">
            <a href="logout.php" class="logout-btn">
                🚪 Güvenli Çıkış
            </a>
        </div>
    </div>

    <script>
        // Şifre görüntüleme/gizleme
        function togglePassword(userId) {
            const hiddenSpan = document.getElementById('password-hidden-' + userId);
            const visibleSpan = document.getElementById('password-visible-' + userId);
            const eyeBtn = document.getElementById('eye-btn-' + userId);
            
            if (hiddenSpan.style.display === 'none') {
                hiddenSpan.style.display = 'inline';
                visibleSpan.style.display = 'none';
                eyeBtn.innerHTML = '🔒';
            } else {
                hiddenSpan.style.display = 'none';
                visibleSpan.style.display = 'inline';
                eyeBtn.innerHTML = '🔓';
            }
        }
        
        // Şifre formu göster/gizle
        function showPasswordForm(userId) {
            document.getElementById('password-form-' + userId).style.display = 'block';
        }
        
        function hidePasswordForm(userId) {
            document.getElementById('password-form-' + userId).style.display = 'none';
        }

        // Mesajları otomatik gizle
        setTimeout(function() {
            const messages = document.querySelectorAll('.message');
            messages.forEach(function(message) {
                message.style.opacity = '0';
                message.style.transform = 'translateY(-20px)';
                setTimeout(function() {
                    message.remove();
                }, 300);
            });
        }, 5000);

        // Otomatik çıkış uyarısı
        setTimeout(function() {
            if(confirm('Oturumunuz yakında sona erecek. Devam etmek istiyor musunuz?')) {
                location.reload();
            }
        }, 3600000);
    </script>
</body>
</html>