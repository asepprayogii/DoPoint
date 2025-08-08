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
    
    // 1. NOTIFIKASI OTOMATIS
    $notif_judul = "Reward Diklaim! 🎉";
    $notif_isi = "Anda telah berhasil mengklaim reward: " . $target['hadiah'] . ". Selamat menikmati!";
    
    $pdo->prepare("INSERT INTO notifikasi (user_id, jenis, judul, isi) VALUES (?, 'reward', ?, ?)")
        ->execute([$user_id, $notif_judul, $notif_isi]);
    
    // 2. AUTO RESET TARGET (Tingkatkan 50%)
    $new_target_pain = (int)($target['target_pain'] * 1.5);
    $new_hadiah = "Hadiah Level Berikutnya - " . $target['hadiah'];
    
    $pdo->prepare("INSERT INTO target_reward (user_id, target_pain, hadiah, status) VALUES (?, ?, ?, 'aktif')")
        ->execute([$user_id, $new_target_pain, $new_hadiah]);
    
    // 3. BADGE/LEVEL SYSTEM
    $total_rewards = $pdo->prepare("SELECT COUNT(*) FROM reward_claim WHERE user_id = ? AND status_verifikasi = 'diterima'");
    $total_rewards->execute([$user_id]);
    $reward_count = $total_rewards->fetchColumn();
    
    // Tentukan level berdasarkan jumlah reward
    if ($reward_count >= 10) {
        $level = 'Master';
    } elseif ($reward_count >= 5) {
        $level = 'Pejuang';
    } else {
        $level = 'Pemula';
    }
    
    // Update level user
    $pdo->prepare("UPDATE users SET level = ? WHERE id = ?")->execute([$level, $user_id]);
    
    // Commit transaksi
    $pdo->commit();
    
    // Tambahkan aktivitas ke riwayat
    $aktivitas = "Mengklaim reward: " . $target['hadiah'];
    $pdo->prepare("INSERT INTO aktivitas_terbaru (user_id, aktivitas) VALUES (?, ?)")
        ->execute([$user_id, $aktivitas]);
    
    // Notifikasi level up jika berubah
    if ($reward_count >= 5 && $reward_count < 10) {
        $level_notif = "Selamat! Anda telah mencapai level 'Pejuang'! 🏆";
        $pdo->prepare("INSERT INTO notifikasi (user_id, jenis, judul, isi) VALUES (?, 'reward', ?, ?)")
            ->execute([$user_id, "Level Up!", $level_notif]);
    } elseif ($reward_count >= 10) {
        $level_notif = "Selamat! Anda telah mencapai level 'Master'! 👑";
        $pdo->prepare("INSERT INTO notifikasi (user_id, jenis, judul, isi) VALUES (?, 'reward', ?, ?)")
            ->execute([$user_id, "Level Up!", $level_notif]);
    }
    
    $_SESSION['success'] = 'Reward berhasil diklaim! Selamat menikmati ' . $target['hadiah'] . '! Target baru telah dibuat otomatis.';
    
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = 'Terjadi kesalahan: ' . $e->getMessage();
}

redirect('dashboard.php');