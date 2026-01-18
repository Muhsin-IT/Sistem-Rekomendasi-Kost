<?php
session_start();
include 'koneksi.php';

// 1. CEK LOGIN
if (!isset($_SESSION['login'])) {
    echo "<script>alert('Silakan login.'); window.location='login';</script>";
    exit;
}

$id_user = $_SESSION['id_user'];

// Ambil dan sanitasi input
$id_kost        = isset($_POST['id_kost']) ? (int)$_POST['id_kost'] : 0;
$id_kamar       = isset($_POST['id_kamar']) && $_POST['id_kamar'] !== '' ? (int)$_POST['id_kamar'] : 0; // gunakan 0 sebagai sentinel => di SQL kita konversi 0 => NULL dengan NULLIF(?,0)
$akurasi        = isset($_POST['rating_akurasi']) ? (int)$_POST['rating_akurasi'] : 0;
$umum           = isset($_POST['rating_umum']) ? (int)$_POST['rating_umum'] : 0;
$jenis_reviewer = isset($_POST['jenis_reviewer']) ? $_POST['jenis_reviewer'] : null; // expect 'sewa' or 'survei'
$komen          = isset($_POST['komentar']) ? $_POST['komentar'] : '';
$komen          = mysqli_real_escape_string($conn, $komen);

// redirect target ke halaman detail_kost bagian ulasan
$redirect = "detail_kost?id={$id_kost}#ulasan";

// Jika ada id_review => edit
if (!empty($_POST['id_review'])) {
    $id_review = (int)$_POST['id_review'];

    // Pastikan yang edit adalah pemilik review
    $stmtCheck = mysqli_prepare($conn, "SELECT id_user FROM review WHERE id_review = ?");
    mysqli_stmt_bind_param($stmtCheck, "i", $id_review);
    mysqli_stmt_execute($stmtCheck);
    mysqli_stmt_bind_result($stmtCheck, $owner_id);
    mysqli_stmt_fetch($stmtCheck);
    mysqli_stmt_close($stmtCheck);

    if ($owner_id != $id_user) {
        echo "<script>alert('Anda tidak berhak mengedit ulasan ini.'); window.location='{$redirect}';</script>";
        exit;
    }

    // Update review; set updated_at = CURRENT_TIMESTAMP
    // gunakan NULLIF(?,0) agar id_kamar=0 disimpan sebagai NULL
    $stmt = mysqli_prepare($conn, "UPDATE review SET id_kamar = NULLIF(?,0), skor_akurasi = ?, rating = ?, komentar = ?, jenis_reviewer = ?, updated_at = CURRENT_TIMESTAMP WHERE id_review = ?");
    mysqli_stmt_bind_param($stmt, "iiissi", $id_kamar, $akurasi, $umum, $komen, $jenis_reviewer, $id_review);
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        echo "<script>alert('Ulasan berhasil diperbarui.'); window.location='{$redirect}';</script>";
        exit;
    } else {
        $err = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        echo "Error: " . $err;
        exit;
    }
}

// Create baru: cek duplikat (user+kost)
$cek = mysqli_prepare($conn, "SELECT id_review FROM review WHERE id_user = ? AND id_kost = ?");
mysqli_stmt_bind_param($cek, "ii", $id_user, $id_kost);
mysqli_stmt_execute($cek);
mysqli_stmt_store_result($cek);
$dup_count = mysqli_stmt_num_rows($cek);
mysqli_stmt_close($cek);

if ($dup_count > 0) {
    // Jika sudah ada, arahkan ke detail_kost agar user bisa mengedit dari sana
    echo "<script>alert('Anda sudah pernah memberikan ulasan untuk kost ini. Anda dapat mengedit ulasan pada halaman detail kost.'); window.location='{$redirect}';</script>";
    exit;
}

// Insert baru
// gunakan NULLIF(?,0) agar id_kamar=0 disimpan sebagai NULL
$stmtIns = mysqli_prepare($conn, "INSERT INTO review (id_user, id_kost, id_kamar, skor_akurasi, rating, komentar, jenis_reviewer) VALUES (?, ?, NULLIF(?,0), ?, ?, ?, ?)");
// tipe: id_user i, id_kost i, id_kamar i, skor_akurasi i, rating i, komentar s, jenis_reviewer s => "iiiiiss"
mysqli_stmt_bind_param($stmtIns, "iiiiiss", $id_user, $id_kost, $id_kamar, $akurasi, $umum, $komen, $jenis_reviewer);

if (mysqli_stmt_execute($stmtIns)) {
    mysqli_stmt_close($stmtIns);
    echo "<script>alert('Terima kasih! Ulasan Anda berhasil disimpan.'); window.location='{$redirect}';</script>";
    exit;
} else {
    $err = mysqli_error($conn);
    mysqli_stmt_close($stmtIns);
    echo "Error: " . $err;
    exit;
}
?>