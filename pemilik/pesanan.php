<?php
session_start();
include '../koneksi.php';

// Cek Login Pemilik
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'pemilik') {
    header("Location: ../login.php");
    exit;
}

$id_pemilik = $_SESSION['id_user'];

// LOGIKA TERIMA / TOLAK
if (isset($_GET['aksi']) && isset($_GET['id'])) {
    $id_sewa = $_GET['id'];
    $aksi    = $_GET['aksi']; // 'terima' atau 'tolak'

    if ($aksi == 'terima') {
        // 1. Update status jadi Diterima
        mysqli_query($conn, "UPDATE pengajuan_sewa SET status = 'Diterima' WHERE id_pengajuan = '$id_sewa'");

        // 2. Kurangi Stok Kamar
        // Ambil ID Kamar dulu
        $k = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id_kamar FROM pengajuan_sewa WHERE id_pengajuan = '$id_sewa'"));
        $id_kamar_dipilih = $k['id_kamar'];

        mysqli_query($conn, "UPDATE kamar SET stok_kamar = stok_kamar - 1 WHERE id_kamar = '$id_kamar_dipilih'");

        echo "<script>alert('Pesanan Diterima! Stok kamar berkurang.'); window.location='pesanan.php';</script>";
    } elseif ($aksi == 'tolak') {
        mysqli_query($conn, "UPDATE pengajuan_sewa SET status = 'Ditolak' WHERE id_pengajuan = '$id_sewa'");
        echo "<script>alert('Pesanan Ditolak.'); window.location='pesanan.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <title>Pesanan Masuk - Owner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <div class="col-md-10 p-4" style="margin-left: 16.6%;">


                <div class="container my-5">
                    <div class="d-flex justify-content-between mb-4">
                        <h3><i class="fa-solid fa-inbox me-2"></i> Pesanan Masuk</h3>
                        <a href="dashboard.php" class="btn btn-secondary">Kembali</a>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Pemohon</th>
                                            <th>Kost/Kamar</th>
                                            <th>Rencana Masuk</th>
                                            <th>Durasi</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Query ambil pesanan milik kost si pemilik ini
                                        $query = "SELECT p.*, u.nama_lengkap, u.no_hp, k.nama_kost, km.nama_tipe_kamar 
                                  FROM pengajuan_sewa p
                                  JOIN users u ON p.id_user = u.id_user
                                  JOIN kost k ON p.id_kost = k.id_kost
                                  JOIN kamar km ON p.id_kamar = km.id_kamar
                                  WHERE k.id_pemilik = '$id_pemilik'
                                  ORDER BY p.id_pengajuan DESC";

                                        $result = mysqli_query($conn, $query);

                                        if (mysqli_num_rows($result) > 0):
                                            while ($row = mysqli_fetch_assoc($result)):
                                        ?>
                                                <tr>
                                                    <td>
                                                        <strong><?= $row['nama_lengkap'] ?></strong><br>
                                                        <small class="text-muted"><?= $row['no_hp'] ?></small>
                                                    </td>
                                                    <td>
                                                        <small class="fw-bold"><?= $row['nama_kost'] ?></small><br>
                                                        <?= $row['nama_tipe_kamar'] ?>
                                                    </td>
                                                    <td><?= date('d M Y', strtotime($row['tanggal_mulai_kos'])) ?></td>
                                                    <td><?= $row['durasi_bulan'] ?> Bulan</td>
                                                    <td>
                                                        <?php if ($row['status'] == 'Menunggu'): ?>
                                                            <span class="badge bg-warning text-dark">Menunggu</span>
                                                        <?php elseif ($row['status'] == 'Diterima'): ?>
                                                            <span class="badge bg-success">Diterima</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">Ditolak</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($row['status'] == 'Menunggu'): ?>
                                                            <a href="pesanan.php?aksi=terima&id=<?= $row['id_pengajuan'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Terima pesanan ini? Stok kamar akan berkurang.')"><i class="fa-solid fa-check"></i></a>
                                                            <a href="pesanan.php?aksi=tolak&id=<?= $row['id_pengajuan'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tolak pesanan?')"><i class="fa-solid fa-xmark"></i></a>
                                                        <?php else: ?>
                                                            <span class="text-muted small">Selesai</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile;
                                        else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-4">Belum ada pesanan masuk.</td>
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
    </div>

</body>

</html>