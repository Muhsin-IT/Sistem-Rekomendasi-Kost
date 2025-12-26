<?php
session_start();
include 'koneksi.php';

// 1. Ambil ID Kost dari URL
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id_kost = $_GET['id'];

// 2. Ambil data utama Kost & Pemilik
$query_kost = "SELECT kost.*, users.nama_lengkap as nama_pemilik, users.no_hp 
               FROM kost 
               JOIN users ON kost.id_pemilik = users.id_user 
               WHERE kost.id_kost = '$id_kost'";
$res_kost = mysqli_query($conn, $query_kost);
$kost = mysqli_fetch_assoc($res_kost);

if (!$kost) {
    die("Data kost tidak ditemukan.");
}

// 3. Ambil Fasilitas Umum Kost
$q_f_umum = "SELECT mf.nama_fasilitaS FROM rel_fasilitas rf 
             JOIN master_fasilitas mf ON rf.id_master_fasilitas = mf.id_master_fasilitaS 
             WHERE rf.id_kost = '$id_kost'";
$res_f_umum = mysqli_query($conn, $q_f_umum);

// 4. Ambil Peraturan Kost
$q_p = "SELECT mp.nama_peraturan FROM rel_peraturan rp 
        JOIN master_peraturan mp ON rp.id_master_peraturan = mp.id_master_peraturan 
        WHERE rp.id_kost = '$id_kost'";
$res_p = mysqli_query($conn, $q_p);

// 5. Ambil Daftar Tipe Kamar
$q_kamar = "SELECT * FROM kamar WHERE id_kost = '$id_kost'";
$res_kamar = mysqli_query($conn, $q_kamar);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Detail Kost - <?= $kost['nama_kost']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .hero-img {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 15px;
        }

        .sticky-contact {
            position: sticky;
            top: 20px;
        }
    </style>
</head>

<body class="bg-light">

    <nav class="navbar navbar-dark bg-success mb-4">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="bi bi-arrow-left"></i> Kembali</a>
        </div>
    </nav>

    <div class="container mb-5">
        <div class="row">
            <div class="col-md-8">
                <img src="https://via.placeholder.com/800x400?text=Foto+Utama+Kost" class="hero-img mb-4 shadow-sm">

                <div class="card shadow-sm border-0 p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="badge bg-primary mb-2"><?= $kost['jenis_kost']; ?></span>
                            <h2 class="fw-bold"><?= $kost['nama_kost']; ?></h2>
                            <p class="text-muted"><i class="bi bi-geo-alt"></i> <?= $kost['alamat']; ?></p>
                        </div>
                    </div>

                    <hr>
                    <h5>Fasilitas Umum</h5>
                    <div class="mb-3">
                        <?php while ($fu = mysqli_fetch_assoc($res_f_umum)): ?>
                            <span class="badge border text-dark fw-normal me-2 mb-2 p-2">
                                <i class="bi bi-check-circle-fill text-success"></i> <?= $fu['nama_fasilitaS']; ?>
                            </span>
                        <?php endwhile; ?>
                    </div>

                    <hr>

                    <h5>Peraturan Kost (Gedung)</h5>
                    <ul class="list-unstyled">
                        <?php
                        // Ambil peraturan yang terhubung ke id_kost (Umum)
                        $q_p_umum = "SELECT mp.nama_peraturan FROM rel_peraturan rp 
                 JOIN master_peraturan mp ON rp.id_master_peraturan = mp.id_master_peraturan 
                 WHERE rp.id_kost = '$id_kost' AND mp.kategori = 'Kost'";
                        $res_p_umum = mysqli_query($conn, $q_p_umum);
                        while ($p = mysqli_fetch_assoc($res_p_umum)): ?>
                            <li class="small"><i class="bi bi-building text-success"></i> <?= $p['nama_peraturan']; ?></li>
                        <?php endwhile; ?>
                    </ul>
                </div>

                <h4 class="mb-3 fw-bold">Pilihan Kamar</h4>
                <?php while ($k = mysqli_fetch_assoc($res_kamar)): ?>
                    <div class="card shadow-sm border-0 p-3 mb-3">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <img src="https://via.placeholder.com/200x150?text=Kamar" class="img-fluid rounded">
                            </div>
                            <div class="col-md-6">
                                <h5 class="fw-bold text-success"><?= $k['nama_tipe_kamar']; ?></h5>
                                <p class="small text-muted mb-2">Ukuran: <?= $k['lebar_ruangan']; ?> | <?= $k['sudah_termasuk_listrik'] ? 'Sudah Termasuk Listrik' : 'Belum Listrik'; ?></p>

                                <div>
                                    <?php
                                    $id_k = $k['id_kamar'];
                                    $q_fk = "SELECT mf.nama_fasilitaS FROM rel_fasilitas rf JOIN master_fasilitas mf ON rf.id_master_fasilitas = mf.id_master_fasilitaS WHERE rf.id_kamar = '$id_k'";
                                    $res_fk = mysqli_query($conn, $q_fk);
                                    while ($fk = mysqli_fetch_assoc($res_fk)): ?>
                                        <span class="badge bg-light text-dark border me-1 mb-1" style="font-size: 0.75rem;"><?= $fk['nama_fasilitaS']; ?></span>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                            <div class="col-md-3 text-end">
                                <h5 class="fw-bold text-primary">Rp <?= number_format($k['harga_per_bulan'], 0, ',', '.'); ?></h5>
                                <p class="small <?= $k['stok_kamar'] > 0 ? 'text-success' : 'text-danger'; ?>">Sisa <?= $k['stok_kamar']; ?> Kamar</p>

                                <?php if (isset($_SESSION['login'])): ?>
                                    <a href="mahasiswa/booking.php?id=<?= $k['id_kamar']; ?>" class="btn btn-success btn-sm w-100 mt-2">Pesan Sekarang</a>
                                <?php else: ?>
                                    <a href="login.php?pesan=login_dulu&next=detail_kost.php?id=<?= $id_kost; ?>" class="btn btn-outline-success btn-sm w-100 mt-2">Login untuk Pesan</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm border-0 p-4 sticky-contact">
                    <h5 class="fw-bold mb-3">Informasi Pemilik</h5>
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                            <i class="bi bi-person-fill fs-4"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold"><?= $kost['nama_pemilik']; ?></h6>
                            <small class="text-muted">Pemilik Kost</small>
                        </div>
                    </div>
                    <hr>
                    <div class="d-grid gap-2">
                        <?php if (isset($_SESSION['login'])): ?>
                            <a href="https://wa.me/<?= $kost['no_hp']; ?>" target="_blank" class="btn btn-success">
                                <i class="bi bi-whatsapp"></i> Chat via WhatsApp
                            </a>
                        <?php else: ?>
                            <a href="login.php?pesan=login_dulu&next=detail_kost.php?id=<?= $id_kost; ?>" class="btn btn-outline-success">
                                <i class="bi bi-lock"></i> Login untuk Chat
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>