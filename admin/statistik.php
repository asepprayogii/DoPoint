<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_admin()) {
    redirect('../login.php');
}

// Statistik misi per kategori
$kategori_stats = $pdo->query("
    SELECT k.nama_kategori, 
           COUNT(m.id) as jumlah_misi,
           SUM(m.nilai_pain) as total_pain
    FROM kategori_misi k
    LEFT JOIN misi m ON k.id = m.kategori_id
    GROUP BY k.id
    ORDER BY jumlah_misi DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Statistik aktivitas per bulan
$activity_stats = $pdo->query("
    SELECT DATE_FORMAT(completed_at, '%Y-%m') as bulan,
           COUNT(*) as jumlah_misi,
           SUM(nilai_pain) as total_pain
    FROM misi
    WHERE status = 'selesai'
    GROUP BY bulan
    ORDER BY bulan DESC
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

// Statistik user aktif
$user_stats = $pdo->query("
    SELECT COUNT(*) as total_users,
           SUM(CASE WHEN last_login >= CURDATE() - INTERVAL 7 DAY THEN 1 ELSE 0 END) as aktif_minggu_ini,
           SUM(CASE WHEN last_login < CURDATE() - INTERVAL 30 DAY THEN 1 ELSE 0 END) as tidak_aktif
    FROM users
    WHERE role = 'user'
")->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Statistik - Admin</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Tambahkan/replace di bagian <style> */
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --warning: #f72585;
            --light: #f8f9fa;
            --dark: #212529;
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
        .error {
            color: #e63946;
            background: #ffeaee;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #e63946;
        }
        .success {
            color: #2a9d8f;
            background: #e8f9f6;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #2a9d8f;
        }
        .stats-grid, .chart-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card, .chart-box, .section, .table-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .stat-card-title, .chart-title, .section-title {
            font-size: 1.1rem;
            margin-bottom: 15px;
            color: var(--primary);
        }
        .stat-card-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--primary);
            margin: 10px 0;
        }
        .stat-card-desc {
            font-size: 0.9rem;
            color: #6c757d;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        tbody tr:hover {
            background: #f1f3fa;
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
        @media (max-width: 700px) {
            .header, .container {
                flex-direction: column;
                padding: 10px;
            }
            .stat-card, .chart-box, .section, .table-container {
                padding: 10px;
            }
            .btn {
                width: 100%;
                margin-bottom: 10px;
            }
            th, td {
                padding: 8px 5px;
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
                    <p>Statistik Umum</p>
                </div>
            </div>
            
            <nav class="nav-menu">
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="kelola_kategori.php" class="nav-link">Kategori</a>
                <a href="data_user.php" class="nav-link">Data User</a>
                <a href="statistik.php" class="nav-link active">Statistik</a>
                <a href="verifikasi_reward.php" class="nav-link">Verifikasi Reward</a>
                <a href="notifikasi.php" class="nav-link">Notifikasi</a>
                <a href="../logout.php" class="nav-link">Logout</a>
            </nav>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-title">Total Pengguna</div>
                <div class="stat-card-value"><?= $user_stats['total_users'] ?></div>
                <div class="stat-card-desc">Pengguna Terdaftar</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-card-title">Pengguna Aktif</div>
                <div class="stat-card-value"><?= $user_stats['aktif_minggu_ini'] ?></div>
                <div class="stat-card-desc">Aktif dalam 7 hari terakhir</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-card-title">Pengguna Tidak Aktif</div>
                <div class="stat-card-value"><?= $user_stats['tidak_aktif'] ?></div>
                <div class="stat-card-desc">Tidak aktif >30 hari</div>
            </div>
        </div>
        
        <div class="chart-container">
            <div class="chart-box">
                <h3 class="chart-title">Misi per Kategori</h3>
                <canvas id="kategoriChart"></canvas>
            </div>
            
            <div class="chart-box">
                <h3 class="chart-title">Aktivitas per Bulan</h3>
                <canvas id="activityChart"></canvas>
            </div>
        </div>
        
        <div class="table-container">
            <h3 class="section-title">Detail Statistik Kategori</h3>
            <table>
                <thead>
                    <tr>
                        <th>Kategori</th>
                        <th>Jumlah Misi</th>
                        <th>Total Pain</th>
                        <th>Persentase</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_misi = array_sum(array_column($kategori_stats, 'jumlah_misi'));
                    foreach ($kategori_stats as $kategori): 
                        $persentase = $total_misi > 0 ? round(($kategori['jumlah_misi'] / $total_misi) * 100, 1) : 0;
                    ?>
                        <tr>
                            <td><?= $kategori['nama_kategori'] ?></td>
                            <td><?= $kategori['jumlah_misi'] ?></td>
                            <td><?= $kategori['total_pain'] ?></td>
                            <td>
                                <div style="display: flex; align-items: center;">
                                    <div style="width: 100%; background: #f0f2f5; border-radius: 4px; margin-right: 10px;">
                                        <div style="width: <?= $persentase ?>%; height: 8px; background: var(--primary); border-radius: 4px;"></div>
                                    </div>
                                    <span><?= $persentase ?>%</span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        // Data untuk chart kategori
        const kategoriData = {
            labels: <?= json_encode(array_column($kategori_stats, 'nama_kategori')) ?>,
            datasets: [{
                label: 'Jumlah Misi',
                data: <?= json_encode(array_column($kategori_stats, 'jumlah_misi')) ?>,
                backgroundColor: [
                    '#4361ee', '#3f37c9', '#4cc9f0', '#f72585', 
                    '#7209b7', '#3a0ca3', '#2a9d8f', '#e9c46a'
                ],
                borderWidth: 1
            }]
        };
        
        // Data untuk chart aktivitas
        const activityData = {
            labels: <?= json_encode(array_map(function($item) {
                $date = DateTime::createFromFormat('Y-m', $item['bulan']);
                return $date->format('M Y');
            }, $activity_stats)) ?>,
            datasets: [{
                label: 'Jumlah Misi',
                data: <?= json_encode(array_column($activity_stats, 'jumlah_misi')) ?>,
                backgroundColor: '#4361ee',
                borderColor: '#3f37c9',
                borderWidth: 2,
                tension: 0.3,
                fill: false
            }, {
                label: 'Total Pain',
                data: <?= json_encode(array_column($activity_stats, 'total_pain')) ?>,
                backgroundColor: '#f72585',
                borderColor: '#b5179e',
                borderWidth: 2,
                tension: 0.3,
                fill: false
            }]
        };
        
        // Inisialisasi chart
        window.onload = function() {
            // Chart kategori
            const kategoriCtx = document.getElementById('kategoriChart').getContext('2d');
            new Chart(kategoriCtx, {
                type: 'doughnut',
                data: kategoriData,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right',
                        }
                    }
                }
            });
            
            // Chart aktivitas
            const activityCtx = document.getElementById('activityChart').getContext('2d');
            new Chart(activityCtx, {
                type: 'line',
                data: activityData,
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        };
    </script>
</body>
</html>