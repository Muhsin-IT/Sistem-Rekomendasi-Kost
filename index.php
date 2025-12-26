<?php
session_start();
include 'koneksi.php';

// Ambil data kost beserta harga terendah dari tabel kamar
$query = "SELECT kost.*, MIN(kamar.harga_per_bulan) as harga_min 
          FROM kost 
          LEFT JOIN kamar ON kost.id_kost = kamar.id_kost 
          GROUP BY kost.id_kost";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kost UNU Yogyakarta - Cari Kost Mahasiswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('https://images.unsplash.com/photo-1555854877-bab0e564b8d5?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            color: white;
            padding: 100px 0;
        }

        .card-kost:hover {
            transform: translateY(-5px);
            transition: 0.3s;
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-success sticky-top">
        <div class="container">
            <a class="navbar-brand" href="#"><b>KOST UNU</b></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" href="index.php">Jelajah</a></li>
                    <?php if (isset($_SESSION['login'])): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= $_SESSION['role'] ?>/dashboard.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link btn btn-danger btn-sm text-white ms-lg-2" href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                        <li class="nav-item"><a class="nav-link btn btn-light text-success btn-sm ms-lg-2" href="daftar.php">Daftar</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <header class="hero-section text-center">
        <div class="container">
            <h1 class="display-4 fw-bold">Cari Kost Dekat UNU Yogyakarta</h1>
            <p class="lead">Temukan hunian nyaman, aman, dan sesuai budget mahasiswa.</p>
            <div class="row justify-content-center mt-4">
                <div class="col-md-6">
                    <form action="search.php" method="GET" class="d-flex">
                        <input class="form-control me-2 py-3" type="search" placeholder="Cari lokasi atau nama kost..." name="keyword">
                        <button class="btn btn-warning px-4" type="submit">Cari</button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <main class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Rekomendasi Kost Terpopuler</h2>
            <a href="rekomendasi_saw.php" class="btn btn-outline-success"><i class="bi bi-stars"></i> Gunakan Rekomendasi SAW</a>
        </div>

        <div class="row">
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <div class="col-md-4 mb-4">
                    <div class="card card-kost shadow-sm h-100">
                        <img src="https://via.placeholder.com/400x250?text=Foto+Kost" class="card-img-top" alt="Kost">
                        <div class="card-body">
                            <div class="mb-2">
                                <span class="badge bg-<?= $row['jenis_kost'] == 'Putra' ? 'primary' : ($row['jenis_kost'] == 'Putri' ? 'danger' : 'info') ?>">
                                    <?= $row['jenis_kost'] ?>
                                </span>
                            </div>
                            <h5 class="card-title"><?= $row['nama_kost'] ?></h5>
                            <p class="text-muted mb-2 small"><i class="bi bi-geo-alt-fill"></i> <?= $row['alamat'] ?></p>
                            <h6 class="text-success fw-bold"><?php if ($row['harga_min']): ?>
                                    Rp <?= number_format($row['harga_min'], 0, ',', '.') ?>
                                <?php else: ?>
                                    <span class="text-muted small">Harga N/A</span>
                                <?php endif; ?>
                            </h6>
                            <hr>
                            <div class="d-grid">
                                <a href="detail_kost.php?id=<?= $row['id_kost'] ?>" class="btn btn-outline-success">Lihat Detail</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </main>

    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p>&copy; 2025 Kost UNU Yogyakarta - Muhsin Project</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>