<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: ../auth/login.php");
  exit();
}

$user_id = $_SESSION['user_id'];
$kategori = $_POST['kategori'];
$nama = $_POST['nama'];
$deskripsi = $_POST['deskripsi'];
$tanggal = $_POST['tanggal'];
$poin = $_POST['poin'];

$sql = "INSERT INTO aktivitas (user_id, kategori, nama_aktivitas, deskripsi, tanggal, poin)
        VALUES (?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($kon, $sql);
mysqli_stmt_bind_param($stmt, "issssi", $user_id, $kategori, $nama, $deskripsi, $tanggal, $poin);

if (mysqli_stmt_execute($stmt)) {
  echo "Aktivitas berhasil ditambahkan. <a href='index.php'>Kembali ke Dashboard</a>";
} else {
  echo "Gagal menambahkan aktivitas: " . mysqli_error($kon);
}
?>
