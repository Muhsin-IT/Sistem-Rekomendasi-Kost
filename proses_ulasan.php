<?php
session_start();
include 'koneksi.php';

// 1. CEK LOGIN
if (!isset($_SESSION['login'])) {
    echo "<script>alert('Silakan login terlebih dahulu.'); window.location='login.php';</script>";
    exit;
}

$id_user = $_SESSION['id_user'];

// 2. TERIMA DATA
$id_kost      = $_POST['id_kost'];
$skor_akurasi = $_POST['rating_akurasi'];
$rating_umum  = $_POST['rating_umum'];
$komentar     = mysqli_real_escape_string($conn, $_POST['komentar']);
$user_lat     = $_POST['user_lat'];
$user_long    = $_POST['user_long'];

// 3. AMBIL KOORDINAT KOST
$q_kost = mysqli_query($conn, "SELECT latitude, longitude FROM kost WHERE id_kost = '$id_kost'");
$kost   = mysqli_fetch_assoc($q_kost);

if (!$kost) {
    echo "<script>alert('Data Kost tidak ditemukan!'); window.history.back();</script>";
    exit;
}

// 4. FUNGSI HITUNG JARAK (Haversine Formula)
function hitungJarak($lat1, $lon1, $lat2, $lon2)
{
    if (($lat1 == $lat2) && ($lon1 == $lon2)) return 0;

    $theta = $lon1 - $lon2;
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
    $dist = acos($dist);
    $dist = rad2deg($dist);
    $miles = $dist * 60 * 1.1515;
    $unit = 1.609344;
    $meters = ($miles * $unit) * 1000; // Konversi ke Meter
    return round($meters);
}

// 5. CEK HAK AKSES
$is_booking = false;
$is_survei  = false;

// Cek apakah dia Anak Kost (Sewa Diterima)
$cek_sewa = mysqli_query($conn, "SELECT * FROM pengajuan_sewa WHERE id_user='$id_user' AND id_kost='$id_kost' AND status='Diterima'");
if (mysqli_num_rows($cek_sewa) > 0) {
    $is_booking = true;
}

// Cek apakah dia Peserta Survei (Diterima/Selesai)
$cek_survei = mysqli_query($conn, "SELECT * FROM survei WHERE id_user='$id_user' AND id_kost='$id_kost' AND (status='Diterima' OR status='Selesai')");
if (mysqli_num_rows($cek_survei) > 0) {
    $is_survei = true;
}

// 6. LOGIKA VALIDASI LOKASI
$boleh_review = false;
$pesan_debug = "";

if ($is_booking) {
    // JIKA ANAK KOST -> BEBAS REVIEW DARI MANA SAJA
    $boleh_review = true;
    $pesan_debug = "Validasi: OK (Status Penyewa - Bebas Lokasi)";
} elseif ($is_survei) {
    // JIKA SURVEI -> WAJIB CEK LOKASI
    if (empty($user_lat) || empty($user_long)) {
        echo "<script>alert('Error: GPS User tidak terdeteksi. Wajib izinkan lokasi di browser.'); window.history.back();</script>";
        exit;
    }

    // Hitung Jarak
    $jarak = hitungJarak($user_lat, $user_long, $kost['latitude'], $kost['longitude']);

    // Toleransi 50 Meter
    if ($jarak <= 50) {
        $boleh_review = true;
        $pesan_debug = "Validasi: OK (Jarak $jarak meter <= 50m)";
    } else {
        echo "<script>
            alert('GAGAL! Anda terdeteksi berjarak $jarak meter dari kost. Wajib berada di lokasi (maks 50m) untuk review jalur survei.'); 
            window.history.back();
        </script>";
        exit;
    }
} else {
    echo "<script>alert('Anda tidak memiliki akses review untuk kost ini (Belum Sewa/Survei).'); window.history.back();</script>";
    exit;
}

// 7. SIMPAN DATA
if ($boleh_review) {
    // Cek dulu apakah sudah pernah review (agar tidak spam)
    $cek_double = mysqli_query($conn, "SELECT id_review FROM review WHERE id_user='$id_user' AND id_kost='$id_kost'");
    if (mysqli_num_rows($cek_double) > 0) {
        // Update review lama
        $query = "UPDATE review SET skor_akurasi='$skor_akurasi', rating='$rating_umum', komentar='$komentar' WHERE id_user='$id_user' AND id_kost='$id_kost'";
    } else {
        // Insert baru
        $query = "INSERT INTO review (id_user, id_kost, skor_akurasi, rating, komentar) VALUES ('$id_user', '$id_kost', '$skor_akurasi', '$rating_umum', '$komentar')";
    }

    if (mysqli_query($conn, $query)) {
        // TAMPILKAN HASIL DEBUG (Bisa dihapus nanti kalau sudah fix)
        echo "<script>
            alert('Berhasil Disimpan! $pesan_debug'); 
            window.location='riwayat_sewa.php'; // Hapus .php jika pakai htaccess
        </script>";
    } else {
        echo "Error DB: " . mysqli_error($conn);
    }
}
?>