<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 20;
$offset = ($current_page - 1) * $per_page;

// Ambil data klasemen
$leaderboard = $pdo->query("
    SELECT 
        u.id, 
        u.nama, 
        u.foto_profil, 
        u.total_pain,
        (SELECT COUNT(*) FROM reward_claim WHERE user_id = u.id AND status_verifikasi = 'diterima') AS jumlah_reward,
        RANK() OVER (ORDER BY u.total_pain DESC) AS peringkat
    FROM users u
    WHERE u.role = 'user'
    ORDER BY u.total_pain DESC
    LIMIT $per_page OFFSET $offset
")->fetchAll(PDO::FETCH_ASSOC);

// Hitung total halaman
$total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$total_pages = ceil($total_users / $per_page);

// Ambil peringkat user saat ini
$user_rank = $pdo->query("
    SELECT peringkat FROM (
        SELECT 
            id, 
            RANK() OVER (ORDER BY total_pain DESC) AS peringkat
        FROM users
        WHERE role = 'user'
    ) AS ranked_users
    WHERE id = $user_id
")->fetchColumn();

// Jika tidak ditemukan, set ke 0
$user_rank = $user_rank ? $user_rank : 0;

// Ambil data user saat ini
$current_user = $pdo->query("
    SELECT 
        nama, 
        foto_profil, 
        total_pain,
        (SELECT COUNT(*) FROM reward_claim WHERE user_id = $user_id) AS jumlah_reward
    FROM users
    WHERE id = $user_id
")->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Leaderboard - Aplikasi Produktivitas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --gold: #FFD700;
            --silver: #C0C0C0;
            --bronze: #CD7F32;
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
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Navbar styles sudah ada di includes/navbar.php */
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .card-title {
            font-size: 1.8rem;
            margin-bottom: 25px;
            color: var(--primary);
            text-align: center;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f2f5;
        }
        
        .user-position {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .position-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .rank-badge {
            font-size: 1.5rem;
            font-weight: bold;
            min-width: 40px;
            text-align: center;
        }
        
        .rank-1 {
            color: var(--gold);
            text-shadow: 0 0 3px rgba(0,0,0,0.3);
        }
        
        .rank-2 {
            color: var(--silver);
        }
        
        .rank-3 {
            color: var(--bronze);
        }
        
        .user-stats {
            display: flex;
            gap: 20px;
        }
        
        .stat {
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.2rem;
            font-weight: bold;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .leaderboard-list {
            list-style: none;
        }
        
        .leaderboard-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
            transition: all 0.3s;
        }
        
        .leaderboard-item:hover {
            background-color: #f8f9fa;
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-radius: 8px;
        }
        
        .item-rank {
            font-size: 1.2rem;
            font-weight: bold;
            width: 40px;
            text-align: center;
        }
        
        .rank-1-bg {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: white;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .rank-2-bg {
            background: linear-gradient(135deg, #C0C0C0, #A9A9A9);
            color: white;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .rank-3-bg {
            background: linear-gradient(135deg, #CD7F32, #8B4513);
            color: white;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .item-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 15px;
            border: 2px solid #ddd;
        }
        
        .item-details {
            flex-grow: 1;
        }
        
        .item-name {
            font-weight: 500;
            margin-bottom: 3px;
        }
        
        .item-stats {
            display: flex;
            gap: 15px;
            font-size: 0.9rem;
            color: #666;
        }
        
        .item-pain {
            font-weight: bold;
            color: var(--primary);
            min-width: 80px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 30px;
            gap: 5px;
        }
        
        .pagination a, .pagination span {
            display: inline-block;
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-decoration: none;
            color: var(--primary);
        }
        
        .pagination a:hover {
            background: var(--primary);
            color: white;
        }
        
        .pagination .current {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .pagination .disabled {
            color: #ccc;
            pointer-events: none;
        }
        
        .filter-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .filter-options {
            display: flex;
            gap: 10px;
        }
        
        .filter-btn {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .filter-btn:hover, .filter-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .search-box {
            position: relative;
        }
        
        .search-box input {
            padding: 8px 15px 8px 35px;
            border: 1px solid #ddd;
            border-radius: 5px;
            width: 250px;
        }
        
        .search-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }
        
        .current-user-highlight {
            background: linear-gradient(135deg, rgba(67, 97, 238, 0.1), rgba(63, 55, 201, 0.1));
            border-left: 4px solid var(--primary);
        }
        
        .top-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: bold;
            margin-left: 5px;
            vertical-align: middle;
        }
        
        .badge-gold {
            background: var(--gold);
            color: #333;
        }
        
        .badge-silver {
            background: var(--silver);
            color: #333;
        }
        
        .badge-bronze {
            background: var(--bronze);
            color: white;
        }
    </style>
    <script>
        function searchUsers() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('.leaderboard-item');
            
            rows.forEach(row => {
                const name = row.querySelector('.item-name').textContent.toLowerCase();
                if (name.includes(searchTerm)) {
                    row.style.display = 'flex';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</head>
<body>
    <div class="container">
        <?php 
        $current_page = 'klasemen';
        include '../includes/navbar.php'; 
        ?>
        
        <div class="card">
            <h1 class="card-title">🏆 Klasemen Produktivitas</h1>
            
            <!-- Posisi User Saat Ini -->
            <div class="user-position">
                <div class="position-info">
                    <div class="rank-badge">#<?= $user_rank ?></div>
                    <div>
                        <h3>Posisi Anda</h3>
                        <p>Terus tingkatkan produktivitas untuk naik peringkat!</p>
                    </div>
                </div>
                <div class="user-stats">
                    <div class="stat">
                        <div class="stat-value"><?= $current_user['total_pain'] ?></div>
                        <div class="stat-label">Total Pain</div>
                    </div>
                    <div class="stat">
                        <div class="stat-value"><?= $current_user['jumlah_reward'] ?></div>
                        <div class="stat-label">Reward</div>
                    </div>
                </div>
            </div>
            
            <!-- Filter dan Pencarian -->
            <div class="filter-section">
                <div class="filter-options">
                    <button class="filter-btn active">Semua</button>
                    <button class="filter-btn">Minggu Ini</button>
                    <button class="filter-btn">Bulan Ini</button>
                </div>
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="searchInput" placeholder="Cari pengguna..." onkeyup="searchUsers()">
                </div>
            </div>
            
            <!-- Daftar Leaderboard -->
            <ul class="leaderboard-list">
                <?php foreach ($leaderboard as $index => $user): 
                    $is_current_user = $user['id'] == $user_id;
                    $rank_class = '';
                    $rank_badge = '';
                    
                    if ($user['peringkat'] == 1) {
                        $rank_class = 'rank-1';
                        $rank_badge = '<span class="top-badge badge-gold"><i class="fas fa-crown"></i> Juara 1</span>';
                    } elseif ($user['peringkat'] == 2) {
                        $rank_class = 'rank-2';
                        $rank_badge = '<span class="top-badge badge-silver">Juara 2</span>';
                    } elseif ($user['peringkat'] == 3) {
                        $rank_class = 'rank-3';
                        $rank_badge = '<span class="top-badge badge-bronze">Juara 3</span>';
                    }
                ?>
                    <li class="leaderboard-item <?= $is_current_user ? 'current-user-highlight' : '' ?>">
                        <div class="item-rank">
                            <?php if ($user['peringkat'] <= 3): ?>
                                <div class="rank-<?= $user['peringkat'] ?>-bg">
                                    <?= $user['peringkat'] ?>
                                </div>
                            <?php else: ?>
                                <?= $user['peringkat'] ?>
                            <?php endif; ?>
                        </div>
                        
                        <img src="../foto_profil/<?= $user['foto_profil'] ?>" alt="Avatar" class="item-avatar">
                        
                        <div class="item-details">
                            <div class="item-name">
                                <?= htmlspecialchars($user['nama']) ?>
                                <?= $rank_badge ?>
                                <?php if ($is_current_user): ?>
                                    <span style="color: var(--primary);">(Anda)</span>
                                <?php endif; ?>
                            </div>
                            <div class="item-stats">
                                <div>Reward: <?= $user['jumlah_reward'] ?></div>
                                <div>Level: <?= getLevel($user['jumlah_reward']) ?></div>
                            </div>
                        </div>
                        
                        <div class="item-pain">
                            <?= $user['total_pain'] ?> pain
                        </div>
                        
                        <a href="lihat_profil.php?id=<?= $user['id'] ?>" class="btn" style="padding: 5px 15px;">
                            Lihat Profil
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            
            <!-- Pagination -->
            <div class="pagination">
                <?php if ($current_page > 1): ?>
                    <a href="?page=<?= $current_page - 1 ?>">&laquo; Sebelumnya</a>
                <?php else: ?>
                    <span class="disabled">&laquo; Sebelumnya</span>
                <?php endif; ?>
                
                <?php 
                $start_page = max(1, $current_page - 2);
                $end_page = min($total_pages, $current_page + 2);
                
                if ($start_page > 1): ?>
                    <a href="?page=1">1</a>
                    <?php if ($start_page > 2): ?>
                        <span>...</span>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <?php if ($i == $current_page): ?>
                        <span class="current"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?page=<?= $i ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($end_page < $total_pages): ?>
                    <?php if ($end_page < $total_pages - 1): ?>
                        <span>...</span>
                    <?php endif; ?>
                    <a href="?page=<?= $total_pages ?>"><?= $total_pages ?></a>
                <?php endif; ?>
                
                <?php if ($current_page < $total_pages): ?>
                    <a href="?page=<?= $current_page + 1 ?>">Berikutnya &raquo;</a>
                <?php else: ?>
                    <span class="disabled">Berikutnya &raquo;</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>