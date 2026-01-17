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
$rating_akurasi = $_POST['rating_akurasi'];
$rating_umum = $_POST['rating_umum'];
$komentar = mysqli_real_escape_string($conn, $_POST['komentar']);
$user_lat = $_POST['user_lat'];
$user_long = $_POST['user_long'];
$jenis_reviewer = $_POST['jenis_reviewer']; // 'sewa' atau 'survei'
$id_review_edit = isset($_POST['id_review']) && !empty($_POST['id_review']) ? $_POST['id_review'] : null;

// JIKA EDIT MODE
if ($id_review_edit) {
    $sql = "UPDATE review SET 
            skor_akurasi = '$rating_akurasi',
            rating = '$rating_umum',
            komentar = '$komentar',
            latitude_reviewer = '$user_lat',
            longitude_reviewer = '$user_long',
            jenis_reviewer = '$jenis_reviewer',
            tanggal_review = NOW()
            WHERE id_review = '$id_review_edit' AND id_user = '$id_user'";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Ulasan berhasil diupdate!'); window.location='riwayat_sewa';</script>";
    } else {
        echo "<script>alert('Gagal update ulasan!'); window.history.back();</script>";
    }
} else {
    // MODE TAMBAH BARU
    // Cek duplikasi
    $cek = mysqli_query($conn, "SELECT id_review FROM review WHERE id_kost='$id_kost' AND id_user='$id_user'");
    if (mysqli_num_rows($cek) > 0) {
        echo "<script>alert('Anda sudah pernah memberikan ulasan untuk kost ini!'); window.location='riwayat_sewa';</script>";
        exit;
    }

    $sql = "INSERT INTO review (id_kost, id_user, skor_akurasi, rating, komentar, latitude_reviewer, longitude_reviewer, jenis_reviewer, tanggal_review) 
            VALUES ('$id_kost', '$id_user', '$rating_akurasi', '$rating_umum', '$komentar', '$user_lat', '$user_long', '$jenis_reviewer', NOW())";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Terima kasih atas ulasan Anda!'); window.location='riwayat_sewa';</script>";
    } else {
        echo "<script>alert('Gagal menyimpan ulasan!'); window.history.back();</script>";
    }
}
?>