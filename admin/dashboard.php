<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_admin()) {
    redirect('../login.php');
}

// Query statistik
$total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$total_misi = $pdo->query("SELECT COUNT(*) FROM misi")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin</title>
</head>
<body>
    <h1>Dashboard Admin</h1>
    <p>Halo, <?= $_SESSION['nama'] ?> (Admin)</p>
    
    <h2>Statistik Umum</h2>
    <ul>
        <li>Total Pengguna: <?= $total_users ?></li>
        <li>Total Misi: <?= $total_misi ?></li>
    </ul>
    
    <h2>Menu Admin:</h2>
    <ul>
        <li><a href="kelola_kategori.php">Kelola Kategori Misi</a></li>
        <li><a href="data_user.php">Lihat Data User</a></li>
        <li><a href="statistik.php">Statistik Lengkap</a></li>
        <li><a href="verifikasi_reward.php">Verifikasi Reward</a></li>
    </ul>
    
    <p><a href="../logout.php">Logout</a></p>
</body>
</html>