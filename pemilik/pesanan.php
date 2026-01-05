<?php
session_start();
include '../koneksi.php';

// Cek Login Pemilik
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'pemilik') {
    header("Location: ../login.php");
    exit;
}

$id_pemilik = $_SESSION['id_user'];

// ==================== LOGIKA 1: ACTION SURVEI ====================
if (isset($_GET['aksi_survei']) && isset($_GET['id'])) {
    $id_survei = $_GET['id'];
    $aksi      = $_GET['aksi_survei'];

    if ($aksi == 'terima') {
        mysqli_query($conn, "UPDATE survei SET status = 'Diterima' WHERE id_survei = '$id_survei'");
        echo "<script>alert('Jadwal survei DISETUJUI.'); window.location='pesanan.php';</script>";
    } elseif ($aksi == 'tolak') {
        mysqli_query($conn, "UPDATE survei SET status = 'Ditolak' WHERE id_survei = '$id_survei'");
        echo "<script>alert('Jadwal survei DITOLAK.'); window.location='pesanan.php';</script>";
    } elseif ($aksi == 'selesai') {
        // Tandai survei sudah dilakukan (User datang)
        mysqli_query($conn, "UPDATE survei SET status = 'Selesai' WHERE id_survei = '$id_survei'");
        echo "<script>alert('Survei selesai.'); window.location='pesanan.php';</script>";
    }
}

// ==================== LOGIKA 2: ACTION SEWA (BOOKING) ====================
if (isset($_GET['aksi_sewa']) && isset($_GET['id'])) {
    $id_sewa = $_GET['id'];
    $aksi    = $_GET['aksi_sewa'];

    if ($aksi == 'terima') {
        mysqli_query($conn, "UPDATE pengajuan_sewa SET status = 'Diterima' WHERE id_pengajuan = '$id_sewa'");
        echo "<script>alert('Pesanan Sewa DITERIMA!'); window.location='pesanan.php';</script>";
    } elseif ($aksi == 'tolak') {
        mysqli_query($conn, "UPDATE pengajuan_sewa SET status = 'Ditolak' WHERE id_pengajuan = '$id_sewa'");

        // KEMBALIKAN STOK KAMAR
        $k = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id_kamar FROM pengajuan_sewa WHERE id_pengajuan = '$id_sewa'"));
        if ($k) {
            $id_kamar = $k['id_kamar'];
            mysqli_query($conn, "UPDATE kamar SET stok_kamar = stok_kamar + 1 WHERE id_kamar = '$id_kamar'");
        }
        echo "<script>alert('Pesanan Ditolak & Stok dikembalikan.'); window.location='pesanan.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <title>Manajemen Pesanan - Owner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>

<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <div class="col-md-10 p-4" style="margin-left: 16.6%;">

                <div class="container my-5">
                    <div class="d-flex justify-content-between mb-4">
                        <h3><i class="bi bi-inbox me-2"></i> Manajemen Permintaan</h3>
                        <a href="dashboard.php" class="btn btn-secondary">Kembali Dashboard</a>
                    </div>

                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-eye me-2"></i> Permintaan Survei Lokasi</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Pemohon</th>
                                            <th>Kost Tujuan</th>
                                            <th>Jadwal Minta</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $q_survei = mysqli_query($conn, "SELECT s.*, u.nama_lengkap, u.no_hp, k.nama_kost 
                                                         FROM survei s
                                                         JOIN users u ON s.id_user = u.id_user
                                                         JOIN kost k ON s.id_kost = k.id_kost
                                                         WHERE k.id_pemilik = '$id_pemilik'
                                                         ORDER BY s.id_survei DESC");

                                        if (mysqli_num_rows($q_survei) > 0):
                                            while ($row = mysqli_fetch_assoc($q_survei)):
                                        ?>
                                                <tr>
                                                    <td>
                                                        <strong><?= $row['nama_lengkap'] ?></strong><br>
                                                        <a href="https://wa.me/<?= $row['no_hp'] ?>" target="_blank" class="text-decoration-none small text-success">
                                                            <i class="bi bi-whatsapp"></i> <?= $row['no_hp'] ?>
                                                        </a>
                                                    </td>
                                                    <td><?= $row['nama_kost'] ?></td>
                                                    <td>
                                                        <div class="fw-bold"><?= date('d M Y', strtotime($row['tgl_survei'])) ?></div>
                                                        <small class="text-muted">Jam <?= date('H:i', strtotime($row['jam_survei'])) ?></small>
                                                    </td>
                                                    <td>
                                                        <?php if ($row['status'] == 'Menunggu'): ?>
                                                            <span class="badge bg-warning text-dark">Menunggu</span>
                                                        <?php elseif ($row['status'] == 'Diterima'): ?>
                                                            <span class="badge bg-success">Disetujui</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary"><?= $row['status'] ?></span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($row['status'] == 'Menunggu'): ?>
                                                            <a href="pesanan.php?aksi_survei=terima&id=<?= $row['id_survei'] ?>" class="btn btn-sm btn-primary" onclick="return confirm('Setujui jadwal survei ini?')">Terima</a>
                                                            <a href="pesanan.php?aksi_survei=tolak&id=<?= $row['id_survei'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Tolak jadwal?')">Tolak</a>
                                                        <?php elseif ($row['status'] == 'Diterima'): ?>
                                                            <a href="pesanan.php?aksi_survei=selesai&id=<?= $row['id_survei'] ?>" class="btn btn-sm btn-outline-secondary" onclick="return confirm('Tandai survei selesai?')">Tandai Selesai</a>
                                                        <?php else: ?>
                                                            <small class="text-muted">-</small>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile;
                                        else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-3">Tidak ada permintaan survei.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>


                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="bi bi-house-door me-2"></i> Pesanan Sewa Masuk</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Penyewa</th>
                                            <th>Kost/Kamar</th>
                                            <th>Mulai & Durasi</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $q_sewa = mysqli_query($conn, "SELECT p.*, u.nama_lengkap, u.no_hp, k.nama_kost, km.nama_tipe_kamar 
                                  FROM pengajuan_sewa p
                                  JOIN users u ON p.id_user = u.id_user
                                  JOIN kost k ON p.id_kost = k.id_kost
                                  JOIN kamar km ON p.id_kamar = km.id_kamar
                                  WHERE k.id_pemilik = '$id_pemilik'
                                  ORDER BY p.id_pengajuan DESC");

                                        if (mysqli_num_rows($q_sewa) > 0):
                                            while ($row = mysqli_fetch_assoc($q_sewa)):
                                        ?>
                                                <tr>
                                                    <td>
                                                        <strong><?= $row['nama_lengkap'] ?></strong><br>
                                                        <a href="https://wa.me/<?= $row['no_hp'] ?>" target="_blank" class="text-decoration-none small text-success">
                                                            <i class="bi bi-whatsapp"></i> <?= $row['no_hp'] ?>
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <span class="d-block fw-bold"><?= $row['nama_kost'] ?></span>
                                                        <small class="text-muted"><?= $row['nama_tipe_kamar'] ?></small>
                                                    </td>
                                                    <td>
                                                        <?= date('d M Y', strtotime($row['tanggal_mulai_kos'])) ?><br>
                                                        <span class="badge bg-light text-dark border"><?= $row['durasi_bulan'] ?> Bulan</span>
                                                    </td>
                                                    <td>
                                                        <?php if ($row['status'] == 'Menunggu'): ?>
                                                            <span class="badge bg-warning text-dark">Perlu Konfirmasi</span>
                                                        <?php elseif ($row['status'] == 'Diterima'): ?>
                                                            <span class="badge bg-success">Aktif/Diterima</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger"><?= $row['status'] ?></span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($row['status'] == 'Menunggu'): ?>
                                                            <a href="pesanan.php?aksi_sewa=terima&id=<?= $row['id_pengajuan'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Terima pesanan ini?')">Terima</a>
                                                            <a href="pesanan.php?aksi_sewa=tolak&id=<?= $row['id_pengajuan'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tolak pesanan? Stok akan dikembalikan.')">Tolak</a>
                                                        <?php else: ?>
                                                            <small class="text-muted">Selesai</small>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile;
                                        else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-3">Belum ada pesanan sewa.</td>
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