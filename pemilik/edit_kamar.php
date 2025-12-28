<?php
session_start();
include '../koneksi.php';

// Cek Login Pemilik
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'pemilik') {
    header("Location: ../login");
    exit;
}

// --- PERBAIKAN DI SINI ---
// Cek apakah URL membawa 'id' (sesuai link Anda) ATAU 'id_kamar'
if (isset($_GET['id'])) {
    $id_kamar = $_GET['id'];
} elseif (isset($_GET['id_kamar'])) {
    $id_kamar = $_GET['id_kamar'];
} else {
    // Jika tidak ada ID sama sekali, baru mental ke dashboard
    header("Location: dashboard");
    exit;
}

$id_user_login = $_SESSION['id_user'];

// 1. AMBIL DATA KAMAR
$q_kamar = mysqli_query($conn, "SELECT * FROM kamar WHERE id_kamar = '$id_kamar'");
$kamar   = mysqli_fetch_assoc($q_kamar);

if (!$kamar) {
    echo "<script>alert('Data kamar tidak ditemukan!'); window.location='dashboard';</script>";
    exit;
}

// Pecah ukuran ruangan (Misal "3x4 m" menjadi P=3, L=4)
$dimensi = explode("x", str_replace(" m", "", $kamar['lebar_ruangan']));
$p_lama = isset($dimensi[0]) ? $dimensi[0] : '';
$l_lama = isset($dimensi[1]) ? $dimensi[1] : '';

// 2. AMBIL DATA GALERI LAMA (Dikelompokkan)
$q_gal = mysqli_query($conn, "SELECT * FROM galeri WHERE id_kamar = '$id_kamar'");
$galeri_lama = [];
while ($row = mysqli_fetch_assoc($q_gal)) {
    // Kunci array: [Kategori][Is360][]
    $galeri_lama[$row['kategori_foto']][$row['is_360']][] = $row;
}

// 3. AMBIL FASILITAS & PERATURAN AKTIF
$fas_aktif = [];
$q_fa = mysqli_query($conn, "SELECT id_master_fasilitas FROM rel_fasilitas WHERE id_kamar = '$id_kamar'");
while ($row = mysqli_fetch_assoc($q_fa)) $fas_aktif[] = $row['id_master_fasilitas'];

$per_aktif = [];
$q_pa = mysqli_query($conn, "SELECT id_master_peraturan FROM rel_peraturan WHERE id_kamar = '$id_kamar'");
while ($row = mysqli_fetch_assoc($q_pa)) $per_aktif[] = $row['id_master_peraturan'];


// ==========================================
// PROSES SIMPAN PERUBAHAN
// ==========================================
if (isset($_POST['update_kamar'])) {
    $nama_tipe = mysqli_real_escape_string($conn, $_POST['nama_tipe']);
    $harga     = $_POST['harga'];
    $stok      = $_POST['stok'];
    $listrik   = $_POST['listrik'];
    $lebar_ruangan = $_POST['p_ruang'] . "x" . $_POST['l_ruang'] . " m";

    // A. UPDATE DATA UTAMA
    $q_update = "UPDATE kamar SET 
                 nama_tipe_kamar = '$nama_tipe',
                 harga_per_bulan = '$harga',
                 stok_kamar = '$stok',
                 lebar_ruangan = '$lebar_ruangan',
                 sudah_termasuk_listrik = '$listrik'
                 WHERE id_kamar = '$id_kamar'";

    mysqli_query($conn, $q_update);

    // B. HAPUS FOTO YANG DICENTANG
    if (isset($_POST['hapus_foto'])) {
        foreach ($_POST['hapus_foto'] as $id_galeri_hapus) {
            // Ambil nama file dulu untuk dihapus dari folder
            $q_file = mysqli_query($conn, "SELECT nama_file FROM galeri WHERE id_galeri = '$id_galeri_hapus'");
            $data_file = mysqli_fetch_assoc($q_file);

            if ($data_file) {
                $path = "../assets/img/galeri/" . $data_file['nama_file'];
                if (file_exists($path)) {
                    unlink($path); // Hapus file fisik
                }
                // Hapus dari database
                mysqli_query($conn, "DELETE FROM galeri WHERE id_galeri = '$id_galeri_hapus'");
            }
        }
    }

    // C. UPLOAD FOTO BARU (Sama seperti Tambah)
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

    // Panggil fungsi upload untuk 6 kategori (sama seperti tambah_kamar)
    $id_kost = $kamar['id_kost']; // Ambil ID kost dari data kamar
    if (isset($_FILES['foto_dalam_biasa'])) uploadKategori($_FILES['foto_dalam_biasa'], 'Dalam Kamar', 0, $conn, $id_kost, $id_kamar);
    if (isset($_FILES['foto_dalam_360']))   uploadKategori($_FILES['foto_dalam_360'],   'Dalam Kamar', 1, $conn, $id_kost, $id_kamar);
    if (isset($_FILES['foto_mandi_biasa'])) uploadKategori($_FILES['foto_mandi_biasa'], 'Kamar Mandi', 0, $conn, $id_kost, $id_kamar);
    if (isset($_FILES['foto_mandi_360']))   uploadKategori($_FILES['foto_mandi_360'],   'Kamar Mandi', 1, $conn, $id_kost, $id_kamar);
    if (isset($_FILES['foto_depan_biasa'])) uploadKategori($_FILES['foto_depan_biasa'], 'Depan Kamar', 0, $conn, $id_kost, $id_kamar);
    if (isset($_FILES['foto_depan_360']))   uploadKategori($_FILES['foto_depan_360'],   'Depan Kamar', 1, $conn, $id_kost, $id_kamar);

    // D. UPDATE FASILITAS (Hapus Semua -> Insert Baru)
    mysqli_query($conn, "DELETE FROM rel_fasilitas WHERE id_kamar = '$id_kamar'");
    if (!empty($_POST['fasilitas'])) {
        foreach ($_POST['fasilitas'] as $id_f) {
            mysqli_query($conn, "INSERT INTO rel_fasilitas (id_kamar, id_kost, id_master_fasilitas) VALUES ('$id_kamar', NULL, '$id_f')");
        }
    }

    // E. UPDATE PERATURAN (Hapus Semua -> Insert Baru)
    mysqli_query($conn, "DELETE FROM rel_peraturan WHERE id_kamar = '$id_kamar'");
    if (!empty($_POST['peraturan'])) {
        foreach ($_POST['peraturan'] as $id_p) {
            mysqli_query($conn, "INSERT INTO rel_peraturan (id_kamar, id_kost, id_master_peraturan) VALUES ('$id_kamar', NULL, '$id_p')");
        }
    }

    $id_kost_redirect = $kamar['id_kost'];

    echo "<script> alert('Perubahan berhasil disimpan!'); window.location='kelola_kamar.php?id=$id_kost_redirect';</script>";
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <title>Edit Kamar - Kost UNU</title>
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
            color: #ffc107;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .bg-custom-light {
            background-color: #fff9e6;
            border: 1px solid #ffeeba;
            border-radius: 8px;
            padding: 15px;
            height: 100%;
        }

        /* Nuansa Kuning Edit */
        .card-header-custom {
            background: linear-gradient(45deg, #ffc107, #fd7e14);
            color: white;
        }

        .label-input-foto {
            font-size: 0.85rem;
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
        }

        /* Style untuk preview foto lama */
        .foto-item {
            position: relative;
            display: inline-block;
            margin: 2px;
            border: 1px solid #ddd;
        }

        .foto-item img {
            height: 60px;
            width: 60px;
            object-fit: cover;
        }

        .foto-del-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255, 0, 0, 0.8);
            color: white;
            font-size: 10px;
            text-align: center;
            cursor: pointer;
        }

        .foto-check-input {
            position: absolute;
            top: 0;
            right: 0;
            z-index: 10;
            cursor: pointer;
        }
    </style>
</head>

<body class="bg-light">

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-11">
                <div class="card shadow border-0 rounded-4">
                    <div class="card-header card-header-custom py-3 rounded-top-4">
                        <h5 class="mb-0 fw-bold"><i class="fa-solid fa-pen-to-square me-2"></i> Edit Data Kamar</h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" enctype="multipart/form-data">

                            <div class="form-section">
                                <h6 class="section-title text-dark"><i class="fa-solid fa-info-circle text-warning"></i> Informasi Dasar</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Nama Tipe Kamar</label>
                                        <input type="text" name="nama_tipe" class="form-control" value="<?= $kamar['nama_tipe_kamar'] ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Harga Per Bulan (Rp)</label>
                                        <input type="number" name="harga" class="form-control" value="<?= $kamar['harga_per_bulan'] ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Jumlah Stok</label>
                                        <input type="number" name="stok" class="form-control" value="<?= $kamar['stok_kamar'] ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Dimensi (Meter)</label>
                                        <div class="input-group">
                                            <input type="number" name="p_ruang" class="form-control" value="<?= $p_lama ?>" step="0.1" required>
                                            <span class="input-group-text">x</span>
                                            <input type="number" name="l_ruang" class="form-control" value="<?= $l_lama ?>" step="0.1" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Listrik</label>
                                        <select name="listrik" class="form-select">
                                            <option value="1" <?= $kamar['sudah_termasuk_listrik'] == 1 ? 'selected' : '' ?>>Sudah Termasuk</option>
                                            <option value="0" <?= $kamar['sudah_termasuk_listrik'] == 0 ? 'selected' : '' ?>>Tidak Termasuk (Token)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h6 class="section-title text-dark"><i class="fa-solid fa-images text-warning"></i> Galeri Foto Kamar</h6>
                                <div class="alert alert-warning py-2 small">
                                    <i class="fa-solid fa-triangle-exclamation me-1"></i> <b>Cara Hapus Foto:</b> Centang kotak merah pada foto yang ingin dihapus, lalu klik tombol Simpan di bawah.
                                </div>

                                <div class="row g-3">
                                    <?php
                                    // Array Kategori untuk Looping Tampilan (Supaya kode rapi)
                                    $cats = [
                                        'Dalam Kamar' => ['label' => '1. Dalam Kamar', 'color' => 'text-primary', 'input_biasa' => 'foto_dalam_biasa', 'input_360' => 'foto_dalam_360'],
                                        'Kamar Mandi' => ['label' => '2. Kamar Mandi', 'color' => 'text-info', 'input_biasa' => 'foto_mandi_biasa', 'input_360' => 'foto_mandi_360'],
                                        'Depan Kamar' => ['label' => '3. Depan Kamar', 'color' => 'text-success', 'input_biasa' => 'foto_depan_biasa', 'input_360' => 'foto_depan_360']
                                    ];

                                    foreach ($cats as $key => $val):
                                    ?>
                                        <div class="col-md-4">
                                            <div class="bg-custom-light">
                                                <h6 class="fw-bold <?= $val['color'] ?> border-bottom pb-2 mb-3"><?= $val['label'] ?></h6>

                                                <div class="mb-3">
                                                    <span class="label-input-foto"><i class="fa-solid fa-image"></i> Foto Biasa</span>
                                                    <div class="mb-2">
                                                        <?php if (isset($galeri_lama[$key][0])): ?>
                                                            <?php foreach ($galeri_lama[$key][0] as $g): ?>
                                                                <div class="foto-item" title="Centang untuk hapus">
                                                                    <img src="../assets/img/galeri/<?= $g['nama_file'] ?>">
                                                                    <input type="checkbox" name="hapus_foto[]" value="<?= $g['id_galeri'] ?>" class="foto-check-input form-check-input bg-danger border-white">
                                                                </div>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <small class="text-muted d-block fst-italic">Belum ada foto.</small>
                                                        <?php endif; ?>
                                                    </div>
                                                    <input type="file" name="<?= $val['input_biasa'] ?>[]" class="form-control form-control-sm" multiple accept="image/*">
                                                </div>

                                                <div>
                                                    <span class="label-input-foto text-warning"><i class="fa-solid fa-street-view"></i> Foto 360°</span>
                                                    <div class="mb-2">
                                                        <?php if (isset($galeri_lama[$key][1])): ?>
                                                            <?php foreach ($galeri_lama[$key][1] as $g): ?>
                                                                <div class="foto-item" title="Centang untuk hapus">
                                                                    <img src="../assets/img/galeri/<?= $g['nama_file'] ?>" style="filter: brightness(0.7);">
                                                                    <i class="fa-solid fa-street-view text-white position-absolute top-50 start-50 translate-middle"></i>
                                                                    <input type="checkbox" name="hapus_foto[]" value="<?= $g['id_galeri'] ?>" class="foto-check-input form-check-input bg-danger border-white">
                                                                </div>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <small class="text-muted d-block fst-italic">Belum ada foto.</small>
                                                        <?php endif; ?>
                                                    </div>
                                                    <input type="file" name="<?= $val['input_360'] ?>[]" class="form-control form-control-sm" multiple accept="image/*">
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="form-section">
                                <h6 class="section-title text-dark"><i class="fa-solid fa-list-check text-warning"></i> Fasilitas Kamar</h6>
                                <div class="row g-3">
                                    <?php
                                    $fas_cats = ['Kamar' => 'bg-primary-subtle', 'Kamar Mandi' => 'bg-info-subtle', 'Parkir' => 'bg-warning-subtle'];
                                    foreach ($fas_cats as $cat => $bg):
                                    ?>
                                        <div class="col-md-4">
                                            <div class="card h-100 border-0 shadow-sm">
                                                <div class="card-header <?= $bg ?> fw-bold small"><?= $cat ?></div>
                                                <div class="card-body">
                                                    <?php
                                                    $res = mysqli_query($conn, "SELECT * FROM master_fasilitas WHERE (id_pemilik IS NULL OR id_pemilik = '$id_user_login') AND kategori = '$cat'");
                                                    while ($f = mysqli_fetch_assoc($res)):
                                                        $checked = in_array($f['id_master_fasilitas'], $fas_aktif) ? 'checked' : '';
                                                    ?>
                                                        <div class="form-check mb-1">
                                                            <input class="form-check-input" type="checkbox" name="fasilitas[]" value="<?= $f['id_master_fasilitas'] ?>" id="f_<?= $f['id_master_fasilitas'] ?>" <?= $checked ?>>
                                                            <label class="form-check-label small" for="f_<?= $f['id_master_fasilitas'] ?>"><?= $f['nama_fasilitas'] ?></label>
                                                        </div>
                                                    <?php endwhile; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="form-section border-0">
                                <h6 class="section-title text-danger"><i class="fa-solid fa-triangle-exclamation"></i> Peraturan Khusus</h6>
                                <div class="row">
                                    <?php
                                    $res = mysqli_query($conn, "SELECT * FROM master_peraturan WHERE (id_pemilik IS NULL OR id_pemilik = '$id_user_login') AND kategori = 'Kamar'");
                                    while ($p = mysqli_fetch_assoc($res)):
                                        $checked = in_array($p['id_master_peraturan'], $per_aktif) ? 'checked' : '';
                                    ?>
                                        <div class="col-md-4 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="peraturan[]" value="<?= $p['id_master_peraturan'] ?>" id="p_<?= $p['id_master_peraturan'] ?>" <?= $checked ?>>
                                                <label class="form-check-label small" for="p_<?= $p['id_master_peraturan'] ?>"><?= $p['nama_peraturan'] ?></label>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>

                            <div class="d-flex gap-2 pt-3">
                                <button type="submit" name="update_kamar" class="btn btn-warning px-5 py-2 fw-bold rounded-pill text-dark shadow">
                                    <i class="fa-solid fa-save me-2"></i> Simpan Perubahan
                                </button>
                                <a href="dashboard" class="btn btn-outline-secondary px-4 py-2 fw-bold rounded-pill">Batal</a>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>

</html>