<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_admin()) {
    redirect('../login.php');
}

// Query statistik
$total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$total_misi = $pdo->query("SELECT COUNT(*) FROM misi")->fetchColumn();
$total_reward_klaim = $pdo->query("SELECT COUNT(*) FROM reward_claim")->fetchColumn();
$total_kategori = $pdo->query("SELECT COUNT(*) FROM kategori_misi")->fetchColumn();

// Kategori paling populer
$kategori_populer = $pdo->query("
    SELECT k.nama_kategori, COUNT(m.id) as jumlah
    FROM kategori_misi k
    LEFT JOIN misi m ON k.id = m.kategori_id
    GROUP BY k.id
    ORDER BY jumlah DESC
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

// User dengan pain tertinggi
$top_user = $pdo->query("
    SELECT nama, total_pain 
    FROM users 
    WHERE role = 'user'
    ORDER BY total_pain DESC 
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

// Reward yang perlu diverifikasi
$reward_pending = $pdo->query("SELECT COUNT(*) FROM reward_claim WHERE status_verifikasi = 'pending'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - Aplikasi Produktivitas</title>
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
        
        .admin-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .admin-avatar {
            width: 50px;
            height: 50px;
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
        
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .card-title {
            font-size: 1rem;
            color: #6c757d;
            margin-bottom: 10px;
        }
        
        .card-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary);
            margin: 10px 0;
        }
        
        .card-desc {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 1.2rem;
            margin-bottom: 15px;
            color: var(--primary);
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .stat-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        .stat-value {
            font-size: 1.2rem;
            font-weight: 500;
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
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }
        
        .alert-icon {
            font-size: 1.5rem;
            margin-right: 15px;
        }
        @media (max-width: 700px) {
            .header, .container {
                flex-direction: column;
                padding: 10px;
            }
            .card, .section {
                padding: 10px;
            }
            .btn {
                width: 100%;
                margin-bottom: 10px;
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
            <div class="admin-info">
                <img src="../foto_profil/<?= $_SESSION['foto_profil'] ?>" alt="Avatar" class="admin-avatar">
                <div>
                    <h2>Admin: <?= $_SESSION['nama'] ?></h2>
                    <p>Dashboard Admin</p>
                </div>
            </div>
            
            <nav class="nav-menu">
                <a href="dashboard.php" class="nav-link active">Dashboard</a>
                <a href="kelola_kategori.php" class="nav-link">Kategori</a>
                <a href="data_user.php" class="nav-link">Data User</a>
                <a href="statistik.php" class="nav-link">Statistik</a>
                <a href="verifikasi_reward.php" class="nav-link">Verifikasi Reward</a>
                <a href="notifikasi.php" class="nav-link">Notifikasi</a>
                <a href="../logout.php" class="nav-link">Logout</a>
            </nav>
        </div>
        
        <?php if ($reward_pending > 0): ?>
            <div class="alert alert-warning">
                <div class="alert-icon">⚠️</div>
                <div>
                    <strong>Ada <?= $reward_pending ?> reward yang perlu diverifikasi!</strong>
                    <p>Segera lakukan verifikasi untuk reward yang masih pending.</p>
                    <a href="verifikasi_reward.php" class="btn" style="margin-top: 10px;">Verifikasi Sekarang</a>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="card-grid">
            <div class="card">
                <div class="card-title">Total Pengguna</div>
                <div class="card-value"><?= $total_users ?></div>
                <div class="card-desc">Pengguna Terdaftar</div>
            </div>
            
            <div class="card">
                <div class="card-title">Total Misi</div>
                <div class="card-value"><?= $total_misi ?></div>
                <div class="card-desc">Misi Dibuat</div>
            </div>
            
            <div class="card">
                <div class="card-title">Total Reward</div>
                <div class="card-value"><?= $total_reward_klaim ?></div>
                <div class="card-desc">Reward Diklaim</div>
            </div>
            
            <div class="card">
                <div class="card-title">Total Kategori</div>
                <div class="card-value"><?= $total_kategori ?></div>
                <div class="card-desc">Kategori Misi</div>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="section">
                <h3 class="section-title">Kategori Paling Populer</h3>
                <?php if ($kategori_populer): ?>
                    <div class="stat-item">
                        <div class="stat-label">Kategori</div>
                        <div class="stat-value"><?= $kategori_populer['nama_kategori'] ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Jumlah Misi</div>
                        <div class="stat-value"><?= $kategori_populer['jumlah'] ?></div>
                    </div>
                <?php else: ?>
                    <p>Tidak ada data kategori.</p>
                <?php endif; ?>
            </div>
            
            <div class="section">
                <h3 class="section-title">Top User</h3>
                <?php if ($top_user): ?>
                    <div class="stat-item">
                        <div class="stat-label">Nama</div>
                        <div class="stat-value"><?= $top_user['nama'] ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Total Pain</div>
                        <div class="stat-value"><?= $top_user['total_pain'] ?></div>
                    </div>
                <?php else: ?>
                    <p>Tidak ada data user.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="section">
            <h3 class="section-title">Aksi Cepat</h3>
            <div style="display: flex; gap: 10px;">
                <a href="kelola_kategori.php" class="btn">Kelola Kategori</a>
                <a href="data_user.php" class="btn">Lihat Data User</a>
                <a href="verifikasi_reward.php" class="btn">Verifikasi Reward</a>
                <a href="notifikasi.php" class="btn">Kirim Notifikasi</a>
            </div>
        </div>
    </div>
</body>
</html>