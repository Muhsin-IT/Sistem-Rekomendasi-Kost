<?php
session_start();
include '../koneksi.php';

// Cek Keamanan: Hanya Role 'admin' yang boleh masuk
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// LOGIKA HAPUS USER
if (isset($_GET['hapus_user'])) {
    $id_hapus = $_GET['hapus_user'];
    // Hapus data terkait (Optional: Tambahkan logika hapus kost/kamar jika pemilik dihapus)
    mysqli_query($conn, "DELETE FROM users WHERE id_user = '$id_hapus'");
    echo "<script>alert('User berhasil dihapus!'); window.location='index.php';</script>";
}

// STATISTIK DASHBOARD
$jml_mhs   = mysqli_num_rows(mysqli_query($conn, "SELECT id_user FROM users WHERE role='mahasiswa'"));
$jml_owner = mysqli_num_rows(mysqli_query($conn, "SELECT id_user FROM users WHERE role='pemilik'"));
$jml_kost  = mysqli_num_rows(mysqli_query($conn, "SELECT id_kost FROM kost"));
$jml_kamar = mysqli_num_rows(mysqli_query($conn, "SELECT id_kamar FROM kamar"));

// DATA USER TERBARU
$users = mysqli_query($conn, "SELECT * FROM users WHERE role != 'admin' ORDER BY id_user DESC LIMIT 10");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin - RadenStay</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            /* Background Konsisten dengan Login */
            background-image: url('../assets/img/logo/bg-hero-wide.webp');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
        }

        /* Overlay Gelap agar konten terbaca */
        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            /* Lebih gelap dikit untuk admin */
            z-index: -1;
            position: fixed;
            /* Agar overlay tetap saat scroll */
        }

        /* Sidebar Glass */

        /* Content Area */
        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            /* Putih semi transparan */
            backdrop-filter: blur(20px);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s;
        }

        .stat-card {
            color: white;
            border: none;
            overflow: hidden;
            position: relative;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        /* Warna-warni Card Statistik */
        .bg-grad-1 {
            background: linear-gradient(45deg, #4e54c8, #8f94fb);
        }

        /* Biru */
        .bg-grad-2 {
            background: linear-gradient(45deg, #11998e, #38ef7d);
        }

        /* Hijau */
        .bg-grad-3 {
            background: linear-gradient(45deg, #ff9966, #ff5e62);
        }

        /* Orange */
        .bg-grad-4 {
            background: linear-gradient(45deg, #833ab4, #fd1d1d);
        }

        /* Merah/Ungu */

        .stat-icon {
            position: absolute;
            right: 20px;
            bottom: 20px;
            font-size: 3rem;
            opacity: 0.3;
        }

        .table-glass {
            background: transparent;
        }

        .table-glass thead th {
            background-color: rgba(13, 110, 253, 0.1);
            border-bottom: 2px solid #0d6efd;
            color: #333;
        }

        .logo-admin {
            width: 50px;
            height: 50px;
            background: white;
            color: #0d6efd;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.5);
        }
    </style>
</head>

<body>

    <div class="container-fluid">
        <div class="row">

            <?php include 'sidebar.php'; ?>

            <div class="col-md-9 col-lg-10 p-4">

                <div class="d-block d-md-none mb-4 text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="fw-bold">Admin Panel</h4>
                        <a href="../logout.php" class="btn btn-sm btn-warning">Logout</a>
                    </div>
                </div>

                <h3 class="fw-bold text-white mb-4"><i class="fa-solid fa-chart-line me-2"></i> Overview Sistem</h3>

                <div class="row g-4 mb-5">
                    <div class="col-md-3">
                        <div class="card glass-card stat-card bg-grad-1 p-3">
                            <h6 class="text-white-50">Total Mahasiswa</h6>
                            <h2 class="fw-bold mb-0"><?= $jml_mhs ?></h2>
                            <i class="fa-solid fa-user-graduate stat-icon"></i>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card glass-card stat-card bg-grad-2 p-3">
                            <h6 class="text-white-50">Partner Owner</h6>
                            <h2 class="fw-bold mb-0"><?= $jml_owner ?></h2>
                            <i class="fa-solid fa-user-tie stat-icon"></i>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card glass-card stat-card bg-grad-3 p-3">
                            <h6 class="text-white-50">Unit Kost Terdaftar</h6>
                            <h2 class="fw-bold mb-0"><?= $jml_kost ?></h2>
                            <i class="fa-solid fa-building stat-icon"></i>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card glass-card stat-card bg-grad-4 p-3">
                            <h6 class="text-white-50">Total Kamar</h6>
                            <h2 class="fw-bold mb-0"><?= $jml_kamar ?></h2>
                            <i class="fa-solid fa-bed stat-icon"></i>
                        </div>
                    </div>
                </div>

                <div class="card glass-card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold text-dark"><i class="fa-solid fa-users-gear text-primary me-2"></i> Pengguna Terbaru</h5>
                        <button class="btn btn-primary btn-sm rounded-pill px-3">Lihat Semua</button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Lengkap</th>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>No. HP</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1;
                                while ($u = mysqli_fetch_assoc($users)): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td class="fw-bold"><?= $u['nama_lengkap'] ?></td>
                                        <td><span class="text-muted">@<?= $u['username'] ?></span></td>
                                        <td>
                                            <?php if ($u['role'] == 'pemilik'): ?>
                                                <span class="badge bg-warning text-dark"><i class="fa-solid fa-crown me-1"></i> Owner</span>
                                            <?php else: ?>
                                                <span class="badge bg-info"><i class="fa-solid fa-user me-1"></i> Mahasiswa</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $u['no_hp'] ?></td>
                                        <td>
                                            <a href="index.php?hapus_user=<?= $u['id_user'] ?>" class="btn btn-danger btn-sm rounded-circle shadow-sm" title="Hapus User" onclick="return confirm('Yakin ingin menghapus user ini? Data kost/kamar mereka mungkin akan error jika tidak ditangani.')">
                                                <i class="fa-solid fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

</body>

</html>