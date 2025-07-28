<?php
require_once 'config.php';

// Giriş kontrolü
requireLogin();

$success_message = '';
$error_message = '';

// Admin ise kullanıcı işlemleri
if ($_SESSION['role'] == 'admin') {
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
    
    // Tüm kullanıcıları getir
    try {
        $stmt = $pdo->prepare("SELECT id, username, status, role, created_at FROM users ORDER BY created_at DESC");
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
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="konya-logo.png">
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
            <h3>Hoşgeldiniz, <?php echo htmlspecialchars(isset($_SESSION['full_name']) ? $_SESSION['full_name'] : $_SESSION['username']); ?>!</h3>
            <p>
                <strong>Rol:</strong> <?php echo strtoupper($_SESSION['role']); ?> | 
                <strong>Giriş Zamanı:</strong> <?php echo date('d.m.Y H:i', $_SESSION['login_time']); ?> 
            
                <?php if (isset($_SESSION['department'])): ?>
                | <strong>Birim:</strong> <?php echo htmlspecialchars($_SESSION['department']); ?>
                <?php endif; ?>
            </p>
            
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
                                <th>Rol</th>
                                <th>Durum</th>
                                <th>Kayıt Tarihi</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
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
                    <h4>🏢 Hoş Geldiniz</h4>
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
    
    <style>
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
    </style>
</body>
</html>