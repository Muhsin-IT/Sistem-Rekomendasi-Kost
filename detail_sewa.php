<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$id_pengajuan = isset($_GET['id']) ? $_GET['id'] : null;

// Ambil data detail sewa
$query = "SELECT p.*, k.nama_kost, k.alamat, k.id_pemilik,
          km.nama_tipe_kamar, km.harga_per_bulan, km.lebar_ruangan, km.sudah_termasuk_listrik,
          u.nama_lengkap as nama_pemilik, u.no_hp as hp_pemilik,
          (SELECT nama_file FROM galeri WHERE id_kamar = p.id_kamar ORDER BY (kategori_foto = 'Kamar') DESC LIMIT 1) as foto_kamar
          FROM pengajuan_sewa p
          JOIN kost k ON p.id_kost = k.id_kost
          JOIN kamar km ON p.id_kamar = km.id_kamar
          JOIN users u ON k.id_pemilik = u.id_user
          WHERE p.id_pengajuan = '$id_pengajuan' AND p.id_user = '$id_user'";

$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    echo "<script>alert('Data tidak ditemukan'); window.location='riwayat_sewa.php';</script>";
    exit;
}

// Perhitungan Tanggal
$tgl_mulai = new DateTime($data['tanggal_mulai_kos']);
$durasi = (int)$data['durasi_bulan'];
$tgl_selesai = clone $tgl_mulai;
$tgl_selesai->modify("+$durasi months");
$sekarang = new DateTime();

// Perhitungan Sisa Waktu (untuk Progress Bar)
$total_hari = $tgl_selesai->diff($tgl_mulai)->days;
$hari_berjalan = $sekarang->diff($tgl_mulai)->days;
// Jika belum mulai (hari berjalan negatif), set 0
if ($sekarang < $tgl_mulai) $hari_berjalan = 0;

$sisa_hari_obj = $tgl_selesai->diff($sekarang);
$sisa_hari = (int)$sisa_hari_obj->format('%r%a'); // %r memberi tanda minus jika lewat

// Logika persentase progress
if ($total_hari > 0) {
    if ($sekarang > $tgl_selesai) {
        $persentase = 100;
        $sisa_hari_text = "Sewa Berakhir";
    } elseif ($sekarang < $tgl_mulai) {
        $persentase = 0;
        $sisa_hari_text = $tgl_mulai->diff($sekarang)->days . " hari lagi mulai";
    } else {
        $persentase = ($hari_berjalan / $total_hari) * 100;
        $sisa_hari_text = abs($sisa_hari) . " Hari Lagi";
    }
} else {
    $persentase = 0;
    $sisa_hari_text = "-";
}

// Keuangan
$harga_per_bulan = $data['harga_per_bulan'];
$total_biaya = $harga_per_bulan * $durasi;

// Warna status
$status_badge = match ($data['status']) {
    'Diterima' => 'success',
    'Menunggu' => 'warning',
    'Ditolak' => 'danger',
    'Dibatalkan' => 'secondary',
    default => 'secondary'
};

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan #<?= $id_pengajuan ?> - RadenStay</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #f0f0f0;
            border-radius: 12px 12px 0 0 !important;
            font-weight: 600;
        }

        .bg-gradient-primary {
            background: linear-gradient(45deg, #0d6efd, #0a58ca);
            color: white;
        }
    </style>
</head>

<body>

    <?php include 'navbar.php'; ?>

    <div class="container my-5">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark mb-0">Detail Pesanan <span class="text-primary">#<?= $data['id_pengajuan'] ?></span></h2>
                <p class="text-muted small">Dibuat pada <?= date('d M Y, H:i', strtotime($data['tanggal_pengajuan'])) ?></p>
            </div>
            <div>
                <span class="badge bg-<?= $status_badge ?> fs-6 px-3 py-2 rounded-pill"><?= $data['status'] ?></span>
                <a href="riwayat_sewa.php" class="btn btn-outline-secondary ms-2 rounded-pill">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Kolom Kiri: Detil Kamar & Tagihan -->
            <div class="col-lg-8">

                <!-- Info Hunian -->
                <div class="card mb-4">
                    <div class="card-header py-3 text-primary">
                        <i class="bi bi-house-door me-2"></i>Informasi Hunian
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h4 class="fw-bold"><?= $data['nama_kost'] ?> | <?= $data['nama_tipe_kamar'] ?></h4>
                                <p class="text-muted mb-2"><i class="bi bi-geo-alt me-1"></i> <?= $data['alamat'] ?></p>
                                <span class="badge bg-info text-dark mb-3"><?= $data['nama_tipe_kamar'] ?></span>

                                <div class="row mt-3">
                                    <div class="col-6 mb-3">
                                        <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.75rem;">Check-in</small>
                                        <span class="fs-5 fw-bold text-dark"><?= $tgl_mulai->format('d M Y') ?></span>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.75rem;">Check-out</small>
                                        <span class="fs-5 fw-bold text-dark"><?= $tgl_selesai->format('d M Y') ?></span>
                                    </div>
                                    <div class="col-12">
                                        <div class="p-3 bg-light rounded d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="bi bi-rulers me-2"></i> <?= $data['lebar_ruangan'] ?>
                                            </div>
                                            <div>
                                                <i class="bi bi-lightning-charge me-2"></i> <?= $data['sudah_termasuk_listrik'] ? 'Termasuk Listrik' : 'Tidak Termasuk Listrik' ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-center d-none d-md-block">
                                <?php if (!empty($data['foto_kamar'])): ?>
                                    <!-- Pastikan path gambarnya sesuai folder upload Anda, misal assets/uploads/ -->
                                    <img src="assets/img/galeri/<?= $data['foto_kamar'] ?>" alt="Foto Kamar" class="rounded w-100 h-100" style="object-fit: cover; min-height: 150px;">
                                <?php else: ?>
                                    <div class="bg-secondary bg-opacity-10 rounded w-100 h-100 d-flex align-items-center justify-content-center" style="min-height: 150px;">
                                        <i class="bi bi-image text-muted fs-1"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tagihan Bulanan (Simulasi) -->
                <div class="card mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <span class="text-primary"><i class="bi bi-receipt me-2"></i>Rincian Tagihan Bulanan</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4">Bulan Ke</th>
                                        <th>Jatuh Tempo</th>
                                        <th>Nominal</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $upcoming_bill = 0;
                                    for ($i = 0; $i < $durasi; $i++) {
                                        $tgl_tagihan = clone $tgl_mulai;
                                        $tgl_tagihan->modify("+$i months");

                                        // Logika sederhana status bayar: 
                                        // Jika status sewa diterima, bulan berjalan dianggap lunas (atau sesuai logika bisnis Anda)
                                        // Disini kita asumsikan bulan yg sudah lewat = Lunas
                                        $is_paid = false;
                                        $status_bill_text = "Belum Bayar";
                                        $status_bill_class = "warning text-dark";

                                        if ($data['status'] == 'Diterima') {
                                            if ($sekarang >= $tgl_tagihan) {
                                                $is_paid = true;
                                                $status_bill_text = "Lunas";
                                                $status_bill_class = "success";
                                            } else {
                                                // Tagihan mendatang terdekat
                                                if ($upcoming_bill == 0) $upcoming_bill = $harga_per_bulan;
                                            }
                                        } else if ($data['status'] == 'Dibatalkan' || $data['status'] == 'Ditolak') {
                                            $status_bill_text = "Batal";
                                            $status_bill_class = "secondary";
                                        }
                                    ?>
                                        <tr>
                                            <td class="ps-4 fw-bold">Bulan <?= $i + 1 ?></td>
                                            <td><?= $tgl_tagihan->format('d M Y') ?></td>
                                            <td>Rp <?= number_format($harga_per_bulan, 0, ',', '.') ?></td>
                                            <td><span class="badge bg-<?= $status_bill_class ?> rounded-pill"><?= $status_bill_text ?></span></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Kolom Kanan: Summary & Status -->
            <div class="col-lg-4">

                <!-- Sisa Waktu Sewa -->
                <?php if ($data['status'] == 'Diterima'): ?>
                    <div class="card mb-4 bg-gradient-primary text-white">
                        <div class="card-body">
                            <h5 class="card-title fw-bold mb-3"><i class="bi bi-hourglass-split me-2"></i>Sisa Waktu Sewa</h5>

                            <div class="d-flex justify-content-between align-items-end mb-2">
                                <span class="display-6 fw-bold"><?= abs($sisa_hari) ?></span>
                                <span class="fs-5 mb-2">Hari Lagi</span>
                            </div>

                            <div class="progress bg-white bg-opacity-25" style="height: 8px;">
                                <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $persentase ?>%"></div>
                            </div>
                            <small class="mt-2 d-block text-white-50">Berakhir pada <?= $tgl_selesai->format('d M Y') ?></small>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Ringkasan Pembayaran -->
                <div class="card mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 fw-bold text-dark">Ringkasan Pembayaran</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Harga per Bulan</span>
                            <span class="fw-bold">Rp <?= number_format($harga_per_bulan, 0, ',', '.') ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Durasi Sewa</span>
                            <span class="fw-bold"><?= $durasi ?> Bulan</span>
                        </div>
                        <hr class="my-3">
                        <div class="d-flex justify-content-between mb-3">
                            <span class="h5 mb-0 fw-bold">Total Biaya</span>
                            <span class="h5 mb-0 text-primary fw-bold">Rp <?= number_format($total_biaya, 0, ',', '.') ?></span>
                        </div>

                        <?php if ($upcoming_bill > 0): ?>
                            <div class="alert alert-warning border-warning d-flex justify-content-between align-items-center mb-0 p-2">
                                <small class="text-dark fw-bold">Tagihan Mendatang</small>
                                <span class="fw-bold text-danger">Rp <?= number_format($upcoming_bill, 0, ',', '.') ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Kontak Pemilik -->
                <div class="card">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="bi bi-person-circle fs-1 text-secondary"></i>
                        </div>
                        <h5 class="fw-bold"><?= $data['nama_pemilik'] ?></h5>
                        <p class="text-muted small mb-3">Pemilik Kost</p>
                        <a href="https://wa.me/<?= $data['hp_pemilik'] ?>" target="_blank" class="btn btn-success w-100 rounded-pill">
                            <i class="bi bi-whatsapp me-2"></i>Hubungi Pemilik
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>