<?php
require_once 'includes/auth.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - Aplikasi Produktivitas</title>
    <style>
        .container { width: 400px; margin: 100px auto; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Login</h1>
        
        <?php if (isset($error)) echo display_error($error); ?>
        <?php if (isset($success)) echo '<div class="success">'.$success.'</div>'; ?>
        
        <form method="POST">
            <div>
                <label>Email atau Username:</label>
                <input type="text" name="login_credential" required>
            </div>
            <div>
                <label>Password:</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" name="login">Login</button>
        </form>
        
        <p>Belum punya akun? <a href="register.php">Daftar disini</a></p>
    </div>
</body>
</html>