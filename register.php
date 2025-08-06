<?php
require_once 'includes/auth.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Registrasi - Aplikasi Produktivitas</title>
    <style>
        .container { width: 400px; margin: 100px auto; }
        .error { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Registrasi Pengguna Baru</h1>
        
        <?php if (isset($error)) echo display_error($error); ?>
        
        <form method="POST">
            <div>
                <label>Nama Lengkap:</label>
                <input type="text" name="nama" required>
            </div>
            <div>
                <label>Email:</label>
                <input type="email" name="email" required>
            </div>
            <div>
                <label>Password:</label>
                <input type="password" name="password" required>
            </div>
            <div>
                <label>Konfirmasi Password:</label>
                <input type="password" name="confirm_password" required>
            </div>
            <div>
                <label>Username:</label>
                <input type="text" name="username" required>
            </div>
            <div>
                <label>No HP:</label>
                <input type="text" name="no_hp" required>
            </div>
            <button type="submit" name="register">Daftar</button>
        </form>
        
        <p>Sudah punya akun? <a href="login.php">Login disini</a></p>
    </div>
</body>
</html>