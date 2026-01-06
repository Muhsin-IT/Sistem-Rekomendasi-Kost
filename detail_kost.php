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
// Fungsi Jarak
function hitungJarak($lat1, $lon1, $lat2, $lon2)
{
    if (!$lat1 || !$lon1) return 0;
    $earth = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
    return round($earth * (2 * atan2(sqrt($a), sqrt(1 - $a))), 2);
}
$jarak = hitungJarak($kost['latitude'], $kost['longitude'], -7.7472, 110.3554);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $kost['nama_kost'] ?> - Detail Kost</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
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
            height: 250px;
            width: 100%;
            border-radius: 12px;
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
                                    <div class="text-primary fw-bold small mb-1"><i class="bi bi-shield-check"></i> SKOR AKURASI INFO (C5)</div>
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
                    <h6 class="fw-bold mb-2">Lokasi</h6>
                    <?php if ($kost['latitude']): ?>
                        <div id="map-detail" class="mb-3"></div>
                        <h4 class="text-center fw-bold text-success"><?= $jarak ?> Km <span class="fs-6 text-muted fw-normal">ke UNU</span></h4>
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
            var map = L.map('map-detail', {
                center: [<?= $kost['latitude'] ?>, <?= $kost['longitude'] ?>],
                zoom: 15,
                zoomControl: false,
                dragging: false
            });
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
            L.marker([<?= $kost['latitude'] ?>, <?= $kost['longitude'] ?>]).addTo(map);
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