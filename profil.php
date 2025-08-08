<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Fungsi untuk mendapatkan warna avatar berdasarkan inisial
function getAvatarColor($name) {
    $initial = strtolower(substr($name, 0, 1));
    $colors = [
        'a' => 'a', 'b' => 'b', 'c' => 'c', 'd' => 'd', 'e' => 'e',
        'f' => 'f', 'g' => 'g', 'h' => 'h', 'i' => 'i', 'j' => 'j',
        'k' => 'k', 'l' => 'l', 'm' => 'm', 'n' => 'n', 'o' => 'o',
        'p' => 'p', 'q' => 'q', 'r' => 'r', 's' => 's', 't' => 't',
        'u' => 'u', 'v' => 'v', 'w' => 'w', 'x' => 'x', 'y' => 'y', 'z' => 'z'
    ];
    return isset($colors[$initial]) ? $colors[$initial] : 'a';
}

// Ambil data user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    redirect('login.php');
}

// Proses update profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profil'])) {
    $nama = trim($_POST['nama']);
    $username = trim($_POST['username']);
    $no_hp = trim($_POST['no_hp']);
    $bio = trim($_POST['bio']);

    $errors = [];

    // Validasi
    if (empty($nama)) {
        $errors[] = "Nama tidak boleh kosong";
    }

    if (empty($username)) {
        $errors[] = "Username tidak boleh kosong";
    } elseif (strlen($username) < 5) {
        $errors[] = "Username minimal 5 karakter";
    } else {
        // Cek username sudah digunakan oleh user lain
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id <> ?");
        $stmt->execute([$username, $user_id]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Username sudah digunakan";
        }
    }

    if (empty($no_hp)) {
        $errors[] = "Nomor HP tidak boleh kosong";
    } elseif (!preg_match('/^[0-9]{10,13}$/', $no_hp)) {
        $errors[] = "Nomor HP harus angka, 10-13 digit";
    }

    // Jika ada error, tampilkan
    if (!empty($errors)) {
        $error = implode("<br>", $errors);
    } else {
        // Update data
        $stmt = $pdo->prepare("UPDATE users SET nama = ?, username = ?, no_hp = ?, bio = ? WHERE id = ?");
        if ($stmt->execute([$nama, $username, $no_hp, $bio, $user_id])) {
            $_SESSION['nama'] = $nama;
            $_SESSION['username'] = $username;
            $success = "Profil berhasil diperbarui!";
        } else {
            $error = "Gagal memperbarui profil";
        }
    }
}

// Proses update foto profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_foto'])) {
    if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['foto_profil'];
        
                // Validasi ukuran file (maksimal 2MB)
        $max_size = 2 * 1024 * 1024; // 2MB dalam bytes
        if ($file['size'] > $max_size) {
            $error = "Ukuran file terlalu besar. Maksimal 2MB";
        } elseif (!in_array($file['type'], ['image/jpeg', 'image/png', 'image/gif'])) {
            $error = "Hanya file JPG, PNG, dan GIF yang diperbolehkan";
        } elseif (!getimagesize($file['tmp_name'])) {
            $error = "File yang dipilih bukan gambar yang valid";
        } else {
            // Generate nama unik
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $ext;
            $target_path = "foto_profil/" . $filename;
            
            // Pindahkan file
            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                // Hapus foto lama jika bukan default
                if ($user['foto_profil'] != 'default.jpg') {
                    @unlink("foto_profil/" . $user['foto_profil']);
                }
                
                // Update database
                $stmt = $pdo->prepare("UPDATE users SET foto_profil = ? WHERE id = ?");
                if ($stmt->execute([$filename, $user_id])) {
                    $_SESSION['foto_profil'] = $filename;
                    $success = "Foto profil berhasil diperbarui!";
                    // Refresh data user
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch();
                } else {
                    $error = "Gagal menyimpan data foto";
                }
            } else {
                $error = "Gagal mengunggah foto";
            }
        }
    } else {
        $error = "Silakan pilih file foto";
    }
}

// Proses update password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    $konfirmasi_password = $_POST['konfirmasi_password'];
    
    // Validasi
    if (!password_verify($password_lama, $user['password'])) {
        $error = "Password lama salah";
    } elseif (strlen($password_baru) < 8) {
        $error = "Password baru minimal 8 karakter";
    } elseif (!preg_match('/[A-Z]/', $password_baru) || 
              !preg_match('/[a-z]/', $password_baru) || 
              !preg_match('/[0-9]/', $password_baru) || 
              !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password_baru)) {
        $error = "Password harus mengandung huruf besar, huruf kecil, angka, dan simbol";
    } elseif ($password_baru !== $konfirmasi_password) {
        $error = "Konfirmasi password tidak cocok";
    } else {
        // Update password
        $hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($stmt->execute([$hashed_password, $user_id])) {
            $success = "Password berhasil diperbarui!";
        } else {
            $error = "Gagal memperbarui password";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Pengguna - Aplikasi Produktivitas</title>
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
            
            .profile-header {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }
            
            .profile-avatar {
                width: 100px;
                height: 100px;
            }
            
            .profile-avatar-fallback {
                width: 100px;
                height: 100px;
                font-size: 36px;
            }
            
            .profile-name {
                font-size: 1.5rem;
            }
            
            .tabs {
                flex-direction: column;
                border-bottom: none;
            }
            
            .tab {
                border-bottom: 1px solid #ddd;
                text-align: center;
                padding: 15px;
            }
            
            .tab.active {
                border-bottom: 3px solid var(--primary);
                background-color: rgba(67, 97, 238, 0.1);
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
            
            .btn-block {
                margin: 20px 0 15px;
            }
            
            /* Preview container responsive */
            #preview-container .preview-row {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            #preview-container .preview-arrow {
                transform: rotate(90deg);
                margin: 10px 0;
            }
        }
        
        @media (max-width: 480px) {
            .container {
                margin: 10px auto;
                padding: 10px;
            }
            
            .card {
                padding: 20px;
            }
            
            .profile-name {
                font-size: 1.3rem;
            }
            
            .nav-link {
                padding: 8px 12px;
                font-size: 0.85rem;
            }
            
            .form-group label {
                font-size: 0.9rem;
            }
            
            .form-group input,
            .form-group textarea {
                padding: 12px;
            }
            
            .btn {
                padding: 12px 20px;
            }
            
            /* Tips box responsive */
            .tips-box {
                padding: 8px !important;
                font-size: 0.8rem !important;
            }
            
            .tips-box small {
                font-size: 0.75rem !important;
            }
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
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary);
        }
        
        .avatar-fallback {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
            border: 3px solid #667eea;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }
        
        .profile-avatar-fallback {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: bold;
            border: 5px solid #667eea;
            box-shadow: 0 4px 16px rgba(102, 126, 234, 0.3);
        }
        
        /* Warna-warna untuk avatar berdasarkan inisial */
        .avatar-fallback.a, .profile-avatar-fallback.a { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%); border-color: #ff6b6b; box-shadow: 0 2px 8px rgba(255, 107, 107, 0.3); }
        .avatar-fallback.b, .profile-avatar-fallback.b { background: linear-gradient(135deg, #4ecdc4 0%, #44a08d 100%); border-color: #4ecdc4; box-shadow: 0 2px 8px rgba(78, 205, 196, 0.3); }
        .avatar-fallback.c, .profile-avatar-fallback.c { background: linear-gradient(135deg, #45b7d1 0%, #96c93d 100%); border-color: #45b7d1; box-shadow: 0 2px 8px rgba(69, 183, 209, 0.3); }
        .avatar-fallback.d, .profile-avatar-fallback.d { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-color: #f093fb; box-shadow: 0 2px 8px rgba(240, 147, 251, 0.3); }
        .avatar-fallback.e, .profile-avatar-fallback.e { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); border-color: #4facfe; box-shadow: 0 2px 8px rgba(79, 172, 254, 0.3); }
        .avatar-fallback.f, .profile-avatar-fallback.f { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); border-color: #43e97b; box-shadow: 0 2px 8px rgba(67, 233, 123, 0.3); }
        .avatar-fallback.g, .profile-avatar-fallback.g { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); border-color: #fa709a; box-shadow: 0 2px 8px rgba(250, 112, 154, 0.3); }
        .avatar-fallback.h, .profile-avatar-fallback.h { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); border-color: #a8edea; box-shadow: 0 2px 8px rgba(168, 237, 234, 0.3); }
        .avatar-fallback.i, .profile-avatar-fallback.i { background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); border-color: #ffecd2; box-shadow: 0 2px 8px rgba(255, 236, 210, 0.3); }
        .avatar-fallback.j, .profile-avatar-fallback.j { background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%); border-color: #ff9a9e; box-shadow: 0 2px 8px rgba(255, 154, 158, 0.3); }
        .avatar-fallback.k, .profile-avatar-fallback.k { background: linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%); border-color: #a18cd1; box-shadow: 0 2px 8px rgba(161, 140, 209, 0.3); }
        .avatar-fallback.l, .profile-avatar-fallback.l { background: linear-gradient(135deg, #fad0c4 0%, #ffd1ff 100%); border-color: #fad0c4; box-shadow: 0 2px 8px rgba(250, 208, 196, 0.3); }
        .avatar-fallback.m, .profile-avatar-fallback.m { background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); border-color: #ffecd2; box-shadow: 0 2px 8px rgba(255, 236, 210, 0.3); }
        .avatar-fallback.n, .profile-avatar-fallback.n { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); border-color: #a8edea; box-shadow: 0 2px 8px rgba(168, 237, 234, 0.3); }
        .avatar-fallback.o, .profile-avatar-fallback.o { background: linear-gradient(135deg, #d299c2 0%, #fef9d7 100%); border-color: #d299c2; box-shadow: 0 2px 8px rgba(210, 153, 194, 0.3); }
        .avatar-fallback.p, .profile-avatar-fallback.p { background: linear-gradient(135deg, #89f7fe 0%, #66a6ff 100%); border-color: #89f7fe; box-shadow: 0 2px 8px rgba(137, 247, 254, 0.3); }
        .avatar-fallback.q, .profile-avatar-fallback.q { background: linear-gradient(135deg, #fdbb2d 0%, #22c1c3 100%); border-color: #fdbb2d; box-shadow: 0 2px 8px rgba(253, 187, 45, 0.3); }
        .avatar-fallback.r, .profile-avatar-fallback.r { background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%); border-color: #ff9a9e; box-shadow: 0 2px 8px rgba(255, 154, 158, 0.3); }
        .avatar-fallback.s, .profile-avatar-fallback.s { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-color: #667eea; box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3); }
        .avatar-fallback.t, .profile-avatar-fallback.t { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-color: #f093fb; box-shadow: 0 2px 8px rgba(240, 147, 251, 0.3); }
        .avatar-fallback.u, .profile-avatar-fallback.u { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); border-color: #4facfe; box-shadow: 0 2px 8px rgba(79, 172, 254, 0.3); }
        .avatar-fallback.v, .profile-avatar-fallback.v { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); border-color: #43e97b; box-shadow: 0 2px 8px rgba(67, 233, 123, 0.3); }
        .avatar-fallback.w, .profile-avatar-fallback.w { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); border-color: #fa709a; box-shadow: 0 2px 8px rgba(250, 112, 154, 0.3); }
        .avatar-fallback.x, .profile-avatar-fallback.x { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); border-color: #a8edea; box-shadow: 0 2px 8px rgba(168, 237, 234, 0.3); }
        .avatar-fallback.y, .profile-avatar-fallback.y { background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); border-color: #ffecd2; box-shadow: 0 2px 8px rgba(255, 236, 210, 0.3); }
        .avatar-fallback.z, .profile-avatar-fallback.z { background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%); border-color: #ff9a9e; box-shadow: 0 2px 8px rgba(255, 154, 158, 0.3); }
        
        /* CSS khusus untuk preview avatar yang lebih kecil */
        .preview-avatar-fallback {
            width: 100px !important;
            height: 100px !important;
            font-size: 36px !important;
            border: 3px solid #ddd !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1) !important;
        }
        
        /* Animasi hover untuk avatar */
        .avatar-fallback:hover, .profile-avatar-fallback:hover {
            transform: scale(1.05);
            transition: transform 0.3s ease;
        }
        
        /* Animasi untuk avatar yang muncul */
        .avatar-fallback, .profile-avatar-fallback {
            animation: fadeInScale 0.5s ease-out;
        }
        
        /* Mobile avatar improvements */
        @media (max-width: 768px) {
            .user-avatar {
                width: 50px;
                height: 50px;
                border-width: 2px;
            }
            
            .avatar-fallback {
                width: 50px;
                height: 50px;
                font-size: 20px;
                border-width: 2px;
            }
            
            .profile-avatar {
                width: 80px;
                height: 80px;
                border-width: 3px;
            }
            
            .profile-avatar-fallback {
                width: 80px;
                height: 80px;
                font-size: 32px;
                border-width: 3px;
            }
            
            .role-badge {
                font-size: 0.8rem !important;
                padding: 3px 10px !important;
            }
        }
        
        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
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
        
        .profile-header {
            display: flex;
            align-items: center;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid var(--primary);
        }
        
        .profile-details {
            flex-grow: 1;
        }
        
        .profile-name {
            font-size: 1.8rem;
            margin-bottom: 10px;
        }
        
        .profile-email {
            color: #666;
            margin-bottom: 15px;
        }
        
        .profile-bio {
            font-style: italic;
            color: #555;
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
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }
        
        /* File input styling */
        .form-group input[type="file"] {
            padding: 10px;
            border: 2px dashed #ddd;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .form-group input[type="file"]:hover {
            border-color: var(--primary);
            background: rgba(67, 97, 238, 0.05);
        }
        
        .form-group input[type="file"]:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }
        
        /* Touch-friendly improvements */
        .tab {
            min-height: 44px; /* Minimum touch target size */
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn {
            min-height: 44px; /* Minimum touch target size */
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Loading state for buttons */
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        /* Better spacing for mobile */
        .form-group small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 0.85rem;
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
        
        /* Mobile-friendly error and success messages */
        @media (max-width: 768px) {
            .error, .success {
                padding: 12px;
                font-size: 0.9rem;
                margin-bottom: 15px;
            }
            
            .error {
                border-left-width: 3px;
            }
            
            .success {
                border-left-width: 3px;
            }
        }
        
        .tab-container {
            margin-top: 30px;
        }
        
        .tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
        }
        
        .tab.active {
            border-bottom: 3px solid var(--primary);
            color: var(--primary);
            font-weight: 500;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
    </style>
    <script>
        function openTab(evt, tabName) {
            // Sembunyikan semua tab content
            const tabcontent = document.getElementsByClassName("tab-content");
            for (let i = 0; i < tabcontent.length; i++) {
                tabcontent[i].classList.remove("active");
            }
            
            // Hapus class active dari semua tab
            const tablinks = document.getElementsByClassName("tab");
            for (let i = 0; i < tablinks.length; i++) {
                tablinks[i].classList.remove("active");
            }
            
            // Tampilkan tab yang dipilih dan tambahkan class active
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");
        }
        
        // Buka tab pertama secara default
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('.tab').click();
            
            // Handle image loading errors
            const images = document.querySelectorAll('img[src*="foto_profil"]');
            images.forEach(function(img) {
                img.addEventListener('error', function() {
                    this.style.display = 'none';
                    const fallback = this.nextElementSibling;
                    if (fallback && (fallback.classList.contains('avatar-fallback') || fallback.classList.contains('profile-avatar-fallback'))) {
                        fallback.style.display = 'flex';
                    }
                });
            });
        });
        
        // Fungsi untuk preview gambar
        function previewImage(input) {
            const previewContainer = document.getElementById('preview-container');
            const previewImage = document.getElementById('preview-image');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    previewContainer.style.display = 'block';
                }
                
                reader.readAsDataURL(input.files[0]);
            } else {
                previewContainer.style.display = 'none';
            }
        }
        
        // Handle form submissions
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            
            forms.forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<span style="display: inline-block; width: 16px; height: 16px; border: 2px solid #ffffff; border-radius: 50%; border-top-color: transparent; animation: spin 1s linear infinite; margin-right: 8px;"></span>Memproses...';
                    }
                });
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
        
        // Handle mobile navigation
        function handleMobileNav() {
            const navMenu = document.querySelector('.nav-menu');
            if (window.innerWidth <= 768) {
                navMenu.style.flexDirection = 'column';
                navMenu.style.width = '100%';
            } else {
                navMenu.style.flexDirection = 'row';
                navMenu.style.width = 'auto';
            }
        }
        
        // Call on load and resize
        window.addEventListener('load', handleMobileNav);
        window.addEventListener('resize', handleMobileNav);
    </script>
</head>
<body>
    <div class="container">
        <?php 
        $current_page = 'profil';
        include 'includes/navbar.php'; 
        ?>
        
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="profile-header">
                <img src="foto_profil/<?= $user['foto_profil'] ?? 'default.jpg' ?>" alt="Foto Profil" class="profile-avatar" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <div class="profile-avatar-fallback <?= getAvatarColor($user['nama'] ?? 'U') ?>" style="display:none;"><?= strtoupper(substr($user['nama'] ?? 'U', 0, 1)) ?></div>
                <div class="profile-details">
                    <h1 class="profile-name"><?= htmlspecialchars($user['nama']) ?></h1>
                    <div class="profile-email"><?= htmlspecialchars($user['email']) ?></div>
                    <div style="margin-bottom: 10px;">
                        <span class="role-badge" style="background: var(--primary); color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.9rem; font-weight: 500; display: inline-block;">
                            <?= is_admin() ? 'Administrator' : 'Pengguna' ?>
                        </span>
                    </div>
                    <div class="profile-bio"><?= nl2br(htmlspecialchars($user['bio'] ?? '-')) ?></div>
                </div>
            </div>
            
            <div class="tab-container">
                <div class="tabs">
                    <div class="tab active" onclick="openTab(event, 'edit-profil')">Edit Profil</div>
                    <div class="tab" onclick="openTab(event, 'edit-foto')">Edit Foto Profil</div>
                    <div class="tab" onclick="openTab(event, 'edit-password')">Edit Password</div>
                </div>
                
                <!-- Tab Edit Profil -->
                <div id="edit-profil" class="tab-content active">
                    <form method="POST">
                        <div class="form-group">
                            <label for="nama">Nama Lengkap</label>
                            <input type="text" id="nama" name="nama" value="<?= htmlspecialchars($user['nama']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="no_hp">Nomor HP</label>
                            <input type="tel" id="no_hp" name="no_hp" value="<?= htmlspecialchars($user['no_hp']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="bio">Bio</label>
                            <textarea id="bio" name="bio"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                        </div>
                        
                        <button type="submit" name="update_profil" class="btn btn-block">Simpan Perubahan</button>
                    </form>
                </div>
                
                <!-- Tab Edit Foto Profil -->
                <div id="edit-foto" class="tab-content">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="foto_profil">Pilih Foto Profil Baru</label>
                            <input type="file" id="foto_profil" name="foto_profil" accept="image/*" required onchange="previewImage(this)">
                            <small>Format: JPG, PNG, GIF (Maks. 2MB)</small>
                            <div class="tips-box" style="margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid var(--primary);">
                                <small style="color: #666;">
                                    <strong>💡 Tips:</strong> Jika Anda belum memiliki foto profil, sistem akan menampilkan avatar default dengan inisial nama Anda dan warna yang unik, seperti kontak di WhatsApp!
                                </small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div id="preview-container" style="display:none; margin-top: 15px;">
                                <label>Preview Foto:</label>
                                <div class="preview-row" style="display: flex; gap: 20px; align-items: center; margin-top: 10px;">
                                    <div>
                                        <p style="margin-bottom: 10px; color: #666;">Foto Saat Ini:</p>
                                        <img src="foto_profil/<?= $user['foto_profil'] ?? 'default.jpg' ?>" alt="Foto Saat Ini" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #ddd;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="profile-avatar-fallback preview-avatar-fallback <?= getAvatarColor($user['nama'] ?? 'U') ?>" style="display:none;"><?= strtoupper(substr($user['nama'] ?? 'U', 0, 1)) ?></div>
                                    </div>
                                    <div class="preview-arrow" style="font-size: 24px; color: #ddd;">→</div>
                                    <div>
                                        <p style="margin-bottom: 10px; color: #666;">Foto Baru:</p>
                                        <img id="preview-image" alt="Preview" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid var(--primary);">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" name="update_foto" class="btn btn-block">Unggah Foto Baru</button>
                    </form>
                </div>
                
                <!-- Tab Edit Password -->
                <div id="edit-password" class="tab-content">
                    <form method="POST">
                        <div class="form-group">
                            <label for="password_lama">Password Lama</label>
                            <input type="password" id="password_lama" name="password_lama" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password_baru">Password Baru</label>
                            <input type="password" id="password_baru" name="password_baru" required>
                            <small>Minimal 8 karakter, mengandung huruf besar, huruf kecil, angka, dan simbol</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="konfirmasi_password">Konfirmasi Password Baru</label>
                            <input type="password" id="konfirmasi_password" name="konfirmasi_password" required>
                        </div>
                        
                        <button type="submit" name="update_password" class="btn btn-block">Ganti Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>