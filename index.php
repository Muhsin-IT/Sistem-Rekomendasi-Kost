<?php
session_start();
include 'koneksi.php';

// Query Kost
$query = "SELECT k.*, 
          (SELECT MIN(harga_per_bulan) FROM kamar WHERE id_kost = k.id_kost) as harga_min,
          (SELECT nama_file FROM galeri WHERE id_kost = k.id_kost AND kategori_foto = 'Tampak Depan' LIMIT 1) as foto_depan,
          (SELECT AVG(rating) FROM review WHERE id_kost = k.id_kost) as rating_avg
          FROM kost k
          ORDER BY k.id_kost DESC LIMIT 6"; // Dilimit 6 agar tidak terlalu panjang di home
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/img/logo/persegi.webp">
    <title>RadenStay - Cari Kost Dekat UNU Jogja</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* CSS Variables untuk Palet Warna yang Lebih Lembut */
        :root {
            --bg-soft: #f4f7f9;
            /* Latar belakang abu-abu sangat muda */
            --text-dark: #2c3e50;
            /* Teks utama (bukan hitam pekat) */
            --text-muted: #6c757d;
            /* Teks sekunder */
            --brand-blue: #0d6efd;
            /* Biru utama */
            --brand-orange: #fd7e14;
            /* Oranye utama */
        }

        body {
            background-color: var(--bg-soft);
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: var(--text-dark);
        }

        /* HERO SECTION RESPONSIF & LEMBUT */
        .hero-section {
            /* Gradien yang sangat halus, tidak menyilaukan */
            background: linear-gradient(180deg, #ffffff 0%, #eef2f7 100%);
            /* Padding responsif: lebih kecil di HP (py-4), besar di layar lebar (py-lg-5) */
            padding: 3rem 0;
            border-bottom: 1px solid #e1e5eb;
        }

        /* Judul Hero Responsif */
        .hero-title {
            font-weight: 800;
            color: var(--text-dark);
            letter-spacing: -0.5px;
            /* Ukuran font menyesuaikan layar */
            font-size: calc(1.8rem + 1.5vw);
        }

        /* Search Bar Responsif */
        .search-container {
            background: white;
            padding: 8px;
            border-radius: 50px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            /* Bayangan lembut */
            border: 1px solid #eee;
        }

        .search-input {
            border: none;
            box-shadow: none !important;
            font-size: 1rem;
        }

        .search-btn {
            background-color: var(--brand-orange);
            border: none;
            padding: 10px 25px;
            border-radius: 30px;
            transition: all 0.3s;
        }

        .search-btn:hover {
            background-color: #e36d0a;
            /* Oranye sedikit lebih gelap saat hover */
            transform: translateY(-2px);
        }

        /* CARD KOST RESPONSIF */
        .card-kost {
            border: none;
            border-radius: 16px;
            /* Bayangan sangat halus agar tidak terlihat kotor */
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            background: white;
            overflow: hidden;
            height: 100%;
            /* Agar tinggi kartu sama */
        }

        .card-kost:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .kost-img-wrapper {
            /* Tinggi gambar responsif: 180px di HP, 200px di Tablet ke atas */
            height: 180px;
            position: relative;
        }

        @media (min-width: 768px) {
            .kost-img-wrapper {
                height: 200px;
            }
        }

        .kost-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .badge-tipe {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 700;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(4px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        /* Harga yang lebih mudah dilihat */
        .price-tag {
            color: var(--brand-blue);
            font-weight: 800;
            font-size: 1.25rem;
        }

        .badge-rating {
            position: absolute;
            bottom: 10px;
            left: 10px;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: bold;
            background: rgba(0, 0, 0, 0.7);
            color: #ffc107;
            font-size: 0.8rem;
        }
    </style>
</head>

<body>

    <?php include 'navbar.php'; ?>

    <header class="hero-section text-center text-lg-start">
        <div class="container py-lg-4">
            <div class="row justify-content-center align-items-center gx-lg-5">
                <div class="col-lg-7 order-2 order-lg-1">
                    <h1 class="hero-title mb-3">Hunian Nyaman Mahasiswa <span class="text-primary">UNU Jogja</span></h1>
                    <p class="lead text-muted mb-4" style="font-weight: 400;">Temukan kost strategis, fasilitas lengkap, dan harga yang pas di kantong mahasiswa. Mulai pencarianmu sekarang!</p>

                    <div class="search-container d-inline-block w-100" style="max-width: 550px;">
                        <form action="search" method="GET" class="d-flex align-items-center">
                            <i class="bi bi-search text-muted ms-3 fs-5"></i>
                            <input class="form-control search-input ps-3" type="search" placeholder="Cari nama kost fasilitas atau peraturan..." name="keyword" required>
                            <button class="btn btn-warning text-white fw-bold search-btn" type="submit">CARI</button>
                        </form>
                    </div>

                    <div class="mt-4 text-muted small">
                        <i class="bi bi-info-circle me-1"></i> Populer: "Kost Putra", "Parkiran Mobil", "Kamar Mandi Dalam"
                    </div>
                </div>

                <div class="col-lg-5 order-1 order-lg-2 mb-4 mb-lg-0 d-none d-md-block">
                    <img src="https://img.freepik.com/free-vector/college-campus-concept-illustration_114360-1050.jpg?w=740&t=st=1703604000~exp=1703604600~hmac=f20c10..." alt="Ilustrasi Mahasiswa" class="img-fluid rounded-4 shadow-sm" style="opacity: 0.9;">
                </div>
            </div>
        </div>
    </header>

    <main class="container my-5 py-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
            <div>
                <h3 class="fw-bold mb-1">Rekomendasi Kost Terbaru</h3>
                <p class="text-muted mb-0">Pilihan tempat tinggal yang baru ditambahkan.</p>
            </div>
            <a href="rekomendasi_saw" class="btn btn-outline-primary rounded-pill px-4 fw-semibold">
                <i class="bi bi-stars me-1"></i> Coba Rekomendasi Pintar (SAW)
            </a>
        </div>

        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <?php $imgSrc = $row['foto_depan'] ? "assets/img/galeri/" . $row['foto_depan'] : "https://via.placeholder.com/400x250?text=Belum+Ada+Foto"; ?>

                <div class="col">
                    <div class="card card-kost h-100">
                        <div class="kost-img-wrapper">
                            <img src="<?= $imgSrc ?>" class="kost-img" alt="<?= $row['nama_kost'] ?>">
                            <span class="badge bg-white text-dark badge-tipe shadow-sm">
                                <?php if ($row['jenis_kost'] == 'Putra'): ?>
                                    <i class="bi bi-gender-male text-primary"></i> Putra
                                <?php elseif ($row['jenis_kost'] == 'Putri'): ?>
                                    <i class="bi bi-gender-female text-danger"></i> Putri
                                <?php else: ?>
                                    <i class="bi bi-gender-ambiguous text-success"></i> Campur
                                <?php endif; ?>
                            </span>
                            <?php if ($row['rating_avg'] > 0): ?>
                                <span class="badge-rating">
                                    <i class="bi bi-star-fill"></i> <?= round($row['rating_avg'], 1) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="card-body p-4 d-flex flex-column">
                            <h5 class="card-title fw-bold text-dark mb-2"><?= substr($row['nama_kost'], 0, 25) ?><?= strlen($row['nama_kost']) > 25 ? '...' : '' ?></h5>

                            <p class="text-muted small mb-3 flex-grow-1">
                                <i class="bi bi-geo-alt me-1 text-danger opacity-75"></i>
                                <?= substr($row['alamat'], 0, 45) ?>...
                            </p>

                            <hr class="my-3 border-light">

                            <div class="d-flex justify-content-between align-items-end">
                                <div>
                                    <small class="text-muted d-block fw-semibold" style="font-size: 0.8rem;">Mulai dari</small>
                                    <?php if ($row['harga_min']): ?>
                                        <span class="price-tag">Rp <?= number_format($row['harga_min'], 0, ',', '.') ?></span>
                                        <small class="text-muted">/bln</small>
                                    <?php else: ?>
                                        <span class="text-danger fw-bold small">Penuh/Blm ada kamar</span>
                                    <?php endif; ?>
                                </div>
                                <a href="detail_kost?id=<?= $row['id_kost'] ?>" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" style="font-size: 0.9rem;">
                                    Detail <i class="bi bi-chevron-right ms-1" style="font-size: 0.8rem;"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>

            <?php if (mysqli_num_rows($result) == 0): ?>
                <div class="col-12 text-center py-5 text-muted">
                    <img src="https://cdn-icons-png.flaticon.com/512/7465/7465691.png" height="100" class="mb-3 opacity-50">
                    <h5>Belum ada data kost yang tersedia.</h5>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>