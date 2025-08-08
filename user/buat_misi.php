<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];

// Ambil kategori misi
$kategori = $pdo->query("SELECT * FROM kategori_misi ORDER BY nama_kategori")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_misi = trim($_POST['nama_misi']);
    $deskripsi = trim($_POST['deskripsi']);
    $kategori_id = $_POST['kategori_id'];
    $nilai_pain = (int)$_POST['nilai_pain'];
    
    if (empty($nama_misi) || empty($deskripsi) || empty($kategori_id) || $nilai_pain <= 0) {
        $error = "Semua field harus diisi dan nilai pain harus lebih dari 0";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO misi (user_id, nama_misi, deskripsi, kategori_id, nilai_pain, status) VALUES (?, ?, ?, ?, ?, 'aktif')");
            $stmt->execute([$user_id, $nama_misi, $deskripsi, $kategori_id, $nilai_pain]);
            
            $_SESSION['success'] = "Misi berhasil dibuat!";
            redirect('dashboard.php');
        } catch (PDOException $e) {
            $error = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buat Misi - Aplikasi Produktivitas</title>
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
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Navbar styles sudah ada di includes/navbar.php */
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        
        .card-title {
            font-size: 1.8rem;
            margin-bottom: 25px;
            color: var(--primary);
            text-align: center;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f2f5;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }
        
        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .btn {
            display: inline-block;
            background: var(--primary);
            color: white;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 1rem;
        }
        
        .btn:hover {
            background: var(--secondary);
            transform: translateY(-2px);
        }
        
        .btn-block {
            display: block;
            width: 100%;
            margin-top: 20px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .tips-box {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border-left: 4px solid var(--primary);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        
        .tips-title {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 10px;
        }
        
        .tips-content {
            color: #333;
            line-height: 1.6;
        }
        
        .pain-slider {
            width: 100%;
            margin: 10px 0;
        }
        
        .pain-value {
            text-align: center;
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--primary);
            margin: 10px 0;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .card {
                padding: 20px;
            }
            
            .card-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php 
        $current_page = 'buat_misi';
        include '../includes/navbar.php'; 
        ?>
        
        <div class="card">
            <h1 class="card-title">🎯 Buat Misi Baru</h1>
            
            <div class="tips-box">
                <div class="tips-title">💡 Tips Membuat Misi yang Efektif:</div>
                <div class="tips-content">
                    <ul>
                        <li><strong>Spesifik:</strong> Buat misi yang jelas dan terukur</li>
                        <li><strong>Realistis:</strong> Sesuaikan dengan kemampuan dan waktu Anda</li>
                        <li><strong>Bertahap:</strong> Mulai dari misi kecil, lalu tingkatkan kompleksitasnya</li>
                        <li><strong>Relevan:</strong> Pilih kategori yang sesuai dengan tujuan Anda</li>
                    </ul>
                </div>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="nama_misi" class="form-label">Nama Misi *</label>
                    <input type="text" id="nama_misi" name="nama_misi" class="form-control" 
                           value="<?= isset($_POST['nama_misi']) ? htmlspecialchars($_POST['nama_misi']) : '' ?>" 
                           placeholder="Contoh: Olahraga pagi 30 menit" required>
                </div>
                
                <div class="form-group">
                    <label for="deskripsi" class="form-label">Deskripsi *</label>
                    <textarea id="deskripsi" name="deskripsi" class="form-control form-textarea" 
                              placeholder="Jelaskan detail misi Anda..." required><?= isset($_POST['deskripsi']) ? htmlspecialchars($_POST['deskripsi']) : '' ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="kategori_id" class="form-label">Kategori *</label>
                    <select id="kategori_id" name="kategori_id" class="form-control" required>
                        <option value="">Pilih kategori</option>
                        <?php foreach ($kategori as $kat): ?>
                            <option value="<?= $kat['id'] ?>" <?= (isset($_POST['kategori_id']) && $_POST['kategori_id'] == $kat['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($kat['nama_kategori']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="nilai_pain" class="form-label">Nilai Pain Point *</label>
                    <input type="range" id="nilai_pain" name="nilai_pain" class="pain-slider" 
                           min="1" max="10" value="<?= isset($_POST['nilai_pain']) ? $_POST['nilai_pain'] : '5' ?>" required>
                    <div class="pain-value">
                        <span id="pain-display">5</span> pain points
                    </div>
                    <small style="color: #666;">
                        Nilai 1-3: Misi ringan | 4-7: Misi sedang | 8-10: Misi berat
                    </small>
                </div>
                
                <button type="submit" class="btn btn-block">🚀 Buat Misi</button>
            </form>
        </div>
    </div>
    
    <script>
        // Update pain value display
        const painSlider = document.getElementById('nilai_pain');
        const painDisplay = document.getElementById('pain-display');
        
        painSlider.addEventListener('input', function() {
            painDisplay.textContent = this.value;
        });
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const namaMisi = document.getElementById('nama_misi').value.trim();
            const deskripsi = document.getElementById('deskripsi').value.trim();
            const kategori = document.getElementById('kategori_id').value;
            
            if (!namaMisi || !deskripsi || !kategori) {
                e.preventDefault();
                alert('Semua field harus diisi!');
            }
        });
    </script>
</body>
</html>