<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_admin()) {
    redirect('../login.php');
}

// Ambil semua user
$users = $pdo->query("SELECT * FROM users WHERE role = 'user' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data User - Admin</title>
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
        .section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .user-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .user-table th, .user-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .user-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .user-table tbody tr:hover {
            background: #f1f3fa;
        }
        .user-avatar-small {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        .user-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        .status-active {
            background: #e8f5e9;
            color: #2e7d32;
        }
        .status-inactive {
            background: #ffebee;
            color: #c62828;
        }
        .action-link {
            color: var(--primary);
            text-decoration: none;
            margin-right: 10px;
            transition: color 0.2s;
        }
        .action-link:hover {
            text-decoration: underline;
            color: var(--secondary);
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
        .search-bar {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        .search-bar input {
            flex-grow: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }
        .pagination a, .pagination span {
            display: inline-block;
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-decoration: none;
        }
        .pagination a:hover {
            background: #f0f2f5;
        }
        .pagination .current {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        @media (max-width: 700px) {
            .header, .container {
                flex-direction: column;
                padding: 10px;
            }
            .section {
                padding: 10px;
            }
            .btn {
                width: 100%;
                margin-bottom: 10px;
            }
            .user-table th, .user-table td {
                padding: 8px 5px;
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
                    <p>Data User</p>
                </div>
            </div>
            
            <nav class="nav-menu">
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="kelola_kategori.php" class="nav-link">Kategori</a>
                <a href="data_user.php" class="nav-link active">Data User</a>
                <a href="statistik.php" class="nav-link">Statistik</a>
                <a href="verifikasi_reward.php" class="nav-link">Verifikasi Reward</a>
                <a href="notifikasi.php" class="nav-link">Notifikasi</a>
                <a href="../logout.php" class="nav-link">Logout</a>
            </nav>
        </div>
        
        <div class="section">
            <h3 class="section-title">Daftar Pengguna</h3>
            
            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="Cari pengguna...">
                <button class="btn" onclick="searchUsers()">Cari</button>
            </div>
            
            <table class="user-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Profil</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Total Pain</th>
                        <th>Reward Diklaim</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): 
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM reward_claim WHERE user_id = ?");
                        $stmt->execute([$user['id']]);
                        $reward_count = $stmt->fetchColumn();
                    ?>
                        <tr>
                            <td><?= $user['id'] ?></td>
                            <td><img src="../foto_profil/<?= htmlspecialchars($user['foto_profil']) ?>" alt="Avatar" class="user-avatar-small"></td>
                            <td><?= htmlspecialchars($user['nama']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= $user['total_pain'] ?></td>
                            <td><?= $reward_count ?></td>
                            <td>
                                <span class="user-status status-active">Aktif</span>
                            </td>
                            <td>
                                <a href="user_detail.php?id=<?= $user['id'] ?>" class="action-link">Detail</a>
                                <a href="#" class="action-link">Reset Password</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="pagination">
                <a href="#">&laquo;</a>
                <a href="#" class="current">1</a>
                <a href="#">2</a>
                <a href="#">3</a>
                <a href="#">&raquo;</a>
            </div>
        </div>
    </div>
    
    <script>
        function searchUsers() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('.user-table tbody tr');
            
            rows.forEach(row => {
                const name = row.cells[2].textContent.toLowerCase();
                const email = row.cells[3].textContent.toLowerCase();
                
                if (name.includes(searchTerm) || email.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        // Tambahkan event listener untuk input
        document.getElementById('searchInput').addEventListener('keyup', searchUsers);
    </script>
</body>
</html>