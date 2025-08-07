<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_admin()) {
    redirect('../login.php');
}

$error = '';
$success = '';

// Proses pengiriman notifikasi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = trim($_POST['judul']);
    $isi = trim($_POST['isi']);
    
    if (empty($judul) || empty($isi)) {
        $error = 'Judul dan isi notifikasi harus diisi!';
    } else {
        // Ambil semua user
        $users = $pdo->query("SELECT id FROM users")->fetchAll(PDO::FETCH_COLUMN);
        
        // Mulai transaksi
        $pdo->beginTransaction();
        
        try {
            foreach ($users as $user_id) {
                $stmt = $pdo->prepare("INSERT INTO notifikasi (user_id, jenis, judul, isi) VALUES (?, 'global', ?, ?)");
                $stmt->execute([$user_id, $judul, $isi]);
            }
            
            // Commit transaksi
            $pdo->commit();
            
            $success = 'Notifikasi berhasil dikirim ke semua pengguna!';
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Gagal mengirim notifikasi: ' . $e->getMessage();
        }
    }
}

// Ambil riwayat notifikasi global
$notifikasi = $pdo->query("
    SELECT * FROM notifikasi 
    WHERE jenis = 'global'
    ORDER BY created_at DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Notifikasi Global - Admin</title>
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
        .form-container, .history-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }
        .form-group textarea {
            min-height: 150px;
        }
        .notification-item {
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 8px;
            margin-bottom: 15px;
            background: #f8f9fa;
        }
        .notification-title {
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--primary);
        }
        .notification-date {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 10px;
        }
        .no-notification {
            text-align: center;
            padding: 20px;
            color: #666;
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
        @media (max-width: 700px) {
            .header, .container {
                flex-direction: column;
                padding: 10px;
            }
            .form-container, .history-container {
                padding: 10px;
            }
            .btn {
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
                <img src="../foto_profil/<?= $_SESSION['foto_profil'] ?>" alt="Avatar" class="admin-avatar">
                <div>
                    <h2>Admin: <?= $_SESSION['nama'] ?></h2>
                    <p>Notifikasi Global</p>
                </div>
            </div>
            
            <nav class="nav-menu">
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="kelola_kategori.php" class="nav-link">Kategori</a>
                <a href="data_user.php" class="nav-link">Data User</a>
                <a href="statistik.php" class="nav-link">Statistik</a>
                <a href="verifikasi_reward.php" class="nav-link">Verifikasi Reward</a>
                <a href="notifikasi.php" class="nav-link active">Notifikasi</a>
                <a href="../logout.php" class="nav-link">Logout</a>
            </nav>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>
        
        <div class="form-container">
            <h3 class="section-title">Kirim Notifikasi Global</h3>
            <form method="POST">
                <div class="form-group">
                    <label for="judul">Judul Notifikasi *</label>
                    <input type="text" id="judul" name="judul" required>
                </div>
                
                <div class="form-group">
                    <label for="isi">Isi Notifikasi *</label>
                    <textarea id="isi" name="isi" required></textarea>
                </div>
                
                <button type="submit" class="btn">Kirim ke Semua Pengguna</button>
            </form>
        </div>
        
        <div class="history-container">
            <h3 class="section-title">Riwayat Notifikasi</h3>
            
            <?php if (empty($notifikasi)): ?>
                <div class="no-notification">
                    <p>Belum ada notifikasi yang dikirim</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifikasi as $notif): ?>
                    <div class="notification-item">
                        <div class="notification-title"><?= htmlspecialchars($notif['judul']) ?></div>
                        <div><?= nl2br(htmlspecialchars($notif['isi'])) ?></div>
                        <div class="notification-date">
                            Dikirim pada: <?= date('d M Y H:i', strtotime($notif['created_at'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>