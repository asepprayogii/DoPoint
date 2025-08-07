<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];
$misi_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($misi_id <= 0) {
    $_SESSION['error'] = 'ID misi tidak valid!';
    redirect('dashboard.php');
}

// Ambil data misi
$stmt = $pdo->prepare("SELECT * FROM misi WHERE id = ? AND user_id = ? AND status = 'aktif'");
$stmt->execute([$misi_id, $user_id]);
$misi = $stmt->fetch();

if (!$misi) {
    $_SESSION['error'] = 'Misi tidak ditemukan atau sudah selesai!';
    redirect('dashboard.php');
}

// Mulai transaksi
$pdo->beginTransaction();

try {
    // Update status misi menjadi selesai
    $update_misi = $pdo->prepare("UPDATE misi SET status = 'selesai', completed_at = NOW() WHERE id = ?");
    $update_misi->execute([$misi_id]);
    
    // Tambahkan pain ke total_pain user
    $update_user = $pdo->prepare("UPDATE users SET total_pain = total_pain + ? WHERE id = ?");
    $update_user->execute([$misi['nilai_pain'], $user_id]);
    
    // Commit transaksi
    $pdo->commit();
    
    // Tambahkan aktivitas ke riwayat
    $aktivitas = "Menyelesaikan misi: " . $misi['nama_misi'];
    $pdo->prepare("INSERT INTO aktivitas_terbaru (user_id, aktivitas) VALUES (?, ?)")
        ->execute([$user_id, $aktivitas]);
    
    $_SESSION['success'] = 'Misi berhasil ditandai selesai! Pain point telah ditambahkan.';
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = 'Terjadi kesalahan: ' . $e->getMessage();
}

redirect('dashboard.php');