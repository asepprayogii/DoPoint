<?php
require_once 'db.php';
require_once 'functions.php';

// Proses Login
if (isset($_POST['login'])) {
    $login_credential = $_POST['login_credential'];
    $password = $_POST['password'];
    
    // Cek apakah input adalah email atau username
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$login_credential, $login_credential]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['foto_profil'] = $user['foto_profil'];
        
        if ($user['role'] === 'admin') {
            redirect('admin/dashboard.php');
        } else {
            redirect('user/dashboard.php');
        }
    } else {
        $error = "Email/username atau password salah!";
    }
}

// Proses Registrasi
if (isset($_POST['register'])) {
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $no_hp = $_POST['no_hp'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    $errors = [];

    // Validasi email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid";
    } elseif (substr($email, -10) !== '@gmail.com') {
        $errors[] = "Email harus menggunakan @gmail.com";
    }

    // Validasi password
    if (strlen($password) < 8) {
        $errors[] = "Password minimal 8 karakter";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password harus mengandung huruf kapital";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password harus mengandung huruf kecil";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password harus mengandung angka";
    }
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        $errors[] = "Password harus mengandung simbol";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Konfirmasi password tidak cocok";
    }

    // Validasi username
    if (strlen($username) < 5) {
        $errors[] = "Username minimal 5 karakter";
    }

    // Cek email/username sudah terdaftar
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$email, $username]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = "Email atau username sudah terdaftar";
    }

    // Jika tidak ada error, simpan ke database
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (nama, email, username, password, no_hp, role) VALUES (?, ?, ?, ?, ?, 'user')");
        if ($stmt->execute([$nama, $email, $username, $hashed_password, $no_hp])) {
            $success = "Registrasi berhasil! Silakan login.";
        } else {
            $errors[] = "Gagal melakukan registrasi";
        }
    }
    // Tampilkan error jika ada
    if (!empty($errors)) {
        $error = implode('<br>', $errors);
    }
}
?>