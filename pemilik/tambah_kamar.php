<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'pemilik') {
    header("Location: ../login.php");
    exit;
}

// Pastikan id_kost ada di URL
if (!isset($_GET['id_kost'])) {
    header("Location: dashboard.php");
    exit;
}

$id_kost = $_GET['id_kost'];

if (isset($_POST['simpan_kamar'])) {
    // Ambil data dari form
    $nama_tipe = mysqli_real_escape_string($conn, $_POST['nama_tipe']);
    $harga     = $_POST['harga'];
    $stok      = $_POST['stok'];
    $lebar     = mysqli_real_escape_string($conn, $_POST['lebar_ruangan']);
    $listrik   = $_POST['listrik'];

    // 1. Simpan ke tabel kamar (Sesuai struktur SQL kamu)
    $q_kamar = "INSERT INTO kamar (id_kost, nama_tipe_kamar, harga_per_bulan, stok_kamar, lebar_ruangan, sudah_termasuk_listrik) 
                VALUES ('$id_kost', '$nama_tipe', '$harga', '$stok', '$lebar', '$listrik')";

    if (mysqli_query($conn, $q_kamar)) {
        $id_kamar_baru = mysqli_insert_id($conn);

        // 2. Simpan Fasilitas Kamar (rel_fasilitas)
        if (!empty($_POST['fasilitas'])) {
            foreach ($_POST['fasilitas'] as $id_f) {
                // Sesuai SQL: id_kamar, id_kost (NULL), id_master_fasilitas
                mysqli_query($conn, "INSERT INTO rel_fasilitas (id_kamar, id_kost, id_master_fasilitas) 
                                     VALUES ('$id_kamar_baru', NULL, '$id_f')");
            }
        }

        echo "<script>alert('Berhasil menambah kamar!'); window.location='dashboard.php';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <title>Tambah Kamar - Kost UNU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container my-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Input Tipe Kamar</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label>Nama Tipe Kamar</label>
                        <input type="text" name="nama_tipe" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label>Harga Per Bulan</label>
                            <input type="number" name="harga" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Stok Kamar</label>
                            <input type="number" name="stok" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Lebar Ruangan (ex: 3x4)</label>
                            <input type="text" name="lebar_ruangan" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Termasuk Listrik?</label>
                        <select name="listrik" class="form-select">
                            <option value="1">Ya</option>
                            <option value="0">Tidak</option>
                        </select>
                    </div>

                    <h6 class="fw-bold text-danger">Peraturan Khusus Kamar Ini</h6>
                    <div class="row mb-4">
                        <?php
                        // Hanya ambil yang kategori 'Kamar'
                        $res_p = mysqli_query($conn, "SELECT * FROM master_peraturan WHERE kategori = 'Kamar'");
                        while ($p = mysqli_fetch_assoc($res_p)):
                        ?>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="peraturan[]" value="<?= $p['id_master_peraturan'] ?>">
                                    <label class="form-check-label small"><?= $p['nama_peraturan'] ?></label>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <h6>Fasilitas Kamar:</h6>
                    <div class="row mb-3">
                        <?php
                        $f_res = mysqli_query($conn, "SELECT * FROM master_fasilitas WHERE kategori IN ('Kamar', 'Kamar Mandi')");
                        while ($f = mysqli_fetch_assoc($f_res)):
                        ?>
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="fasilitas[]" value="<?= $f['id_master_fasilitas'] ?>">
                                    <label class="form-check-label"><?= $f['nama_fasilitas'] ?></label>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <button type="submit" name="simpan_kamar" class="btn btn-primary">Simpan Kamar</button>
                </form>
            </div>
        </div>
    </div>
</body>

</html>