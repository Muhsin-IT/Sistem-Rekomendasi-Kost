<?php
session_start();
include 'koneksi.php';

// 1. CEK LOGIN
if (!isset($_SESSION['login'])) {
    echo "<script>alert('Silakan login.'); window.location='login';</script>";
    exit;
}

$id_user = $_SESSION['id_user'];
$id_kost = $_POST['id_kost'];
$akurasi = $_POST['rating_akurasi'];
$umum    = $_POST['rating_umum'];
$komen   = mysqli_real_escape_string($conn, $_POST['komentar']);

// 2. CEK DUPLIKAT (JANGAN BOLEH REVIEW LAGI)
$cek = mysqli_query($conn, "SELECT id_review FROM review WHERE id_user='$id_user' AND id_kost='$id_kost'");
if (mysqli_num_rows($cek) > 0) {
    echo "<script>alert('Anda sudah pernah memberikan ulasan untuk kost ini. Tidak bisa mengulas dua kali.'); window.location='riwayat_sewa.php';</script>";
    exit;
}

// 3. SIMPAN
$query = "INSERT INTO review (id_user, id_kost, skor_akurasi, rating, komentar) VALUES ('$id_user', '$id_kost', '$akurasi', '$umum', '$komen')";

if (mysqli_query($conn, $query)) {
    echo "<script>alert('Terima kasih! Ulasan Anda berhasil disimpan.'); window.location='riwayat_sewa.php';</script>";
} else {
    echo "Error: " . mysqli_error($conn);
}
?>