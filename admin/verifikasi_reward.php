<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_admin()) {
    redirect('../login.php');
}

$error = '';
$success = '';

// Proses verifikasi
if (isset($_POST['verifikasi'])) {
    $id = (int)$_POST['id'];
    $status = $_POST['status'];
    $catatan = trim($_POST['catatan']);
            $stmt = $pdo->prepare("UPDATE reward_claim SET status_verifikasi = ?, catatan = ?, verified_at = NOW() WHERE id = ?");
        if ($stmt->execute([$status, $catatan, $id])) {
            $success = 'Status reward berhasil diperbarui!';
            
            // Ambil data reward untuk notifikasi
            $stmt_reward = $pdo->prepare("SELECT user_id, hadiah FROM reward_claim WHERE id = ?");
            $stmt_reward->execute([$id]);
            $reward = $stmt_reward->fetch();
            
            // Kirim notifikasi ke user
            if ($status === 'diterima') {
                $notif_judul = "Reward Diterima! 🎉";
                $notif_isi = "Selamat! Reward Anda '{$reward['hadiah']}' telah diterima oleh admin. Selamat menikmati!";
                
                // Tambahkan aktivitas
                $aktivitas = "Reward diterima: " . $reward['hadiah'];
                $pdo->prepare("INSERT INTO aktivitas_terbaru (user_id, aktivitas) VALUES (?, ?)")
                    ->execute([$reward['user_id'], $aktivitas]);
            } else {
                $notif_judul = "Reward Ditolak";
                $notif_isi = "Reward Anda '{$reward['hadiah']}' ditolak oleh admin.";
                if (!empty($catatan)) {
                    $notif_isi .= " Catatan: " . $catatan;
                }
            }
            
            // Insert notifikasi
            $pdo->prepare("INSERT INTO notifikasi (user_id, jenis, judul, isi) VALUES (?, 'reward', ?, ?)")
                ->execute([$reward['user_id'], $notif_judul, $notif_isi]);
                
        } else {
            $error = 'Gagal memperbarui status reward.';
        }
}
// Ambil semua reward yang perlu diverifikasi
$rewards = $pdo->query("SELECT rc.*, u.nama as user_nama, u.email, u.foto_profil, tr.target_pain FROM reward_claim rc JOIN users u ON rc.user_id = u.id JOIN target_reward tr ON rc.target_id = tr.id WHERE rc.status_verifikasi = 'pending' ORDER BY rc.claimed_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Verifikasi Reward - Admin</title>
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
        .reward-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border-left: 4px solid #ffc107;
            transition: box-shadow 0.3s, transform 0.3s;
        }
        .reward-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
            transform: translateY(-3px);
        }
        .reward-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
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
        }
        .reward-details {
            margin: 15px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .detail-item {
            margin-bottom: 10px;
            display: flex;
        }
        .detail-label {
            font-weight: 500;
            width: 150px;
        }
        .verifikasi-form {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            min-height: 100px;
        }
        .status-options {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        .status-option {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .btn-group {
            display: flex;
            gap: 10px;
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
        .btn-accept {
            background: #4caf50;
        }
        .btn-accept:hover {
            background: #388e3c;
        }
        .btn-reject {
            background: #f44336;
        }
        .btn-reject:hover {
            background: #b71c1c;
        }
        .no-rewards {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        .no-rewards i {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #4caf50;
        }
        @media (max-width: 700px) {
            .header, .container {
                flex-direction: column;
                padding: 10px;
            }
            .reward-card, .reward-details {
                padding: 10px;
            }
            .btn, .btn-accept, .btn-reject {
                width: 100%;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="admin-info">
                <img src="../foto_profil/<?= htmlspecialchars($_SESSION['foto_profil']) ?>" alt="Avatar" class="admin-avatar">
                <div>
                    <h2>Admin: <?= htmlspecialchars($_SESSION['nama']) ?></h2>
                    <p>Verifikasi Reward</p>
                </div>
            </div>
            
            <nav class="nav-menu">
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="kelola_kategori.php" class="nav-link">Kategori</a>
                <a href="data_user.php" class="nav-link">Data User</a>
                <a href="statistik.php" class="nav-link">Statistik</a>
                <a href="verifikasi_reward.php" class="nav-link active">Verifikasi Reward</a>
                <a href="notifikasi.php" class="nav-link">Notifikasi</a>
                <a href="../logout.php" class="nav-link">Logout</a>
            </nav>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if (empty($rewards)): ?>
            <div class="no-rewards">
                <i class="fas fa-check-circle"></i>
                <h3>Tidak Ada Reward yang Perlu Diverifikasi</h3>
                <p>Semua klaim reward sudah diverifikasi.</p>
            </div>
        <?php else: ?>
            <?php foreach ($rewards as $reward): ?>
                <div class="reward-card">
                    <div class="reward-header">
                        <div class="user-info">
                            <img src="../foto_profil/<?= htmlspecialchars($reward['foto_profil']) ?>" alt="Avatar" class="user-avatar">
                            <div>
                                <h3><?= htmlspecialchars($reward['user_nama']) ?></h3>
                                <p><?= htmlspecialchars($reward['email']) ?></p>
                            </div>
                        </div>
                        <div>
                            <span class="user-status status-active">Menunggu Verifikasi</span>
                        </div>
                    </div>
                    
                    <div class="reward-details">
                        <div class="detail-item">
                            <div class="detail-label">Hadiah:</div>
                            <div><strong><?= htmlspecialchars($reward['hadiah']) ?></strong></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Target Pain:</div>
                            <div><?= $reward['target_pain'] ?> pain</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Diklaim pada:</div>
                            <div><?= date('d M Y H:i', strtotime($reward['claimed_at'])) ?></div>
                        </div>
                    </div>
                    
                    <div class="verifikasi-form">
                        <form method="POST">
                            <input type="hidden" name="id" value="<?= $reward['id'] ?>">
                            
                            <div class="status-options">
                                <div class="status-option">
                                    <input type="radio" id="status_diterima_<?= $reward['id'] ?>" name="status" value="diterima" required>
                                    <label for="status_diterima_<?= $reward['id'] ?>">Diterima</label>
                                </div>
                                <div class="status-option">
                                    <input type="radio" id="status_ditolak_<?= $reward['id'] ?>" name="status" value="ditolak">
                                    <label for="status_ditolak_<?= $reward['id'] ?>">Ditolak</label>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="catatan_<?= $reward['id'] ?>">Catatan (Opsional):</label>
                                <textarea id="catatan_<?= $reward['id'] ?>" name="catatan" placeholder="Berikan catatan jika perlu..."></textarea>
                            </div>
                            
                            <div class="btn-group">
                                <button type="submit" name="verifikasi" class="btn btn-accept">Simpan Verifikasi</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>