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
        $error = 'Harap isi semua kolom!';
    } else {
        // Set semua target aktif user menjadi non-aktif
        $pdo->prepare("UPDATE target_reward SET status = 'nonaktif' WHERE user_id = ? AND status = 'aktif'")
            ->execute([$user_id]);
        // Simpan target baru
        $stmt = $pdo->prepare("INSERT INTO target_reward (user_id, hadiah, target_pain, status) VALUES (?, ?, ?, 'aktif')");
        if ($stmt->execute([$user_id, $hadiah, $target_pain])) {
            $success = 'Target baru berhasil disimpan!';
        } else {
            $error = 'Gagal menyimpan target. Coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Set Target Reward</title>
</head>
<body>
    <h2>Set Target Reward Baru</h2>
    <?php if ($error): ?><div style="color:red;"> <?= $error ?> </div><?php endif; ?>
    <?php if ($success): ?><div style="color:green;"> <?= $success ?> </div><?php endif; ?>
    <form method="POST">
        <label>Hadiah:</label><br>
        <input type="text" name="hadiah" required><br><br>
        <label>Target Pain:</label><br>
        <input type="number" name="target_pain" min="1" required><br><br>
        <button type="submit">Simpan Target</button>
    </form>
    <a href="dashboard.php">&larr; Kembali ke Dashboard</a>
</body>
</html>