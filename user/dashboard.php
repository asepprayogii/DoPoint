<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];

// Ambil data user
$stmt = $pdo->prepare("SELECT total_pain, foto_profil FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Ambil misi aktif
$misi_aktif = $pdo->prepare("SELECT * FROM misi WHERE user_id = ? AND status = 'aktif'");
$misi_aktif->execute([$user_id]);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard User</title>
</head>
<body>
    <h1>Dashboard User</h1>
    <p>Halo, <?= $_SESSION['nama'] ?></p>
    <p>Total Pain: <?= $user['total_pain'] ?></p>
    
    <h2>Misi Aktif</h2>
    <?php if ($misi_aktif->rowCount() > 0): ?>
        <ul>
            <?php while ($misi = $misi_aktif->fetch()): ?>
                <li>
                    <?= $misi['nama_misi'] ?> 
                    (<?= $misi['nilai_pain'] ?> pain)
                    <a href="selesai_misi.php?id=<?= $misi['id'] ?>">Tandai Selesai</a>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>Belum ada misi aktif</p>
    <?php endif; ?>
    
    <p><a href="buat_misi.php">Buat Misi Baru</a></p>
    <p><a href="set_target.php">Set Target Reward</a></p>
    <p><a href="klasemen.php">Lihat Klasemen</a></p>
    
    <p><a href="../logout.php">Logout</a></p>
</body>
</html>