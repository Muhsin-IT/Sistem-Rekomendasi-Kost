<?php
session_start();
include '../koneksi.php';

// 1. CEK KEAMANAN (Sesuaikan role dengan database Anda: 'admin' atau 'admin super')
if (!isset($_SESSION['login']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'admin super')) {
    header("Location: ../login.php");
    exit;
}

// 2. LOGIKA HAPUS USER
if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];
    // Hapus user (Data terkait seperti kost/kamar akan error jika tidak di-CASCADE di database. 
    // Tapi untuk sekarang kita hapus user-nya saja).
    $del = mysqli_query($conn, "DELETE FROM users WHERE id_user = '$id_hapus'");
    if ($del) {
        echo "<script>alert('User berhasil dihapus!'); window.location='data_pengguna.php';</script>";
    }
}

// 3. LOGIKA PENCARIAN & FILTER
$where = "WHERE role != 'admin' AND role != 'admin super'"; // Jangan tampilkan sesama admin
if (isset($_GET['cari'])) {
    $keyword = $_GET['cari'];
    $where .= " AND (nama_lengkap LIKE '%$keyword%' OR username LIKE '%$keyword%')";
}

// Filter berdasarkan Tab Role (Opsional jika ingin sorting lewat URL)
if (isset($_GET['role'])) {
    $filter_role = $_GET['role'];
    $where .= " AND role = '$filter_role'";
}

$query = "SELECT * FROM users $where ORDER BY id_user DESC";
$users = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pengguna - Admin RadenStay</title>
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


        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .badge-role {
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 20px;
        }
    </style>
</head>

<body>

    <div class="container-fluid">
        <div class="row">

            <?php include 'sidebar.php'; ?>

            <div class="col-md-9 col-lg-10 p-4">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold text-white"><i class="fa-solid fa-users me-2"></i> Manajemen User</h3>
                    <a href="../logout.php" class="btn btn-warning btn-sm d-md-none">Logout</a>
                </div>

                <div class="card glass-card border-0 p-4">

                    <div class="row mb-3 g-2">
                        <div class="col-md-6">
                            <div class="btn-group" role="group">
                                <a href="data_pengguna.php" class="btn btn-outline-primary <?= !isset($_GET['role']) ? 'active' : '' ?>">Semua</a>
                                <a href="data_pengguna.php?role=mahasiswa" class="btn btn-outline-primary <?= (isset($_GET['role']) && $_GET['role'] == 'mahasiswa') ? 'active' : '' ?>">Mahasiswa</a>
                                <a href="data_pengguna.php?role=pemilik" class="btn btn-outline-primary <?= (isset($_GET['role']) && $_GET['role'] == 'pemilik') ? 'active' : '' ?>">Owner</a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <form method="GET">
                                <div class="input-group">
                                    <input type="text" name="cari" class="form-control" placeholder="Cari nama atau username..." value="<?= isset($_GET['cari']) ? $_GET['cari'] : '' ?>">
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
                                    <th>Nama Lengkap</th>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>No. HP</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                if (mysqli_num_rows($users) > 0):
                                    while ($u = mysqli_fetch_assoc($users)):
                                ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td class="fw-bold"><?= $u['nama_lengkap'] ?></td>
                                            <td><span class="text-muted">@<?= $u['username'] ?></span></td>
                                            <td>
                                                <?php if ($u['role'] == 'pemilik'): ?>
                                                    <span class="badge bg-warning text-dark badge-role"><i class="fa-solid fa-crown me-1"></i> Owner</span>
                                                <?php else: ?>
                                                    <span class="badge bg-info badge-role"><i class="fa-solid fa-user-graduate me-1"></i> Mhs</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $u['no_hp'] ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-light text-primary border me-1"><i class="fa-solid fa-pen-to-square"></i></button>

                                                <a href="data_pengguna.php?hapus=<?= $u['id_user'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus user ini? Hati-hati, data kost mereka juga akan hilang!')">
                                                    <i class="fa-solid fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php
                                    endwhile;
                                else:
                                    ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted fst-italic">Data pengguna tidak ditemukan.</td>
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