<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Proses form jika ada POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hadiah = trim($_POST['hadiah']);
    $target_pain = (int)$_POST['target_pain'];
    
    if (empty($hadiah) || $target_pain <= 0) {
        $error = 'Harap isi semua kolom dengan benar!';
    } elseif (strlen($hadiah) > 255) {
        $error = 'Hadiah terlalu panjang. Maksimal 255 karakter.';
    } elseif ($target_pain > 999999) {
        $error = 'Target pain terlalu besar. Maksimal 999,999.';
    } else {
        try {
            // Mulai transaksi
            $pdo->beginTransaction();
            
            // Set semua target aktif user menjadi tercapai (sebagai pengganti nonaktif)
            $update_stmt = $pdo->prepare("UPDATE target_reward SET status = 'tercapai' WHERE user_id = ? AND status = 'aktif'");
            $update_stmt->execute([$user_id]);
            
            // Simpan target baru
            $insert_stmt = $pdo->prepare("INSERT INTO target_reward (user_id, hadiah, target_pain, status) VALUES (?, ?, ?, 'aktif')");
            if ($insert_stmt->execute([$user_id, $hadiah, $target_pain])) {
                // Commit transaksi
                $pdo->commit();
                $success = 'Target baru berhasil disimpan!';
            } else {
                throw new Exception('Gagal menyimpan target baru');
            }
        } catch (Exception $e) {
            // Rollback transaksi jika terjadi error
            $pdo->rollBack();
            $error = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Target Reward - Aplikasi Produktivitas</title>
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
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
        }
        
        /* Navbar styles sudah ada di includes/navbar.php */
        
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
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
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
            text-decoration: none;
            min-height: 44px;
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
        
        .tips-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid var(--primary);
            margin-top: 15px;
        }
        
        .tips-box small {
            color: #666;
            font-size: 0.9rem;
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
            
            .form-group input,
            .form-group textarea {
                padding: 15px;
                font-size: 16px; /* Mencegah zoom di iOS */
            }
            
            .btn {
                padding: 15px 25px;
                font-size: 16px;
            }
            
            .error, .success {
                padding: 12px;
                font-size: 0.9rem;
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
            
            .form-group label {
                font-size: 0.9rem;
            }
            
            .btn {
                padding: 12px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php 
        $current_page = 'set_target';
        include '../includes/navbar.php'; 
        ?>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h2 class="card-title">Set Target Reward Baru</h2>
            
            <form method="POST">
                <div class="form-group">
                    <label for="hadiah">Hadiah yang Diinginkan</label>
                    <input type="text" id="hadiah" name="hadiah" placeholder="Contoh: Makan di restoran favorit" required>
                    <small>Jelaskan hadiah yang ingin Anda dapatkan setelah mencapai target</small>
                </div>
                
                <div class="form-group">
                    <label for="target_pain">Target Pain Points</label>
                    <input type="number" id="target_pain" name="target_pain" min="1" placeholder="Contoh: 100" required>
                    <small>Jumlah pain points yang harus Anda kumpulkan untuk mendapatkan hadiah</small>
                </div>
                
                <div class="tips-box">
                    <small>
                        <strong>💡 Tips:</strong> 
                        <ul style="margin: 10px 0; padding-left: 20px;">
                            <li>Pilih hadiah yang benar-benar memotivasi Anda</li>
                            <li>Set target yang realistis dan dapat dicapai</li>
                            <li>Target pain yang lebih tinggi = hadiah yang lebih besar</li>
                            <li>Anda hanya bisa memiliki satu target aktif pada satu waktu</li>
                            <li>Target lama akan otomatis menjadi "tercapai" saat Anda set target baru</li>
                        </ul>
                    </small>
                </div>
                
                <button type="submit" class="btn btn-block">Simpan Target</button>
            </form>
            
            <a href="dashboard.php" class="btn btn-secondary btn-block">&larr; Kembali ke Dashboard</a>
        </div>
    </div>
    
    <script>
        // Handle form submission
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const submitBtn = form.querySelector('button[type="submit"]');
            
            form.addEventListener('submit', function(e) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span style="display: inline-block; width: 16px; height: 16px; border: 2px solid #ffffff; border-radius: 50%; border-top-color: transparent; animation: spin 1s linear infinite; margin-right: 8px;"></span>Memproses...';
            });
        });
        
        // Add loading animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes spin {
                to { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>