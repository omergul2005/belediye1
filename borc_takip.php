<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Giriş kontrolü
if (!isset($_SESSION['kullanici_id'])) {
    header('Location: index.php');
    exit;
}


$success_message = '';
$error_message = '';

// Firmalar listesi
try {
    $stmt = $pdo->prepare("
        SELECT 
            f.*,
            (f.toplam_borc - f.kalan_borc) as odenen_tutar,
            (SELECT COUNT(*) FROM taksitler t WHERE t.firma_id = f.id AND t.durum = 'odendi') as odenen_taksit,
            (SELECT COUNT(*) FROM taksitler t WHERE t.firma_id = f.id AND t.durum != 'odendi') as kalan_taksit,
            (SELECT COUNT(*) FROM taksitler t WHERE t.firma_id = f.id AND t.vade_tarihi < CURDATE() AND t.durum != 'odendi') as geciken_taksit
        FROM firmalar f 
        ORDER BY f.firma_adi
    ");
    $stmt->execute();
    $firmalar = $stmt->fetchAll();
} catch(PDOException $e) {
    $error_message = "Firmalar yüklenirken hata oluştu!";
    $firmalar = [];
}

// Toplam istatistikler
try {
    $stats_stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as toplam_firma,
            SUM(toplam_borc) as toplam_borc,
            SUM(kalan_borc) as toplam_kalan,
            SUM(toplam_borc - kalan_borc) as toplam_odenen,
            COUNT(CASE WHEN durum = 'gecikme' THEN 1 END) as geciken_firma
        FROM firmalar
    ");
    $stats_stmt->execute();
    $istatistik = $stats_stmt->fetch();
} catch(PDOException $e) {
    $istatistik = ['toplam_firma' => 0, 'toplam_borc' => 0, 'toplam_kalan' => 0, 'toplam_odenen' => 0, 'geciken_firma' => 0];
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konya Belediyesi - Borç Takip Sistemi</title>
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
        }
        
        .container {
            width: 100vw;
            min-height: 100vh;
            background: white;
            display: flex;
            flex-direction: column;
        }
        
        .header {
            background: linear-gradient(135deg, #2c5aa0 0%, #1e3c72 100%);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 24px;
            font-weight: 700;
        }
        
        .header .user-info {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .header .user-info a {
            color: white;
            text-decoration: none;
            margin: 0 5px;
        }
        
        .header .user-info a:hover {
            text-decoration: underline;
        }
        
        .stats-bar {
            background: #f8f9fa;
            padding: 20px 30px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid;
        }
        
        .stat-card.total { border-color: #007bff; }
        .stat-card.remaining { border-color: #dc3545; }
        .stat-card.paid { border-color: #28a745; }
        .stat-card.overdue { border-color: #ffc107; }
        
        .stat-card h3 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-card.total h3 { color: #007bff; }
        .stat-card.remaining h3 { color: #dc3545; }
        .stat-card.paid h3 { color: #28a745; }
        .stat-card.overdue h3 { color: #ffc107; }
        
        .stat-card p {
            font-size: 14px;
            color: #666;
            font-weight: 600;
        }
        
        .content {
            flex: 1;
            padding: 30px;
            overflow-x: auto;
        }
        
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-header {
            background: #2c5aa0;
            color: white;
            padding: 15px 20px;
            font-weight: 600;
        }
        
        .firms-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        
        .firms-table th {
            background: #f8f9fa;
            padding: 15px 10px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
            white-space: nowrap;
        }
        
        .firms-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        
        .firms-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-aktif {
            background: #d4edda;
            color: #155724;
        }
        
        .status-gecikme {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-tamamlandi {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .amount {
            font-weight: 600;
            text-align: right;
        }
        
        .amount.positive {
            color: #28a745;
        }
        
        .amount.negative {
            color: #dc3545;
        }
        
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-1px);
        }
        
        .progress-bar {
            width: 100px;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin: 5px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
            transition: width 0.3s ease;
        }
        
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: 600;
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
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .content {
                padding: 15px;
            }
            
            .firms-table {
                font-size: 12px;
            }
            
            .firms-table th,
            .firms-table td {
                padding: 8px 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Konya Belediyesi - Borç Takip Sistemi</h1>
            <div class="user-info">
                <?php echo htmlspecialchars($_SESSION['username']); ?> - <?php echo strtoupper($_SESSION['role']); ?>
                | <a href="dashboard.php">🏠 Ana Panel</a>
                | <a href="logout.php">🚪 Çıkış</a>
            </div>
        </div>
        
        <div class="stats-bar">
            <div class="stats-grid">
                <div class="stat-card total">
                    <h3><?php echo number_format($istatistik['toplam_firma']); ?></h3>
                    <p>Toplam Firma</p>
                </div>
                <div class="stat-card total">
                    <h3>₺<?php echo number_format($istatistik['toplam_borc'], 0, ',', '.'); ?></h3>
                    <p>Toplam Borç</p>
                </div>
                <div class="stat-card remaining">
                    <h3>₺<?php echo number_format($istatistik['toplam_kalan'], 0, ',', '.'); ?></h3>
                    <p>Kalan Borç</p>
                </div>
                <div class="stat-card paid">
                    <h3>₺<?php echo number_format($istatistik['toplam_odenen'], 0, ',', '.'); ?></h3>
                    <p>Ödenen Tutar</p>
                </div>
                <div class="stat-card overdue">
                    <h3><?php echo $istatistik['geciken_firma']; ?></h3>
                    <p>Gecikme Var</p>
                </div>
            </div>
        </div>
        
        <div class="content">
            <?php if ($success_message): ?>
                <div class="message success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="message error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="table-container">
                <div class="table-header">
                    💼 Firma Borç Listesi - <?php echo count($firmalar); ?> Firma
                </div>
                
                <table class="firms-table">
                    <thead>
                        <tr>
                            <th>Firma Adı</th>
                            <th>Şehir</th>
                            <th>Telefon</th>
                            <th>Toplam Borç</th>
                            <th>Ödenen</th>
                            <th>Kalan Borç</th>
                            <th>Taksit</th>
                            <th>Başlangıç</th>
                            <th>Aylık Ödeme</th>
                            <th>İlerleme</th>
                            <th>Durum</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($firmalar as $firma): ?>
                        <?php 
                            $odeme_orani = $firma['toplam_borc'] > 0 ? (($firma['toplam_borc'] - $firma['kalan_borc']) / $firma['toplam_borc']) * 100 : 0;
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($firma['firma_adi']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($firma['sehir']); ?></td>
                            <td><?php echo htmlspecialchars($firma['telefon']); ?></td>
                            <td class="amount">₺<?php echo number_format($firma['toplam_borc'], 0, ',', '.'); ?></td>
                            <td class="amount positive">₺<?php echo number_format($firma['odenen_tutar'], 0, ',', '.'); ?></td>
                            <td class="amount negative">₺<?php echo number_format($firma['kalan_borc'], 0, ',', '.'); ?></td>
                            <td class="amount">
                                <?php echo $firma['odenen_taksit']; ?>/<?php echo $firma['taksit_sayisi']; ?>
                                <?php if($firma['geciken_taksit'] > 0): ?>
                                    <br><small style="color: #dc3545;">⚠️ <?php echo $firma['geciken_taksit']; ?> geciken</small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d.m.Y', strtotime($firma['baslangic_tarihi'])); ?></td>
                            <td class="amount">₺<?php echo number_format($firma['aylik_odeme'], 0, ',', '.'); ?></td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $odeme_orani; ?>%"></div>
                                </div>
                                <small><?php echo number_format($odeme_orani, 1); ?>%</small>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $firma['durum']; ?>">
                                    <?php 
                                    switch($firma['durum']) {
                                        case 'aktif': echo 'Aktif'; break;
                                        case 'gecikme': echo 'Gecikme'; break;
                                        case 'tamamlandi': echo 'Tamamlandı'; break;
                                        default: echo 'Aktif';
                                    }
                                    ?>
                                </span>
                            </td>
                            <td>
                                <a href="firma_detay.php?id=<?php echo $firma['id']; ?>" class="btn btn-primary">
                                    📋 Detay
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>