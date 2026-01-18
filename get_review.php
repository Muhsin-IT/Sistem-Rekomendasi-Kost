<?php
session_start();
include 'koneksi.php';

header('Content-Type: application/json');

if (!isset($_SESSION['login']) || !isset($_GET['id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$id_review = (int)$_GET['id'];
$id_user = $_SESSION['id_user'];

// ambil fields tambahan untuk prefill edit
$query = mysqli_query($conn, "SELECT komentar, skor_akurasi, rating, id_kamar, jenis_reviewer FROM review WHERE id_review='$id_review' AND id_user='$id_user'");

if ($row = mysqli_fetch_assoc($query)) {
    echo json_encode([
        'success' => true,
        'komentar' => $row['komentar'],
        'skor_akurasi' => (int)$row['skor_akurasi'],
        'rating' => (int)$row['rating'],
        'id_kamar' => $row['id_kamar'],
        'jenis_reviewer' => $row['jenis_reviewer']
    ]);
} else {
    echo json_encode(['success' => false]);
}
