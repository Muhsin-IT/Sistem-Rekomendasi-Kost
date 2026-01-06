<?php
session_start();
include 'koneksi.php';

// 1. CEK LOGIN
if (!isset($_SESSION['login'])) {
    die(json_encode(['status' => 'error', 'msg' => 'Silakan login terlebih dahulu.']));
}

$id_user = $_SESSION['id_user'];

// 2. TERIMA DATA DARI AJAX/FORM
$id_kost      = $_POST['id_kost'];
$skor_akurasi = $_POST['rating_akurasi']; // C5
$rating_umum  = $_POST['rating_umum'];    // C6
$komentar     = mysqli_real_escape_string($conn, $_POST['komentar']);
$user_lat     = $_POST['user_lat']; // Dari GPS HP User
$user_long    = $_POST['user_long'];

// 3. AMBIL DATA KOST (LOKASI KOST)
$q_kost = mysqli_query($conn, "SELECT latitude, longitude FROM kost WHERE id_kost = '$id_kost'");
$kost   = mysqli_fetch_assoc($q_kost);

if (!$kost) {
    echo "<script>alert('Data Kost tidak ditemukan!'); window.history.back();</script>";
    exit;
}

// 4. FUNGSI HITUNG JARAK (HAVERSINE FORMULA) - Return Meter
function hitungJarak($lat1, $lon1, $lat2, $lon2)
{
    if (($lat1 == $lat2) && ($lon1 == $lon2)) {
        return 0;
    } else {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $meters = $miles * 1.609344 * 1000;
        return $meters;
    }
}

// 5. CEK HAK AKSES (Booking vs Survei)
$is_booking = false;
$is_survei  = false;

// Cek Booking (Diterima)
$cek_sewa = mysqli_query($conn, "SELECT * FROM pengajuan_sewa WHERE id_user='$id_user' AND id_kost='$id_kost' AND status='Diterima'");
if (mysqli_num_rows($cek_sewa) > 0) {
    $is_booking = true;
}

// Cek Survei (Diterima/Selesai)
$cek_survei = mysqli_query($conn, "SELECT * FROM survei WHERE id_user='$id_user' AND id_kost='$id_kost' AND (status='Diterima' OR status='Selesai')");
if (mysqli_num_rows($cek_survei) > 0) {
    $is_survei = true;
}

// 6. LOGIKA VALIDASI
$boleh_review = false;

if ($is_booking) {
    // Jika Booking, BEBAS REVIEW di mana saja (karena sudah pasti tinggal di sana/bayar)
    $boleh_review = true;
} elseif ($is_survei) {
    // Jika HANYA Survei, WAJIB CEK LOKASI (Toleransi 50m)

    // Pastikan user mengizinkan GPS
    if (empty($user_lat) || empty($user_long)) {
        echo "<script>alert('Gagal! Untuk user survei, Anda wajib mengaktifkan GPS/Lokasi browser untuk verifikasi kehadiran.'); window.history.back();</script>";
        exit;
    }

    $jarak = hitungJarak($user_lat, $user_long, $kost['latitude'], $kost['longitude']);

    if ($jarak <= 50) { // Toleransi 50 Meter
        $boleh_review = true;
    } else {
        $jarak_bulat = round($jarak);
        echo "<script>alert('Gagal! Posisi Anda terdeteksi $jarak_bulat meter dari lokasi kost. Anda harus berada di lokasi (maks 50m) untuk memberikan ulasan jalur survei.'); window.history.back();</script>";
        exit;
    }
} else {
    echo "<script>alert('Anda belum pernah memesan atau mengajukan survei di kost ini.'); window.history.back();</script>";
    exit;
}

// 7. SIMPAN KE DATABASE
if ($boleh_review) {
    $query_insert = "INSERT INTO review (id_user, id_kost, skor_akurasi, rating, komentar) 
                     VALUES ('$id_user', '$id_kost', '$skor_akurasi', '$rating_umum', '$komentar')";

    if (mysqli_query($conn, $query_insert)) {
        echo "<script>alert('Terima kasih! Ulasan & Penilaian Akurasi Anda berhasil disimpan.'); window.location='riwayat_sewa.php';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
