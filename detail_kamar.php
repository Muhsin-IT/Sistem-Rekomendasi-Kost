<?php
session_start();
include 'koneksi.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id_kamar = $_GET['id'];

// Ambil data kamar beserta informasi kost-nya
$query = "SELECT kamar.*, kost.nama_kost, kost.id_kost, kost.alamat 
          FROM kamar 
          JOIN kost ON kamar.id_kost = kost.id_kost 
          WHERE kamar.id_kamar = '$id_kamar'";
$res = mysqli_query($conn, $query);
$k = mysqli_fetch_assoc($res);

if (!$k) {
    die("Data kamar tidak ditemukan.");
}

// Ambil Fasilitas Khusus Kamar Ini
$q_f = "SELECT mf.nama_fasilitas FROM rel_fasilitas rf 
        JOIN master_fasilitas mf ON rf.id_master_fasilitas = mf.id_master_fasilitas 
        WHERE rf.id_kamar = '$id_kamar'";
$res_f = mysqli_query($conn, $q_f);

// Ambil Peraturan Khusus Kamar Ini
$q_p = "SELECT mp.nama_peraturan FROM rel_peraturan rp 
        JOIN master_peraturan mp ON rp.id_master_peraturan = mp.id_master_peraturan 
        WHERE rp.id_kamar = '$id_kamar'";
$res_p = mysqli_query($conn, $q_p);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Detail Kamar - <?= $k['nama_tipe_kamar']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>

<body class="bg-light">

    <nav class="navbar navbar-dark bg-success mb-4">
        <div class="container">
            <a class="navbar-brand" href="detail_kost.php?id=<?= $k['id_kost']; ?>"><i class="bi bi-arrow-left"></i> Kembali ke Kost</a>
        </div>
    </nav>

    <div class="container mb-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow-sm border-0 overflow-hidden">
                    <div class="row g-0">
                        <div class="col-md-6">
                            <img src="https://homestaydijogja.net/wp-content/uploads/2023/07/Kost-Jogja-500-Ribu-di-Musafir-JEC-Jogja-Expo-Center-14.jpg" class="img-fluid h-100" style="object-fit: cover;">
                            <!-- <img src="https://via.placeholder.com/600x600?text=Foto+Kamar+<?= $k['nama_tipe_kamar']; ?>" class="img-fluid h-100" style="object-fit: cover;"> -->
                        </div>
                        <div class="col-md-6 p-4">
                            <h2 class="fw-bold text-success"><?= $k['nama_tipe_kamar']; ?></h2>
                            <h4 class="text-primary fw-bold">Rp <?= number_format($k['harga_per_bulan'], 0, ',', '.'); ?> <small class="text-muted fs-6">/ bulan</small></h4>
                            <hr>

                            <h5>Spesifikasi Kamar</h5>
                            <div class="row mb-3">
                                <div class="col-6">
                                    <small class="text-muted">Ukuran Kamar</small>
                                    <p class="fw-bold"><i class="bi bi-arrows-fullscreen"></i> <?= $k['lebar_ruangan']; ?></p>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Biaya Listrik</small>
                                    <p class="fw-bold"><i class="bi bi-lightning-charge"></i> <?= $k['sudah_termasuk_listrik'] ? 'Sudah Termasuk' : 'Belum Termasuk'; ?></p>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Status Kamar</small>
                                    <p class="fw-bold <?= $k['stok_kamar'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                        <?= $k['stok_kamar'] > 0 ? 'Tersedia ' . $k['stok_kamar'] . ' Kamar' : 'Penuh'; ?>
                                    </p>
                                </div>
                            </div>

                            <h5>Fasilitas Kamar</h5>
                            <div class="mb-4">
                                <?php while ($f = mysqli_fetch_assoc($res_f)): ?>
                                    <span class="badge bg-light text-dark border p-2 me-1 mb-1"><i class="bi bi-check-circle text-success"></i> <?= $f['nama_fasilitas']; ?></span>
                                <?php endwhile; ?>
                            </div>

                            <h5 class="">Peraturan Khusus Kamar Ini</h5>
                            <ul class="list-unstyled mb-4">
                                <?php while ($p = mysqli_fetch_assoc($res_p)): ?>
                                    <li class="small mb-1"><i class="bi bi-exclamation-triangle-fill text-danger"></i> <?= $p['nama_peraturan']; ?></li>
                                <?php endwhile; ?>
                            </ul>

                            <div class="d-grid gap-2">
                                <a href="booking.php?id=<?= $k['id_kamar']; ?>" class="btn btn-success btn-lg <?= $k['stok_kamar'] <= 0 ? 'disabled' : ''; ?>">
                                    Pesan Sekarang
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 p-3 bg-white rounded shadow-sm">
                    <h6>Lokasi Kost:</h6>
                    <p class="text-muted mb-0 small"><i class="bi bi-geo-alt"></i> <?= $k['alamat']; ?></p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>