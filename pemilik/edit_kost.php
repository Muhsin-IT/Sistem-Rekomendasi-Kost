<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'pemilik') {
    header("Location: ../login.php");
    exit;
}

// 1. Ambil ID Kost dari URL
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}

$id_kost = $_GET['id'];
$id_user = $_SESSION['id_user'];

// 2. Ambil data kost lama (Pastikan milik pemilik yang sedang login)
$query_kost = mysqli_query($conn, "SELECT * FROM kost WHERE id_kost = '$id_kost' AND id_pemilik = '$id_user'");
$kost = mysqli_fetch_assoc($query_kost);

if (!$kost) {
    echo "<script>alert('Data tidak ditemukan atau Anda tidak memiliki akses!'); window.location='dashboard.php';</script>";
    exit;
}

// 3. Ambil Fasilitas Umum & Peraturan yang sudah dipilih sebelumnya
$fas_aktif = [];
$res_f = mysqli_query($conn, "SELECT id_master_fasilitas FROM rel_fasilitas WHERE id_kost = '$id_kost'");
while ($f = mysqli_fetch_assoc($res_f)) {
    $fas_aktif[] = $f['id_master_fasilitas'];
}

$per_aktif = [];
$res_p = mysqli_query($conn, "SELECT id_master_peraturan FROM rel_peraturan WHERE id_kost = '$id_kost'");
while ($p = mysqli_fetch_assoc($res_p)) {
    $per_aktif[] = $p['id_master_peraturan'];
}

// 4. Proses Update
if (isset($_POST['update_kost'])) {
    $nama   = mysqli_real_escape_string($conn, $_POST['nama_kost']);
    $jenis  = $_POST['jenis_kost'];
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);

    // Update tabel kost
    $update_q = "UPDATE kost SET nama_kost = '$nama', jenis_kost = '$jenis', alamat = '$alamat' WHERE id_kost = '$id_kost'";

    if (mysqli_query($conn, $update_q)) {

        // Update Fasilitas Umum: Hapus lama, masukkan baru
        mysqli_query($conn, "DELETE FROM rel_fasilitas WHERE id_kost = '$id_kost' AND id_kamar IS NULL");
        if (!empty($_POST['fasilitas'])) {
            foreach ($_POST['fasilitas'] as $id_f) {
                mysqli_query($conn, "INSERT INTO rel_fasilitas (id_kost, id_master_fasilitas) VALUES ('$id_kost', '$id_f')");
            }
        }

        // Update Peraturan Kost: Hapus lama, masukkan baru
        mysqli_query($conn, "DELETE FROM rel_peraturan WHERE id_kost = '$id_kost' AND id_kamar IS NULL");
        if (!empty($_POST['peraturan'])) {
            foreach ($_POST['peraturan'] as $id_p) {
                mysqli_query($conn, "INSERT INTO rel_peraturan (id_kost, id_master_peraturan) VALUES ('$id_kost', '$id_p')");
            }
        }

        echo "<script>alert('Data Kost berhasil diperbarui!'); window.location='dashboard.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Edit Kost - <?= $kost['nama_kost']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background: #198754;
            color: white;
            padding: 20px;
            position: fixed;
            width: inherit;
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
            margin-left: 16.666667%;
            padding: 30px;
            background: #f8f9fa;
            min-height: 100vh;
        }
    </style>
</head>

<body>

    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <div class="col-md-10 content-area">
                <div class="card shadow-sm border-0 p-4">
                    <h4 class="mb-4">Edit Informasi Kost</h4>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Nama Kost</label>
                                <input type="text" name="nama_kost" class="form-control" value="<?= $kost['nama_kost']; ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Jenis Kost</label>
                                <select name="jenis_kost" class="form-select">
                                    <option value="Putra" <?= $kost['jenis_kost'] == 'Putra' ? 'selected' : ''; ?>>Putra</option>
                                    <option value="Putri" <?= $kost['jenis_kost'] == 'Putri' ? 'selected' : ''; ?>>Putri</option>
                                    <option value="Campur" <?= $kost['jenis_kost'] == 'Campur' ? 'selected' : ''; ?>>Campur</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Alamat Lengkap</label>
                            <textarea name="alamat" class="form-control" rows="3" required><?= $kost['alamat']; ?></textarea>
                        </div>

                        <hr>
                        <h6 class="fw-bold">Fasilitas Umum (Gedung)</h6>
                        <div class="row mb-3">
                            <?php
                            $res_mf = mysqli_query($conn, "SELECT * FROM master_fasilitas WHERE kategori = 'Umum'");
                            while ($mf = mysqli_fetch_assoc($res_mf)):
                                $checked = in_array($mf['id_master_fasilitas'], $fas_aktif) ? 'checked' : '';
                            ?>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="fasilitas[]" value="<?= $mf['id_master_fasilitas'] ?>" <?= $checked; ?>>
                                        <label class="form-check-label"><?= $mf['nama_fasilitas'] ?></label>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>

                        <hr>
                        <h6 class="fw-bold">Peraturan Kost (Gedung)</h6>
                        <div class="row mb-4">
                            <?php
                            $res_mp = mysqli_query($conn, "SELECT * FROM master_peraturan WHERE kategori = 'Kost'");
                            while ($mp = mysqli_fetch_assoc($res_mp)):
                                $checked = in_array($mp['id_master_peraturan'], $per_aktif) ? 'checked' : '';
                            ?>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="peraturan[]" value="<?= $mp['id_master_peraturan'] ?>" <?= $checked; ?>>
                                        <label class="form-check-label"><?= $mp['nama_peraturan'] ?></label>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" name="update_kost" class="btn btn-success px-4">Simpan Perubahan</button>
                            <a href="dashboard.php" class="btn btn-outline-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>