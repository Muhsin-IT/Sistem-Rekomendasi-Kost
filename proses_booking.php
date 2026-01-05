<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'koneksi.php';

if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// ==================== 1. LOGIKA SURVEI (STOK TETAP) ====================
if (isset($_POST['tipe_aksi']) && $_POST['tipe_aksi'] == 'survei') {

    $id_kost    = $_POST['id_kost'];
    $tgl        = $_POST['tgl_survei'];
    $jam        = $_POST['jam_survei'];

    if (empty($tgl) || empty($jam)) {
        echo "<script>alert('Harap isi tanggal dan jam survei!'); window.history.back();</script>";
        exit;
    }

    $query = "INSERT INTO survei (id_user, id_kost, tgl_survei, jam_survei, status) 
              VALUES ('$id_user', '$id_kost', '$tgl', '$jam', 'Menunggu')";

    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Jadwal Survei Berhasil Diajukan!'); window.location='riwayat_sewa.php';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}

// ==================== 2. LOGIKA SEWA (STOK BERKURANG DULUAN) ====================
elseif ((isset($_POST['tipe_aksi']) && $_POST['tipe_aksi'] == 'sewa') || isset($_POST['ajukan_sewa'])) {

    $id_kost  = $_POST['id_kost'];
    $id_kamar = $_POST['id_kamar'];
    $tgl      = $_POST['tgl_mulai'];
    $durasi   = $_POST['durasi'];

    // Cek Stok
    $cek_stok = mysqli_query($conn, "SELECT stok_kamar FROM kamar WHERE id_kamar = '$id_kamar'");
    $data = mysqli_fetch_assoc($cek_stok);

    if ($data && $data['stok_kamar'] > 0) {

        // [BARU] KURANGI STOK LANGSUNG (RESERVASI)
        mysqli_query($conn, "UPDATE kamar SET stok_kamar = stok_kamar - 1 WHERE id_kamar = '$id_kamar'");

        $query = "INSERT INTO pengajuan_sewa (id_user, id_kost, id_kamar, tanggal_mulai_kos, durasi_bulan, status)
                  VALUES ('$id_user', '$id_kost', '$id_kamar', '$tgl', '$durasi', 'Menunggu')";

        if (mysqli_query($conn, $query)) {
            echo "<script>alert('Pengajuan Sewa Berhasil! Kamar telah direservasi untuk Anda.'); window.location='riwayat_sewa.php';</script>";
        } else {
            // Jika gagal insert, kembalikan stok
            mysqli_query($conn, "UPDATE kamar SET stok_kamar = stok_kamar + 1 WHERE id_kamar = '$id_kamar'");
            echo "Error: " . mysqli_error($conn);
        }
    } else {
        echo "<script>alert('Maaf, Stok Kamar Habis!'); window.history.back();</script>";
    }
} else {
    header("Location: index.php");
}
?>