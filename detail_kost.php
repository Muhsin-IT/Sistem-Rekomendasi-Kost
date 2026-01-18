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
// Perbarui query: JOIN users + LEFT JOIN kamar untuk dapat menampilkan nama_kamar saat ada id_kamar
$q_review = mysqli_query($conn, "SELECT r.*, u.nama_lengkap, k.nama_tipe_kamar FROM review r 
    JOIN users u ON r.id_user = u.id_user 
    LEFT JOIN kamar k ON r.id_kamar = k.id_kamar
    WHERE r.id_kost='$id_kost' ORDER BY r.tanggal_review DESC");

$total_review = mysqli_num_rows($q_review);
$avg_rating   = 0;
$avg_akurasi  = 0;

$list_review = [];
if ($total_review > 0) {
    $sum_rating  = 0;
    $sum_akurasi = 0;
    while ($r = mysqli_fetch_assoc($q_review)) {
        $sum_rating  += (int)$r['rating'];
        $sum_akurasi += (int)$r['skor_akurasi'];
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

// ==========================================================
// LOGIC FAIR PRICE CHECKER 
// ==========================================================

// 0. Ambil Harga Termurah Kost Ini (Karena belum ada di variabel $kost)
$q_min_price = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MIN(harga_per_bulan) as min_p FROM kamar WHERE id_kost='$id_kost'"));
$harga_min_ini = $q_min_price['min_p'] ?? 0;

// 1. Ambil Data Kost Lain (Sebagai Data Pembanding/Pasar)
$query_market = "SELECT k.id_kost, k.latitude, k.longitude,
                (SELECT MIN(harga_per_bulan) FROM kamar WHERE id_kost = k.id_kost) as harga,
                (SELECT COUNT(id_rel_fasilitas) FROM rel_fasilitas WHERE id_kost = k.id_kost) as jum_fas,
                (SELECT COUNT(id_rel_peraturan) FROM rel_peraturan WHERE id_kost = k.id_kost) as jum_per
                FROM kost k 
                WHERE k.id_kost != '$id_kost' 
                AND k.latitude != 0";

$res_market = mysqli_query($conn, $query_market);
$market_data = [];

// Koordinat UNU (Pastikan variabel ini terbaca, jika error definisikan ulang di sini)
$lat_unu_ai = -7.787861880324053;
$long_unu_ai = 110.33049620439317;

while ($m = mysqli_fetch_assoc($res_market)) {
    if ($m['harga'] > 0) {
        // Gunakan fungsi hitungJarak yang sudah ada di file Anda
        $jarak = hitungJarak($m['latitude'], $m['longitude'], $lat_unu_ai, $long_unu_ai);
        $market_data[] = [
            'price' => (int)$m['harga'],
            'distance' => (float)$jarak,
            'total_fasilitas' => (int)$m['jum_fas'],
            'total_peraturan' => (int)$m['jum_per']
        ];
    }
}

// 2. Siapkan Data Target (Kost ini)
// PERBAIKAN DISINI: Ganti $data_kost menjadi $kost
$jarak_target = hitungJarak($kost['latitude'], $kost['longitude'], $lat_unu_ai, $long_unu_ai);
$q_fas_target = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM rel_fasilitas WHERE id_kost='$id_kost'"));
$q_per_target = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM rel_peraturan WHERE id_kost='$id_kost'"));

$target_data = [
    'price' => (int)$harga_min_ini, // Gunakan harga yang baru diambil
    'distance' => (float)$jarak_target,
    'total_fasilitas' => (int)$q_fas_target['c'],
    'total_peraturan' => (int)$q_per_target['c']
];

// 3. Kirim ke Python API
$ai_prediction = null;
if (count($market_data) > 3) { // Minimal ada 3 data pasar
    $url_api = "http://127.0.0.1:5001/predict-price";
    $payload = json_encode(['target' => $target_data, 'market' => $market_data]);

    $ch = curl_init($url_api);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);

    $resp = curl_exec($ch);
    $ai_prediction = json_decode($resp, true);
    curl_close($ch);
}

// ==========================================================
// LOGIC SMART REVIEW SUMMARY (AI)
// ==========================================================

// 1. Ambil Semua Teks Ulasan untuk Kost Ini
$query_komentar = mysqli_query($conn, "SELECT komentar FROM review WHERE id_kost='$id_kost' AND komentar != ''");
$all_reviews = [];
while ($row_ul = mysqli_fetch_assoc($query_komentar)) {
    $all_reviews[] = $row_ul['komentar'];
}

$ai_review_summary = null;

// 2. Kirim ke Python (Hanya jika ada ulasan)
if (count($all_reviews) > 0) {
    $url_api_rev = "http://127.0.0.1:5001/analyze-reviews";
    $payload_rev = json_encode(['reviews' => $all_reviews]);

    $ch_rev = curl_init($url_api_rev);
    curl_setopt($ch_rev, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch_rev, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch_rev, CURLOPT_POST, true);
    curl_setopt($ch_rev, CURLOPT_POSTFIELDS, $payload_rev);
    curl_setopt($ch_rev, CURLOPT_TIMEOUT, 3);

    $resp_rev = curl_exec($ch_rev);
    $ai_review_summary = json_decode($resp_rev, true);
    curl_close($ch_rev);
}

// ===============================================================
// cek eligibility tombol Ulasan untuk user yang login
$user_has_review = false;
$user_has_sewa = false;
$user_has_survei = false;
$can_review = false;
$default_reviewer = 'survei';

if (isset($_SESSION['login'])) {
	$id_user_check = $_SESSION['id_user'];
	// sudah mengulas?
	$qr = mysqli_query($conn, "SELECT id_review FROM review WHERE id_kost='$id_kost' AND id_user='$id_user_check' LIMIT 1");
	$user_has_review = mysqli_num_rows($qr) > 0;

	// pernah sewa (Diterima/Selesai)?
	$qs = mysqli_query($conn, "SELECT id_pengajuan FROM pengajuan_sewa WHERE id_kost='$id_kost' AND id_user='$id_user_check' AND status IN ('Diterima','Selesai') LIMIT 1");
	$user_has_sewa = mysqli_num_rows($qs) > 0;

	// pernah survei (Diterima/Selesai)?
	$qv = mysqli_query($conn, "SELECT id_survei FROM survei WHERE id_kost='$id_kost' AND id_user='$id_user_check' AND status IN ('Diterima','Selesai') LIMIT 1");
	$user_has_survei = mysqli_num_rows($qv) > 0;

	$can_review = (!$user_has_review) && ($user_has_sewa || $user_has_survei);
	$default_reviewer = $user_has_sewa ? 'sewa' : ($user_has_survei ? 'survei' : 'survei');
}
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
        padding: 10px;
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
        width: 32px;
        height: 32px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
        flex-shrink: 0;
    }

    .nearby-list-container {
        max-height: 320px;
        overflow-y: auto;
        overflow-x: hidden;
    }

    .nearby-list-container::-webkit-scrollbar {
        width: 6px;
    }

    .nearby-list-container::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    .nearby-list-container::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 10px;
    }

    .nearby-list-container::-webkit-scrollbar-thumb:hover {
        background: #555;
    }

    .loading-spinner {
        text-align: center;
        padding: 20px;
    }

    /* star-rating styles (Shopee-like: click star n fills 1..n from left) */
    .stars {
        display: flex;
        flex-direction: row-reverse;
        gap: 6px;
        align-items: center;
    }
    .stars input { display: none; }

    /* make each star label fill available width and show large star icon */
    .stars label {
        cursor: pointer;
        flex: 1 1 0;
        text-align: center;
        padding: 8px 4px;
        font-size: 2rem; /* larger clickable area */
        color: #d1d5db;  /* inactive gray */
        transition: color .12s ease-in-out, transform .08s;
        line-height: 1;
    }
    .stars label .bi {
        font-size: 1.8rem; /* icon size */
        vertical-align: middle;
        display: inline-block;
    }

    /* hover effect */
    .stars label:hover,
    .stars label:hover ~ label {
        transform: scale(1.05);
    }

    /* akurasi = gold (checked and hover affect labels to the left visually) */
    .stars.akurasi input:checked ~ label,
    .stars.akurasi label:hover,
    .stars.akurasi label:hover ~ label {
        color: #ffc107; /* gold */
    }

    /* umum = green */
    .stars.umum input:checked ~ label,
    .stars.umum label:hover,
    .stars.umum label:hover ~ label {
        color: #198754; /* bootstrap-success */
    }

    /* ensure contrast for checked state on small screens */
    @media (max-width: 480px) {
        .stars label { font-size: 1.6rem; padding: 6px 2px; }
        .stars label .bi { font-size: 1.4rem; }
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
                                <a href="detail_kamar?id=<?= $kmr['id_kamar'] ?>" class="btn btn-outline-primary btn-sm rounded-pill stretched-link">
                                    Lihat Detail Kamar
                                </a>
                            </div>
                            <div class="col-md-3 text-end border-start ps-3">
                                <h5 class="fw-bold text-primary">Rp <?= number_format($kmr['harga_per_bulan'], 0, ',', '.') ?></h5>
                                <small class="text-muted">/ bulan</small>
                            </div>
                            <?php if (isset($ai_prediction['status']) && $ai_prediction['status'] == 'success'): ?>
                                <div class="alert alert-light border shadow-sm mt-3 d-flex align-items-center gap-3">
                                    <div class="fs-1 text-<?= $ai_prediction['color'] ?>">
                                        <?php if ($ai_prediction['color'] == 'danger'): ?>
                                            <i class="bi bi-graph-up-arrow"></i>
                                        <?php elseif ($ai_prediction['color'] == 'primary'): ?>
                                            <i class="bi bi-tags-fill"></i>
                                        <?php else: ?>
                                            <i class="bi bi-check-circle-fill"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold mb-0 text-<?= $ai_prediction['color'] ?>">
                                            <?= $ai_prediction['label'] ?>
                                        </h6>
                                        <small class="text-muted" style="font-size: 0.8rem;">
                                            Menurut AI, harga wajar untuk fasilitas & lokasi ini adalah
                                            <strong>Rp <?= number_format($ai_prediction['predicted_price'], 0, ',', '.') ?></strong>.
                                            <br>
                                            (Selisih: <?= $ai_prediction['diff_percent'] ?>%)
                                        </small>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="mt-5 mb-5">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="fw-bold mb-0">Ulasan & Rating Akurasi</h4>
                        <?php if (isset($_SESSION['login']) && $can_review): ?>
                            <a href="javascript:void(0)" class="btn btn-primary btn-lg fw-bold" 
                               onclick="bukaModalReviewFromPage('<?= $id_kost ?>','<?= addslashes($kost['nama_kost']) ?>', <?= $kost['latitude'] ?: 0 ?>, <?= $kost['longitude'] ?: 0 ?>, '<?= $default_reviewer ?>')">
                                <i class="bi bi-star-fill me-1"></i> Beri Ulasan
                            </a>
                        <?php endif; ?>
                    </div>

                    <?php if (isset($ai_review_summary['status']) && $ai_review_summary['status'] == 'success'): ?>
                        <div class="card border-0 shadow-sm bg-light mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold mb-0">
                                        <i class="bi bi-robot text-primary"></i> Ringkasan Ulasan AI
                                    </h6>
                                    <span class="badge bg-<?= $ai_review_summary['sentiment_color'] ?>">
                                        <?= $ai_review_summary['sentiment_label'] ?>
                                    </span>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-2">
                                        <div class="p-3 bg-white rounded border h-100">
                                            <small class="text-success fw-bold d-block mb-2">
                                                <i class="bi bi-hand-thumbs-up-fill"></i> Paling Disukai
                                            </small>
                                            <?php if (!empty($ai_review_summary['pros'])): ?>
                                                <ul class="mb-0 ps-3 small text-secondary">
                                                    <?php foreach ($ai_review_summary['pros'] as $pro): ?>
                                                        <li><?= $pro ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php else: ?>
                                                <small class="text-muted fst-italic">- Belum ada data spesifik -</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-2">
                                        <div class="p-3 bg-white rounded border h-100">
                                            <small class="text-danger fw-bold d-block mb-2">
                                                <i class="bi bi-hand-thumbs-down-fill"></i> Sering Dikeluhkan
                                            </small>
                                            <?php if (!empty($ai_review_summary['cons'])): ?>
                                                <ul class="mb-0 ps-3 small text-secondary">
                                                    <?php foreach ($ai_review_summary['cons'] as $con): ?>
                                                        <li><?= $con ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php else: ?>
                                                <small class="text-muted fst-italic">- Tidak ada keluhan signifikan -</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-2 text-end">
                                    <small class="text-muted" style="font-size: 0.7rem;">
                                        Dianalisis dari <b><?= $ai_review_summary['total_reviews'] ?> ulasan</b> penghuni.
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

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
                                                <div class="d-flex align-items-center gap-2">
                                                    <h6 class="fw-bold mb-0"><?= $rev['nama_lengkap'] ?></h6>
                                                    <?php if (!empty($rev['jenis_reviewer'])): ?>
                                                        <?php if ($rev['jenis_reviewer'] == 'sewa'): ?>
                                                            <span class="badge bg-success" style="font-size: 0.65rem;">
                                                                <i class="bi bi-house-check-fill"></i> Anak Kost
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-info text-dark" style="font-size: 0.65rem;">
                                                                <i class="bi bi-eye-fill"></i> Survei
                                                            </span>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                                <small class="text-muted" style="font-size: 0.75rem;">
                                                    <?= date('d M Y H:i', strtotime($rev['tanggal_review'])) ?>
                                                    <?php if (!is_null($rev['updated_at'])): ?>
                                                        &middot; <em class="text-danger"><b>Diedit: <?= date('d M Y H:i', strtotime($rev['updated_at'])) ?></b></em>
                                                    <?php endif; ?>
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

                                        <!-- Jika review sudah pernah diupdate tampilkan dari kamar apa -->
                                        <?php if (!is_null($rev['updated_at']) && !empty($rev['nama_tipe_kamar'])): ?>
                                            <small class="text-muted" style="font-size:0.75rem;">
                                                <i class="bi bi-door-open"></i> Dari kamar: <strong><?= htmlspecialchars($rev['nama_tipe_kamar']) ?></strong>
                                            </small>
                                        <?php endif; ?>

                                        <!-- Tombol edit (hanya untuk pemilik ulasan) -->
                                        <?php if (isset($_SESSION['login']) && $_SESSION['id_user'] == $rev['id_user']): ?>
                                            <a href="detail_kost?id=<?= $id_kost ?>&edit_review=<?= $rev['id_review'] ?>#ulasan" class="btn btn-sm btn-outline-warning rounded-pill ms-auto">Edit</a>
                                        <?php endif; ?>
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
                iconUrl: 'assets/img/logo/pinunu3.png',
                // iconSize: [45, 68],
                // popupAnchor: [0, -20],
                // shadowSize: [50, 64], // Ukuran shadow
                // shadowAnchor: [15, 64]

                iconSize: [50, 55],
                iconAnchor: [25, 55], // Tengah bawah icon (setengah lebar, tinggi penuh)
                popupAnchor: [0, -55], // Popup muncul di atas icon
                shadowSize: [60, 60], // Ukuran shadow
                shadowAnchor: [20, 60] // Posisi shadow
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

            // Array untuk menyimpan marker tempat terdekat
            let nearbyMarkers = [];

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
                        icon: 'bi-shop',
                        color: '#198754',
                        bg: '#e6f9f0'
                    },
                    'kuliner': {
                        icon: 'bi-cup-straw',
                        color: '#fd7e14',
                        bg: '#fff4e6'
                    },
                    'bank': {
                        icon: 'bi-bank',
                        color: '#0dcaf0',
                        bg: '#e5f9ff'
                    },
                    'ibadah': {
                        icon: 'bi-moon-stars',
                        color: '#6f42c1',
                        bg: '#f3ebff'
                    },
                    'print': {
                        icon: 'bi-printer',
                        color: '#20c997',
                        bg: '#e6fff8'
                    },
                    'transportasi': {
                        icon: 'bi-fuel-pump',
                        color: '#0d6efd',
                        bg: '#e7f1ff'
                    }
                };
                return icons[category] || {
                    icon: 'bi-geo-alt',
                    color: '#6c757d',
                    bg: '#f0f0f0'
                };
            }

            function formatNamaTempat(name) {
                if (!name) return 'Tanpa Nama';
                return name.length > 30 ? name.substring(0, 30) + '...' : name;
            }

            // FUNGSI UNTUK MEMBUAT MARKER ICON CUSTOM
            function createCustomMarkerIcon(category) {
                const iconData = getIconAndColor(category);

                // Buat HTML icon dengan Bootstrap Icons
                const iconHtml = `
                    <div style="
                        background: ${iconData.bg};
                        color: ${iconData.color};
                        width: 28px;
                        height: 28px;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        border: 2px solid ${iconData.color};
                        box-shadow: 0 2px 6px rgba(0,0,0,0.3);
                        font-size: 14px;
                    ">
                        <i class="bi ${iconData.icon}"></i>
                    </div>
                `;

                return L.divIcon({
                    html: iconHtml,
                    className: 'custom-marker-icon',
                    iconSize: [28, 28],
                    iconAnchor: [14, 14],
                    popupAnchor: [0, -14]
                });
            }

            // FUNGSI UNTUK MENAMPILKAN MARKER DI PETA
            function tampilkanMarkerDiPeta(places) {
                // Hapus marker lama
                nearbyMarkers.forEach(marker => map.removeLayer(marker));
                nearbyMarkers = [];

                // Tambah marker baru untuk setiap tempat
                places.forEach(place => {
                    const customIcon = createCustomMarkerIcon(place.category);

                    const marker = L.marker([place.lat, place.lon], {
                        icon: customIcon
                    }).addTo(map);

                    const jarakText = place.jarak < 1 ?
                        `${(place.jarak * 1000).toFixed(0)} m` :
                        `${place.jarak} km`;

                    // Popup dengan info tempat
                    marker.bindPopup(`
                        <div style="min-width: 150px;">
                            <h6 class="fw-bold mb-1" style="font-size: 0.85rem;">${place.name}</h6>
                            <small class="text-muted">
                                <i class="bi bi-geo-alt"></i> ${jarakText} dari kost
                            </small>
                        </div>
                    `);

                    nearbyMarkers.push(marker);
                });

                console.log(`✅ ${nearbyMarkers.length} marker ditambahkan ke peta`);
            }

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
                        icon: 'bi-shop',
                        color: '#198754',
                        bg: '#e6f9f0'
                    },
                    'kuliner': {
                        icon: 'bi-cup-straw',
                        color: '#fd7e14',
                        bg: '#fff4e6'
                    },
                    'bank': {
                        icon: 'bi-bank',
                        color: '#0dcaf0',
                        bg: '#e5f9ff'
                    },
                    'ibadah': {
                        icon: 'bi-moon-stars',
                        color: '#6f42c1',
                        bg: '#f3ebff'
                    },
                    'print': {
                        icon: 'bi-printer',
                        color: '#20c997',
                        bg: '#e6fff8'
                    },
                    'transportasi': {
                        icon: 'bi-fuel-pump',
                        color: '#0d6efd',
                        bg: '#e7f1ff'
                    }
                };
                return icons[category] || {
                    icon: 'bi-geo-alt',
                    color: '#6c757d',
                    bg: '#f0f0f0'
                };
            }

            function formatNamaTempat(name) {
                if (!name) return 'Tanpa Nama';
                return name.length > 30 ? name.substring(0, 30) + '...' : name;
            }

            // FUNGSI UNTUK MEMBUAT MARKER ICON CUSTOM
            function createCustomMarkerIcon(category) {
                const iconData = getIconAndColor(category);

                // Buat HTML icon dengan Bootstrap Icons
                const iconHtml = `
                    <div style="
                        background: ${iconData.bg};
                        color: ${iconData.color};
                        width: 28px;
                        height: 28px;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        border: 2px solid ${iconData.color};
                        box-shadow: 0 2px 6px rgba(0,0,0,0.3);
                        font-size: 14px;
                    ">
                        <i class="bi ${iconData.icon}"></i>
                    </div>
                `;

                return L.divIcon({
                    html: iconHtml,
                    className: 'custom-marker-icon',
                    iconSize: [28, 28],
                    iconAnchor: [14, 14],
                    popupAnchor: [0, -14]
                });
            }

            // FUNGSI UNTUK MENAMPILKAN MARKER DI PETA
            function tampilkanMarkerDiPeta(places) {
                // Hapus marker lama
                nearbyMarkers.forEach(marker => map.removeLayer(marker));
                nearbyMarkers = [];

                // Tambah marker baru untuk setiap tempat
                places.forEach(place => {
                    const customIcon = createCustomMarkerIcon(place.category);

                    const marker = L.marker([place.lat, place.lon], {
                        icon: customIcon
                    }).addTo(map);

                    const jarakText = place.jarak < 1 ?
                        `${(place.jarak * 1000).toFixed(0)} m` :
                        `${place.jarak} km`;

                    // Popup dengan info tempat
                    marker.bindPopup(`
                        <div style="min-width: 150px;">
                            <h6 class="fw-bold mb-1" style="font-size: 0.85rem;">${place.name}</h6>
                            <small class="text-muted">
                                <i class="bi bi-geo-alt"></i> ${jarakText} dari kost
                            </small>
                        </div>
                    `);

                    nearbyMarkers.push(marker);
                });

                console.log(`✅ ${nearbyMarkers.length} marker ditambahkan ke peta`);
            }

            async function cariTempatTerdekat() {
                const radius = 1000; // 1 km dalam meter

                // QUERY YANG DIPERBAIKI & DISEDERHANAKAN
                const query = `
[out:json][timeout:40];
(
  node["amenity"~"hospital|clinic|pharmacy|doctors"](around:${radius},${latKost},${longKost});
  way["amenity"~"hospital|clinic|pharmacy|doctors"](around:${radius},${latKost},${longKost});
  
  node["shop"="convenience"](around:${radius},${latKost},${longKost});
  way["shop"="convenience"](around:${radius},${latKost},${longKost});
  node["shop"="supermarket"](around:${radius},${latKost},${longKost});
  way["shop"="supermarket"](around:${radius},${latKost},${longKost});
  
  node["amenity"~"restaurant|cafe|fast_food"](around:${radius},${latKost},${longKost});
  way["amenity"~"restaurant|cafe|fast_food"](around:${radius},${latKost},${longKost});
  
  node["amenity"~"bank|atm"](around:${radius},${latKost},${longKost});
  way["amenity"~"bank|atm"](around:${radius},${latKost},${longKost});
  
  node["amenity"="place_of_worship"](around:${radius},${latKost},${longKost});
  way["amenity"="place_of_worship"](around:${radius},${latKost},${longKost});
  
  node["shop"="copyshop"](around:${radius},${latKost},${longKost});
  way["shop"="copyshop"](around:${radius},${latKost},${longKost});
  node["shop"="stationery"](around:${radius},${latKost},${longKost});
  way["shop"="stationery"](around:${radius},${latKost},${longKost});
  
  node["amenity"="fuel"](around:${radius},${latKost},${longKost});
  way["amenity"="fuel"](around:${radius},${latKost},${longKost});
  node["highway"="bus_stop"](around:${radius},${latKost},${longKost});
  node["amenity"="parking"](around:${radius},${latKost},${longKost});
  way["amenity"="parking"](around:${radius},${latKost},${longKost});
);
out center;
`;

                console.log('🔍 Mencari tempat terdekat...'); // Debug log

                try {
                    const response = await fetch('https://overpass-api.de/api/interpreter', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'data=' + encodeURIComponent(query)
                    });

                    console.log('📡 Response status:', response.status); // Debug log

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const data = await response.json();
                    console.log('📦 Data diterima:', data.elements.length, 'tempat'); // Debug log

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

                        if (!lat || !lon) return;

                        const jarak = hitungJarakJS(latKost, longKost, lat, lon);

                        // Kategorisasi
                        let category = 'lainnya';
                        const tags = el.tags || {};

                        if (tags.amenity === 'hospital' || tags.amenity === 'clinic' ||
                            tags.amenity === 'pharmacy' || tags.amenity === 'doctors') {
                            category = 'kesehatan';
                        } else if (tags.shop === 'convenience' || tags.shop === 'supermarket') {
                            category = 'minimarket';
                        } else if (tags.amenity === 'restaurant' || tags.amenity === 'cafe' ||
                            tags.amenity === 'fast_food') {
                            category = 'kuliner';
                        } else if (tags.amenity === 'bank' || tags.amenity === 'atm') {
                            category = 'bank';
                        } else if (tags.amenity === 'place_of_worship') {
                            category = 'ibadah';
                        } else if (tags.shop === 'copyshop' || tags.shop === 'stationery') {
                            category = 'print';
                        } else if (tags.amenity === 'fuel' || tags.highway === 'bus_stop' ||
                            tags.amenity === 'parking') {
                            category = 'transportasi';
                        }

                        // Nama tempat dengan prioritas
                        let name = tags.name || tags.brand || tags.operator || tags['addr:housename'] || 'Tanpa Nama';

                        // Tambahkan info tipe untuk tempat tanpa nama
                        if (name === 'Tanpa Nama') {
                            if (tags.amenity) name = tags.amenity.charAt(0).toUpperCase() + tags.amenity.slice(1);
                            else if (tags.shop) name = tags.shop.charAt(0).toUpperCase() + tags.shop.slice(1);
                        }

                        places.push({
                            name: name,
                            category: category,
                            jarak: parseFloat(jarak),
                            lat: lat,
                            lon: lon
                        });
                    });

                    console.log('✅ Total places found:', places.length); // Debug log

                    // Sort by jarak
                    places.sort((a, b) => a.jarak - b.jarak);

                    // Ambil max 12 terdekat
                    const topPlaces = places.slice(0, 12);

                    // TAMPILKAN DI LIST & PETA
                    tampilkanTempatTerdekat(topPlaces);
                    tampilkanMarkerDiPeta(topPlaces); // TAMBAHAN BARU

                } catch (error) {
                    console.error('❌ Error fetching nearby places:', error);
                    console.error('Error details:', error.message);

                    // Tampilkan pesan error yang lebih spesifik
                    document.getElementById('nearby-places-container').innerHTML = `
                        <div class="alert alert-warning small py-2 mb-0">
                            <i class="bi bi-exclamation-triangle"></i> 
                            <strong>Gagal memuat data</strong><br>
                            <small class="text-muted">${error.message || 'API Overpass tidak merespons'}</small>
                            <br>
                            <button class="btn btn-sm btn-outline-warning mt-2" onclick="cariTempatTerdekat()">
                                <i class="bi bi-arrow-clockwise"></i> Coba Lagi
                            </button>
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

                let html = '<div class="nearby-list-container"><div class="list-group list-group-flush">';

                places.forEach((place, idx) => {
                    const iconData = getIconAndColor(place.category);
                    const jarakText = place.jarak < 1 ?
                        `${(place.jarak * 1000).toFixed(0)} m` :
                        `${place.jarak} km`;

                    html += `
                        <div class="nearby-place-item d-flex align-items-center gap-2" onclick="focusOnMarker(${idx})">
                            <div class="nearby-icon" style="background: ${iconData.bg}; color: ${iconData.color}">
                                <i class="bi ${iconData.icon}"></i>
                            </div>
                            <div class="flex-grow-1 overflow-hidden">
                                <h6 class="mb-0 fw-bold text-truncate" style="font-size: 0.8rem">${formatNamaTempat(place.name)}</h6>
                                <small class="text-muted" style="font-size: 0.7rem">
                                    <i class="bi bi-geo-alt"></i> ${jarakText}
                                </small>
                            </div>
                        </div>
                    `;
                });

                html += '</div></div>';

                // Tambahkan info jumlah total jika lebih dari yang ditampilkan
                if (places.length > 5) {
                    html += `<div class="text-center mt-2">
                        <small class="text-muted" style="font-size: 0.7rem">
                            <i class="bi bi-arrow-down-circle"></i> Scroll untuk lihat ${places.length} tempat
                        </small>
                    </div>`;
                }

                container.innerHTML = html;
            }

            // FUNGSI UNTUK FOKUS KE MARKER SAAT ITEM DIKLIK
            function focusOnMarker(index) {
                if (nearbyMarkers[index]) {
                    const marker = nearbyMarkers[index];
                    map.flyTo(marker.getLatLng(), 16, {
                        duration: 0.8
                    });
                    setTimeout(() => {
                        marker.openPopup();
                    }, 900);
                }
            }

            // Jalankan pencarian saat halaman load dengan delay lebih lama
            setTimeout(() => {
                cariTempatTerdekat();
            }, 2000); // Ubah dari 1000 ke 2000ms
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

<!-- TAMBAHKAN MODAL REVIEW DI SINI (dipindahkan dari riwayat_sewa) -->
<div class="modal fade" id="modalReview" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fs-6"><span id="namaKostReview" class="fw-bold"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="proses_ulasan">
                <div class="modal-body">
                    <input type="hidden" name="id_review" id="idReviewEdit" value="">
                    <input type="hidden" name="id_kost" id="idKostReview">
                    <input type="hidden" name="id_kamar" id="idKamarReview">
                    <input type="hidden" name="user_lat" id="userLat">
                    <input type="hidden" name="user_long" id="userLong">
                    <input type="hidden" name="jenis_reviewer" id="jenisReviewer">

                    <div id="gpsContainer" class="alert alert-secondary small py-2 mb-3">
                        <div class="d-flex align-items-center">
                            <div class="spinner-border spinner-border-sm me-2" id="loadingGps" role="status"></div>
                            <span id="gpsText">Mendeteksi lokasi & alamat...</span>
                        </div>
                        <div id="jarakText" class="fw-bold mt-1 text-primary" style="font-size: 0.9em;"></div>
                    </div>

                    <div class="mb-3 border-bottom pb-3">
                        <label class="form-label fw-bold small">Akurasi Foto/Info (C5)</label>
                        <div class="stars akurasi" role="radiogroup" aria-label="Akurasi">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" name="rating_akurasi" id="ak<?= $i ?>" value="<?= $i ?>" required>
                                <label for="ak<?= $i ?>" title="<?= $i ?> dari 5"><i class="bi bi-star-fill"></i></label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Kepuasan Umum (C6)</label>
                        <div class="stars umum" role="radiogroup" aria-label="Kepuasan Umum">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" name="rating_umum" id="um<?= $i ?>" value="<?= $i ?>" required>
                                <label for="um<?= $i ?>" title="<?= $i ?> dari 5"><i class="bi bi-star-fill"></i></label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <textarea name="komentar" id="komentarReview" class="form-control" rows="2" placeholder="Tulis pengalamanmu..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary w-100" id="btnKirimReview" disabled>
                        <i class="bi bi-lock-fill"></i> Lokasi Belum Terverifikasi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JS modal & GPS (ringkas, sama behavior seperti sebelumnya) -->
<script>
    let targetLat = <?= $kost['latitude'] ?: 0 ?>;
    let targetLong = <?= $kost['longitude'] ?: 0 ?>;
    let userType = '';
    let isEditMode = false;

    function bukaModalReviewFromPage(id, nama, lat, long, type, id_kamar = '') {
        isEditMode = false;
        document.getElementById('idReviewEdit').value = '';
        document.getElementById('idKostReview').value = id;
        document.getElementById('idKamarReview').value = id_kamar;
        document.getElementById('namaKostReview').innerText = nama;
        document.getElementById('jenisReviewer').value = type;
        document.getElementById('komentarReview').value = '';

        document.querySelectorAll('input[name="rating_akurasi"]').forEach(el => el.checked = false);
        document.querySelectorAll('input[name="rating_umum"]').forEach(el => el.checked = false);

        targetLat = lat;
        targetLong = long;
        userType = type;

        // Reset UI then show modal. For 'sewa' skip GPS and enable langsung.
        resetGPSUI();
        var myModal = new bootstrap.Modal(document.getElementById('modalReview'));
        myModal.show();

        if (userType === 'sewa') {
            // Penyewa tidak perlu verifikasi lokasi
            document.getElementById('loadingGps').style.display = 'none';
            document.getElementById('gpsText').innerHTML = "<i class='bi bi-house-fill'></i> Penyewa — verifikasi lokasi tidak diperlukan";
            document.getElementById('jarakText').innerHTML = "Status: Penyewa (Tidak perlu verifikasi lokasi)";
            enableButton();
        } else {
            getLocation();
        }
    }

    // edit via GET param (or link). Jika ada edit_review param, kita panggil fungsi ini di page load.
    function editReviewFromId(idReview) {
        isEditMode = true;
        document.getElementById('idReviewEdit').value = idReview;
        document.getElementById('idKostReview').value = <?= (int)$id_kost ?>;
        document.getElementById('namaKostReview').innerText = "<?= addslashes($kost['nama_kost']) ?> (Edit)";

        // Ambil data lewat AJAX
        fetch('get_review?id=' + idReview)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('komentarReview').value = data.komentar || '';
                    document.getElementById('idKamarReview').value = data.id_kamar || '';
                    document.getElementById('jenisReviewer').value = data.jenis_reviewer || 'survei';

                    if (data.skor_akurasi) {
                        let akel = document.getElementById('ak' + data.skor_akurasi);
                        if (akel) akel.checked = true;
                    }
                    if (data.rating) {
                        let umel = document.getElementById('um' + data.rating);
                        if (umel) umel.checked = true;
                    }

                    userType = data.jenis_reviewer || 'survei';
                    // Jika edit mode atau penyewa, tidak perlu verifikasi lokasi
                    var myModal = new bootstrap.Modal(document.getElementById('modalReview'));
                    myModal.show();

                    if (isEditMode || userType === 'sewa') {
                        document.getElementById('loadingGps').style.display = 'none';
                        document.getElementById('gpsText').innerHTML = "<i class='bi bi-pencil-square'></i> Edit — verifikasi lokasi tidak diperlukan";
                        document.getElementById('jarakText').innerHTML = "Edit mode (Tidak perlu verifikasi lokasi)";
                        enableButton();
                    } else {
                        resetGPSUI();
                        getLocation();
                    }
                } else {
                    alert('Ulasan tidak ditemukan atau bukan milik Anda.');
                }
            });
    }

    function resetGPSUI() {
        document.getElementById('gpsContainer').className = 'alert alert-secondary small py-2 mb-3';
        document.getElementById('gpsText').innerHTML = 'Mendeteksi lokasi...';
        document.getElementById('jarakText').innerHTML = '';
        document.getElementById('loadingGps').style.display = 'inline-block';
        disableButton('Menunggu Lokasi...');
    }

    function getLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(processPosition, showError, {
                enableHighAccuracy: true
            });
        } else {
            showError({
                message: "Browser tidak support GPS"
            });
        }
    }

    function processPosition(position) {
        let uLat = position.coords.latitude;
        let uLong = position.coords.longitude;
        document.getElementById('userLat').value = uLat;
       
        document.getElementById('userLong').value = uLong;
        let jarakMeter = hitungJarak(uLat, uLong, targetLat, targetLong);

        let url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${uLat}&lon=${uLong}&zoom=18&addressdetails=1`;

        fetch(url).then(r => r.json()).then(data => {
            let alamatBersih = data.display_name || "Alamat tidak ditemukan";
            let alamatShort = alamatBersih.split(',').slice(0, 3).join(',');
            document.getElementById('gpsText').innerHTML = `<i class='bi bi-geo-alt-fill'></i> ${alamatShort}`;
            document.getElementById('loadingGps').style.display = 'none';
            validasiJarak(jarakMeter);
        }).catch(err => {
            document.getElementById('gpsText').innerHTML = "Lokasi: " + uLat.toFixed(5) + ", " + uLong.toFixed(5);
            document.getElementById('loadingGps').style.display = 'none';
            validasiJarak(jarakMeter);
        });
    }

    function validasiJarak(meter) {
        let statusBox = document.getElementById('gpsContainer');
        let jarakText = document.getElementById('jarakText');

        if (userType === 'sewa') {
            statusBox.className = 'alert alert-success small py-2 mb-3';
            jarakText.innerHTML = `Status: Penyewa (Bebas Review dari mana saja)`;
            enableButton();
        } else {
            if (meter <= 30) {
                statusBox.className = 'alert alert-success small py-2 mb-3';
                jarakText.innerHTML = `Jarak: ${meter} meter (Dalam jangkauan)`;
                enableButton();
            } else {
                statusBox.className = 'alert alert-danger small py-2 mb-3';
                jarakText.innerHTML = `Kamu berada ${meter}m dari kost (penilaian hanya dapat dilakukan di area kost)`;
                disableButton('Lokasi Terlalu Jauh');
            }
        }
    }

    function hitungJarak(lat1, lon1, lat2, lon2) {
        if ((lat1 == lat2) && (lon1 == lon2)) return 0;
        var radlat1 = Math.PI * lat1 / 180;
        var radlat2 = Math.PI * lat2 / 180;
        var theta = lon1 - lon2;
        var radtheta = Math.PI * theta / 180;
        var dist = Math.sin(radlat1) * Math.sin(radlat2) + Math.cos(radlat1) * Math.cos(radlat2) * Math.cos(radtheta);
        if (dist > 1) dist = 1;
        dist = Math.acos(dist);
        dist = dist * 180 / Math.PI;
        dist = dist * 60 * 1.1515;
        dist = dist * 1.609344 * 1000;
        return Math.round(dist);
    }

    function enableButton() {
        let btn = document.getElementById('btnKirimReview');
        btn.disabled = false;
        btn.innerHTML = isEditMode ? '<i class="bi bi-check-circle"></i> Update Ulasan' : '<i class="bi bi-send"></i> Kirim Ulasan';
        btn.className = 'btn btn-primary w-100';
    }

    function disableButton(msg) {
        let btn = document.getElementById('btnKirimReview');
        btn.disabled = true;
        btn.innerHTML = `<i class="bi bi-x-circle"></i> ${msg}`;
        btn.className = 'btn btn-secondary w-100';
    }

    function showError(error) {
        document.getElementById('gpsText').innerHTML = "Gagal ambil lokasi. Izinkan GPS!";
        document.getElementById('loadingGps').style.display = 'none';
        disableButton('GPS Error');
    }

    // Jika halaman dibuka dengan parameter edit_review, otomatis buka modal edit
    <?php if (isset($_GET['edit_review'])): ?>
        window.addEventListener('DOMContentLoaded', function() {
            editReviewFromId(<?= (int)$_GET['edit_review'] ?>);
        });
    <?php elseif (isset($_GET['reviewer'])): // jika diarahkan untuk memberi ulasan (survei/sewa) buka modal baru 
    ?>
        window.addEventListener('DOMContentLoaded', function() {
            // jika id_kamar diberikan lewat query string, isi juga
            bukaModalReviewFromPage('<?= $id_kost ?>', '<?= addslashes($kost['nama_kost']) ?>', <?= $kost['latitude'] ?: 0 ?>, <?= $kost['longitude'] ?: 0 ?>, '<?= $_GET['reviewer'] ?>', '<?= isset($_GET['id_kamar']) ? (int)$_GET['id_kamar'] : '' ?>');
        });
    <?php endif; ?>
</script>