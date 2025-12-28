<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'pemilik') {
    header("Location: ../login.php");
    exit;
}

$id_pemilik = $_SESSION['id_user'];

// --- PERBAIKAN NAMA PEMILIK ---
// Kita ambil langsung dari database supaya pasti ada namanya
$query_user = mysqli_query($conn, "SELECT nama_lengkap FROM users WHERE id_user = '$id_pemilik'");
$user_data = mysqli_fetch_assoc($query_user);
$displayName = $user_data['nama_lengkap'] ?? 'Pemilik';

// LOGIKA HAPUS KOST
if (isset($_GET['hapus_id'])) {
    $id_hapus = $_GET['hapus_id'];

    // 1. Ambil semua nama file foto di galeri terkait kost ini
    $get_fotos = mysqli_query($conn, "SELECT nama_file FROM galeri WHERE id_kost = '$id_hapus'");
    while ($foto = mysqli_fetch_assoc($get_fotos)) {
        $path = "../assets/img/galeri/" . $foto['nama_file'];
        if (file_exists($path)) {
            unlink($path);
        }
    }

    // 2. Hapus data kost (Pastikan hanya bisa hapus milik sendiri)
    $query_hapus = mysqli_query($conn, "DELETE FROM kost WHERE id_kost = '$id_hapus' AND id_pemilik = '$id_pemilik'");

    if ($query_hapus) {
        echo "<script>alert('Kost berhasil dihapus!'); window.location='dashboard.php';</script>";
    }
}

// Ambil statistik
$query_statistik = "SELECT 
    (SELECT COUNT(*) FROM kost WHERE id_pemilik = '$id_pemilik') as total_kost,
    (SELECT SUM(stok_kamar) FROM kamar JOIN kost ON kamar.id_kost = kost.id_kost WHERE kost.id_pemilik = '$id_pemilik') as total_kamar";
$res_stat = mysqli_query($conn, $query_statistik);
$stat = mysqli_fetch_assoc($res_stat);

// Ambil daftar kost
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

            <div class="col-md-10 p-4" style="margin-left: 16.6%;">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Selamat Datang, <?= htmlspecialchars($displayName); ?>!</h2>
                    <a href="tambah_kost.php" class="btn btn-success"><i class="bi bi-plus-lg"></i> Tambah Kost Baru</a>
                </div>

                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card shadow-sm p-3">
                            <small class="text-muted">Total Properti Kost</small>
                            <h3 class="fw-bold"><?= $stat['total_kost'] ?? 0; ?></h3>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow-sm p-3 border-primary border-start border-4">
                            <small class="text-muted">Total Kamar Tersedia</small>
                            <h3 class="fw-bold text-primary"><?= $stat['total_kamar'] ?? 0; ?></h3>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Kost</th>
                                        <th>Jenis</th>
                                        <th>Alamat</th>
                                        <th class="text-center">Aksi</th>
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
                                            <td><small class="text-muted"><?= $k['alamat']; ?></small></td>
                                            <td class="text-center">
                                                <a href="kelola_kamar.php?id=<?= $k['id_kost']; ?>" class="btn btn-sm btn-primary">Kelola Kamar</a>
                                                <a href="edit_kost.php?id=<?= $k['id_kost']; ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>

                                                <a href="?hapus_id=<?= $k['id_kost']; ?>"
                                                    class="btn btn-danger btn-sm"
                                                    onclick="return confirm('Apakah Anda yakin ingin menghapus kost ini?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>

</html>