<?php
session_start();
include '../koneksi.php';

// Cek jika bukan pemilik, tendang ke login
if ($_SESSION['role'] !== 'pemilik') {
    header("Location: ../login.php");
    exit;
}

if (isset($_POST['simpan_kost'])) {
    $id_pemilik = $_SESSION['id_user'];
    $nama_kost  = $_POST['nama_kost'];
    $alamat     = $_POST['alamat'];
    $jenis      = $_POST['jenis_kost'];


    // 1. Simpan ke tabel kost
    $query_kost = "INSERT INTO kost (id_pemilik, nama_kost, alamat, jenis_kost) 
                   VALUES ('$id_pemilik', '$nama_kost', '$alamat', '$jenis')";

    if (mysqli_query($conn, $query_kost)) {
        $id_kost_baru = mysqli_insert_id($conn);

        // 2. Simpan Peraturan yang dipilih (Checkbox)
        if (!empty($_POST['peraturan'])) {
            foreach ($_POST['peraturan'] as $id_p) {
                mysqli_query($conn, "INSERT INTO rel_peraturan (id_kost, id_master_peraturan) VALUES ('$id_kost_baru', '$id_p')");
            }
        }

        // 3. Simpan Peraturan Baru (Input Text) jika ada
        if (!empty($_POST['peraturan_baru'])) {
            $p_baru = $_POST['peraturan_baru'];
            // Masukkan ke master dulu
            mysqli_query($conn, "INSERT INTO master_peraturan (nama_peraturan) VALUES ('$p_baru')");
            $id_p_baru = mysqli_insert_id($conn);
            // Hubungkan ke kost ini
            mysqli_query($conn, "INSERT INTO rel_peraturan (id_kost, id_master_peraturan) VALUES ('$id_kost_baru', '$id_p_baru')");
        }

        echo "<script>alert('Kost berhasil didaftarkan! Lanjut isi data kamar.'); window.location='tambah_kamar.php?id_kost=$id_kost_baru';</script>";
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <title>Tambah Kost - Pemilik</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

</head>

<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <div class="col-md-10 content-area">
                <div class="container my-5">
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <div class="card shadow border-0">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">Daftarkan Kost Baru</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Nama Kost</label>
                                                <input type="text" name="nama_kost" class="form-control" placeholder="Contoh: Kost Muslimah UNU" required>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Alamat Lengkap</label>
                                            <textarea name="alamat" class="form-control" rows="3" required></textarea>
                                        </div>

                                        <div class="mb-4">
                                            <label class="form-label">Jenis Kost</label><br>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="jenis_kost" value="Putra" required> Putra
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="jenis_kost" value="Putri"> Putri
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="jenis_kost" value="Campur"> Campur
                                            </div>
                                        </div>

                                        <hr>
                                        <h6>Pilih Peraturan Kost yang Tersedia:</h6>
                                        <div class="row mb-3">
                                            <?php
                                            $res_p = mysqli_query($conn, "SELECT * FROM master_peraturan");
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

                                        <div class="mb-4">
                                            <label class="form-label small text-muted">Aturan lainnya (jika tidak ada di atas):</label>
                                            <input type="text" name="peraturan_baru" class="form-control form-control-sm" placeholder="Ketik aturan baru di sini...">
                                        </div>

                                        <div class="d-grid">
                                            <button type="submit" name="simpan_kost" class="btn btn-success">Simpan & Lanjut Input Kamar</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>