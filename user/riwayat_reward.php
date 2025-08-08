<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];

// Ambil riwayat reward
$riwayat_reward = $pdo->prepare("
    SELECT rc.*, tr.target_pain, tr.hadiah as target_hadiah
    FROM reward_claim rc
    JOIN target_reward tr ON rc.target_id = tr.id
    WHERE rc.user_id = ?
    ORDER BY rc.claimed_at DESC
");
$riwayat_reward->execute([$user_id]);

// Statistik reward
$total_claimed = $pdo->prepare("SELECT COUNT(*) FROM reward_claim WHERE user_id = ?")->execute([$user_id])->fetchColumn();
$total_accepted = $pdo->prepare("SELECT COUNT(*) FROM reward_claim WHERE user_id = ? AND status_verifikasi = 'diterima'")->execute([$user_id])->fetchColumn();
$total_pending = $pdo->prepare("SELECT COUNT(*) FROM reward_claim WHERE user_id = ? AND status_verifikasi = 'pending'")->execute([$user_id])->fetchColumn();
$total_rejected = $pdo->prepare("SELECT COUNT(*) FROM reward_claim WHERE user_id = ? AND status_verifikasi = 'ditolak'")->execute([$user_id])->fetchColumn();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Reward - Aplikasi Produktivitas</title>
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --warning: #f72585;
            --light: #f8f9fa;
            --dark: #212529;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f0f2f5;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1000px;
            margin: 50px auto;
            padding: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary);
        }
        
        .nav-menu {
            display: flex;
            gap: 15px;
        }
        
        .nav-link {
            text-decoration: none;
            color: var(--dark);
            font-weight: 500;
            padding: 8px 15px;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .nav-link:hover, .nav-link.active {
            background-color: var(--primary);
            color: white;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .card-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: var(--primary);
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f2f5;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            border-left: 4px solid var(--primary);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #666;
        }
        
        .reward-item {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid var(--primary);
            transition: all 0.3s;
        }
        
        .reward-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .reward-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .reward-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-diterima {
            background: #d4edda;
            color: #155724;
        }
        
        .status-ditolak {
            background: #f8d7da;
            color: #721c24;
        }
        
        .reward-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        
        .detail-label {
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 5px;
        }
        
        .detail-value {
            font-weight: 500;
            color: var(--dark);
        }
        
        .reward-catatan {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            border-left: 3px solid var(--warning);
        }
        
        .catatan-label {
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 5px;
        }
        
        .catatan-text {
            font-style: italic;
            color: #333;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        .btn {
            display: inline-block;
            background: var(--primary);
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .btn:hover {
            background: var(--secondary);
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                margin: 20px auto;
                padding: 15px;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .user-info {
                flex-direction: column;
                gap: 10px;
            }
            
            .nav-menu {
                flex-wrap: wrap;
                justify-content: center;
                gap: 10px;
            }
            
            .nav-link {
                padding: 10px 15px;
                font-size: 0.9rem;
            }
            
            .card {
                padding: 20px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
            
            .reward-details {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 480px) {
            .container {
                margin: 10px auto;
                padding: 10px;
            }
            
            .card {
                padding: 15px;
            }
            
            .nav-link {
                padding: 8px 12px;
                font-size: 0.85rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="user-info">
                <img src="../foto_profil/<?= $_SESSION['foto_profil'] ?? 'default.jpg' ?>" alt="Avatar" class="user-avatar">
                <div>
                    <h2><?= $_SESSION['nama'] ?></h2>
                    <p>Riwayat Reward</p>
                </div>
            </div>
            
            <nav class="nav-menu">
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="riwayat_reward.php" class="nav-link active">Riwayat Reward</a>
                <a href="../profil.php" class="nav-link">Profil</a>
                <a href="../logout.php" class="nav-link">Logout</a>
            </nav>
        </div>
        
        <div class="card">
            <h3 class="card-title">Statistik Reward</h3>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?= $total_claimed ?></div>
                    <div class="stat-label">Total Diklaim</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $total_accepted ?></div>
                    <div class="stat-label">Diterima</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $total_pending ?></div>
                    <div class="stat-label">Menunggu</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $total_rejected ?></div>
                    <div class="stat-label">Ditolak</div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h3 class="card-title">Riwayat Reward</h3>
            
            <?php if ($riwayat_reward->rowCount() > 0): ?>
                <?php while ($reward = $riwayat_reward->fetch()): ?>
                    <div class="reward-item">
                        <div class="reward-header">
                            <div class="reward-title"><?= htmlspecialchars($reward['hadiah']) ?></div>
                            <span class="status-badge status-<?= $reward['status_verifikasi'] ?>">
                                <?= ucfirst($reward['status_verifikasi']) ?>
                            </span>
                        </div>
                        
                        <div class="reward-details">
                            <div class="detail-item">
                                <div class="detail-label">Target Pain</div>
                                <div class="detail-value"><?= $reward['target_pain'] ?> pain</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Diklaim Pada</div>
                                <div class="detail-value"><?= date('d M Y H:i', strtotime($reward['claimed_at'])) ?></div>
                            </div>
                            <?php if ($reward['verified_at']): ?>
                                <div class="detail-item">
                                    <div class="detail-label">Diverifikasi Pada</div>
                                    <div class="detail-value"><?= date('d M Y H:i', strtotime($reward['verified_at'])) ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($reward['catatan'])): ?>
                            <div class="reward-catatan">
                                <div class="catatan-label">Catatan Admin:</div>
                                <div class="catatan-text"><?= htmlspecialchars($reward['catatan']) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div style="font-size: 3rem; margin-bottom: 15px; opacity: 0.5;">🎁</div>
                    <h3>Belum Ada Riwayat Reward</h3>
                    <p>Anda belum pernah mengklaim reward. Mulai buat target dan selesaikan misi untuk mendapatkan reward!</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="dashboard.php" class="btn btn-secondary">&larr; Kembali ke Dashboard</a>
        </div>
    </div>
</body>
</html>
