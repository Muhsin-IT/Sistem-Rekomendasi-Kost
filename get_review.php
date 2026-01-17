<?php
session_start();
include 'koneksi.php';

header('Content-Type: application/json');

if (!isset($_SESSION['login']) || !isset($_GET['id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$id_review = $_GET['id'];
$id_user = $_SESSION['id_user'];

$query = mysqli_query($conn, "SELECT komentar FROM review WHERE id_review='$id_review' AND id_user='$id_user'");

if ($row = mysqli_fetch_assoc($query)) {
    echo json_encode([
        'success' => true,
        'komentar' => $row['komentar']
    ]);
} else {
    echo json_encode(['success' => false]);
}
