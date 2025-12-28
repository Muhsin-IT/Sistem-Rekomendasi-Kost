<?php
session_start();
include '../koneksi.php';

// Proteksi login
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'pemilik') {
    header("Location: ../login");
    exit;
}

// Ambil ID Kost dari URL
if (!isset($_GET['id'])) {
    header("Location: dashboard");
    exit;
}

$id_kost = $_GET['id'];

// Ambil info kost untuk judul halaman
$query_kost = mysqli_query($conn, "SELECT nama_kost FROM kost WHERE id_kost = '$id_kost'");
$data_kost = mysqli_fetch_assoc($query_kost);

// Ambil daftar kamar milik kost ini (Sesuai kolom di SQL kamu)
$query_kamar = "SELECT * FROM kamar WHERE id_kost = '$id_kost'";
$res_kamar = mysqli_query($conn, $query_kamar);

// Logika Hapus Kamar
if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM kamar WHERE id_kamar = '$id_hapus'");
    echo "<script>alert('Kamar dihapus!'); window.location='kelola_kamar?id=$id_kost';</script>";
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Kelola Kamar - <?= $data_kost['nama_kost']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>

<body class="bg-light">

    <nav class="navbar navbar-dark bg-success mb-4">
        <div class="container">
            <a class="navbar-brand" href="dashboard"><i class="bi bi-arrow-left"></i> Kembali ke Dashboard</a>
        </div>
    </nav>

    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>Kelola Tipe Kamar: <strong><?= $data_kost['nama_kost']; ?></strong></h4>
            <a href="tambah_kamar?id_kost=<?= $id_kost; ?>" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Tambah Tipe Kamar Baru
            </a>
        </div>

        <div class="row">
            <?php while ($k = mysqli_fetch_assoc($res_kamar)): ?>
                <div class="col-md-6 mb-3">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <h5 class="card-title text-success">Tipe: <?= $k['nama_tipe_kamar']; ?></h5>
                                <span class="badge bg-<?= $k['stok_kamar'] > 0 ? 'success' : 'danger'; ?>">
                                    Sisa <?= $k['stok_kamar']; ?> Kamar
                                </span>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted d-block">Harga / Bulan:</small>
                                    <strong>Rp <?= number_format($k['harga_per_bulan'], 0, ',', '.'); ?></strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Ukuran:</small>
                                    <strong><?= $k['lebar_ruangan']; ?></strong>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted d-block">Listrik:</small>
                                <span class="badge <?= $k['sudah_termasuk_listrik'] ? 'bg-info' : 'bg-secondary'; ?>">
                                    <?= $k['sudah_termasuk_listrik'] ? 'Sudah Termasuk Listrik' : 'Belum Termasuk Listrik'; ?>
                                </span>
                            </div>

                            <div class="mt-3">
                                <small class="fw-bold">Fasilitas:</small><br>
                                <?php
                                $id_k = $k['id_kamar'];
                                $q_f = "SELECT mf.nama_fasilitas FROM rel_fasilitas rf 
                                JOIN master_fasilitas mf ON rf.id_master_fasilitas = mf.id_master_fasilitas 
                                WHERE rf.id_kamar = '$id_k'";
                                $res_f = mysqli_query($conn, $q_f);
                                while ($f = mysqli_fetch_assoc($res_f)):
                                ?>
                                    <span class="badge border text-dark fw-normal me-1 mb-1"><?= $f['nama_fasilitas']; ?></span>
                                <?php endwhile; ?>
                            </div>

                            <div class="mt-4 d-flex gap-2">
                                <a href="edit_kamar?id=<?= $k['id_kamar']; ?>" class="btn btn-sm btn-outline-primary w-100">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <a href="kelola_kamar?id=<?= $id_kost; ?>&hapus=<?= $k['id_kamar']; ?>"
                                    class="btn btn-sm btn-outline-danger w-100"
                                    onclick="return confirm('Hapus tipe kamar ini?')">
                                    <i class="bi bi-trash"></i> Hapus
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>

            <?php if (mysqli_num_rows($res_kamar) == 0): ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        Belum ada tipe kamar. Silakan klik tombol "Tambah Tipe Kamar Baru".
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>