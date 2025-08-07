<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Ambil kategori misi dari database
$kategori = $pdo->query("SELECT * FROM kategori_misi")->fetchAll(PDO::FETCH_ASSOC);

// Tambahkan array asosiatif untuk range kategori
$kategori_range = [];
foreach ($kategori as $kat) {
    $kategori_range[$kat['id']] = [
        'min' => $kat['min_pain'],
        'max' => $kat['max_pain']
    ];
}

// Proses form jika ada POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kategori_id = $_POST['kategori_id'];
    $nama_misi = trim($_POST['nama_misi']);
    $deskripsi = trim($_POST['deskripsi']);
    $nilai_pain = (int)$_POST['nilai_pain'];

    // Validasi
    if (empty($nama_misi) || empty($kategori_id) || $nilai_pain <= 0) {
        $error = 'Harap isi semua kolom yang wajib!';
    } else {
        // Cek range pain untuk kategori yang dipilih
        $stmt = $pdo->prepare("SELECT min_pain, max_pain FROM kategori_misi WHERE id = ?");
        $stmt->execute([$kategori_id]);
        $kategori_data = $stmt->fetch();

        if ($kategori_data) {
            $min = $kategori_data['min_pain'];
            $max = $kategori_data['max_pain'];

            if ($nilai_pain < $min || $nilai_pain > $max) {
                $error = "Nilai pain harus antara $min dan $max untuk kategori ini!";
            } else {
                // Simpan misi
                $stmt = $pdo->prepare("INSERT INTO misi (user_id, kategori_id, nama_misi, deskripsi, nilai_pain, status) 
                                      VALUES (?, ?, ?, ?, ?, 'aktif')");
                if ($stmt->execute([$user_id, $kategori_id, $nama_misi, $deskripsi, $nilai_pain])) {
                    $success = 'Misi berhasil ditambahkan!';
                    // Reset form
                    $nama_misi = $deskripsi = '';
                    $kategori_id = $nilai_pain = null;
                } else {
                    $error = 'Gagal menambahkan misi. Silakan coba lagi.';
                }
            }
        } else {
            $error = 'Kategori tidak valid!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Misi Baru - Aplikasi Produktivitas</title>
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
            text-align: center;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f2f5;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }
        
        input[type="text"],
        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus,
        input[type="number"]:focus,
        select:focus,
        textarea:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }
        
        textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .btn {
            display: inline-block;
            background: var(--primary);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s;
            text-align: center;
        }
        
        .btn:hover {
            background: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .btn-block {
            display: block;
            width: 100%;
            margin: 25px 0 10px;
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
        
        .back-link {
            display: inline-block;
            margin-top: 15px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .category-info {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .category-range {
            font-weight: 600;
            color: var(--primary);
        }
    </style>
    <script>
        // Fungsi untuk menampilkan range pain berdasarkan kategori yang dipilih
        function updateCategoryInfo() {
            const kategoriSelect = document.getElementById('kategori_id');
            const categoryInfo = document.getElementById('category-info');
            const selectedOption = kategoriSelect.options[kategoriSelect.selectedIndex];
            
            if (selectedOption && selectedOption.dataset.range) {
                categoryInfo.innerHTML = `Range pain untuk kategori ini: <span class="category-range">${selectedOption.dataset.range}</span>`;
                categoryInfo.style.display = 'block';
            } else {
                categoryInfo.style.display = 'none';
            }
        }
        
        // Validasi form sebelum submit
        function validateForm() {
            const kategoriSelect = document.getElementById('kategori_id');
            const namaMisi = document.getElementById('nama_misi').value;
            const nilaiPain = document.getElementById('nilai_pain').value;
            
            if (!kategoriSelect.value) {
                alert('Pilih kategori misi terlebih dahulu!');
                return false;
            }
            
            if (namaMisi.trim() === '') {
                alert('Nama misi tidak boleh kosong!');
                return false;
            }
            
            if (nilaiPain <= 0) {
                alert('Nilai pain harus lebih dari 0!');
                return false;
            }
            
            return true;
        }
        
        // Inisialisasi saat halaman dimuat
        document.addEventListener('DOMContentLoaded', function() {
            updateCategoryInfo();
        });

        // Tambahkan JS untuk update min/max input number sesuai kategori
        const kategoriRange = <?= json_encode($kategori_range) ?>;
        document.getElementById('kategori_id').addEventListener('change', function() {
            const val = this.value;
            const inputPain = document.getElementById('nilai_pain');
            if (kategoriRange[val]) {
                inputPain.min = kategoriRange[val].min;
                inputPain.max = kategoriRange[val].max;
                inputPain.placeholder = `Masukkan nilai pain (${kategoriRange[val].min} - ${kategoriRange[val].max})`;
            } else {
                inputPain.min = 1;
                inputPain.max = '';
                inputPain.placeholder = 'Masukkan nilai pain';
            }
        });
        // Inisialisasi min/max saat load jika kategori sudah terpilih
        window.addEventListener('DOMContentLoaded', function() {
            const kategoriSelect = document.getElementById('kategori_id');
            const inputPain = document.getElementById('nilai_pain');
            if (kategoriRange[kategoriSelect.value]) {
                inputPain.min = kategoriRange[kategoriSelect.value].min;
                inputPain.max = kategoriRange[kategoriSelect.value].max;
                inputPain.placeholder = `Masukkan nilai pain (${kategoriRange[kategoriSelect.value].min} - ${kategoriRange[kategoriSelect.value].max})`;
            }
        });

        // Tambahkan update info range di atas input
        function updatePainRangeInfo() {
            const kategoriSelect = document.getElementById('kategori_id');
            const painInfo = document.getElementById('pain-range-info');
            const val = kategoriSelect.value;
            if (kategoriRange[val]) {
                painInfo.textContent = `Nilai pain untuk kategori ini hanya boleh antara ${kategoriRange[val].min} dan ${kategoriRange[val].max}`;
            } else {
                painInfo.textContent = '';
            }
        }
        document.getElementById('kategori_id').addEventListener('change', function() {
            updatePainRangeInfo();
        });
        window.addEventListener('DOMContentLoaded', function() {
            updatePainRangeInfo();
        });
        // Update placeholder dinamis sesuai kategori
        function updatePainPlaceholder() {
            const kategoriSelect = document.getElementById('kategori_id');
            const inputPain = document.getElementById('nilai_pain');
            const val = kategoriSelect.value;
            if (kategoriRange[val]) {
                inputPain.placeholder = `Masukkan nilai pain (${kategoriRange[val].min} - ${kategoriRange[val].max})`;
            } else {
                inputPain.placeholder = 'Masukkan nilai pain';
            }
        }
        document.getElementById('kategori_id').addEventListener('change', function() {
            updatePainPlaceholder();
        });
        window.addEventListener('DOMContentLoaded', function() {
            updatePainPlaceholder();
        });
        // Blokir input selain angka dan angka di luar range
        const inputPain = document.getElementById('nilai_pain');
        inputPain.addEventListener('keypress', function(e) {
            const kategoriSelect = document.getElementById('kategori_id');
            const val = kategoriSelect.value;
            if (!kategoriRange[val]) return;
            const min = kategoriRange[val].min;
            const max = kategoriRange[val].max;
            // Hanya izinkan angka
            if (!/\d/.test(e.key)) {
                e.preventDefault();
                return;
            }
            // Cek hasil input jika ditambah karakter baru
            let next = this.value + e.key;
            let num = parseInt(next);
            if (num < min || num > max) {
                e.preventDefault();
            }
        });
        // Jika paste, filter juga
        inputPain.addEventListener('paste', function(e) {
            const kategoriSelect = document.getElementById('kategori_id');
            const val = kategoriSelect.value;
            if (!kategoriRange[val]) return;
            const min = kategoriRange[val].min;
            const max = kategoriRange[val].max;
            let paste = (e.clipboardData || window.clipboardData).getData('text');
            if (!/^\d+$/.test(paste)) {
                e.preventDefault();
                return;
            }
            let num = parseInt(paste);
            if (num < min || num > max) {
                e.preventDefault();
            }
        });
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="user-info">
                <img src="foto_profil/<?= $_SESSION['foto_profil'] ?>" alt="Avatar" class="user-avatar">
                <div>
                    <h2><?= $_SESSION['nama'] ?></h2>
                    <p>Buat Misi Baru</p>
                </div>
            </div>
            
            <nav class="nav-menu">
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="buat_misi.php" class="nav-link active">Buat Misi</a>
                <a href="set_target.php" class="nav-link">Target</a>
                <a href="profil.php" class="nav-link">Profil</a>
            </nav>
        </div>
        
        <div class="card">
            <h2 class="card-title">Buat Misi Produktif Baru</h2>
            
            <?php if ($error): ?>
                <div class="error"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success"><?= $success ?></div>
            <?php endif; ?>
            
            <form method="POST" onsubmit="return validateForm()">
                <div class="form-group">
                    <label for="kategori_id">Kategori Misi *</label>
                    <select name="kategori_id" id="kategori_id" required onchange="updateCategoryInfo()">
                        <option value="">-- Pilih Kategori Misi --</option>
                        <?php foreach ($kategori as $kat): ?>
                            <option value="<?= $kat['id'] ?>" 
                                data-range="<?= $kat['min_pain'] ?>-<?= $kat['max_pain'] ?>"
                                <?= isset($_POST['kategori_id']) && $_POST['kategori_id'] == $kat['id'] ? 'selected' : '' ?>>
                                <?= $kat['nama_kategori'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="category-info" class="category-info" style="display: none;">
                        Range pain untuk kategori ini: <span class="category-range"></span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="nama_misi">Nama Misi *</label>
                    <input type="text" name="nama_misi" id="nama_misi" required 
                           placeholder="Contoh: Belajar PHP selama 2 jam" 
                           value="<?= isset($_POST['nama_misi']) ? htmlspecialchars($_POST['nama_misi']) : '' ?>">
                </div>
                
                <div class="form-group">
                    <label for="deskripsi">Deskripsi Misi (Opsional)</label>
                    <textarea name="deskripsi" id="deskripsi" 
                              placeholder="Tambahkan detail misi jika diperlukan..."><?= isset($_POST['deskripsi']) ? htmlspecialchars($_POST['deskripsi']) : '' ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="nilai_pain">Nilai Pain *</label>
                    <div id="pain-range-info" style="margin-bottom:8px; color:#555; font-size:0.95em;"></div>
                    <input type="number" name="nilai_pain" id="nilai_pain" required 
                           min="1" placeholder="Masukkan nilai pain" 
                           value="<?= isset($_POST['nilai_pain']) ? $_POST['nilai_pain'] : '' ?>">
                    <div class="category-info">
                        Pain adalah poin yang mewakili usaha yang diperlukan untuk menyelesaikan misi ini
                    </div>
                </div>
                
                <button type="submit" class="btn btn-block">Simpan Misi</button>
                <a href="dashboard.php" class="back-link">&larr; Kembali ke Dashboard</a>
            </form>
        </div>
    </div>
</body>
</html>