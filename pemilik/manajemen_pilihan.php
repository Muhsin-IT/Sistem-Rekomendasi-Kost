<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'pemilik') {
    header("Location: ../login.php");
    exit;
}

$id_user_login = $_SESSION['id_user'];

// ==========================================
// LOGIKA PERATURAN (TAMBAH, EDIT, HAPUS)
// ==========================================

// Tambah Massal Peraturan
if (isset($_POST['tambah_peraturan'])) {
    $input = $_POST['nama_peraturan'];
    $kat = $_POST['kategori_p'];
    $data = explode(',', $input);
    foreach ($data as $p) {
        $p_clean = mysqli_real_escape_string($conn, trim($p));
        if (!empty($p_clean)) {
            mysqli_query($conn, "INSERT INTO master_peraturan (nama_peraturan, kategori, id_pemilik) VALUES ('$p_clean', '$kat', '$id_user_login')");
        }
    }
    header("Location: manajemen_pilihan.php?pesan=sukses");
    exit;
}

// Edit Peraturan
if (isset($_POST['update_peraturan'])) {
    $id = $_POST['id_peraturan'];
    $nama = mysqli_real_escape_string($conn, $_POST['nama_peraturan']);
    $kat = $_POST['kategori_p'];
    mysqli_query($conn, "UPDATE master_peraturan SET nama_peraturan = '$nama', kategori = '$kat' WHERE id_master_peraturan = '$id' AND id_pemilik = '$id_user_login'");
    header("Location: manajemen_pilihan.php?pesan=updated");
    exit;
}

// Hapus Peraturan
if (isset($_GET['hapus_p'])) {
    $id = $_GET['hapus_p'];
    mysqli_query($conn, "DELETE FROM master_peraturan WHERE id_master_peraturan = '$id' AND id_pemilik = '$id_user_login'");
    header("Location: manajemen_pilihan.php");
    exit;
}

// ==========================================
// LOGIKA FASILITAS (TAMBAH, EDIT, HAPUS)
// ==========================================

// Tambah Massal Fasilitas
if (isset($_POST['tambah_fasilitaS'])) {
    $input = $_POST['nama_fasilitas'];
    $kat = $_POST['kategori_f'];
    $data = explode(',', $input);
    foreach ($data as $f) {
        $f_clean = mysqli_real_escape_string($conn, trim($f));
        if (!empty($f_clean)) {
            mysqli_query($conn, "INSERT INTO master_fasilitas (nama_fasilitas, kategori, id_pemilik) VALUES ('$f_clean', '$kat', '$id_user_login')");
        }
    }
    header("Location: manajemen_pilihan.php?pesan=sukses");
    exit;
}

// Edit Fasilitas
if (isset($_POST['update_fasilitas'])) {
    $id = $_POST['id_fasilitas'];
    $nama = mysqli_real_escape_string($conn, $_POST['nama_fasilitas']);
    $kat = $_POST['kategori_f'];
    mysqli_query($conn, "UPDATE master_fasilitas SET nama_fasilitas = '$nama', kategori = '$kat' WHERE id_master_fasilitas = '$id' AND id_pemilik = '$id_user_login'");
    header("Location: manajemen_pilihan.php?pesan=updated");
    exit;
}

// Hapus Fasilitas
if (isset($_GET['hapus_f'])) {
    $id = $_GET['hapus_f'];
    mysqli_query($conn, "DELETE FROM master_fasilitas WHERE id_master_fasilitas = '$id' AND id_pemilik = '$id_user_login'");
    header("Location: manajemen_pilihan.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Manajemen Pilihan - Kost UNU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f4f7f6;
        }

        .sidebar {
            min-height: 100vh;
            background: #198754;
            color: white;
            padding: 20px;
            position: fixed;
            width: 16.6%;
        }

        .sidebar a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 5px;
        }

        .content-area {
            margin-left: 17%;
            padding: 30px;
        }

        .card {
            border: none;
            border-radius: 12px;
        }
    </style>
</head>

<body>

    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <div class="col-md-10 content-area">
                <h3 class="fw-bold mb-4 text-success">Pusat Kendali Fasilitas & Peraturan</h3>

                <div class="row mb-5">
                    <div class="col-md-4">
                        <div class="card shadow-sm p-3 border-start border-success border-4">
                            <h6>Tambah Fasilitas</h6>
                            <form method="POST">
                                <textarea name="nama_fasilitas" class="form-control mb-2" placeholder="Contoh: WiFi, AC, TV (pisahkan dengan koma)" rows="3" required></textarea>
                                <select name="kategori_f" class="form-select mb-2">
                                    <option value="Kamar">Kamar</option>
                                    <option value="Umum">Umum</option>
                                    <option value="Parkir">Parkir</option>
                                </select>
                                <button name="tambah_fasilitaS" class="btn btn-success w-100">Tambah</button>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card shadow-sm p-3">
                            <table class="table table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nama Fasilitas</th>
                                        <th>Kategori</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $fs = mysqli_query($conn, "SELECT * FROM master_fasilitas WHERE id_pemilik IS NULL OR id_pemilik = '$id_user_login'");
                                    while ($f = mysqli_fetch_assoc($fs)): ?>
                                        <tr>
                                            <td><?= $f['nama_fasilitas']; ?></td>
                                            <td><span class="badge bg-light text-dark border"><?= $f['kategori']; ?></span></td>
                                            <td><?= ($f['id_pemilik'] == null) ? '<small class="text-muted">Sistem</small>' : '<small class="text-success">Milik Saya</small>'; ?></td>
                                            <td>
                                                <?php if ($f['id_pemilik'] == $id_user_login): ?>
                                                    <button class="btn btn-sm btn-link" data-bs-toggle="modal" data-bs-target="#modalF<?= $f['id_master_fasilitas']; ?>"><i class="bi bi-pencil"></i></button>
                                                    <a href="?hapus_f=<?= $f['id_master_fasilitas']; ?>" class="text-danger" onclick="return confirm('Hapus?')"><i class="bi bi-trash"></i></a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <div class="modal fade" id="modalF<?= $f['id_master_fasilitas']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST">
                                                        <div class="modal-header">
                                                            <h5>Edit Fasilitas</h5>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="id_fasilitas" value="<?= $f['id_master_fasilitas']; ?>">
                                                            <input type="text" name="nama_fasilitas" class="form-control mb-2" value="<?= $f['nama_fasilitas']; ?>">
                                                            <select name="kategori_f" class="form-select">
                                                                <option value="Kamar" <?= $f['kategori'] == 'Kamar' ? 'selected' : ''; ?>>Kamar</option>
                                                                <option value="Umum" <?= $f['kategori'] == 'Umum' ? 'selected' : ''; ?>>Umum</option>
                                                                <option value="Parkir" <?= $f['kategori'] == 'Parkir' ? 'selected' : ''; ?>>Parkir</option>
                                                            </select>
                                                        </div>
                                                        <div class="modal-footer"><button type="submit" name="update_fasilitas" class="btn btn-success">Update</button></div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-4">
                        <div class="card shadow-sm p-3 border-start border-danger border-4">
                            <h6>Tambah Peraturan</h6>
                            <form method="POST">
                                <textarea name="nama_peraturan" class="form-control mb-2" placeholder="contoh :Dilarang Berisik, No Smoking (pisahkan dengan koma)" rows="3" required></textarea>
                                <select name="kategori_p" class="form-select mb-2">
                                    <option value="Kost">Gedung</option>
                                    <option value="Kamar">Kamar</option>
                                </select>
                                <button name="tambah_peraturan" class="btn btn-danger w-100">Tambah</button>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card shadow-sm p-3">
                            <table class="table table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nama Peraturan</th>
                                        <th>Kategori</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $ps = mysqli_query($conn, "SELECT * FROM master_peraturan WHERE id_pemilik IS NULL OR id_pemilik = '$id_user_login'");
                                    while ($p = mysqli_fetch_assoc($ps)): ?>
                                        <tr>
                                            <td><?= $p['nama_peraturan']; ?></td>
                                            <td><span class="badge bg-light text-dark border"><?= $p['kategori']; ?></span></td>
                                            <td><?= ($p['id_pemilik'] == null) ? '<small class="text-muted">Sistem</small>' : '<small class="text-danger">Milik Saya</small>'; ?></td>
                                            <td>
                                                <?php if ($p['id_pemilik'] == $id_user_login): ?>
                                                    <button class="btn btn-sm btn-link" data-bs-toggle="modal" data-bs-target="#modalP<?= $p['id_master_peraturan']; ?>"><i class="bi bi-pencil"></i></button>
                                                    <a href="?hapus_p=<?= $p['id_master_peraturan']; ?>" class="text-danger" onclick="return confirm('Hapus?')"><i class="bi bi-trash"></i></a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <div class="modal fade" id="modalP<?= $p['id_master_peraturan']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST">
                                                        <div class="modal-header">
                                                            <h5>Edit Peraturan</h5>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="id_peraturan" value="<?= $p['id_master_peraturan']; ?>">
                                                            <input type="text" name="nama_peraturan" class="form-control mb-2" value="<?= $p['nama_peraturan']; ?>">
                                                            <select name="kategori_p" class="form-select">
                                                                <option value="Kost" <?= $p['kategori'] == 'Kost' ? 'selected' : ''; ?>>Gedung</option>
                                                                <option value="Kamar" <?= $p['kategori'] == 'Kamar' ? 'selected' : ''; ?>>Kamar</option>
                                                            </select>
                                                        </div>
                                                        <div class="modal-footer"><button type="submit" name="update_peraturan" class="btn btn-danger">Update</button></div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>