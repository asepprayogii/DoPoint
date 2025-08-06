<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
include 'koneksi.php';

$user_id = $_SESSION['user_id'];

// Ambil data user dan poin
$queryUser = mysqli_query($kon, "SELECT username, total_point FROM users WHERE id='$user_id'");
$dataUser = mysqli_fetch_assoc($queryUser);

// Ambil misi hari ini
$tanggal_hari_ini = date('Y-m-d');
$queryMisi = mysqli_query($kon, "SELECT * FROM missions WHERE user_id='$user_id' AND tanggal='$tanggal_hari_ini' ORDER BY id DESC");

// Hitung progress ke target (misal target 1000 point)
$target = 1000;
$progress = min(100, ($dataUser['total_point'] / $target) * 100);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .progress { background: #eee; border-radius: 10px; overflow: hidden; height: 20px; margin: 10px 0; }
        .progress-bar { background: green; height: 100%; width: <?= $progress ?>%; }
        .misi { border: 1px solid #ccc; padding: 10px; margin: 10px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <h2>Selamat datang, <?= htmlspecialchars($dataUser['username']) ?>!</h2>
    <p>Total Poin: <strong><?= $dataUser['total_point'] ?></strong> / <?= $target ?></p>

    <div class="progress">
        <div class="progress-bar"></div>
    </div>

    <h3>Misi Hari Ini (<?= $tanggal_hari_ini ?>)</h3>
    <?php while ($misi = mysqli_fetch_assoc($queryMisi)) { ?>
        <div class="misi">
            <strong><?= htmlspecialchars($misi['nama']) ?></strong><br>
            Kategori: <?= htmlspecialchars($misi['kategori']) ?><br>
            Poin: <?= $misi['point'] ?><br>
            Status: <?= $misi['status'] ?>
        </div>
    <?php } ?>

    <p><a href="tambah_misi.php">+ Tambah Misi Baru</a></p>
    <p><a href="logout.php">Logout</a></p>
</body>
</html>
