<?php
// Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Parameter untuk menentukan halaman aktif
$current_page = $current_page ?? 'dashboard';
$user_role = $_SESSION['role'] ?? 'user';
$user_name = $_SESSION['nama'] ?? 'User';
$user_avatar = $_SESSION['foto_profil'] ?? 'default.jpg';
?>

<style>
    .navbar {
        background: white;
        padding: 15px 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
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
    
    .user-details h2 {
        margin: 0;
        font-size: 1.2rem;
        color: var(--dark);
    }
    
    .user-details p {
        margin: 0;
        font-size: 0.9rem;
        color: #666;
    }
    
    .nav-menu {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }
    
    .nav-link {
        text-decoration: none;
        color: var(--dark);
        font-weight: 500;
        padding: 8px 15px;
        border-radius: 5px;
        transition: all 0.3s;
        font-size: 0.9rem;
    }
    
    .nav-link:hover, .nav-link.active {
        background-color: var(--primary);
        color: white;
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
        .navbar {
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
    }
    
    @media (max-width: 480px) {
        .navbar {
            padding: 10px 15px;
        }
        
        .nav-link {
            padding: 8px 12px;
            font-size: 0.85rem;
        }
    }
</style>

<div class="navbar">
    <div class="user-info">
        <img src="../foto_profil/<?= htmlspecialchars($user_avatar) ?>" alt="Avatar" class="user-avatar">
        <div class="user-details">
            <h2><?= htmlspecialchars($user_name) ?></h2>
            <p><?= ucfirst($user_role) ?></p>
        </div>
    </div>
    
    <nav class="nav-menu">
        <?php if ($user_role === 'admin'): ?>
            <!-- Admin Navigation -->
            <a href="../admin/dashboard.php" class="nav-link <?= $current_page === 'admin_dashboard' ? 'active' : '' ?>">Dashboard</a>
            <a href="../admin/kelola_kategori.php" class="nav-link <?= $current_page === 'kelola_kategori' ? 'active' : '' ?>">Kategori</a>
            <a href="../admin/data_user.php" class="nav-link <?= $current_page === 'data_user' ? 'active' : '' ?>">Data User</a>
            <a href="../admin/statistik.php" class="nav-link <?= $current_page === 'statistik' ? 'active' : '' ?>">Statistik</a>
            <a href="../admin/verifikasi_reward.php" class="nav-link <?= $current_page === 'verifikasi_reward' ? 'active' : '' ?>">Verifikasi Reward</a>
            <a href="../admin/notifikasi.php" class="nav-link <?= $current_page === 'admin_notifikasi' ? 'active' : '' ?>">Notifikasi</a>
        <?php else: ?>
            <!-- User Navigation -->
            <a href="../user/dashboard.php" class="nav-link <?= $current_page === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
            <a href="../user/buat_misi.php" class="nav-link <?= $current_page === 'buat_misi' ? 'active' : '' ?>">Buat Misi</a>
            <a href="../user/set_target.php" class="nav-link <?= $current_page === 'set_target' ? 'active' : '' ?>">Target Reward</a>
            <a href="../user/klasemen.php" class="nav-link <?= $current_page === 'klasemen' ? 'active' : '' ?>">Leaderboard</a>
            <a href="../user/notifikasi.php" class="nav-link <?= $current_page === 'notifikasi' ? 'active' : '' ?>">Notifikasi</a>
            <a href="../user/riwayat_reward.php" class="nav-link <?= $current_page === 'riwayat_reward' ? 'active' : '' ?>">Riwayat Reward</a>
        <?php endif; ?>
        
        <a href="../profil.php" class="nav-link <?= $current_page === 'profil' ? 'active' : '' ?>">Profil</a>
        <a href="logout.php" class="nav-link">Logout</a>
    </nav>
</div>
