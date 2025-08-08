<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Mark as read
if (isset($_POST['mark_read'])) {
    $notif_id = (int)$_POST['notif_id'];
    $stmt = $pdo->prepare("UPDATE notifikasi SET dibaca = 1 WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$notif_id, $user_id])) {
        $success = 'Notifikasi ditandai sebagai dibaca!';
    } else {
        $error = 'Gagal menandai notifikasi!';
    }
}

// Mark all as read
if (isset($_POST['mark_all_read'])) {
    $stmt = $pdo->prepare("UPDATE notifikasi SET dibaca = 1 WHERE user_id = ?");
    if ($stmt->execute([$user_id])) {
        $success = 'Semua notifikasi ditandai sebagai dibaca!';
    } else {
        $error = 'Gagal menandai notifikasi!';
    }
}

// Ambil semua notifikasi
$notifikasi = $pdo->prepare("SELECT * FROM notifikasi WHERE user_id = ? ORDER BY created_at DESC");
$notifikasi->execute([$user_id]);

// Hitung notifikasi yang belum dibaca
$unread_count = $pdo->prepare("SELECT COUNT(*) FROM notifikasi WHERE user_id = ? AND dibaca = 0");
$unread_count->execute([$user_id]);
$unread = $unread_count->fetchColumn();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi - Aplikasi Produktivitas</title>
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --warning: #f72585;
            --light: #f8f9fa;
            --dark: #212529;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f0f2f5;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 800px;
            margin: 50px auto;
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
        
        .nav-menu {
            display: flex;
            gap: 15px;
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
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .card-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: var(--primary);
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f2f5;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .btn {
            display: inline-block;
            background: var(--primary);
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .btn:hover {
            background: var(--secondary);
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
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
        
        .notification-item {
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            border-left: 4px solid var(--primary);
            transition: all 0.3s;
        }
        
        .notification-item.unread {
            background: #e3f2fd;
            border-left-color: var(--warning);
        }
        
        .notification-item.read {
            background: #f8f9fa;
        }
        
        .notification-item:hover {
            transform: translateX(5px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }
        
        .notification-title {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
        }
        
        .notification-content {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 8px;
        }
        
        .notification-time {
            font-size: 0.8rem;
            color: #999;
        }
        
        .notification-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                margin: 20px auto;
                padding: 15px;
            }
            
            .header {
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
            
            .card {
                padding: 20px;
            }
            
            .notification-item {
                padding: 12px;
            }
        }
        
        @media (max-width: 480px) {
            .container {
                margin: 10px auto;
                padding: 10px;
            }
            
            .card {
                padding: 15px;
            }
            
            .nav-link {
                padding: 8px 12px;
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="user-info">
                <img src="../foto_profil/<?= $_SESSION['foto_profil'] ?? 'default.jpg' ?>" alt="Avatar" class="user-avatar">
                <div>
                    <h2><?= $_SESSION['nama'] ?></h2>
                    <p>Notifikasi (<?= $unread ?> belum dibaca)</p>
                </div>
            </div>
            
            <nav class="nav-menu">
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="notifikasi.php" class="nav-link active">Notifikasi</a>
                <a href="../profil.php" class="nav-link">Profil</a>
                <a href="../logout.php" class="nav-link">Logout</a>
            </nav>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-title">
                <span>Semua Notifikasi</span>
                <?php if ($unread > 0): ?>
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="mark_all_read" class="btn btn-secondary">
                            Tandai Semua Dibaca
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            
            <?php if ($notifikasi->rowCount() > 0): ?>
                <?php while ($notif = $notifikasi->fetch()): ?>
                    <div class="notification-item <?= $notif['dibaca'] ? 'read' : 'unread' ?>">
                        <div class="notification-header">
                            <div>
                                <div class="notification-title"><?= htmlspecialchars($notif['judul']) ?></div>
                                <div class="notification-content"><?= htmlspecialchars($notif['isi']) ?></div>
                                <div class="notification-time">
                                    <?= date('d M Y H:i', strtotime($notif['created_at'])) ?>
                                </div>
                            </div>
                            <?php if (!$notif['dibaca']): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="notif_id" value="<?= $notif['id'] ?>">
                                    <button type="submit" name="mark_read" class="btn btn-secondary" style="font-size: 0.8rem;">
                                        Tandai Dibaca
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div style="font-size: 3rem; margin-bottom: 15px; opacity: 0.5;">📭</div>
                    <h3>Tidak Ada Notifikasi</h3>
                    <p>Belum ada notifikasi untuk Anda. Notifikasi akan muncul saat ada aktivitas penting.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="dashboard.php" class="btn btn-secondary">&larr; Kembali ke Dashboard</a>
        </div>
    </div>
</body>
</html>
