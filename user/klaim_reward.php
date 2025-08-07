<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];
$target_id = isset($_GET['target_id']) ? (int)$_GET['target_id'] : 0;

if ($target_id <= 0) {
    $_SESSION['error'] = 'ID target tidak valid!';
    redirect('dashboard.php');
}

// Ambil data target
$stmt = $pdo->prepare("SELECT * FROM target_reward WHERE id = ? AND user_id = ? AND status = 'tercapai'");
$stmt->execute([$target_id, $user_id]);
$target = $stmt->fetch();

if (!$target) {
    $_SESSION['error'] = 'Target tidak ditemukan atau belum tercapai!';
    redirect('dashboard.php');
}

$pdo->beginTransaction();
try {
    // Update status target menjadi "diklaim"
    $update_target = $pdo->prepare("UPDATE target_reward SET status = 'diklaim' WHERE id = ?");
    $update_target->execute([$target_id]);
    // Simpan ke riwayat klaim reward
    $stmt = $pdo->prepare("INSERT INTO reward_claim (user_id, target_id, hadiah) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $target_id, $target['hadiah']]);
    // Commit transaksi
    $pdo->commit();
    // Tambahkan aktivitas ke riwayat
    $aktivitas = "Mengklaim reward: " . $target['hadiah'];
    $pdo->prepare("INSERT INTO aktivitas_terbaru (user_id, aktivitas) VALUES (?, ?)")
        ->execute([$user_id, $aktivitas]);
    $_SESSION['success'] = 'Reward berhasil diklaim! Selamat menikmati ' . $target['hadiah'] . '!';
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = 'Terjadi kesalahan: ' . $e->getMessage();
}
redirect('dashboard.php');