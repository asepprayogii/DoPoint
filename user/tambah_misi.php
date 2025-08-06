<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: ../auth/login.php");
  exit();
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Tambah Aktivitas</title>
</head>
<body>
  <h2>Tambah Aktivitas</h2>
  <form action="tambah_misi_proses.php" method="POST">
    <label>Kategori:</label>
    <select name="kategori" required>
      <option value="">--Pilih--</option>
      <option value="Akademik">Akademik</option>
      <option value="Rohani">Rohani</option>
      <option value="Fisik">Fisik</option>
      <option value="Sosial">Sosial</option>
    </select><br><br>

    <label>Nama Aktivitas:</label>
    <input type="text" name="nama" required><br><br>

    <label>Deskripsi:</label>
    <textarea name="deskripsi"></textarea><br><br>

    <label>Tanggal dan Waktu:</label>
    <input type="datetime-local" name="tanggal" required><br><br>

    <label>Poin:</label>
    <input type="number" name="poin" required><br><br>

    <button type="submit">Simpan Aktivitas</button>
  </form>
</body>
</html>
