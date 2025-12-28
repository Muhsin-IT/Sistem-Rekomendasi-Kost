<?php
session_start();
include '../koneksi.php';

// Cek Login
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'pemilik') {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id_kost'])) {
    header("Location: dashboard.php");
    exit;
}

$id_kost = $_GET['id_kost'];
$id_user_login = $_SESSION['id_user'];

if (isset($_POST['simpan_kamar'])) {
    $nama_tipe = mysqli_real_escape_string($conn, $_POST['nama_tipe']);
    $harga     = $_POST['harga'];
    $stok      = $_POST['stok'];
    $listrik   = $_POST['listrik'];
    $lebar_ruangan = $_POST['p_ruang'] . "x" . $_POST['l_ruang'] . " m";

    // 1. INSERT DATA KAMAR
    $q_kamar = "INSERT INTO kamar (id_kost, nama_tipe_kamar, harga_per_bulan, stok_kamar, lebar_ruangan, sudah_termasuk_listrik) 
                VALUES ('$id_kost', '$nama_tipe', '$harga', '$stok', '$lebar_ruangan', '$listrik')";

    if (mysqli_query($conn, $q_kamar)) {
        $id_kamar_baru = mysqli_insert_id($conn);

        // --- FUNGSI UPLOAD ---
        function uploadKategori($files, $kategori, $is_360_flag, $conn, $id_k_kost, $id_k_kamar)
        {
            if (!empty($files['name'][0])) {
                $jumlah = count($files['name']);
                for ($i = 0; $i < $jumlah; $i++) {
                    $nama = $files['name'][$i];
                    $tmp  = $files['tmp_name'][$i];

                    if ($files['error'][$i] === 0) {
                        $prefix = $is_360_flag ? '360_' : 'img_';
                        $nama_baru = time() . '_' . $i . '_' . $prefix . str_replace(' ', '', $kategori) . '_' . $nama;
                        $target = "../assets/img/galeri/" . $nama_baru;

                        if (move_uploaded_file($tmp, $target)) {
                            $q_gal = "INSERT INTO galeri (id_kost, id_kamar, nama_file, kategori_foto, is_360) 
                                      VALUES ('$id_k_kost', '$id_k_kamar', '$nama_baru', '$kategori', '$is_360_flag')";
                            mysqli_query($conn, $q_gal);
                        }
                    }
                }
            }
        }

        // 2. PROSES UPLOAD (6 KOMBINASI)

        // A. DALAM KAMAR
        if (isset($_FILES['foto_dalam_biasa'])) uploadKategori($_FILES['foto_dalam_biasa'], 'Dalam Kamar', 0, $conn, $id_kost, $id_kamar_baru);
        if (isset($_FILES['foto_dalam_360']))   uploadKategori($_FILES['foto_dalam_360'],   'Dalam Kamar', 1, $conn, $id_kost, $id_kamar_baru);

        // B. KAMAR MANDI
        if (isset($_FILES['foto_mandi_biasa'])) uploadKategori($_FILES['foto_mandi_biasa'], 'Kamar Mandi', 0, $conn, $id_kost, $id_kamar_baru);
        if (isset($_FILES['foto_mandi_360']))   uploadKategori($_FILES['foto_mandi_360'],   'Kamar Mandi', 1, $conn, $id_kost, $id_kamar_baru);

        // C. DEPAN KAMAR
        if (isset($_FILES['foto_depan_biasa'])) uploadKategori($_FILES['foto_depan_biasa'], 'Depan Kamar', 0, $conn, $id_kost, $id_kamar_baru);
        if (isset($_FILES['foto_depan_360']))   uploadKategori($_FILES['foto_depan_360'],   'Depan Kamar', 1, $conn, $id_kost, $id_kamar_baru);


        // 3. SIMPAN FASILITAS
        if (!empty($_POST['fasilitas'])) {
            foreach ($_POST['fasilitas'] as $id_f) {
                mysqli_query($conn, "INSERT INTO rel_fasilitas (id_kamar, id_kost, id_master_fasilitas) VALUES ('$id_kamar_baru', NULL, '$id_f')");
            }
        }

        // 4. SIMPAN PERATURAN
        if (!empty($_POST['peraturan'])) {
            foreach ($_POST['peraturan'] as $id_p) {
                mysqli_query($conn, "INSERT INTO rel_peraturan (id_kamar, id_kost, id_master_peraturan) VALUES ('$id_kamar_baru', NULL, '$id_p')");
            }
        }

        echo "<script>alert('Berhasil menyimpan kamar!'); window.location='dashboard.php';</script>";
    } else {
        echo "<script>alert('Gagal: " . mysqli_error($conn) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <title>Tambah Kamar - Kost UNU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-section {
            border-bottom: 2px solid #f0f2f5;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }

        .section-title {
            font-weight: bold;
            color: #0d6efd;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .bg-custom-light {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            height: 100%;
        }

        .card-header-custom {
            background: linear-gradient(45deg, #0d6efd, #0043a8);
            color: white;
        }

        .label-input-foto {
            font-size: 0.85rem;
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
        }
    </style>
</head>

<body class="bg-light">

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-11">
                <div class="card shadow border-0 rounded-4">
                    <div class="card-header card-header-custom py-3 rounded-top-4">
                        <h5 class="mb-0 fw-bold"><i class="fa-solid fa-plus-circle me-2"></i> Tambah Tipe Kamar Baru</h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" enctype="multipart/form-data">

                            <div class="form-section">
                                <h6 class="section-title"><i class="fa-solid fa-info-circle"></i> Informasi Dasar</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Nama Tipe Kamar</label>
                                        <input type="text" name="nama_tipe" class="form-control" placeholder="Contoh: Kamar VIP A" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Harga Per Bulan (Rp)</label>
                                        <input type="number" name="harga" class="form-control" placeholder="1000000" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Jumlah Stok</label>
                                        <input type="number" name="stok" class="form-control" value="1" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Dimensi (Meter)</label>
                                        <div class="input-group">
                                            <input type="number" name="p_ruang" class="form-control" placeholder="P" step="0.1" required>
                                            <span class="input-group-text">x</span>
                                            <input type="number" name="l_ruang" class="form-control" placeholder="L" step="0.1" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Listrik</label>
                                        <select name="listrik" class="form-select">
                                            <option value="1">Sudah Termasuk</option>
                                            <option value="0">Tidak Termasuk (Token)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h6 class="section-title"><i class="fa-solid fa-images"></i> Galeri Foto Kamar</h6>
                                <div class="alert alert-info py-2 small"><i class="fa-solid fa-circle-info me-1"></i> Anda bisa mengupload <b>Foto Biasa</b> dan <b>Foto 360°</b> secara bersamaan di setiap kategori.</div>

                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <div class="bg-custom-light">
                                            <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">1. Dalam Kamar</h6>

                                            <div class="mb-3">
                                                <span class="label-input-foto"><i class="fa-solid fa-image"></i> Foto Biasa (Wajib)</span>
                                                <input type="file" name="foto_dalam_biasa[]" class="form-control form-control-sm" multiple accept="image/*" required>
                                            </div>

                                            <div>
                                                <span class="label-input-foto text-warning"><i class="fa-solid fa-street-view"></i> Foto 360° (Opsional)</span>
                                                <input type="file" name="foto_dalam_360[]" class="form-control form-control-sm" multiple accept="image/*">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="bg-custom-light">
                                            <h6 class="fw-bold text-info border-bottom pb-2 mb-3">2. Kamar Mandi</h6>

                                            <div class="mb-3">
                                                <span class="label-input-foto"><i class="fa-solid fa-image"></i> Foto Biasa</span>
                                                <input type="file" name="foto_mandi_biasa[]" class="form-control form-control-sm" multiple accept="image/*">
                                            </div>

                                            <div>
                                                <span class="label-input-foto text-warning"><i class="fa-solid fa-street-view"></i> Foto 360° (Opsional)</span>
                                                <input type="file" name="foto_mandi_360[]" class="form-control form-control-sm" multiple accept="image/*">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="bg-custom-light">
                                            <h6 class="fw-bold text-success border-bottom pb-2 mb-3">3. Depan Kamar</h6>

                                            <div class="mb-3">
                                                <span class="label-input-foto"><i class="fa-solid fa-image"></i> Foto Biasa</span>
                                                <input type="file" name="foto_depan_biasa[]" class="form-control form-control-sm" multiple accept="image/*">
                                            </div>

                                            <div>
                                                <span class="label-input-foto text-warning"><i class="fa-solid fa-street-view"></i> Foto 360° (Opsional)</span>
                                                <input type="file" name="foto_depan_360[]" class="form-control form-control-sm" multiple accept="image/*">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h6 class="section-title"><i class="fa-solid fa-list-check"></i> Fasilitas Kamar</h6>
                                <div class="row g-3">

                                    <div class="col-md-4">
                                        <div class="card h-100 border-primary-subtle">
                                            <div class="card-header bg-primary-subtle fw-bold small">Area Kamar</div>
                                            <div class="card-body">
                                                <?php
                                                $res = mysqli_query($conn, "SELECT * FROM master_fasilitas WHERE (id_pemilik IS NULL OR id_pemilik = '$id_user_login') AND kategori = 'Kamar'");
                                                while ($f = mysqli_fetch_assoc($res)): ?>
                                                    <div class="form-check mb-1">
                                                        <input class="form-check-input" type="checkbox" name="fasilitas[]" value="<?= $f['id_master_fasilitas'] ?>" id="f_<?= $f['id_master_fasilitas'] ?>">
                                                        <label class="form-check-label small" for="f_<?= $f['id_master_fasilitas'] ?>"><?= $f['nama_fasilitas'] ?></label>
                                                    </div>
                                                <?php endwhile; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="card h-100 border-info-subtle">
                                            <div class="card-header bg-info-subtle fw-bold small">Area Kamar Mandi</div>
                                            <div class="card-body">
                                                <?php
                                                $res = mysqli_query($conn, "SELECT * FROM master_fasilitas WHERE (id_pemilik IS NULL OR id_pemilik = '$id_user_login') AND kategori = 'Kamar Mandi'");
                                                while ($f = mysqli_fetch_assoc($res)): ?>
                                                    <div class="form-check mb-1">
                                                        <input class="form-check-input" type="checkbox" name="fasilitas[]" value="<?= $f['id_master_fasilitas'] ?>" id="f_<?= $f['id_master_fasilitas'] ?>">
                                                        <label class="form-check-label small" for="f_<?= $f['id_master_fasilitas'] ?>"><?= $f['nama_fasilitas'] ?></label>
                                                    </div>
                                                <?php endwhile; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="card h-100 border-warning-subtle">
                                            <div class="card-header bg-warning-subtle fw-bold small">Area Parkir</div>
                                            <div class="card-body">
                                                <?php
                                                $res = mysqli_query($conn, "SELECT * FROM master_fasilitas WHERE (id_pemilik IS NULL OR id_pemilik = '$id_user_login') AND kategori = 'Parkir'");
                                                while ($f = mysqli_fetch_assoc($res)): ?>
                                                    <div class="form-check mb-1">
                                                        <input class="form-check-input" type="checkbox" name="fasilitas[]" value="<?= $f['id_master_fasilitas'] ?>" id="f_<?= $f['id_master_fasilitas'] ?>">
                                                        <label class="form-check-label small" for="f_<?= $f['id_master_fasilitas'] ?>"><?= $f['nama_fasilitas'] ?></label>
                                                    </div>
                                                <?php endwhile; ?>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <div class="form-section border-0">
                                <h6 class="section-title text-danger"><i class="fa-solid fa-triangle-exclamation"></i> Peraturan Khusus</h6>
                                <div class="row">
                                    <?php
                                    $res = mysqli_query($conn, "SELECT * FROM master_peraturan WHERE (id_pemilik IS NULL OR id_pemilik = '$id_user_login') AND kategori = 'Kamar'");
                                    while ($p = mysqli_fetch_assoc($res)): ?>
                                        <div class="col-md-4 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="peraturan[]" value="<?= $p['id_master_peraturan'] ?>" id="p_<?= $p['id_master_peraturan'] ?>">
                                                <label class="form-check-label small" for="p_<?= $p['id_master_peraturan'] ?>"><?= $p['nama_peraturan'] ?></label>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>

                            <div class="d-flex gap-2 pt-3">
                                <button type="submit" name="simpan_kamar" class="btn btn-primary px-5 py-2 fw-bold rounded-pill">
                                    <i class="fa-solid fa-save me-2"></i> Simpan Data Kamar
                                </button>
                                <a href="dashboard.php" class="btn btn-outline-secondary px-4 py-2 fw-bold rounded-pill">Batal</a>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>

</html>