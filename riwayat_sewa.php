<?php
session_start();
include 'koneksi.php'; // Pastikan path koneksi benar

// 1. CEK LOGIN
if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// 2. LOGIKA BATALKAN SEWA
if (isset($_GET['aksi']) && $_GET['aksi'] == 'batal' && isset($_GET['id'])) {
    $id_sewa = $_GET['id'];

    // Ambil ID Kamar sebelum hapus untuk kembalikan stok
    $cek = mysqli_query($conn, "SELECT id_kamar FROM pengajuan_sewa WHERE id_pengajuan='$id_sewa' AND id_user='$id_user' AND status='Menunggu'");

    if (mysqli_num_rows($cek) > 0) {
        $data = mysqli_fetch_assoc($cek);
        $id_kamar_batal = $data['id_kamar'];

        $hapus = mysqli_query($conn, "DELETE FROM pengajuan_sewa WHERE id_pengajuan='$id_sewa'");

        if ($hapus) {
            // Kembalikan Stok
            mysqli_query($conn, "UPDATE kamar SET stok_kamar = stok_kamar + 1 WHERE id_kamar = '$id_kamar_batal'");
            echo "<script>alert('Pengajuan dibatalkan & Stok dikembalikan.'); window.location='riwayat_sewa.php';</script>";
        }
    }
}

// 3. LOGIKA BATALKAN SURVEI (Opsional, hapus data survei)
if (isset($_GET['aksi']) && $_GET['aksi'] == 'batal_survei' && isset($_GET['id'])) {
    $id_survei = $_GET['id'];
    mysqli_query($conn, "DELETE FROM survei WHERE id_survei='$id_survei' AND id_user='$id_user' AND status='Menunggu'");
    echo "<script>alert('Jadwal survei dibatalkan.'); window.location='riwayat_sewa.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Aktivitas - RadenStay</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background-color: #f5f7fa;
        }
    </style>
</head>

<body>

    <?php include 'navbar.php'; ?>

    <div class="container my-5">

        <h4 class="fw-bold mb-4 text-secondary"><i class="bi bi-clock-history me-2"></i> Riwayat Aktivitas Saya</h4>

        <div class="card border-0 shadow-sm mb-5">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-eye me-2"></i> Jadwal Survei Lokasi</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Kost Tujuan</th>
                                <th>Rencana Tanggal</th>
                                <th>Jam</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $q_survei = mysqli_query($conn, "SELECT s.*, k.nama_kost, k.alamat, u.no_hp as hp_pemilik 
                                                         FROM survei s 
                                                         JOIN kost k ON s.id_kost = k.id_kost 
                                                         JOIN users u ON k.id_pemilik = u.id_user
                                                         WHERE s.id_user = '$id_user' 
                                                         ORDER BY s.id_survei DESC");

                            if (mysqli_num_rows($q_survei) > 0):
                                while ($s = mysqli_fetch_assoc($q_survei)):
                            ?>
                                    <tr>
                                        <td class="ps-4">
                                            <span class="fw-bold d-block"><?= $s['nama_kost'] ?></span>
                                            <small class="text-muted"><i class="bi bi-geo-alt"></i> <?= substr($s['alamat'], 0, 30) ?>...</small>
                                        </td>
                                        <td><?= date('d M Y', strtotime($s['tgl_survei'])) ?></td>
                                        <td><?= date('H:i', strtotime($s['jam_survei'])) ?></td>
                                        <td>
                                            <?php if ($s['status'] == 'Menunggu'): ?>
                                                <span class="badge bg-warning text-dark">Menunggu</span>
                                            <?php elseif ($s['status'] == 'Diterima'): ?>
                                                <span class="badge bg-success">Disetujui</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Ditolak</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <?php if ($s['status'] == 'Diterima'): ?>
                                                <a href="https://wa.me/<?= $s['hp_pemilik'] ?>?text=Halo Kak, saya mau konfirmasi jadi survei ke <?= $s['nama_kost'] ?> tanggal <?= $s['tgl_survei'] ?> jam <?= $s['jam_survei'] ?>" target="_blank" class="btn btn-sm btn-success rounded-pill">
                                                    <i class="bi bi-whatsapp"></i> Chat
                                                </a>
                                            <?php elseif ($s['status'] == 'Menunggu'): ?>
                                                <a href="riwayat_sewa.php?aksi=batal_survei&id=<?= $s['id_survei'] ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Batalkan jadwal survei ini?')">Batal</a>
                                            <?php else: ?>
                                                <small class="text-muted">-</small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile;
                            else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">Belum ada jadwal survei.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>


        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0 text-success"><i class="bi bi-house-check me-2"></i> Pengajuan Sewa Kamar</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Kost/Kamar</th>
                                <th>Mulai Kost</th>
                                <th>Durasi</th>
                                <th>Biaya</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $q_sewa = mysqli_query($conn, "SELECT p.*, k.nama_kost, k.alamat, km.nama_tipe_kamar, km.harga_per_bulan, u.no_hp as hp_pemilik
                                  FROM pengajuan_sewa p
                                  JOIN kost k ON p.id_kost = k.id_kost
                                  JOIN kamar km ON p.id_kamar = km.id_kamar
                                  JOIN users u ON k.id_pemilik = u.id_user
                                  WHERE p.id_user = '$id_user'
                                  ORDER BY p.id_pengajuan DESC");

                            if (mysqli_num_rows($q_sewa) > 0):
                                while ($row = mysqli_fetch_assoc($q_sewa)):
                                    $total = $row['harga_per_bulan'] * $row['durasi_bulan'];
                            ?>
                                    <tr>
                                        <td class="ps-4">
                                            <span class="fw-bold d-block"><?= $row['nama_kost'] ?></span>
                                            <small class="text-muted"><?= $row['nama_tipe_kamar'] ?></small>
                                        </td>
                                        <td><?= date('d M Y', strtotime($row['tanggal_mulai_kos'])) ?></td>
                                        <td><?= $row['durasi_bulan'] ?> Bulan</td>
                                        <td class="fw-bold text-primary">Rp <?= number_format($total, 0, ',', '.') ?></td>
                                        <td>
                                            <?php if ($row['status'] == 'Menunggu'): ?>
                                                <span class="badge bg-warning text-dark">Menunggu</span>
                                            <?php elseif ($row['status'] == 'Diterima'): ?>
                                                <span class="badge bg-success">Diterima</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger"><?= $row['status'] ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <?php if ($row['status'] == 'Menunggu'): ?>
                                                <a href="riwayat_sewa.php?aksi=batal&id=<?= $row['id_pengajuan'] ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Batalkan sewa? Stok kamar akan dikembalikan.')">Batal</a>
                                            <?php elseif ($row['status'] == 'Diterima'): ?>
                                                <a href="https://wa.me/<?= $row['hp_pemilik'] ?>?text=Halo Kak, pengajuan sewa saya di <?= $row['nama_kost'] ?> sudah DITERIMA. Mohon info pembayaran." target="_blank" class="btn btn-sm btn-success rounded-pill"><i class="bi bi-whatsapp"></i> Bayar</a>
                                            <?php else: ?>
                                                <small class="text-muted">Selesai</small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile;
                            else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Belum ada pengajuan sewa.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>