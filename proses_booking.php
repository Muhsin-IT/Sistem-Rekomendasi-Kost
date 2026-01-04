<?php
// 1. NYALAKAN PELAPORAN ERROR (PENTING UNTUK DEBUGGING)
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'koneksi.php';

// Cek apakah koneksi database berhasil
if (!$conn) {
    die("Koneksi Database Gagal: " . mysqli_connect_error());
}

// 2. CEK LOGIN
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'mahasiswa') {
    die("Error: Anda belum login sebagai mahasiswa. <a href='login.php'>Login disini</a>");
}

// 3. CEK APAKAH TOMBOL DIKLIK
if (isset($_POST['ajukan_sewa']) || isset($_POST['id_kamar'])) {

    // Tampilkan data yang dikirim (Untuk memastikan form bekerja)
    // echo "<pre>"; print_r($_POST); echo "</pre>"; // Hilangkan komentar ini jika ingin melihat isi data

    $id_user  = $_SESSION['id_user'];
    $id_kost  = $_POST['id_kost'];
    $id_kamar = $_POST['id_kamar'];
    $tgl      = $_POST['tgl_mulai'];
    $durasi   = $_POST['durasi'];

    // Validasi data tidak boleh kosong
    if (empty($id_kost) || empty($id_kamar) || empty($tgl) || empty($durasi)) {
        die("Error: Data tidak lengkap. Pastikan semua input terisi.");
    }

    // 4. CEK STOK KAMAR
    $cek_stok = mysqli_query($conn, "SELECT stok_kamar FROM kamar WHERE id_kamar = '$id_kamar'");

    if (!$cek_stok) {
        die("Error Query Cek Stok: " . mysqli_error($conn));
    }

    $data = mysqli_fetch_assoc($cek_stok);

    if ($data['stok_kamar'] > 0) {
        // 5. EKSEKUSI INSERT
        $query = "INSERT INTO pengajuan_sewa (id_user, id_kost, id_kamar, tanggal_mulai_kos, durasi_bulan, status)
                  VALUES ('$id_user', '$id_kost', '$id_kamar', '$tgl', '$durasi', 'Menunggu')";

        if (mysqli_query($conn, $query)) {
            // BERHASIL
            echo "<script>
                    alert('BERHASIL! Pengajuan sewa terkirim.'); 
                    window.location='riwayat_sewa';
                  </script>";
        } else {
            // GAGAL SQL
            echo "<h1>GAGAL MENYIMPAN KE DATABASE</h1>";
            echo "<p>Pesan Error: " . mysqli_error($conn) . "</p>";
            echo "<p>Coba cek apakah ID User, ID Kost, atau ID Kamar benar-benar ada di database?</p>";
        }
    } else {
        echo "<script>alert('Maaf, Stok Kamar Habis!'); window.history.back();</script>";
    }
} else {
    // Jika file dibuka langsung tanpa klik tombol submit
    echo "<h1>Akses Ditolak</h1>";
    echo "<p>Anda membuka file ini secara langsung, bukan dari tombol 'Ajukan Sewa'.</p>";
    echo "<p>Pastikan di form HTML atribut <code>name='ajukan_sewa'</code> sudah ada pada tombol submit.</p>";
}
