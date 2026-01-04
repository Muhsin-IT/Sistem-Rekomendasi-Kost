<?php
session_start();
include 'koneksi.php'; // Sesuaikan path jika file ini ada di dalam folder

// 1. CEK LOGIN MAHASISWA
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: login");
    exit;
}

$id_user = $_SESSION['id_user'];

// 2. LOGIKA BATALKAN PESANAN (Hanya jika status 'Menunggu')
if (isset($_GET['aksi']) && $_GET['aksi'] == 'batal' && isset($_GET['id'])) {
    $id_sewa = $_GET['id'];

    // Pastikan yang dihapus adalah milik user yang sedang login (Keamanan)
    $cek = mysqli_query($conn, "SELECT id_pengajuan FROM pengajuan_sewa WHERE id_pengajuan='$id_sewa' AND id_user='$id_user' AND status='Menunggu'");

    if (mysqli_num_rows($cek) > 0) {
        mysqli_query($conn, "DELETE FROM pengajuan_sewa WHERE id_pengajuan='$id_sewa'");
        echo "<script>alert('Pengajuan berhasil dibatalkan.'); window.location='riwayat_sewa';</script>";
    } else {
        echo "<script>alert('Gagal membatalkan. Mungkin status sudah berubah.'); window.location='riwayat_sewa';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Sewa - RadenStay</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background-color: #f5f7fa;
        }

        .card-history {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
            overflow: hidden;
            margin-bottom: 20px;
            background: white;
        }

        .card-history:hover {
            transform: translateY(-5px);
        }

        .status-badge {
            font-size: 0.8rem;
            padding: 6px 15px;
            border-radius: 50px;
            font-weight: 600;
        }

        .price-tag {
            color: #0d6efd;
            font-weight: bold;
            font-size: 1.1rem;
        }

        .img-thumb-history {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
            min-height: 120px;
        }
    </style>
</head>

<body>

    <?php include 'navbar.php'; ?>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">

                <h4 class="fw-bold mb-4 text-secondary border-bottom pb-3">
                    <i class="bi bi-clock-history me-2"></i> Riwayat Pengajuan Sewa
                </h4>

                <?php
                // QUERY MENGAMBIL DATA LENGKAP
                // Gabungkan: pengajuan -> kost -> kamar -> users (pemilik)
                $query = "SELECT p.*, k.nama_kost, k.alamat, km.nama_tipe_kamar, km.harga_per_bulan, u.nama_lengkap as nama_pemilik, u.no_hp as hp_pemilik
                      FROM pengajuan_sewa p
                      JOIN kost k ON p.id_kost = k.id_kost
                      JOIN kamar km ON p.id_kamar = km.id_kamar
                      JOIN users u ON k.id_pemilik = u.id_user
                      WHERE p.id_user = '$id_user'
                      ORDER BY p.id_pengajuan DESC";

                $result = mysqli_query($conn, $query);

                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        // Hitung Total Bayar
                        $total_bayar = $row['harga_per_bulan'] * $row['durasi_bulan'];

                        // Tentukan Warna Status & Icon
                        $status_class = 'bg-secondary';
                        $icon_status  = 'bi-question-circle';
                        $status_text  = $row['status'];

                        if ($row['status'] == 'Menunggu') {
                            $status_class = 'bg-warning text-dark';
                            $icon_status  = 'bi-hourglass-split';
                        } elseif ($row['status'] == 'Diterima') {
                            $status_class = 'bg-success';
                            $icon_status  = 'bi-check-circle-fill';
                        } elseif ($row['status'] == 'Ditolak') {
                            $status_class = 'bg-danger';
                            $icon_status  = 'bi-x-circle-fill';
                        } else {
                            // Jika status dibatalkan atau lainnya
                            $status_class = 'bg-secondary';
                            $icon_status  = 'bi-dash-circle';
                        }
                ?>

                        <div class="card card-history">
                            <div class="card-body p-4">
                                <div class="row align-items-center">

                                    <div class="col-md-5 mb-3 mb-md-0">
                                        <h5 class="fw-bold mb-1 text-primary"><?= $row['nama_kost'] ?></h5>
                                        <p class="text-muted small mb-2"><i class="bi bi-geo-alt-fill me-1"></i> <?= $row['alamat'] ?></p>

                                        <div class="d-flex gap-3 mt-3">
                                            <div class="bg-light p-2 rounded border">
                                                <small class="d-block text-muted" style="font-size: 11px;">Tipe Kamar</small>
                                                <span class="fw-bold text-dark small"><?= $row['nama_tipe_kamar'] ?></span>
                                            </div>
                                            <div class="bg-light p-2 rounded border">
                                                <small class="d-block text-muted" style="font-size: 11px;">Durasi</small>
                                                <span class="fw-bold text-dark small"><?= $row['durasi_bulan'] ?> Bulan</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-3 mb-3 mb-md-0 border-start border-end px-md-4">
                                        <small class="text-muted d-block">Mulai Tanggal:</small>
                                        <div class="fw-bold mb-3 text-dark">
                                            <i class="bi bi-calendar-event me-1"></i>
                                            <?= date('d M Y', strtotime($row['tanggal_mulai_kos'])) ?>
                                        </div>

                                        <small class="text-muted d-block">Total Biaya:</small>
                                        <div class="price-tag">Rp <?= number_format($total_bayar, 0, ',', '.') ?></div>
                                    </div>

                                    <div class="col-md-4 text-center">
                                        <div class="mb-3">
                                            <span class="badge status-badge <?= $status_class ?>">
                                                <i class="bi <?= $icon_status ?> me-1"></i> <?= $status_text ?>
                                            </span>
                                        </div>

                                        <?php if ($row['status'] == 'Menunggu'): ?>
                                            <a href="riwayat_sewa.php?aksi=batal&id=<?= $row['id_pengajuan'] ?>" class="btn btn-outline-danger btn-sm w-100 rounded-pill" onclick="return confirm('Yakin ingin membatalkan pengajuan ini?')">
                                                <i class="bi bi-trash3 me-1"></i> Batalkan
                                            </a>
                                            <small class="text-muted d-block mt-2 fst-italic" style="font-size: 11px;">Menunggu konfirmasi pemilik</small>

                                        <?php elseif ($row['status'] == 'Diterima'): ?>
                                            <?php
                                            // Template Pesan WA
                                            $pesan = "Halo Kak, saya " . $_SESSION['nama_lengkap'] . ". Pesanan kost saya di " . $row['nama_kost'] . " (Tipe " . $row['nama_tipe_kamar'] . ") sudah DITERIMA di aplikasi. Mohon info pembayaran selanjutnya ya. Terima kasih.";
                                            $linkWA = "https://wa.me/" . $row['hp_pemilik'] . "?text=" . urlencode($pesan);
                                            ?>
                                            <a href="<?= $linkWA ?>" target="_blank" class="btn btn-success btn-sm w-100 rounded-pill mb-2 fw-bold">
                                                <i class="bi bi-whatsapp me-1"></i> Hubungi Pemilik
                                            </a>
                                            <div class="alert alert-success py-1 px-2 small mb-0" style="font-size: 11px;">
                                                <i class="bi bi-info-circle"></i> Segera hubungi pemilik untuk pembayaran/ambil kunci.
                                            </div>

                                        <?php elseif ($row['status'] == 'Ditolak'): ?>
                                            <button class="btn btn-secondary btn-sm w-100 rounded-pill" disabled>Permintaan Ditolak</button>
                                            <small class="text-muted d-block mt-2" style="font-size: 11px;">Mungkin kamar penuh.</small>
                                        <?php endif; ?>
                                    </div>

                                </div>
                            </div>
                        </div>

                <?php
                    } // End While
                } else {
                    // TAMPILAN JIKA KOSONG
                    echo '
                <div class="text-center py-5">
                    <div class="mb-3">
                         <i class="bi bi-clipboard-x text-muted" style="font-size: 4rem;"></i>
                    </div>
                    <h5 class="text-muted fw-bold">Belum ada riwayat sewa.</h5>
                    <p class="text-muted small">Ayo cari kost impianmu sekarang!</p>
                    <a href="index" class="btn btn-primary rounded-pill px-4 mt-2">Cari Kost</a>
                </div>';
                }
                ?>

            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>