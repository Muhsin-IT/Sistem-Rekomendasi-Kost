<?php
session_start();
include '../koneksi.php';

// 1. CEK KEAMANAN
if (!isset($_SESSION['login']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'admin super')) {
    header("Location: ../login.php");
    exit;
}

// 2. LOGIKA HAPUS KOST
if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];

    // Ambil data gambar kost dulu untuk dihapus dari folder (Opsional/Advance)
    // Untuk sekarang kita hapus datanya saja dari database.
    // Pastikan di database Foreign Key sudah ON DELETE CASCADE agar kamar & galeri ikut terhapus otomatis.

    $del = mysqli_query($conn, "DELETE FROM kost WHERE id_kost = '$id_hapus'");
    if ($del) {
        echo "<script>alert('Data Kost berhasil dihapus!'); window.location='data_kost.php';</script>";
    }
}

// 3. LOGIKA PENCARIAN
$where = "";
if (isset($_GET['cari'])) {
    $keyword = $_GET['cari'];
    $where = " AND (k.nama_kost LIKE '%$keyword%' OR u.nama_lengkap LIKE '%$keyword%' OR k.kota LIKE '%$keyword%')";
}

// Query: Gabungkan tabel kost dengan users (untuk dapat nama pemilik)
$query = "SELECT k.*, u.nama_lengkap as nama_pemilik, u.no_hp 
          FROM kost k 
          JOIN users u ON k.id_pemilik = u.id_user 
          WHERE 1=1 $where 
          ORDER BY k.id_kost DESC";

$kosts = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Kost - Admin RadenStay</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            background-image: url('../assets/img/logo/bg-hero-wide.webp');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
        }

        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: -1;
            position: fixed;
        }

        /* Style Sidebar & Glass Card diambil dari file sebelumnya */
        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
    </style>
</head>

<body>

    <div class="container-fluid">
        <div class="row">

            <?php include 'sidebar.php'; ?>

            <div class="col-md-9 col-lg-10 p-4">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold text-white"><i class="fa-solid fa-building me-2"></i> Manajemen Kost</h3>
                    <a href="../logout.php" class="btn btn-warning btn-sm d-md-none">Logout</a>
                </div>

                <div class="card glass-card border-0 p-4">

                    <div class="row mb-3">
                        <div class="col-md-6 ms-auto">
                            <form method="GET">
                                <div class="input-group">
                                    <input type="text" name="cari" class="form-control" placeholder="Cari nama kost, pemilik, atau kota..." value="<?= isset($_GET['cari']) ? $_GET['cari'] : '' ?>">
                                    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-search"></i></button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th style="width: 25%;">Nama Kost</th>
                                    <th>Pemilik</th>
                                    <th>Alamat</th>
                                    <th>Kontak</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                if (mysqli_num_rows($kosts) > 0):
                                    while ($k = mysqli_fetch_assoc($kosts)):
                                ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td>
                                                <div class="fw-bold"><?= $k['nama_kost'] ?></div>
                                                <small class="text-muted"><i class="fa-solid fa-map-pin me-1"></i> <?= substr($k['alamat'], 0, 30) ?>...</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info text-dark">
                                                    <i class="fa-solid fa-user-tie me-1"></i> <?= $k['nama_pemilik'] ?>
                                                </span>
                                            </td>
                                            <td><?= $k['alamat'] ?></td>
                                            <td>
                                                <a href="https://wa.me/<?= $k['no_hp'] ?>" target="_blank" class="btn btn-sm btn-success rounded-pill">
                                                    <i class="fa-brands fa-whatsapp"></i> Chat
                                                </a>
                                            </td>
                                            <td>
                                                <a href="data_kost.php?hapus=<?= $k['id_kost'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('PERINGATAN: Menghapus kost ini akan menghapus semua kamar dan galeri di dalamnya. Lanjutkan?')">
                                                    <i class="fa-solid fa-trash"></i> Hapus
                                                </a>
                                            </td>
                                        </tr>
                                    <?php
                                    endwhile;
                                else:
                                    ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted fst-italic">
                                            Belum ada data kost yang terdaftar.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>

</body>

</html>