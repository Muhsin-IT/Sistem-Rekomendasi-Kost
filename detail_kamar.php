<?php
session_start();
include 'koneksi.php';

if (!isset($_GET['id'])) {
    header("Location: index");
    exit;
}
$id_kamar = $_GET['id'];

// 1. DATA KAMAR
$query = "SELECT km.*, k.id_kost, k.nama_kost, k.alamat, k.latitude, k.longitude, u.nama_lengkap as nama_pemilik, u.no_hp as hp_pemilik 
          FROM kamar km 
          JOIN kost k ON km.id_kost = k.id_kost 
          JOIN users u ON k.id_pemilik = u.id_user 
          WHERE km.id_kamar = '$id_kamar'";
$kamar = mysqli_fetch_assoc(mysqli_query($conn, $query));
if (!$kamar) {
    echo "Kamar tidak ditemukan";
    exit;
}
$id_kost = $kamar['id_kost'];
$kapasitas = isset($kamar['kapasitas']) ? $kamar['kapasitas'] : 1;

// 2. GALERI
$galeri_semua = [];
$q_gal = mysqli_query($conn, "SELECT * FROM galeri WHERE id_kamar='$id_kamar'");
while ($row = mysqli_fetch_assoc($q_gal)) $galeri_semua[] = $row;

$galeri_by_kategori = [];
foreach ($galeri_semua as $idx => $foto) {
    $kategori = $foto['kategori_foto'] ?: 'Tanpa Kategori';
    $galeri_by_kategori[$kategori][] = $idx;
}

// 3. FASILITAS
$fasilitas_by_cat = ['Kamar' => [], 'Kamar Mandi' => [], 'Umum' => [], 'Parkir' => []];
$q_fk = mysqli_query($conn, "SELECT mf.nama_fasilitas, mf.kategori FROM rel_fasilitas rf JOIN master_fasilitas mf ON rf.id_master_fasilitas=mf.id_master_fasilitas WHERE rf.id_kamar='$id_kamar'");
while ($row = mysqli_fetch_assoc($q_fk)) $fasilitas_by_cat[$row['kategori']][] = $row['nama_fasilitas'];
$q_fg = mysqli_query($conn, "SELECT mf.nama_fasilitas, mf.kategori FROM rel_fasilitas rf JOIN master_fasilitas mf ON rf.id_master_fasilitas=mf.id_master_fasilitas WHERE rf.id_kost='$id_kost' AND rf.id_kamar IS NULL");
while ($row = mysqli_fetch_assoc($q_fg)) {
    if (in_array($row['kategori'], ['Umum', 'Parkir'])) $fasilitas_by_cat[$row['kategori']][] = $row['nama_fasilitas'];
}

// 4. PERATURAN
$peraturan_kamar = [];
$q_pk = mysqli_query($conn, "SELECT mp.nama_peraturan FROM rel_peraturan rp JOIN master_peraturan mp ON rp.id_master_peraturan=mp.id_master_peraturan WHERE rp.id_kamar='$id_kamar'");
while ($r = mysqli_fetch_assoc($q_pk)) $peraturan_kamar[] = $r['nama_peraturan'];
$peraturan_kost = [];
$q_pg = mysqli_query($conn, "SELECT mp.nama_peraturan FROM rel_peraturan rp JOIN master_peraturan mp ON rp.id_master_peraturan=mp.id_master_peraturan WHERE rp.id_kost='$id_kost' AND rp.id_kamar IS NULL");
while ($r = mysqli_fetch_assoc($q_pg)) $peraturan_kost[] = $r['nama_peraturan'];

// 5. REVIEW & RATING (KHUSUS KAMAR INI)
$q_review = mysqli_query($conn, "SELECT r.*, u.nama_lengkap FROM review r 
    JOIN users u ON r.id_user = u.id_user 
    WHERE r.id_kamar='$id_kamar' ORDER BY r.tanggal_review DESC");

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

// login status
$is_logged_in = isset($_SESSION['login']);

// Simpan URL halaman ini supaya nanti bisa balik lagi setelah login
$current_page = urlencode("detail_kamar?id=" . $id_kamar);

// Siapkan Link WA Asli
$pesan_wa = "Halo, saya mau sewa kamar tipe *" . $kamar['nama_tipe_kamar'] . "* di *" . $kamar['nama_kost'] . "*";
$link_wa_asli = "https://wa.me/" . $kamar['hp_pemilik'] . "?text=" . urlencode($pesan_wa);

// Inisialisasi variabel dulu supaya tidak error "Undefined"
$target_blank = "";

if ($is_logged_in) {
    // Jika SUDAH login
    $btn_action_chat = $link_wa_asli;
    $btn_action_sewa = "booking.php?id=" . $id_kamar;
    $target_blank = 'target="_blank"'; // Isi variabel
} else {
    // Jika BELUM login
    // Pastikan pakai urlencode seperti revisi sebelumnya
    $btn_action_chat = "login?next=" . $current_page;
    $btn_action_sewa = "login?next=" . $current_page;

    $target_blank = ''; // Kosongkan variabel
}

// Eligibility review khusus kamar
$user_has_review = $user_has_sewa = $user_has_survei = $can_review = false;
$default_reviewer = 'survei';
if ($is_logged_in) {
    $id_user_check = $_SESSION['id_user'];
    $qr = mysqli_query($conn, "SELECT id_review FROM review WHERE id_kamar='$id_kamar' AND id_user='$id_user_check' LIMIT 1");
    $user_has_review = mysqli_num_rows($qr) > 0;

    $qs = mysqli_query($conn, "SELECT id_pengajuan FROM pengajuan_sewa WHERE id_kamar='$id_kamar' AND id_user='$id_user_check' AND status IN ('Diterima','Selesai') LIMIT 1");
    $user_has_sewa = mysqli_num_rows($qs) > 0;

    // Survei hanya memiliki id_kost, tidak ada id_kamar
    $qv = mysqli_query($conn, "SELECT id_survei FROM survei WHERE id_kost='$id_kost' AND id_user='$id_user_check' AND status IN ('Diterima','Selesai') LIMIT 1");
    $user_has_survei = mysqli_num_rows($qv) > 0;

    $can_review = (!$user_has_review) && ($user_has_sewa || $user_has_survei);
    $default_reviewer = $user_has_sewa ? 'sewa' : ($user_has_survei ? 'survei' : 'survei');
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/img/logo/persegi.webp">
    <title>Tipe <?= $kamar['nama_tipe_kamar'] ?> - RadenStay</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css" />
    <script src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"></script>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .card-custom {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
            background: white;
            margin-bottom: 20px;
        }

        /* HEADER SLIDER */
        .main-carousel-item {
            height: 400px;
            background-color: #000;
            cursor: zoom-in;
            position: relative;
        }

        .main-carousel-item img {
            height: 100%;
            width: 100%;
            object-fit: contain;
        }

        /* ICON 360 Overlay */
        .icon-360-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.6);
            color: white;
            border: 2px solid white;
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            pointer-events: none;
        }

        /* MODAL FULLSCREEN (CAROUSEL FOTO BIASA) */
        .modal-fullscreen .modal-body {
            padding: 0;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            overflow: hidden;
        }

        .carousel-fs-item {
            height: 100vh;
            width: 100vw;
            position: relative;
            background: #000;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .fs-img {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
        }

        /* TOMBOL LOAD 360 DI DALAM CAROUSEL */
        .btn-launch-360 {
            z-index: 50;
            padding: 15px 40px;
            font-weight: bold;
            font-size: 1.2rem;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
        }

        /* MODAL KHUSUS 360 (TERPISAH) */
        #modalDedicated360 .modal-body {
            padding: 0;
            height: 100vh;
            background: #000;
        }

        #pano-container-dedicated {
            width: 100%;
            height: 100%;
        }

        /* Tombol Close 360 */
        .btn-close-360 {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 9999;
            background: white;
            border-radius: 50%;
            padding: 10px;
            opacity: 0.8;
        }

        .btn-close-360:hover {
            opacity: 1;
        }

        @media (max-width: 768px) {
            .main-carousel-item {
                height: 250px;
            }
        }

        .kategori-foto-badge {
            display: inline-block;
            padding: 0.35rem 0.9rem;
            background: rgba(0, 0, 0, 0.65);
            color: #fff;
            border-radius: 0.4rem;
            backdrop-filter: blur(2px);
        }

        .kategori-foto-pill {
            position: absolute;
            top: 1rem;
            left: 1rem;
            padding: 0.3rem 0.8rem;
            background: rgba(0, 0, 0, 0.7);
            color: #fff;
            border-radius: 999px;
            font-size: 0.85rem;
        }

        .calendar-widget .calendar-weekdays,
        .calendar-widget .calendar-days {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
        }

        .calendar-widget .calendar-days button {
            min-height: 42px;
        }

        .calendar-widget .calendar-days button.active {
            background-color: #198754;
            color: #fff;
        }

        .calendar-widget .calendar-days button:disabled {
            opacity: 0.35;
            cursor: not-allowed;
        }

        /* Review Styles */
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
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container my-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index">Home</a></li>
                <li class="breadcrumb-item"><a href="detail_kost.php?id=<?= $kamar['id_kost'] ?>"><?= $kamar['nama_kost'] ?></a></li>
                <li class="breadcrumb-item active">Tipe <?= $kamar['nama_tipe_kamar'] ?></li>
            </ol>
        </nav>

        <div class="row g-4">
            <div class="col-lg-8">

                <div class="card card-custom overflow-hidden p-0 mb-3">
                    <?php if (count($galeri_semua) > 0): ?>
                        <div id="mainSlider" class="carousel slide bg-dark" data-bs-ride="carousel">
                            <div class="carousel-indicators">
                                <?php foreach ($galeri_semua as $i => $g): ?>
                                    <button type="button" data-bs-target="#mainSlider" data-bs-slide-to="<?= $i ?>" class="<?= $i == 0 ? 'active' : '' ?>"></button>
                                <?php endforeach; ?>
                            </div>
                            <div class="carousel-inner">
                                <?php foreach ($galeri_semua as $i => $g): ?>
                                    <div class="carousel-item main-carousel-item <?= $i == 0 ? 'active' : '' ?>" onclick="openFullscreen(<?= $i ?>)">
                                        <img src="assets/img/galeri/<?= $g['nama_file'] ?>">
                                        <span class="kategori-foto-pill"><?= $g['kategori_foto'] ?></span>
                                        <?php if ($g['is_360']): ?>
                                            <div class="icon-360-overlay"><i class="bi bi-arrow-repeat"></i></div>
                                            <div class="carousel-caption d-none d-md-block">klik untuk putar 360°</div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button class="carousel-control-prev" type="button" data-bs-target="#mainSlider" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
                            <button class="carousel-control-next" type="button" data-bs-target="#mainSlider" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 bg-light text-muted">Belum ada foto.</div>
                    <?php endif; ?>
                </div>

                <div class="d-flex gap-2 mb-4">
                    <button class="btn btn-outline-dark fw-bold rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#modalGrid">
                        <i class="bi bi-grid"></i> Lihat Semua Foto
                    </button>

                    <?php
                    // Cari foto 360 pertama di database untuk tombol shortcut ini
                    $first360 = '';
                    foreach ($galeri_semua as $g) {
                        if ($g['is_360'] == 1) {
                            $first360 = $g['nama_file'];
                            break;
                        }
                    }
                    ?>

                    <?php if ($first360 != ''): ?>
                        <button class="btn btn-primary fw-bold rounded-pill shadow-sm" onclick="launch360Modal('assets/img/galeri/<?= $first360 ?>')">
                            <i class="bi bi-goggles"></i> Foto 360°
                        </button>
                    <?php endif; ?>
                </div>

                <div class="card card-custom p-4">
                    <h4 class="fw-bold mb-4">Detail Kamar | Tipe <?= $kamar['nama_tipe_kamar'] ?></h4>
                    <div class="row mb-4 border-bottom pb-3">
                        <div class="col-4 text-center border-end"><i class="bi bi-aspect-ratio fs-4 text-primary"></i>
                            <div class="small text-muted">Luas</div>
                            <div class="fw-bold"><?= $kamar['lebar_ruangan'] ?></div>
                        </div>
                        <div class="col-4 text-center border-end"><i class="bi bi-lightning-charge fs-4 text-warning"></i>
                            <div class="small text-muted">Listrik</div>
                            <div class="fw-bold"><?= $kamar['sudah_termasuk_listrik'] ? 'Gratis' : 'Token' ?></div>
                        </div>
                        <div class="col-4 text-center"><i class="bi bi-people-fill fs-4 text-info"></i>
                            <div class="small text-muted">Kapasitas</div>
                            <div class="fw-bold">Max <?= $kapasitas ?></div>
                        </div>
                    </div>

                    <h6 class="fw-bold text-secondary mb-3">Fasilitas Tersedia</h6>
                    <div class="row g-3">
                        <?php foreach ($fasilitas_by_cat as $kat => $list): ?>
                            <div class="col-md-6">
                                <div class="bg-light p-3 rounded h-100">
                                    <strong class="d-block text-primary mb-2"><?= $kat ?></strong>
                                    <ul class="list-unstyled mb-0 small">
                                        <?php foreach ($list as $f) echo "<li class='mb-1'><i class='bi bi-check2 text-success'></i> $f</li>"; ?>
                                        <?php if (empty($list)) echo "<li class='text-muted'>-</li>"; ?>
                                    </ul>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="card card-custom p-4 mt-4">
                    <h5 class="fw-bold mb-3">Peraturan</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-danger small fw-bold text-uppercase"><i class="bi bi-exclamation-circle"></i> Khusus Kamar Ini</h6>
                            <ul class="small mb-3">
                                <?php foreach ($peraturan_kamar as $pk) echo "<li>$pk</li>";
                                if (empty($peraturan_kamar)) echo "<li>-</li>"; ?>
                            </ul>
                        </div>
                        <div class="col-md-6 border-start">
                            <h6 class="text-secondary small fw-bold text-uppercase"><i class="bi bi-building"></i> Umum Gedung</h6>
                            <ul class="small">
                                <?php foreach ($peraturan_kost as $pk) echo "<li>$pk</li>";
                                if (empty($peraturan_kost)) echo "<li>-</li>"; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- SECTION REVIEW & RATING -->
                <?php if ($total_review > 0 || $can_review): ?>
                    <div class="card card-custom p-4 mt-4" id="ulasan">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold mb-0">Ulasan & Rating Kamar</h5>
                            <?php if ($is_logged_in && $can_review): ?>
                                <button class="btn btn-primary btn-sm fw-bold" onclick="bukaModalReviewKamar('<?= $default_reviewer ?>')">
                                    <i class="bi bi-star-fill me-1"></i> Beri Ulasan
                                </button>
                            <?php endif; ?>
                        </div>

                        <!-- Rating Summary -->
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <div class="rating-box text-center">
                                    <div class="rating-number"><?= $avg_rating ?></div>
                                    <div class="text-warning mb-1">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="bi bi-star-fill<?= $i <= floor($avg_rating) ? '' : ($i - $avg_rating < 1 ? '-half' : ' text-muted') ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <small class="text-muted">Rating Umum</small>
                                    <div class="mt-2 small text-muted"><?= $total_review ?> ulasan</div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="rating-box text-center">
                                    <div class="rating-number text-warning"><?= $avg_akurasi ?></div>
                                    <div class="text-warning mb-1">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="bi bi-star-fill<?= $i <= floor($avg_akurasi) ? '' : ($i - $avg_akurasi < 1 ? '-half' : ' text-muted') ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <small class="text-muted">Akurasi Info</small>
                                    <div class="mt-2 small text-muted"><?= $total_review ?> ulasan</div>
                                </div>
                            </div>
                        </div>

                        <!-- List Review -->
                        <h6 class="fw-bold mb-3">Semua Ulasan</h6>
                        <div class="review-list">
                            <?php foreach ($list_review as $review): ?>
                                <div class="review-item">
                                    <div class="d-flex gap-3">
                                        <div class="avatar-review">
                                            <?= strtoupper(substr($review['nama_lengkap'], 0, 1)) ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <h6 class="mb-0 fw-bold"><?= htmlspecialchars($review['nama_lengkap']) ?></h6>
                                                    <small class="text-muted"><?= date('d M Y', strtotime($review['tanggal_review'])) ?></small>
                                                </div>
                                            </div>

                                            <div class="mb-2">
                                                <span class="badge bg-success me-2">
                                                    <i class="bi bi-star-fill"></i> <?= $review['rating'] ?>/5 Rating
                                                </span>
                                                <span class="badge bg-warning text-dark">
                                                    <i class="bi bi-check-circle-fill"></i> <?= $review['skor_akurasi'] ?>/5 Akurasi
                                                </span>
                                            </div>

                                            <?php if ($review['komentar']): ?>
                                                <p class="mb-0 text-secondary"><?= nl2br(htmlspecialchars($review['komentar'])) ?></p>
                                            <?php endif; ?>
                                            <?php if ($is_logged_in && $_SESSION['id_user'] == $review['id_user']): ?>
                                                <div class="mt-2">
                                                    <a href="detail_kamar?id=<?= $id_kamar ?>&edit_review=<?= $review['id_review'] ?>#ulasan" class="btn btn-sm btn-outline-warning rounded-pill">Edit</a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-4 d-none d-lg-block">
                <div class="card card-custom p-4 sticky-top" style="top: 90px;">
                    <h6 class="text-muted mb-1">Harga Sewa Kamar</h6>
                    <h2 class="fw-bold text-primary mb-3">Rp <?= number_format($kamar['harga_per_bulan'], 0, ',', '.') ?> <small class="fs-6 text-muted fw-normal">/ bln</small></h2>
                    <?php $waLink = "https://wa.me/" . $kamar['hp_pemilik'] . "?text=" . urlencode("Halo, saya mau sewa kamar tipe " . $kamar['nama_tipe_kamar'] . " di " . $kamar['nama_kost']); ?>
                    <div class="d-grid gap-2">
                        <a href="<?= $is_logged_in ? $waLink : 'login' ?>" target="_blank" class="btn btn-success text-white fw-bold py-2 shadow-sm">
                            <i class="bi bi-whatsapp"></i> Chat Pemilik
                        </a>

                        <?php if ($is_logged_in): ?>
                            <div class="row g-2">
                                <div class="col-6">
                                    <button type="button" class="btn btn-outline-primary fw-bold w-100" data-bs-toggle="modal" data-bs-target="#modalSurvei">
                                        <i class="bi bi-eye"></i> Survei
                                    </button>
                                </div>
                                <div class="col-6">
                                    <button type="button" class="btn btn-primary fw-bold w-100" data-bs-toggle="modal" data-bs-target="#modalAjukanSewa">
                                        Sewa
                                    </button>
                                </div>
                            </div>
                        <?php else: ?>
                            <a href="login" class="btn btn-primary fw-bold py-2">
                                Ajukan Sewa / Survei
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================================= Modal Ajukan Sewa ========================================== -->
    <?php if ($is_logged_in): ?>
        <div class="modal fade" id="modalAjukanSewa" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Sewa Kamar: <?= $kamar['nama_tipe_kamar'] ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" action="proses_booking">
                        <div class="modal-body">
                            <input type="hidden" name="ajukan_sewa" value="true">
                            <input type="hidden" name="tipe_aksi" value="sewa"> <input type="hidden" name="id_kost" value="<?= $id_kost ?>">
                            <input type="hidden" name="id_kamar" value="<?= $id_kamar ?>">

                            <div class="mb-3">
                                <label class="form-label">Mulai Tanggal Berapa?</label>
                                <div id="calendarWidget" class="calendar-widget border rounded-3 p-3"></div>
                                <input type="hidden" name="tgl_mulai" id="tglMulaiInput" required min="<?= date('Y-m-d') ?>">
                                <small id="selectedDateLabel" class="text-muted d-block mt-2">Belum memilih tanggal.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Rencana Sewa (Bulan)</label>
                                <div class="d-flex align-items-center gap-3">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="durasiMinus">-</button>
                                    <span id="durasiValue" class="fw-bold fs-5">1</span>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="durasiPlus">+</button>
                                </div>
                                <input type="hidden" name="durasi" id="durasiInput" value="1">
                            </div>
                            <div class="alert alert-info small">
                                <i class="bi bi-info-circle"></i> Pemilik akan dikonfirmasi Pengajuan anda , Cek status pengajuan di halaman pesanan.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Kirim Pengajuan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modalSurvei" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Jadwalkan Survei Lokasi</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" action="proses_booking">
                        <div class="modal-body">
                            <input type="hidden" name="tipe_aksi" value="survei">
                            <input type="hidden" name="id_kost" value="<?= $id_kost ?>">

                            <div class="alert alert-warning small">
                                <i class="bi bi-exclamation-circle-fill me-1"></i> <strong>Perhatian:</strong><br>
                                Batas waktu survei maksimal <strong>3 hari</strong> dari hari ini.
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small">Rencana Tanggal (Maks 3 Hari)</label>
                                <div id="calendarSurvei" class="calendar-widget border rounded-3 p-3"></div>
                                <input type="hidden" name="tgl_survei" id="tglSurveiInput" required>
                                <small id="selectedSurveiLabel" class="text-muted d-block mt-2">Belum memilih tanggal.</small>
                                <div class="form-text text-muted">Hanya bisa memilih tanggal <?= date('d M') ?> s/d <?= date('d M', strtotime('+3 days')) ?></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Jam Kira-kira</label>
                                <input type="time" name="jam_survei" class="form-control" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-outline-primary w-100">Ajukan Jadwal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <!-- =========================================================================================== -->

    <div class="modal fade" id="modalGrid" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Semua Foto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php foreach ($galeri_by_kategori as $kategori => $fotoIndexes): ?>
                        <h6 class="fw-bold text-primary mt-3"><?= $kategori ?></h6>
                        <div class="row g-2 mb-2">
                            <?php foreach ($fotoIndexes as $fotoIdx):
                                $g = $galeri_semua[$fotoIdx]; ?>
                                <div class="col-6 col-md-3">
                                    <div class="position-relative" style="cursor: pointer;" onclick="openFullscreen(<?= $fotoIdx ?>)">
                                        <img src="assets/img/galeri/<?= $g['nama_file'] ?>" class="w-100 rounded" style="height: 150px; object-fit: cover;">
                                        <?php if ($g['is_360']): ?>
                                            <div class="position-absolute top-50 start-50 translate-middle text-white bg-dark bg-opacity-50 rounded-circle p-2">
                                                <i class="bi bi-arrow-repeat fs-4"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
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
                            <?php foreach ($galeri_semua as $idx => $g): ?>
                                <div class="carousel-item h-100 w-100 <?= $idx == 0 ? 'active' : '' ?>">
                                    <div class="carousel-fs-item">

                                        <?php if ($g['is_360']): ?>
                                            <div class="w-100 h-100 d-flex justify-content-center align-items-center position-relative">
                                                <img src="assets/img/galeri/<?= $g['nama_file'] ?>" class="fs-img" style="opacity: 0.4;">
                                                <button class="btn btn-primary btn-launch-360 rounded-pill" onclick="launch360Modal('assets/img/galeri/<?= $g['nama_file'] ?>')">
                                                    <i class="bi bi-goggles me-2"></i> Buka Mode 360°
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <img src="assets/img/galeri/<?= $g['nama_file'] ?>" class="fs-img">
                                        <?php endif; ?>

                                        <div class="carousel-caption d-none d-md-block pb-5">
                                            <h5 class="kategori-foto-badge"><?= $g['kategori_foto'] ?></h5>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#fsCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
                        <button class="carousel-control-next" type="button" data-bs-target="#fsCarousel" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalDedicated360" tabindex="-1">
        <div class="modal-dialog modal-fullscreen bg-dark">
            <div class="modal-content bg-dark border-0">
                <button type="button" class="btn-close-360 shadow" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
                <div class="modal-body p-0">
                    <div id="pano-container-dedicated"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-block d-lg-none fixed-bottom bg-white border-top p-3 shadow">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <small class="text-muted d-block">Harga per bulan</small>
                <h5 class="fw-bold text-primary mb-0">Rp <?= number_format($kamar['harga_per_bulan'], 0, ',', '.') ?></h5>
            </div>

            <div class="d-flex gap-2">
                <a href="<?= $btn_action_chat ?>" <?= $target_blank ?> class="btn btn-success text-white fw-bold rounded-pill px-2">
                    <i class="bi bi-whatsapp ms-1"></i>
                </a>
                <?php if ($is_logged_in): ?>
                    <button type="button" class="btn btn-outline-secondary fw-bold rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalSurvei">
                        Survei
                    </button>
                    <button type="button" class="btn btn-outline-primary fw-bold rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalAjukanSewa">
                        Sewa
                    </button>
                <?php else: ?>
                    <a href="login" class="btn btn-outline-secondary fw-bold rounded-pill px-4">
                        Survei
                    </a>
                    <a href="<?= $btn_action_sewa ?>" class="btn btn-outline-primary fw-bold rounded-pill px-4">
                        Sewa
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Review Kamar -->
    <div class="modal fade" id="modalReviewKamar" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fs-6"><span id="namaKostReview" class="fw-bold"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="proses_ulasan">
                    <div class="modal-body">
                        <input type="hidden" name="id_review" id="idReviewEdit" value="">
                        <input type="hidden" name="id_kost" id="idKostReview" value="<?= $id_kost ?>">
                        <input type="hidden" name="id_kamar" id="idKamarReview" value="<?= $id_kamar ?>">
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
                            <label class="form-label fw-bold small">Akurasi Foto/Info</label>
                            <div class="stars akurasi" role="radiogroup" aria-label="Akurasi">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <input type="radio" name="rating_akurasi" id="ak<?= $i ?>" value="<?= $i ?>" required>
                                    <label for="ak<?= $i ?>"><i class="bi bi-star-fill"></i></label>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Kepuasan Umum</label>
                            <div class="stars umum" role="radiogroup" aria-label="Kepuasan Umum">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <input type="radio" name="rating_umum" id="um<?= $i ?>" value="<?= $i ?>" required>
                                    <label for="um<?= $i ?>"><i class="bi bi-star-fill"></i></label>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        var modalFsEl = document.getElementById('modalFullscreen');
        var modalFs = new bootstrap.Modal(modalFsEl);

        var modal360El = document.getElementById('modalDedicated360');
        var modal360 = new bootstrap.Modal(modal360El);

        var sliderEl = document.getElementById('fsCarousel');
        var bsCarousel = new bootstrap.Carousel(sliderEl, {
            interval: false
        });

        // 1. Buka Carousel (Foto Biasa)
        function openFullscreen(idx) {
            var modalGridEl = document.getElementById('modalGrid');
            var modalGrid = bootstrap.Modal.getInstance(modalGridEl);
            if (modalGrid) modalGrid.hide(); // Tutup grid jika ada

            modalFs.show();
            setTimeout(() => {
                bsCarousel.to(idx);
            }, 250);
        }

        // 2. Luncurkan Modal 360 (Logika "Nuclear")
        function launch360Modal(imgSrc) {
            // Tutup carousel dulu agar tidak ada script konflik
            modalFs.hide();

            // Buka Modal Khusus 360
            modal360.show();

            // Tunggu modal tampil, lalu render Pannellum di div bersih
            setTimeout(() => {
                var container = document.getElementById('pano-container-dedicated');
                container.innerHTML = ""; // Bersihkan sisa lama

                pannellum.viewer('pano-container-dedicated', {
                    "type": "equirectangular",
                    "panorama": imgSrc,
                    "autoLoad": true,
                    "compass": true,
                    "mouseZoom": true, // Aktifkan zoom di laptop
                    "showZoomCtrl": true // Tampilkan kontrol zoom
                });
            }, 300);
        }

        // 3. Saat Modal 360 ditutup, buka kembali Carousel (Opsional, UX bagus)
        modal360El.addEventListener('hidden.bs.modal', function() {
            modalFs.show();
        });

        const minTanggalStr = "<?= date('Y-m-d') ?>";
        const maxTanggalSurveiStr = "<?= date('Y-m-d', strtotime('+3 days')) ?>";
        const calendarConfigs = [];
        const dayNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

        const calendarWidget = document.getElementById('calendarWidget');
        const tglMulaiInput = document.getElementById('tglMulaiInput');
        const selectedDateLabel = document.getElementById('selectedDateLabel');
        if (calendarWidget && tglMulaiInput && selectedDateLabel) {
            calendarConfigs.push({
                container: calendarWidget,
                input: tglMulaiInput,
                label: selectedDateLabel,
                minDate: minTanggalStr,
                maxDate: null
            });
        }

        const calendarSurvei = document.getElementById('calendarSurvei');
        const tglSurveiInput = document.getElementById('tglSurveiInput');
        const selectedSurveiLabel = document.getElementById('selectedSurveiLabel');
        if (calendarSurvei && tglSurveiInput && selectedSurveiLabel) {
            calendarConfigs.push({
                container: calendarSurvei,
                input: tglSurveiInput,
                label: selectedSurveiLabel,
                minDate: minTanggalStr,
                maxDate: maxTanggalSurveiStr
            });
        }

        calendarConfigs.forEach(cfg => initCalendarWidget(cfg));

        function initCalendarWidget({
            container,
            input,
            label,
            minDate,
            maxDate
        }) {
            let calendarCurrent = new Date(minDate);
            let selectedDate = input.value || null;

            function renderCalendar() {
                const year = calendarCurrent.getFullYear();
                const month = calendarCurrent.getMonth();
                const firstDay = new Date(year, month, 1).getDay();
                const daysInMonth = new Date(year, month + 1, 0).getDate();
                const monthLabel = new Intl.DateTimeFormat('id-ID', {
                    month: 'long',
                    year: 'numeric'
                }).format(calendarCurrent);

                const cells = [];
                for (let i = 0; i < firstDay; i++) cells.push('');
                for (let day = 1; day <= daysInMonth; day++) cells.push(day);

                container.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-cal-nav="prev">&lt;</button>
                        <strong>${monthLabel}</strong>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-cal-nav="next">&gt;</button>
                    </div>
                    <div class="calendar-weekdays gap-1 mb-1">
                        ${dayNames.map(d => `<div class="text-center small fw-semibold text-muted">${d}</div>`).join('')}
                    </div>
                    <div class="calendar-days gap-1">
                        ${cells.map(val => {
                            if (!val) return '<div></div>';
                            const dateObj = new Date(year, month, val);
                            const dateStr = dateObj.toISOString().split('T')[0];
                            const disabled = dateStr < minDate || (maxDate && dateStr > maxDate) ? 'disabled' : '';
                            const active = selectedDate === dateStr ? 'active' : '';
                            return `<button type="button" class="btn btn-light ${active}" data-date="${dateStr}" ${disabled}>${val}</button>`;
                        }).join('')}
                    </div>
                `;
                updateSelectedLabel();
            }

            function updateSelectedLabel() {
                if (!label) return;
                label.textContent = selectedDate ?
                    `Tanggal dipilih: ${new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }).format(new Date(selectedDate))}` :
                    'Belum memilih tanggal.';
            }

            container.addEventListener('click', function(e) {
                const nav = e.target.getAttribute('data-cal-nav');
                if (nav === 'prev') {
                    calendarCurrent.setMonth(calendarCurrent.getMonth() - 1);
                    renderCalendar();
                    return;
                }
                if (nav === 'next') {
                    calendarCurrent.setMonth(calendarCurrent.getMonth() + 1);
                    renderCalendar();
                    return;
                }
                const date = e.target.getAttribute('data-date');
                if (date && date >= minDate && (!maxDate || date <= maxDate)) {
                    selectedDate = date;
                    input.value = date;
                    updateSelectedLabel();
                    renderCalendar();
                }
            });

            renderCalendar();
        }

        const durasiInput = document.getElementById('durasiInput');
        const durasiValue = document.getElementById('durasiValue');
        const durasiMinus = document.getElementById('durasiMinus');
        const durasiPlus = document.getElementById('durasiPlus');

        function setDurasi(value) {
            const safeValue = Math.max(1, value);
            durasiInput.value = safeValue;
            durasiValue.textContent = safeValue;
        }

        if (durasiMinus && durasiPlus) {
            durasiMinus.addEventListener('click', () => setDurasi(parseInt(durasiInput.value, 10) - 1));
            durasiPlus.addEventListener('click', () => setDurasi(parseInt(durasiInput.value, 10) + 1));
        }

        const modalAjukanSewaEl = document.getElementById('modalAjukanSewa');
        if (modalAjukanSewaEl) {
            modalAjukanSewaEl.addEventListener('shown.bs.modal', function() {
                const inputTanggal = document.getElementById('tglMulaiInput');
                if (!inputTanggal) return;
                if (typeof inputTanggal.showPicker === 'function') {
                    inputTanggal.showPicker();
                } else {
                    inputTanggal.focus();
                }
            });
        }

        // Review modal handlers khusus kamar
        let targetLat = <?= $kamar['latitude'] ?: 0 ?>;
        let targetLong = <?= $kamar['longitude'] ?: 0 ?>;
        let userType = '';
        let isEditMode = false;

        function bukaModalReviewKamar(type = 'survei') {
            isEditMode = false;
            document.getElementById('idReviewEdit').value = '';
            document.getElementById('idKostReview').value = '<?= $id_kost ?>';
            document.getElementById('idKamarReview').value = '<?= $id_kamar ?>';
            document.getElementById('namaKostReview').innerText = "<?= addslashes($kamar['nama_kost']) ?>";
            document.getElementById('jenisReviewer').value = type;
            document.getElementById('komentarReview').value = '';
            document.querySelectorAll('input[name="rating_akurasi"]').forEach(el => el.checked = false);
            document.querySelectorAll('input[name="rating_umum"]').forEach(el => el.checked = false);

            userType = type;
            resetGPSUI();
            var myModal = new bootstrap.Modal(document.getElementById('modalReviewKamar'));
            myModal.show();

            if (userType === 'sewa') {
                document.getElementById('loadingGps').style.display = 'none';
                document.getElementById('gpsText').innerHTML = "<i class='bi bi-house-fill'></i> Penyewa — verifikasi lokasi tidak diperlukan";
                document.getElementById('jarakText').innerHTML = "Status: Penyewa (Tidak perlu verifikasi lokasi)";
                enableButton();
            } else {
                getLocation();
            }
        }

        function editReviewFromId(idReview) {
            isEditMode = true;
            document.getElementById('idReviewEdit').value = idReview;
            document.getElementById('idKostReview').value = '<?= $id_kost ?>';
            document.getElementById('idKamarReview').value = '<?= $id_kamar ?>';
            document.getElementById('namaKostReview').innerText = "<?= addslashes($kamar['nama_kost']) ?> (Edit)";

            fetch('get_review?id=' + idReview)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('komentarReview').value = data.komentar || '';
                        document.getElementById('jenisReviewer').value = data.jenis_reviewer || 'survei';

                        if (data.skor_akurasi) {
                            const akel = document.getElementById('ak' + data.skor_akurasi);
                            if (akel) akel.checked = true;
                        }
                        if (data.rating) {
                            const umel = document.getElementById('um' + data.rating);
                            if (umel) umel.checked = true;
                        }

                        userType = data.jenis_reviewer || 'survei';
                        var myModal = new bootstrap.Modal(document.getElementById('modalReviewKamar'));
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

        // GPS & Review
        function getLocation() {
            if (!navigator.geolocation) {
                showError('Browser ini tidak mendukung geolokasi.');
                return;
            }
            document.getElementById('loadingGps').style.display = 'flex';

            navigator.geolocation.getCurrentPosition(processPosition, function(err) {
                document.getElementById('loadingGps').style.display = 'none';
                if (err.code === err.PERMISSION_DENIED) {
                    showError('Izin akses lokasi ditolak. Aktifkan izin lokasi untuk menggunakan fitur ini.');
                } else {
                    showError('Gagal mendapatkan lokasi Anda. Error: ' + err.message);
                }
            }, {
                enableHighAccuracy: true
            });
        }

        function processPosition(position) {
            document.getElementById('loadingGps').style.display = 'none';
            const lat = position.coords.latitude;
            const long = position.coords.longitude;

            document.getElementById('userLat').value = lat;
            document.getElementById('userLong').value = long;

            const jarak = hitungJarak(lat, long, targetLat, targetLong);
            const jarakKm = (jarak / 1000).toFixed(2);
            document.getElementById('jarakText').innerHTML = "Jarak Anda: " + jarakKm + " km dari lokasi kamar";

            if (validasiJarak(jarak)) {
                enableButton();
            } else {
                disableButton();
            }
        }

        function validasiJarak(jarak) {
            return jarak <= 100; // Maksimal 100 meter
        }

        function hitungJarak(lat1, long1, lat2, long2) {
            const toRad = (value) => (value * Math.PI) / 180;
            const R = 6371e3; // Radius Bumi dalam meter
            const φ1 = toRad(lat1);
            const φ2 = toRad(lat2);
            const Δφ = toRad(lat2 - lat1);
            const Δλ = toRad(long2 - long1);

            const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
                Math.cos(φ1) * Math.cos(φ2) *
                Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

            return R * c; // Dalam meter
        }

        function resetGPSUI() {
            document.getElementById('loadingGps').style.display = 'none';
            document.getElementById('gpsText').innerHTML = "<i class='bi bi-geo'></i> Verifikasi lokasi Anda untuk memberikan ulasan";
            document.getElementById('jarakText').innerHTML = "Status: Belum diverifikasi";
            disableButton();
        }

        function enableButton() {
            document.getElementById('btnKirimReview').disabled = false;
            document.getElementById('btnKirimReview').innerHTML = "<i class='bi bi-send'></i> Kirim Ulasan";
        }

        function disableButton() {
            document.getElementById('btnKirimReview').disabled = true;
            document.getElementById('btnKirimReview').innerHTML = "<i class='bi bi-lock-fill'></i> Lokasi Belum Terverifikasi";
        }

        function showError(message) {
            document.getElementById('gpsText').innerHTML = "<i class='bi bi-exclamation-circle'></i> " + message;
            document.getElementById('loadingGps').style.display = 'none';
        }

        // Auto open review modal if edit_review param exists
        <?php if (isset($_GET['edit_review'])): ?>
            window.addEventListener('DOMContentLoaded', function() {
                editReviewFromId(<?= (int)$_GET['edit_review'] ?>);
            });
        <?php endif; ?>

        // Auto open via ?reviewer=survei|sewa&optional id_kamar (pakai kamar ini)
        <?php if (isset($_GET['reviewer'])): ?>
            window.addEventListener('DOMContentLoaded', function() {
                bukaModalReviewKamar('<?= $_GET['reviewer'] === 'sewa' ? 'sewa' : 'survei' ?>');
            });
        <?php endif; ?>
    </script>
    <?php include 'footer.php'; ?>
</body>

</html>