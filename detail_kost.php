<?php
session_start();
include 'koneksi.php';

if (!isset($_GET['id'])) {
    header("Location: index");
    exit;
}

$id_kost = $_GET['id'];

// 1. DATA KOST
$query = "SELECT k.*, u.nama_lengkap as nama_pemilik, u.no_hp as hp_pemilik 
          FROM kost k JOIN users u ON k.id_pemilik = u.id_user 
          WHERE k.id_kost = '$id_kost'";
$kost = mysqli_fetch_assoc(mysqli_query($conn, $query));

// 2. GALERI KOST (HANYA FOTO GEDUNG/UMUM)
// Foto Biasa (id_kamar NULL & is_360 = 0)
$galeri_biasa = [];
$q_gb = mysqli_query($conn, "SELECT * FROM galeri WHERE id_kost='$id_kost' AND id_kamar IS NULL AND is_360=0");
while ($row = mysqli_fetch_assoc($q_gb)) $galeri_biasa[] = $row;

// Foto 360 (id_kamar NULL & is_360 = 1)
$galeri_360 = [];
$q_g360 = mysqli_query($conn, "SELECT * FROM galeri WHERE id_kost='$id_kost' AND id_kamar IS NULL AND is_360=1");
while ($row = mysqli_fetch_assoc($q_g360)) $galeri_360[] = $row;

// 3. FASILITAS & PERATURAN KOST
$fasilitas = [];
$q_fas = mysqli_query($conn, "SELECT mf.nama_fasilitas FROM rel_fasilitas rf JOIN master_fasilitas mf ON rf.id_master_fasilitas = mf.id_master_fasilitas WHERE rf.id_kost='$id_kost' AND rf.id_kamar IS NULL");
while ($row = mysqli_fetch_assoc($q_fas)) $fasilitas[] = $row;

$peraturan = [];
$q_per = mysqli_query($conn, "SELECT mp.nama_peraturan FROM rel_peraturan rp JOIN master_peraturan mp ON rp.id_master_peraturan = mp.id_master_peraturan WHERE rp.id_kost='$id_kost' AND rp.id_kamar IS NULL");
while ($row = mysqli_fetch_assoc($q_per)) $peraturan[] = $row;

// 4. DAFTAR KAMAR
$kamars = [];
$q_kam = mysqli_query($conn, "SELECT * FROM kamar WHERE id_kost='$id_kost'");
while ($row = mysqli_fetch_assoc($q_kam)) {
    // Ambil 1 foto thumbnail kamar
    $f = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama_file FROM galeri WHERE id_kamar='" . $row['id_kamar'] . "' LIMIT 1"));
    $row['foto'] = $f ? $f['nama_file'] : null;
    $kamars[] = $row;
}
// ==================================================== review & RATING ====================================================
$q_review = mysqli_query($conn, "SELECT r.*, u.nama_lengkap FROM review r JOIN users u ON r.id_user = u.id_user WHERE r.id_kost='$id_kost' ORDER BY r.tanggal_review DESC");
$total_review = mysqli_num_rows($q_review);
$avg_rating   = 0;
$avg_akurasi  = 0;

$list_review = [];
if ($total_review > 0) {
    $sum_rating  = 0;
    $sum_akurasi = 0;
    while ($r = mysqli_fetch_assoc($q_review)) {
        $sum_rating  += $r['rating'];
        $sum_akurasi += $r['skor_akurasi'];
        $list_review[] = $r;
    }
    $avg_rating  = round($sum_rating / $total_review, 1);
    $avg_akurasi = round($sum_akurasi / $total_review, 1);
}
// ===============================================================
// Fungsi Jarak & Koordinat UNU
$lat_unu = -7.787861880324053;
$long_unu = 110.33049620439317;

function hitungJarak($lat1, $lon1, $lat2, $lon2)
{
    if (!$lat1 || !$lon1) return 0;
    $earth = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
    return round($earth * (2 * atan2(sqrt($a), sqrt(1 - $a))), 2);
}
$jarak = hitungJarak($kost['latitude'], $kost['longitude'], $lat_unu, $long_unu);
?>

<!DOCTYPE html>
<html lang="id">

<head></head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/png" sizes="32x32" href="assets/img/logo/persegi.webp">
<title><?= $kost['nama_kost'] ?> - Detail Kost</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
<script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css" />
<script src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"></script>
<link rel="stylesheet" href="style.css">
<style>
    body {
        background-color: #f4f7f9;
        color: #2c3e50;
    }

    .gallery-container {
        height: 400px;
        display: grid;
        grid-template-columns: 2fr 1fr;
        grid-template-rows: 1fr 1fr;
        gap: 8px;
        border-radius: 16px;
        overflow: hidden;
        position: relative;
        margin-bottom: 15px;
    }

    .gallery-slot {
        position: relative;
        width: 100%;
        height: 100%;
    }

    .gallery-slot.main-img {
        grid-row: 1 / 3;
    }

    .gallery-item {
        width: 100%;
        height: 100%;
        object-fit: cover;
        cursor: pointer;
        transition: 0.3s;
    }

    .gallery-item:hover {
        transform: scale(1.02);
    }

    .kategori-foto-pill {
        position: absolute;
        top: 0.75rem;
        left: 0.75rem;
        padding: 0.25rem 0.9rem;
        background: rgba(0, 0, 0, 0.7);
        color: #fff;
        border-radius: 999px;
        font-size: 0.8rem;
        letter-spacing: 0.2px;
    }

    .card-custom {
        border: none;
        border-radius: 16px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
        background: white;
    }

    #map-detail {
        height: 400px;
        width: 100%;
        border-radius: 12px;
        position: relative;
    }

    .route-info-box {
        position: absolute;
        bottom: 10px;
        left: 10px;
        z-index: 9999;
        background: white;
        padding: 12px 16px;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        min-width: 200px;
    }

    .leaflet-routing-container {
        display: none !important;
    }

    .panorama-container {
        width: 100%;
        height: 400px;
        border-radius: 10px;
    }

    /* MODAL FULLSCREEN STYLE */
    .modal-fullscreen .modal-body {
        padding: 0;
        background: #000;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100vh;
    }

    .carousel-fs-item {
        height: 100vh;
        width: 100%;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #000;
    }

    .fs-img {
        max-height: 100%;
        max-width: 100%;
        object-fit: contain;
    }

    .carousel-caption h5 {
        background-color: rgba(0, 0, 0, 0.6);
        /* Warna hitam transparansi 60% */
        color: white;
        padding: 10px 20px;
        border-radius: 30px;
        /* Membuat sudut membulat */
        display: inline-block;
        /* Agar background membungkus teks saja, bukan selebar layar */
        backdrop-filter: blur(5px);
        /* Efek blur di belakang teks (opsional, biar keren) */
        margin-bottom: 20px;
    }

    /*============================= Review Styles =====================================*/
    .rating-box {
        background: #fff;
        padding: 20px;
        border-radius: 12px;
        border: 1px solid #eee;
        text-align: center;
    }

    .rating-number {
        font-size: 2.5rem;
        font-weight: bold;
        color: #0d6efd;
        line-height: 1;
    }

    .review-item {
        border-bottom: 1px solid #f0f0f0;
        padding: 15px 0;
    }

    .review-item:last-child {
        border-bottom: none;
    }

    .avatar-review {
        width: 40px;
        height: 40px;
        background: #e9ecef;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: #0d6efd;
    }

    /*============================= /Review Styles =====================================*/

    .nearby-place-item {
        padding: 12px;
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.2s;
        cursor: pointer;
    }

    .nearby-place-item:hover {
        background: #f8f9fa;
    }

    .nearby-place-item:last-child {
        border-bottom: none;
    }

    .nearby-icon {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }

    .loading-spinner {
        text-align: center;
        padding: 20px;
    }
</style>
</head>

<body>

    <?php include 'navbar.php'; ?>

    <main class="container my-4">
        <div class="mb-3">
            <h2 class="fw-bold mb-1"><?= $kost['nama_kost'] ?></h2>
            <?php if ($total_review > 0): ?>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="badge bg-warning text-dark"><i class="bi bi-star-fill"></i> <?= $avg_rating ?></span>
                    <span class="text-muted small"><?= $total_review ?> Ulasan</span>
                </div>
            <?php endif; ?>
            <p class="text-muted"><i class="bi bi-geo-alt-fill text-danger"></i> <?= $kost['alamat'] ?></p>
        </div>

        <div class="gallery-container">
            <?php if (count($galeri_biasa) > 0): ?>
                <?php for ($i = 0; $i < min(3, count($galeri_biasa)); $i++):
                    $foto = $galeri_biasa[$i];
                    $slotClass = $i === 0 ? 'gallery-slot main-img' : 'gallery-slot';
                    $kategori = $foto['kategori_foto'] ?: 'Tanpa Kategori';
                ?>
                    <div class="<?= $slotClass ?>" onclick="bukaFullscreen(<?= $i ?>)">
                        <img src="assets/img/galeri/<?= $foto['nama_file'] ?>" class="gallery-item" />
                        <span class="kategori-foto-pill"><?= $kategori ?></span>
                    </div>
                <?php endfor; ?>
            <?php else: ?>
                <img src="https://via.placeholder.com/800x400?text=Tidak+Ada+Foto" class="gallery-item main-img">
            <?php endif; ?>
        </div>

        <div class="d-flex gap-2 mb-4">
            <button class="btn btn-light border fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalFotoBiasa">
                <i class="bi bi-grid-3x3-gap"></i> Lihat Semua Foto
            </button>
            <?php if (count($galeri_360) > 0): ?>
                <button class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modal360">
                    <i class="bi bi-arrow-repeat"></i> Virtual Tour 360°
                </button>
            <?php endif; ?>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card card-custom p-4 mb-4">
                    <h5 class="fw-bold mb-3">Fasilitas & Peraturan Gedung</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <h6 class="fw-bold text-secondary">Fasilitas Umum</h6>
                            <?php foreach ($fasilitas as $f) echo "<div class='small mb-1'><i class='bi bi-check-circle text-success me-1'></i> $f[nama_fasilitas]</div>"; ?>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold text-secondary">Peraturan</h6>
                            <?php foreach ($peraturan as $p) echo "<div class='small mb-1 text-danger'><i class='bi bi-dot'></i> $p[nama_peraturan]</div>"; ?>
                        </div>
                    </div>
                </div>

                <h4 class="fw-bold mb-3">Pilihan Kamar</h4>
                <?php foreach ($kamars as $kmr): ?>
                    <div class="card card-custom mb-3 p-3">
                        <div class="row g-0 align-items-center">
                            <div class="col-md-4">
                                <?php $img = $kmr['foto'] ? "assets/img/galeri/" . $kmr['foto'] : "https://via.placeholder.com/300x200"; ?>
                                <img src="<?= $img ?>" class="img-fluid rounded" style="height: 140px; width:100%; object-fit: cover;">
                            </div>
                            <div class="col-md-5 px-3">
                                <h5 class="fw-bold mb-1"><?= $kmr['nama_tipe_kamar'] ?></h5>
                                <p class="small text-muted mb-2"><?= $kmr['lebar_ruangan'] ?> • Listrik <?= $kmr['sudah_termasuk_listrik'] ? 'Incl.' : 'Excl.' ?></p>
                                <a href="detail_kamar.php?id=<?= $kmr['id_kamar'] ?>" class="btn btn-outline-primary btn-sm rounded-pill stretched-link">
                                    Lihat Detail Kamar
                                </a>
                            </div>
                            <div class="col-md-3 text-end border-start ps-3">
                                <h5 class="fw-bold text-primary">Rp <?= number_format($kmr['harga_per_bulan'], 0, ',', '.') ?></h5>
                                <small class="text-muted">/ bulan</small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="mt-5 mb-5">
                    <h4 class="fw-bold mb-4">Ulasan & Rating Akurasi</h4>

                    <?php if ($total_review > 0): ?>
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <div class="rating-box h-100">
                                    <div class="text-muted small mb-1">Kepuasan Umum</div>
                                    <div class="rating-number text-warning"><?= $avg_rating ?></div>
                                    <div class="small text-muted">
                                        <?php for ($i = 0; $i < 5; $i++) echo $i < round($avg_rating) ? '<i class="bi bi-star-fill text-warning"></i>' : '<i class="bi bi-star text-muted"></i>'; ?>
                                    </div>
                                    <div class="small mt-2">Dari <?= $total_review ?> Ulasan</div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="rating-box h-100 border-primary">
                                    <div class="text-primary fw-bold small mb-1"><i class="bi bi-shield-check"></i> AKURASI INFO</div>
                                    <div class="rating-number text-primary"><?= $avg_akurasi ?></div>
                                    <div class="progress mt-2" style="height: 6px;">
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?= ($avg_akurasi / 5) * 100 ?>%"></div>
                                    </div>
                                    <div class="small mt-2 text-muted">Tingkat kesesuaian foto & lokasi</div>
                                </div>
                            </div>
                        </div>

                        <div class="card card-custom p-4">
                            <?php foreach ($list_review as $rev): ?>
                                <div class="review-item">
                                    <div class="d-flex justify-content-between mb-2">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-review me-3"><?= substr($rev['nama_lengkap'], 0, 1) ?></div>
                                            <div>
                                                <h6 class="fw-bold mb-0"><?= $rev['nama_lengkap'] ?></h6>
                                                <small class="text-muted" style="font-size: 0.75rem;">
                                                    <?= date('d M Y', strtotime($rev['tanggal_review'])) ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-light text-warning border"><i class="bi bi-star-fill"></i> <?= $rev['rating'] ?></span>
                                        </div>
                                    </div>
                                    <p class="mb-1 text-dark"><?= htmlspecialchars($rev['komentar']) ?></p>
                                    <div class="d-flex gap-3">
                                        <small class="text-muted" style="font-size: 0.75rem;">
                                            <i class="bi bi-shield-check text-primary"></i> Akurasi: <strong><?= $rev['skor_akurasi'] ?>/5</strong>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-light border text-center py-4">
                            <h6 class="text-muted">Belum ada ulasan.</h6>
                            <small>Jadilah yang pertama memberikan nilai akurasi!</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card card-custom p-4 sticky-top" style="top:90px">
                    <h6 class="fw-bold mb-3"><i class="bi bi-geo-alt-fill text-danger"></i> Lokasi & Rute</h6>
                    <?php if ($kost['latitude']): ?>
                        <div id="map-detail" class="mb-3 position-relative">
                            <div id="route-info" class="route-info-box" style="display:none;">
                                <h6 class="fw-bold mb-1"><i class="bi bi-cursor-fill text-primary"></i> Rute & Estimasi</h6>
                                <div id="route-details" class="small text-dark"></div>
                            </div>
                        </div>
                        <div class="alert alert-info mb-3 py-2">
                            <small><i class="bi bi-info-circle"></i> Jarak dari kampus UNU: <strong><?= $jarak ?> km</strong></small>
                        </div>

                        <!-- TEMPAT TERDEKAT -->
                        <div class="mt-4">
                            <h6 class="fw-bold mb-3"><i class="bi bi-shop"></i> Tempat Terdekat (1 km)</h6>
                            <div id="nearby-places-container">
                                <div class="loading-spinner">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="small text-muted mt-2">Mencari fasilitas sekitar...</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <hr>
                    <div class="d-flex align-items-center">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width:40px;height:40px">
                            <?= strtoupper(substr($kost['nama_pemilik'], 0, 1)) ?>
                        </div>
                        <div class="ms-3">
                            <p class="mb-0 fw-bold"><?= $kost['nama_pemilik'] ?></p>
                            <small class="text-success">Pemilik Kost</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div class="modal fade" id="modalFotoBiasa" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Galeri Foto Kost</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light">
                    <div class="row g-3">
                        <?php foreach ($galeri_biasa as $i => $ph): ?>
                            <div class="col-md-4 col-6">
                                <div class="card h-100 border-0 shadow-sm">
                                    <div class="position-relative">
                                        <img src="assets/img/galeri/<?= $ph['nama_file'] ?>"
                                            class="card-img-top"
                                            style="cursor: pointer; height: 180px; object-fit: cover;"
                                            onclick="bukaFullscreen(<?= $i ?>)">

                                    </div>
                                    <div class="card-body p-2 text-center bg-white">
                                        <small class="fw-bold text-dark"><?= $ph['kategori_foto'] ?: 'Tanpa Kategori' ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalFullscreen" tabindex="-1">
        <div class="modal-dialog modal-fullscreen bg-dark">
            <div class="modal-content bg-dark border-0">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-4 z-3" data-bs-dismiss="modal"></button>

                <div class="modal-body p-0">
                    <div id="fsCarousel" class="carousel slide h-100 w-100" data-bs-interval="false">
                        <div class="carousel-inner h-100 w-100">
                            <?php foreach ($galeri_biasa as $idx => $g): ?>
                                <div class="carousel-item h-100 w-100 <?= $idx == 0 ? 'active' : '' ?>">
                                    <div class="carousel-fs-item">
                                        <img src="assets/img/galeri/<?= $g['nama_file'] ?>" class="fs-img">
                                        <div class="carousel-caption d-none d-md-block pb-5">
                                            <h5><?= $g['kategori_foto'] ?></h5>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <button class="carousel-control-prev" type="button" data-bs-target="#fsCarousel" data-bs-slide="prev" style="width: 10%;">
                            <span class="carousel-control-prev-icon p-3 bg-dark rounded-circle"></span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#fsCarousel" data-bs-slide="next" style="width: 10%;">
                            <span class="carousel-control-next-icon p-3 bg-dark rounded-circle"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (count($galeri_360) > 0): ?>
        <div class="modal fade" id="modal360" tabindex="-1">
            <div class="modal-dialog modal-fullscreen">
                <div class="modal-content bg-dark">
                    <div class="modal-header border-0">
                        <h5 class="modal-title fw-bold text-white">Virtual Tour 360°</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <?php foreach ($galeri_360 as $idx => $ph): ?>
                            <div class="mb-4">
                                <h6 class="text-white bg-dark"><?= $ph['kategori_foto'] ?></h6>
                                <div id="pano-<?= $idx ?>" class="panorama-container"></div>
                                <script>
                                    setTimeout(() => {
                                        pannellum.viewer('pano-<?= $idx ?>', {
                                            "type": "equirectangular",
                                            "panorama": "assets/img/galeri/<?= $ph['nama_file'] ?>",
                                            "autoLoad": true
                                        });
                                    }, 1000);
                                </script>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($kost['latitude']): ?>
        <script>
            const latUNU = <?= $lat_unu ?>;
            const longUNU = <?= $long_unu ?>;
            const latKost = <?= $kost['latitude'] ?>;
            const longKost = <?= $kost['longitude'] ?>;

            // Setup Peta
            var map = L.map('map-detail', {
                zoomControl: false
            }).setView([latKost, longKost], 14);

            L.control.zoom({
                position: 'topright'
            }).addTo(map);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: 'RadenStay'
            }).addTo(map);

            // Marker UNU (Kampus)
            const unuIcon = L.icon({
                iconUrl: 'https://cdn-icons-png.flaticon.com/512/1673/1673188.png',
                iconSize: [40, 40],
                popupAnchor: [0, -20]
            });
            L.marker([latUNU, longUNU], {
                icon: unuIcon
            }).addTo(map).bindPopup("<b>Kampus UNU</b><br>Titik Awal");

            // Marker Kost (Biru, Besar)
            const kostIcon = L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
                iconSize: [30, 50],
                iconAnchor: [15, 50],
                popupAnchor: [0, -45],
                shadowSize: [50, 50]
            });
            L.marker([latKost, longKost], {
                icon: kostIcon
            }).addTo(map).bindPopup("<b><?= $kost['nama_kost'] ?></b>");

            // Routing dari UNU ke Kost
            const routingControl = L.Routing.control({
                waypoints: [
                    L.latLng(latUNU, longUNU),
                    L.latLng(latKost, longKost)
                ],
                routeWhileDragging: false,
                addWaypoints: false,
                draggableWaypoints: false,
                fitSelectedRoutes: true,
                lineOptions: {
                    styles: [{
                        color: '#0d6efd',
                        opacity: 0.8,
                        weight: 6
                    }]
                },
                createMarker: function() {
                    return null; // Tidak buat marker tambahan
                },
                show: false
            }).on('routesfound', function(e) {
                var summary = e.routes[0].summary;
                let jarak = (summary.totalDistance / 1000).toFixed(1);
                let waktu = Math.round(summary.totalTime / 60);

                document.getElementById('route-info').style.display = 'block';
                document.getElementById('route-details').innerHTML = `
                    <div class="mb-1"><i class="bi bi-signpost-2"></i> Jarak: <b>${jarak} km</b></div>
                    <div><i class="bi bi-clock"></i> Waktu: <b>${waktu} menit</b></div>
                `;
            }).addTo(map);

            // Fit bounds agar semua marker & rute terlihat
            setTimeout(() => {
                map.fitBounds([
                    [latUNU, longUNU],
                    [latKost, longKost]
                ], {
                    padding: [50, 50]
                });
            }, 500);

            // ============================================
            // FUNGSI PENCARIAN TEMPAT TERDEKAT
            // ============================================

            function hitungJarakJS(lat1, lon1, lat2, lon2) {
                const R = 6371; // Radius bumi dalam km
                const dLat = (lat2 - lat1) * Math.PI / 180;
                const dLon = (lon2 - lon1) * Math.PI / 180;
                const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                    Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                    Math.sin(dLon / 2) * Math.sin(dLon / 2);
                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                return (R * c).toFixed(1);
            }

            function getIconAndColor(category) {
                const icons = {
                    'kesehatan': {
                        icon: 'bi-hospital',
                        color: '#dc3545',
                        bg: '#ffe5e8'
                    },
                    'minimarket': {
                        icon: 'bi-cart3',
                        color: '#198754',
                        bg: '#e6f9f0'
                    },
                    'kuliner': {
                        icon: 'bi-cup-hot',
                        color: '#fd7e14',
                        bg: '#fff4e6'
                    },
                    'bank': {
                        icon: 'bi-credit-card',
                        color: '#0dcaf0',
                        bg: '#e5f9ff'
                    },
                    'ibadah': {
                        icon: 'bi-brightness-high',
                        color: '#6f42c1',
                        bg: '#f3ebff'
                    },
                    'print': {
                        icon: 'bi-printer',
                        color: '#20c997',
                        bg: '#e6fff8'
                    },
                    'transportasi': {
                        icon: 'bi-bus-front',
                        color: '#0d6efd',
                        bg: '#e7f1ff'
                    }
                };
                return icons[category] || {
                    icon: 'bi-pin-map',
                    color: '#6c757d',
                    bg: '#f0f0f0'
                };
            }

            function formatNamaTempat(name) {
                if (!name) return 'Tanpa Nama';
                return name.length > 35 ? name.substring(0, 35) + '...' : name;
            }

            async function cariTempatTerdekat() {
                const radius = 1000; // 1 km dalam meter

                // Query Overpass API
                const query = `
                [out:json][timeout:25];
                (
                  // Kesehatan
                  node["amenity"~"hospital|clinic|pharmacy|doctors"](around:${radius},${latKost},${longKost});
                  way["amenity"~"hospital|clinic|pharmacy|doctors"](around:${radius},${latKost},${longKost});
                  
                  // Minimarket
                  node["shop"~"convenience|supermarket"]["name"~"Indomaret|Alfamart|Alfamidi|Circle K|Lawson|Tomira",i](around:${radius},${latKost},${longKost});
                  way["shop"~"convenience|supermarket"]["name"~"Indomaret|Alfamart|Alfamidi|Circle K|Lawson|Tomira",i](around:${radius},${latKost},${longKost});
                  
                  // Kuliner
                  node["amenity"~"restaurant|cafe|fast_food|food_court"](around:${radius},${latKost},${longKost});
                  way["amenity"~"restaurant|cafe|fast_food|food_court"](around:${radius},${latKost},${longKost});
                  
                  // Bank/ATM
                  node["amenity"~"bank|atm"](around:${radius},${latKost},${longKost});
                  way["amenity"~"bank|atm"](around:${radius},${latKost},${longKost});
                  
                  // Tempat Ibadah
                  node["amenity"="place_of_worship"](around:${radius},${latKost},${longKost});
                  way["amenity"="place_of_worship"](around:${radius},${latKost},${longKost});
                  
                  // Print/Fotocopy
                  node["shop"~"copyshop|stationery"]["service"~"print|copy",i](around:${radius},${latKost},${longKost});
                  way["shop"~"copyshop|stationery"]["service"~"print|copy",i](around:${radius},${latKost},${longKost});
                  
                  // Transportasi
                  node["amenity"~"fuel|bus_station|parking"]["highway"="bus_stop"](around:${radius},${latKost},${longKost});
                  way["amenity"~"fuel|bus_station|parking"](around:${radius},${latKost},${longKost});
                );
                out body;
                >;
                out skel qt;
                `;

                try {
                    const response = await fetch('https://overpass-api.de/api/interpreter', {
                        method: 'POST',
                        body: query
                    });

                    const data = await response.json();
                    const places = [];

                    data.elements.forEach(el => {
                        let lat, lon;
                        if (el.type === 'node') {
                            lat = el.lat;
                            lon = el.lon;
                        } else if (el.type === 'way' && el.center) {
                            lat = el.center.lat;
                            lon = el.center.lon;
                        } else {
                            return;
                        }

                        const jarak = hitungJarakJS(latKost, longKost, lat, lon);

                        // Kategorisasi
                        let category = 'lainnya';
                        if (el.tags.amenity === 'hospital' || el.tags.amenity === 'clinic' ||
                            el.tags.amenity === 'pharmacy' || el.tags.amenity === 'doctors') {
                            category = 'kesehatan';
                        } else if (el.tags.shop === 'convenience' || el.tags.shop === 'supermarket') {
                            category = 'minimarket';
                        } else if (el.tags.amenity === 'restaurant' || el.tags.amenity === 'cafe' ||
                            el.tags.amenity === 'fast_food' || el.tags.amenity === 'food_court') {
                            category = 'kuliner';
                        } else if (el.tags.amenity === 'bank' || el.tags.amenity === 'atm') {
                            category = 'bank';
                        } else if (el.tags.amenity === 'place_of_worship') {
                            category = 'ibadah';
                        } else if (el.tags.shop === 'copyshop' || el.tags.shop === 'stationery') {
                            category = 'print';
                        } else if (el.tags.amenity === 'fuel' || el.tags.amenity === 'bus_station' ||
                            el.tags.highway === 'bus_stop' || el.tags.amenity === 'parking') {
                            category = 'transportasi';
                        }

                        places.push({
                            name: el.tags.name || el.tags.brand || 'Tanpa Nama',
                            category: category,
                            jarak: parseFloat(jarak),
                            lat: lat,
                            lon: lon
                        });
                    });

                    // Sort by jarak
                    places.sort((a, b) => a.jarak - b.jarak);

                    // Ambil max 10 terdekat
                    const topPlaces = places.slice(0, 10);

                    tampilkanTempatTerdekat(topPlaces);

                } catch (error) {
                    console.error('Error fetching nearby places:', error);
                    document.getElementById('nearby-places-container').innerHTML = `
                        <div class="alert alert-warning small py-2 mb-0">
                            <i class="bi bi-exclamation-triangle"></i> Gagal memuat data tempat terdekat.
                        </div>
                    `;
                }
            }

            function tampilkanTempatTerdekat(places) {
                const container = document.getElementById('nearby-places-container');

                if (places.length === 0) {
                    container.innerHTML = `
                        <div class="alert alert-light small py-2 mb-0 text-center">
                            <i class="bi bi-info-circle"></i> Tidak ada fasilitas dalam radius 1 km.
                        </div>
                    `;
                    return;
                }

                let html = '<div class="list-group list-group-flush">';

                places.forEach(place => {
                    const iconData = getIconAndColor(place.category);
                    const jarakText = place.jarak < 1 ?
                        `${(place.jarak * 1000).toFixed(0)} m` :
                        `${place.jarak} km`;

                    html += `
                        <div class="nearby-place-item d-flex align-items-center gap-3">
                            <div class="nearby-icon" style="background: ${iconData.bg}; color: ${iconData.color}">
                                <i class="bi ${iconData.icon}"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0 fw-bold" style="font-size: 0.85rem">${formatNamaTempat(place.name)}</h6>
                                <small class="text-muted" style="font-size: 0.75rem">
                                    <i class="bi bi-geo-alt"></i> ${jarakText}
                                </small>
                            </div>
                        </div>
                    `;
                });

                html += '</div>';
                container.innerHTML = html;
            }

            // Jalankan pencarian saat halaman load
            setTimeout(() => {
                cariTempatTerdekat();
            }, 1000);
        </script>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        var modalFsEl = document.getElementById('modalFullscreen');
        var modalFs = new bootstrap.Modal(modalFsEl);
        var sliderEl = document.getElementById('fsCarousel');
        var bsCarousel = new bootstrap.Carousel(sliderEl, {
            interval: false
        });

        function bukaFullscreen(idx) {
            // Tutup modal grid jika sedang terbuka (misal user klik foto dari dalam modal grid)
            var modalGridEl = document.getElementById('modalFotoBiasa');
            var modalGrid = bootstrap.Modal.getInstance(modalGridEl);
            if (modalGrid) modalGrid.hide();

            modalFs.show();
            // Beri jeda sedikit agar modal render dulu, baru pindah slide (Fix Bug Bootstrap)
            setTimeout(() => {
                bsCarousel.to(idx);
            }, 250);
        }
    </script>
    <?php include 'footer.php'; ?>
</body>

</html>