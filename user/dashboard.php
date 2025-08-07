<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];

// Ambil data user
$user_stmt = $pdo->prepare("SELECT total_pain, foto_profil, created_at FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch();

// Ambil misi aktif
$misi_aktif = $pdo->prepare("SELECT m.*, k.nama_kategori 
                            FROM misi m 
                            JOIN kategori_misi k ON m.kategori_id = k.id
                            WHERE m.user_id = ? AND m.status = 'aktif'");
$misi_aktif->execute([$user_id]);

// Ambil target aktif
$target_aktif = $pdo->prepare("SELECT * FROM target_reward 
                              WHERE user_id = ? AND status IN ('aktif', 'tercapai') 
                              ORDER BY created_at DESC");
$target_aktif->execute([$user_id]);

// Ambil leaderboard
$leaderboard = $pdo->query("
    SELECT nama, foto_profil, total_pain, 
           (SELECT COUNT(*) FROM reward_claim WHERE user_id = users.id) AS jumlah_reward
    FROM users
    WHERE role = 'user'
    ORDER BY total_pain DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Statistik
$stmt_misi_selesai = $pdo->prepare("SELECT COUNT(*) FROM misi WHERE user_id = ? AND status = 'selesai'");
$stmt_misi_selesai->execute([$user_id]);
$misi_selesai = $stmt_misi_selesai->fetchColumn();

$stmt_reward_diklaim = $pdo->prepare("SELECT COUNT(*) FROM reward_claim WHERE user_id = ?");
$stmt_reward_diklaim->execute([$user_id]);
$reward_diklaim = $stmt_reward_diklaim->fetchColumn();

$statistik = [
    'misi_aktif' => $misi_aktif->rowCount(),
    'misi_selesai' => $misi_selesai,
    'reward_diklaim' => $reward_diklaim,
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard User</title>
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
            max-width: 1200px;
            margin: 0 auto;
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
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary);
        }
        
        .nav-menu {
            display: flex;
            gap: 20px;
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
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 3fr 1fr;
            gap: 20px;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .card-title {
            font-size: 1.2rem;
            margin-bottom: 15px;
            color: var(--primary);
            border-bottom: 2px solid #f0f2f5;
            padding-bottom: 10px;
        }
        
        .pain-counter {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary);
            text-align: center;
            margin: 20px 0;
        }
        
        .progress-container {
            background: #e9ecef;
            border-radius: 20px;
            height: 20px;
            margin: 20px 0;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 100%;
            background: var(--primary);
            border-radius: 20px;
            text-align: center;
            color: white;
            font-size: 0.8rem;
            line-height: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--primary);
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .btn {
            display: inline-block;
            background: var(--primary);
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }
        
        .btn:hover {
            background: var(--secondary);
            transform: translateY(-2px);
        }
        
        .btn-block {
            display: block;
            width: 100%;
            margin: 10px 0;
        }
        
        .mission-list {
            list-style: none;
        }
        
        .mission-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .mission-category {
            font-size: 0.8rem;
            background: #e0e7ff;
            color: var(--primary);
            padding: 2px 8px;
            border-radius: 20px;
        }
        
        .leaderboard-list {
            list-style: none;
        }
        
        .leaderboard-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .leaderboard-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
        }
        
        .leaderboard-rank {
            font-weight: bold;
            width: 25px;
            text-align: center;
        }
        
        .leaderboard-rank.rank-1 {
            color: gold;
            font-size: 1.2rem;
        }
        
        .leaderboard-rank.rank-2 {
            color: silver;
            font-size: 1.1rem;
        }
        
        .leaderboard-rank.rank-3 {
            color: #cd7f32;
            font-size: 1.0rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="user-info">
                <img src="../foto_profil/<?= $user['foto_profil'] ?>" alt="Avatar" class="user-avatar">
                <div>
                    <h2>Halo, <?= $_SESSION['nama'] ?></h2>
                    <p>Member sejak <?= date('d M Y', strtotime($user['created_at'])) ?></p>
                </div>
            </div>
            
            <nav class="nav-menu">
                <a href="dashboard.php" class="nav-link active">Dashboard</a>
                <a href="buat_misi.php" class="nav-link">Buat Misi</a>
                <a href="set_target.php" class="nav-link">Target Reward</a>
                <a href="klasemen.php" class="nav-link">Leaderboard</a>
                <a href="profil.php" class="nav-link">Profil</a>
                <a href="../logout.php" class="nav-link">Logout</a>
            </nav>
        </div>
        
        <div class="dashboard-grid">
            <div class="main-content">
                <!-- Pain Counter -->
                <div class="card">
                    <h3 class="card-title">Pain Point Anda</h3>
                    <div class="pain-counter"><?= $user['total_pain'] ?></div>
                    
                    <?php if (!empty($target_aktif)):
                        $target = $target_aktif->fetch();
                        if ($target && isset($target['target_pain']) && $target['target_pain'] > 0) {
                            $progress = min(100, ($user['total_pain'] / $target['target_pain']) * 100);
                        } else {
                            $progress = 0;
                        }
                    ?>
                    <div class="progress-container">
                        <div class="progress-bar" style="width: <?= $progress ?>%">
                            <?= number_format($progress, 0) ?>%
                        </div>
                    </div>
                    <?php if ($target): ?>
                    <p>Menuju target: <strong><?= $target['hadiah'] ?></strong> (<?= $user['total_pain'] ?>/<?= $target['target_pain'] ?> pain)</p>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Statistik -->
                <div class="card">
                    <h3 class="card-title">Statistik Produktivitas</h3>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-value"><?= $statistik['misi_aktif'] ?></div>
                            <div class="stat-label">Misi Aktif</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?= $statistik['misi_selesai'] ?></div>
                            <div class="stat-label">Misi Selesai</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?= $statistik['reward_diklaim'] ?></div>
                            <div class="stat-label">Reward Diklaim</div>
                        </div>
                    </div>
                    <a href="riwayat_misi.php" class="btn">Lihat Riwayat Lengkap</a>
                </div>
                
                <!-- Misi Aktif -->
                <div class="card">
                    <h3 class="card-title">Misi Aktif Anda</h3>
                    <ul class="mission-list">
                        <?php if ($misi_aktif->rowCount() > 0): ?>
                            <?php while ($misi = $misi_aktif->fetch()): ?>
                                <li class="mission-item">
                                    <div>
                                        <strong><?= $misi['nama_misi'] ?></strong>
                                        <div class="mission-category"><?= $misi['nama_kategori'] ?></div>
                                    </div>
                                    <div>
                                        <span><?= $misi['nilai_pain'] ?> pain</span>
                                        <a href="selesai_misi.php?id=<?= $misi['id'] ?>" class="btn" style="padding: 5px 10px; margin-left: 10px;">
                                            Tandai Selesai
                                        </a>
                                    </div>
                                </li>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>Belum ada misi aktif. <a href="buat_misi.php">Buat misi baru sekarang!</a></p>
                        <?php endif; ?>
                    </ul>
                    <a href="buat_misi.php" class="btn btn-block">+ Buat Misi Baru</a>
                </div>
            </div>
            
            <div class="sidebar">
                <!-- Target Reward -->
                <div class="card">
                    <h3 class="card-title">Target Reward</h3>
                    <?php if ($target_aktif->rowCount() > 0): 
                        $target_aktif->execute([$user_id]); // Reset pointer
                        while ($target = $target_aktif->fetch()): ?>
                            <div style="margin-bottom: 15px; padding: 10px; background: #f8f9fa; border-radius: 8px;">
                                <strong><?= $target['hadiah'] ?></strong>
                                <p><?= $target['target_pain'] ?> pain</p>
                                <p>Status: 
                                    <span style="color: <?= $target['status'] == 'tercapai' ? 'green' : 'orange' ?>">
                                        <?= ucfirst($target['status']) ?>
                                    </span>
                                </p>
                                <?php if ($target['status'] == 'tercapai'): ?>
                                    <a href="klaim_reward.php?target_id=<?= $target['id'] ?>" class="btn" style="padding: 5px 10px;">
                                        Klaim Reward
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>Belum ada target reward</p>
                    <?php endif; ?>
                    <a href="set_target.php" class="btn btn-block">Set Target Baru</a>
                </div>
                
                <!-- Leaderboard -->
                <div class="card">
                    <h3 class="card-title">Top 5 Leaderboard</h3>
                    <ul class="leaderboard-list">
                        <?php $rank = 1; ?>
                        <?php foreach ($leaderboard as $player): ?>
                            <li class="leaderboard-item">
                                <span class="leaderboard-rank rank-<?= $rank ?>"><?= $rank ?></span>
                                <img src="../foto_profil/<?= $player['foto_profil'] ?>" alt="Avatar" class="leaderboard-avatar">
                                <div style="flex-grow: 1;">
                                    <strong><?= $player['nama'] ?></strong>
                                    <div><?= $player['total_pain'] ?> pain</div>
                                </div>
                            </li>
                            <?php $rank++; ?>
                        <?php endforeach; ?>
                    </ul>
                    <a href="klasemen.php" class="btn btn-block">Lihat Klasemen Lengkap</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>