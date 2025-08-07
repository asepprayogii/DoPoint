<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_admin()) {
    redirect('../login.php');
}

$error = '';
$success = '';

// Tambah Kategori
if (isset($_POST['tambah'])) {
    $nama_kategori = trim($_POST['nama_kategori']);
    $min_pain = (int)$_POST['min_pain'];
    $max_pain = (int)$_POST['max_pain'];
    
    if (empty($nama_kategori)) {
        $error = 'Nama kategori tidak boleh kosong!';
    } elseif ($min_pain <= 0 || $max_pain <= 0 || $min_pain > $max_pain) {
        $error = 'Range pain tidak valid!';
    } else {
        $stmt = $pdo->prepare("INSERT INTO kategori_misi (nama_kategori, min_pain, max_pain) VALUES (?, ?, ?)");
        if ($stmt->execute([$nama_kategori, $min_pain, $max_pain])) {
            $success = 'Kategori berhasil ditambahkan!';
        } else {
            $error = 'Gagal menambahkan kategori. Nama kategori mungkin sudah ada.';
        }
    }
}

// Edit Kategori
if (isset($_POST['edit'])) {
    $id = (int)$_POST['id'];
    $nama_kategori = trim($_POST['nama_kategori']);
    $min_pain = (int)$_POST['min_pain'];
    $max_pain = (int)$_POST['max_pain'];
    
    if (empty($nama_kategori)) {
        $error = 'Nama kategori tidak boleh kosong!';
    } elseif ($min_pain <= 0 || $max_pain <= 0 || $min_pain > $max_pain) {
        $error = 'Range pain tidak valid!';
    } else {
        $stmt = $pdo->prepare("UPDATE kategori_misi SET nama_kategori = ?, min_pain = ?, max_pain = ? WHERE id = ?");
        if ($stmt->execute([$nama_kategori, $min_pain, $max_pain, $id])) {
            $success = 'Kategori berhasil diperbarui!';
        } else {
            $error = 'Gagal memperbarui kategori. Nama kategori mungkin sudah ada.';
        }
    }
}

// Hapus Kategori
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    
    // Cek apakah kategori digunakan
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM misi WHERE kategori_id = ?");
    $stmt->execute([$id]);
    $used = $stmt->fetchColumn();
    
    if ($used > 0) {
        $error = 'Kategori tidak dapat dihapus karena sudah digunakan!';
    } else {
        $stmt = $pdo->prepare("DELETE FROM kategori_misi WHERE id = ?");
        if ($stmt->execute([$id])) {
            $success = 'Kategori berhasil dihapus!';
        } else {
            $error = 'Gagal menghapus kategori.';
        }
    }
}

// Ambil semua kategori
$kategori = $pdo->query("SELECT * FROM kategori_misi ORDER BY nama_kategori")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Kategori - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        tbody tr:hover {
            background: #f1f3fa;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        .btn-edit, .btn-hapus {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.9rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-edit {
            background: #4cc9f0;
            color: white;
        }
        .btn-edit:hover {
            background: #4361ee;
        }
        .btn-hapus {
            background: #f72585;
            color: white;
        }
        .btn-hapus:hover {
            background: #b5179e;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .form-title {
            font-size: 1.2rem;
            margin-bottom: 15px;
            color: var(--primary);
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        @media (max-width: 700px) {
            .header, .container {
                flex-direction: column;
                padding: 10px;
            }
            .form-container {
                padding: 10px;
            }
            .btn-edit, .btn-hapus {
                width: 100%;
                margin-bottom: 10px;
            }
            th, td {
                padding: 8px 5px;
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
                    <p>Kelola Kategori Misi</p>
                </div>
            </div>
            
            <nav class="nav-menu">
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="kelola_kategori.php" class="nav-link active">Kategori</a>
                <a href="data_user.php" class="nav-link">Data User</a>
                <a href="statistik.php" class="nav-link">Statistik</a>
                <a href="verifikasi_reward.php" class="nav-link">Verifikasi Reward</a>
                <a href="notifikasi.php" class="nav-link">Notifikasi</a>
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
            <h3 class="form-title">Tambah Kategori Baru</h3>
            <form method="POST">
                <div class="form-group">
                    <label for="nama_kategori">Nama Kategori *</label>
                    <input type="text" id="nama_kategori" name="nama_kategori" required>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label for="min_pain">Minimal Pain *</label>
                        <input type="number" id="min_pain" name="min_pain" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="max_pain">Maksimal Pain *</label>
                        <input type="number" id="max_pain" name="max_pain" min="1" required>
                    </div>
                </div>
                
                <button type="submit" name="tambah" class="btn">Tambah Kategori</button>
            </form>
        </div>
        
        <div class="form-container">
            <h3 class="form-title">Daftar Kategori</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Kategori</th>
                        <th>Range Pain</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($kategori as $kat): ?>
                        <tr>
                            <td><?= $kat['id'] ?></td>
                            <td><?= htmlspecialchars($kat['nama_kategori']) ?></td>
                            <td><?= $kat['min_pain'] ?> - <?= $kat['max_pain'] ?></td>
                            <td class="action-buttons">
                                <button class="btn-edit" onclick="editKategori(<?= $kat['id'] ?>, '<?= htmlspecialchars($kat['nama_kategori']) ?>', <?= $kat['min_pain'] ?>, <?= $kat['max_pain'] ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <a href="?hapus=<?= $kat['id'] ?>" class="btn-hapus" onclick="return confirm('Hapus kategori ini?')">
                                    <i class="fas fa-trash-alt"></i> Hapus
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Form Edit (Tersembunyi) -->
        <div id="editForm" class="form-container" style="display: none;">
            <h3 class="form-title">Edit Kategori</h3>
            <form method="POST">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label for="edit_nama_kategori">Nama Kategori *</label>
                    <input type="text" id="edit_nama_kategori" name="nama_kategori" required>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label for="edit_min_pain">Minimal Pain *</label>
                        <input type="number" id="edit_min_pain" name="min_pain" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_max_pain">Maksimal Pain *</label>
                        <input type="number" id="edit_max_pain" name="max_pain" min="1" required>
                    </div>
                </div>
                
                <button type="submit" name="edit" class="btn">Simpan Perubahan</button>
                <button type="button" class="btn" onclick="document.getElementById('editForm').style.display='none'" style="background: #6c757d;">Batal</button>
            </form>
        </div>
    </div>
    
    <script>
        function editKategori(id, nama, min_pain, max_pain) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama_kategori').value = nama;
            document.getElementById('edit_min_pain').value = min_pain;
            document.getElementById('edit_max_pain').value = max_pain;
            document.getElementById('editForm').style.display = 'block';
            window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
        }
    </script>
</body>
</html>