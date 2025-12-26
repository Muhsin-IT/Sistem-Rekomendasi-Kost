<?php
session_start();
include '../koneksi.php';

// Proteksi halaman: Hanya role 'pemilik' yang bisa masuk
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'pemilik') {
    header("Location: ../login.php");
    exit;
}

$id_pemilik = $_SESSION['id_user'];

// Tambahkan: siapkan nama tampilan dengan fallback agar tidak munculkan warning
$displayName = $_SESSION['nama'] ?? $_SESSION['username'] ?? 'Pemilik';

// Ambil statistik ringkasan untuk pemilik ini
$query_statistik = "SELECT 
    (SELECT COUNT(*) FROM kost WHERE id_pemilik = '$id_pemilik') as total_kost,
    (SELECT SUM(stok_kamar) FROM kamar JOIN kost ON kamar.id_kost = kost.id_kost WHERE kost.id_pemilik = '$id_pemilik') as total_kamar";
$res_stat = mysqli_query($conn, $query_statistik);
$stat = mysqli_fetch_assoc($res_stat);

// Ambil daftar kost yang dimiliki
$query_kost = "SELECT * FROM kost WHERE id_pemilik = '$id_pemilik'";
$res_kost = mysqli_query($conn, $query_kost);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Dashboard Pemilik - Kost UNU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>

<body>

    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Selamat Datang, <?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>!</h2>
                    <a href="tambah_kost.php" class="btn btn-success"><i class="bi bi-plus-lg"></i> Tambah Kost Baru</a>
                </div>

                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card card-stat shadow-sm p-3">
                            <small class="text-muted">Total Properti Kost</small>
                            <h3 class="fw-bold"><?= $stat['total_kost'] ?? 0; ?></h3>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-stat shadow-sm p-3 border-primary">
                            <small class="text-muted">Total Kamar Tersedia</small>
                            <h3 class="fw-bold text-primary"><?= $stat['total_kamar'] ?? 0; ?></h3>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-stat shadow-sm p-3 border-warning">
                            <small class="text-muted">Chat Masuk</small>
                            <h3 class="fw-bold text-warning">0</h3>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Daftar Properti Kost Anda</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Kost</th>
                                        <th>Jenis</th>
                                        <th>Alamat</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    while ($k = mysqli_fetch_assoc($res_kost)):
                                    ?>
                                        <tr>
                                            <td><?= $no++; ?></td>
                                            <td><strong><?= $k['nama_kost']; ?></strong></td>
                                            <td><span class="badge bg-info text-dark"><?= $k['jenis_kost']; ?></span></td>
                                            <td><small><?= $k['alamat']; ?></small></td>
                                            <td>
                                                <a href="kelola_kamar.php?id=<?= $k['id_kost']; ?>" class="btn btn-sm btn-primary">Kelola Kamar</a>
                                                <a href="edit_kost.php?id=<?= $k['id_kost']; ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>

                                    <?php if (mysqli_num_rows($res_kost) == 0): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">Belum ada kost yang didaftarkan.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>