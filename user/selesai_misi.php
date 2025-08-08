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
    
    // Ambil total pain terbaru
    $stmt_total = $pdo->prepare("SELECT total_pain FROM users WHERE id = ?");
    $stmt_total->execute([$user_id]);
    $total_pain_baru = $stmt_total->fetchColumn();
    
    // Cek apakah ada target yang tercapai
    $stmt_target = $pdo->prepare("SELECT * FROM target_reward WHERE user_id = ? AND status = 'aktif' AND target_pain <= ?");
    $stmt_target->execute([$user_id, $total_pain_baru]);
    $target_tercapai = $stmt_target->fetch();
    
    if ($target_tercapai) {
        // Update status target menjadi tercapai
        $update_target = $pdo->prepare("UPDATE target_reward SET status = 'tercapai', tercapai_at = NOW() WHERE id = ?");
        $update_target->execute([$target_tercapai['id']]);
        
        // Kirim notifikasi target tercapai
        $notif_judul = "Target Reward Tercapai! 🎉";
        $notif_isi = "Selamat! Anda telah mencapai target {$target_tercapai['target_pain']} pain points. Hadiah '{$target_tercapai['hadiah']}' siap diklaim!";
        
        $pdo->prepare("INSERT INTO notifikasi (user_id, jenis, judul, isi) VALUES (?, 'reward', ?, ?)")
            ->execute([$user_id, $notif_judul, $notif_isi]);
        
        // Tambahkan aktivitas target tercapai
        $aktivitas_target = "Target tercapai: {$target_tercapai['hadiah']} ({$target_tercapai['target_pain']} pain)";
        $pdo->prepare("INSERT INTO aktivitas_terbaru (user_id, aktivitas) VALUES (?, ?)")
            ->execute([$user_id, $aktivitas_target]);
        
        $_SESSION['success'] = 'Misi berhasil ditandai selesai! Pain point telah ditambahkan. Target reward Anda telah tercapai! 🎉';
    } else {
        $_SESSION['success'] = 'Misi berhasil ditandai selesai! Pain point telah ditambahkan.';
    }
    
    // Tambahkan aktivitas ke riwayat
    $aktivitas = "Menyelesaikan misi: " . $misi['nama_misi'];
    $pdo->prepare("INSERT INTO aktivitas_terbaru (user_id, aktivitas) VALUES (?, ?)")
        ->execute([$user_id, $aktivitas]);
    
    // Commit transaksi
    $pdo->commit();
    
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = 'Terjadi kesalahan: ' . $e->getMessage();
}

redirect('dashboard.php');