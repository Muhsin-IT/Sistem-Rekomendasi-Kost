<?php
session_start();
include '../koneksi.php';

// 1. CEK KEAMANAN
if (!isset($_SESSION['login']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'admin super')) {
    header("Location: ../login.php");
    exit;
}

// ==========================================
// A. LOGIKA FASILITAS (Tambah, Edit, Hapus)
// ==========================================

// 1. TAMBAH FASILITAS (MASSAL)
if (isset($_POST['tambah_fasilitas'])) {
    $raw_input = $_POST['nama_fasilitas'];
    $kategori  = $_POST['kategori_fasilitas'];

    $list_item = explode(',', $raw_input); // Pecah berdasarkan koma

    foreach ($list_item as $item) {
        $nama_bersih = trim($item);
        if (!empty($nama_bersih)) {
            $nama_sql = mysqli_real_escape_string($conn, $nama_bersih);
            // Simpan (id_pemilik NULL artinya punya Admin)
            mysqli_query($conn, "INSERT INTO master_fasilitas (nama_fasilitas, kategori, id_pemilik) VALUES ('$nama_sql', '$kategori', NULL)");
        }
    }
    echo "<script>window.location='master_data.php';</script>";
}

// 2. EDIT FASILITAS (SATUAN)
if (isset($_POST['edit_fasilitas'])) {
    $id_edit   = $_POST['id_edit'];
    $nama_edit = mysqli_real_escape_string($conn, $_POST['nama_edit']);
    $kat_edit  = $_POST['kategori_edit'];

    $q = "UPDATE master_fasilitas SET nama_fasilitas = '$nama_edit', kategori = '$kat_edit' 
          WHERE id_master_fasilitas = '$id_edit' AND id_pemilik IS NULL";
    mysqli_query($conn, $q);
    echo "<script>window.location='master_data.php';</script>";
}

// 3. HAPUS FASILITAS (AMAN DARI ERROR FOREIGN KEY)
if (isset($_GET['hapus_fasilitas'])) {
    $id = $_GET['hapus_fasilitas'];

    // Hapus relasinya dulu di tabel rel_fasilitas
    mysqli_query($conn, "DELETE FROM rel_fasilitas WHERE id_master_fasilitas = '$id'");

    // Baru hapus master datanya
    mysqli_query($conn, "DELETE FROM master_fasilitas WHERE id_master_fasilitas = '$id' AND id_pemilik IS NULL");

    echo "<script>window.location='master_data.php';</script>";
}


// ==========================================
// B. LOGIKA PERATURAN (Tambah, Edit, Hapus)
// ==========================================

// 1. TAMBAH PERATURAN (MASSAL)
if (isset($_POST['tambah_peraturan'])) {
    $raw_input = $_POST['nama_peraturan'];
    $kategori  = $_POST['kategori_peraturan'];

    $list_item = explode(',', $raw_input);

    foreach ($list_item as $item) {
        $nama_bersih = trim($item);
        if (!empty($nama_bersih)) {
            $nama_sql = mysqli_real_escape_string($conn, $nama_bersih);
            mysqli_query($conn, "INSERT INTO master_peraturan (nama_peraturan, kategori, id_pemilik) VALUES ('$nama_sql', '$kategori', NULL)");
        }
    }
    echo "<script>window.location='master_data.php';</script>";
}

// 2. EDIT PERATURAN (SATUAN)
if (isset($_POST['edit_peraturan'])) {
    $id_edit   = $_POST['id_edit'];
    $nama_edit = mysqli_real_escape_string($conn, $_POST['nama_edit']);
    $kat_edit  = $_POST['kategori_edit'];

    $q = "UPDATE master_peraturan SET nama_peraturan = '$nama_edit', kategori = '$kat_edit' 
          WHERE id_master_peraturan = '$id_edit' AND id_pemilik IS NULL";
    mysqli_query($conn, $q);
    echo "<script>window.location='master_data.php';</script>";
}

// 3. HAPUS PERATURAN (AMAN)
if (isset($_GET['hapus_peraturan'])) {
    $id = $_GET['hapus_peraturan'];

    // Hapus relasinya dulu
    mysqli_query($conn, "DELETE FROM rel_peraturan WHERE id_master_peraturan = '$id'");

    // Baru hapus master datanya
    mysqli_query($conn, "DELETE FROM master_peraturan WHERE id_master_peraturan = '$id' AND id_pemilik IS NULL");

    echo "<script>window.location='master_data.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Data - Admin RadenStay</title>
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
            height: 100%;
        }

        .table-sm td,
        .table-sm th {
            font-size: 0.9rem;
            vertical-align: middle;
        }

        .badge-cat {
            font-size: 0.75rem;
            width: 100px;
            display: inline-block;
            text-align: center;
        }

        .form-hint {
            font-size: 0.75rem;
            color: #6c757d;
            font-style: italic;
        }
    </style>
</head>

<body>

    <div class="container-fluid">
        <div class="row">

            <?php include 'sidebar.php'; ?>

            <div class="col-md-9 col-lg-10 p-4">

                <h3 class="fw-bold text-white mb-4"><i class="fa-solid fa-database me-2"></i> Master Data Default</h3>

                <div class="row g-4">

                    <div class="col-md-6">
                        <div class="card glass-card border-0 p-4">
                            <h5 class="fw-bold text-primary mb-3"><i class="fa-solid fa-couch me-2"></i> Master Fasilitas</h5>

                            <form method="POST" class="mb-4 p-3 bg-light rounded border">
                                <div class="row g-2">
                                    <div class="col-12 mb-1">
                                        <label class="fw-bold small">Tambah Fasilitas <span class="text-muted fw-normal">(Pisahkan koma untuk banyak)</span></label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" name="nama_fasilitas" class="form-control form-control-sm" placeholder="Contoh: WiFi, AC, TV" required>
                                    </div>
                                    <div class="col-md-3">
                                        <select name="kategori_fasilitas" class="form-select form-select-sm">
                                            <option value="Kamar">Kamar</option>
                                            <option value="Kamar Mandi">Kamar Mandi</option>
                                            <option value="Umum">Umum</option>
                                            <option value="Parkir">Parkir</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" name="tambah_fasilitas" class="btn btn-primary btn-sm w-100"><i class="fa-solid fa-plus"></i></button>
                                    </div>
                                </div>
                            </form>

                            <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                                <table class="table table-hover table-sm">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th>Nama Fasilitas</th>
                                            <th>Kategori</th>
                                            <th class="text-end">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $qf = mysqli_query($conn, "SELECT * FROM master_fasilitas WHERE id_pemilik IS NULL ORDER BY kategori ASC, nama_fasilitas ASC");
                                        while ($f = mysqli_fetch_assoc($qf)):
                                            $bg = 'bg-secondary';
                                            if ($f['kategori'] == 'Kamar') $bg = 'bg-primary';
                                            if ($f['kategori'] == 'Kamar Mandi') $bg = 'bg-info text-dark';
                                            if ($f['kategori'] == 'Umum') $bg = 'bg-success';
                                            if ($f['kategori'] == 'Parkir') $bg = 'bg-warning text-dark';
                                        ?>
                                            <tr>
                                                <td class="fw-bold"><?= $f['nama_fasilitas'] ?></td>
                                                <td><span class="badge rounded-pill badge-cat <?= $bg ?>"><?= $f['kategori'] ?></span></td>
                                                <td class="text-end">
                                                    <button type="button" class="btn btn-sm text-primary p-0 me-2 border-0 bg-transparent" data-bs-toggle="modal" data-bs-target="#editFas<?= $f['id_master_fasilitas'] ?>">
                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                    </button>

                                                    <a href="master_data.php?hapus_fasilitas=<?= $f['id_master_fasilitas'] ?>" class="text-danger" onclick="return confirm('Hapus fasilitas default ini?')"><i class="fa-solid fa-trash"></i></a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card glass-card border-0 p-4">
                            <h5 class="fw-bold text-danger mb-3"><i class="fa-solid fa-gavel me-2"></i> Master Peraturan</h5>

                            <form method="POST" class="mb-4 p-3 bg-light rounded border">
                                <div class="row g-2">
                                    <div class="col-12 mb-1">
                                        <label class="fw-bold small">Tambah Peraturan <span class="text-muted fw-normal">(Pisahkan koma)</span></label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" name="nama_peraturan" class="form-control form-control-sm" placeholder="Contoh: No Smoking, Max 2 Orang" required>
                                    </div>
                                    <div class="col-md-3">
                                        <select name="kategori_peraturan" class="form-select form-select-sm">
                                            <option value="Kamar">Kamar</option>
                                            <option value="Kost">Umum/Kost</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" name="tambah_peraturan" class="btn btn-danger btn-sm w-100"><i class="fa-solid fa-plus"></i></button>
                                    </div>
                                </div>
                            </form>

                            <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                                <table class="table table-hover table-sm">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th>Isi Peraturan</th>
                                            <th>Kategori</th>
                                            <th class="text-end">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $qp = mysqli_query($conn, "SELECT * FROM master_peraturan WHERE id_pemilik IS NULL ORDER BY kategori ASC, nama_peraturan ASC");
                                        while ($p = mysqli_fetch_assoc($qp)):
                                            $bg = ($p['kategori'] == 'Kamar') ? 'bg-primary' : 'bg-dark';
                                        ?>
                                            <tr>
                                                <td class="fw-bold"><?= $p['nama_peraturan'] ?></td>
                                                <td><span class="badge rounded-pill badge-cat <?= $bg ?>"><?= $p['kategori'] ?></span></td>
                                                <td class="text-end">
                                                    <button type="button" class="btn btn-sm text-primary p-0 me-2 border-0 bg-transparent" data-bs-toggle="modal" data-bs-target="#editPer<?= $p['id_master_peraturan'] ?>">
                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                    </button>

                                                    <a href="master_data.php?hapus_peraturan=<?= $p['id_master_peraturan'] ?>" class="text-danger" onclick="return confirm('Hapus peraturan default ini?')"><i class="fa-solid fa-trash"></i></a>
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
        </div>
    </div>

    <?php
    // Reset pointer query fasilitas agar bisa diloop ulang
    if (mysqli_num_rows($qf) > 0) {
        mysqli_data_seek($qf, 0);
        while ($f = mysqli_fetch_assoc($qf)):
    ?>
            <div class="modal fade" id="editFas<?= $f['id_master_fasilitas'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-sm modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header py-2">
                            <h6 class="modal-title fw-bold">Edit Fasilitas</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST">
                            <div class="modal-body">
                                <input type="hidden" name="id_edit" value="<?= $f['id_master_fasilitas'] ?>">
                                <div class="mb-2">
                                    <label class="small fw-bold">Nama Fasilitas</label>
                                    <input type="text" name="nama_edit" class="form-control form-control-sm" value="<?= $f['nama_fasilitas'] ?>" required>
                                </div>
                                <div class="mb-2">
                                    <label class="small fw-bold">Kategori</label>
                                    <select name="kategori_edit" class="form-select form-select-sm">
                                        <option value="Kamar" <?= $f['kategori'] == 'Kamar' ? 'selected' : '' ?>>Kamar</option>
                                        <option value="Kamar Mandi" <?= $f['kategori'] == 'Kamar Mandi' ? 'selected' : '' ?>>Kamar Mandi</option>
                                        <option value="Umum" <?= $f['kategori'] == 'Umum' ? 'selected' : '' ?>>Umum</option>
                                        <option value="Parkir" <?= $f['kategori'] == 'Parkir' ? 'selected' : '' ?>>Parkir</option>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer py-1">
                                <button type="submit" name="edit_fasilitas" class="btn btn-primary btn-sm w-100">Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
    <?php endwhile;
    } ?>

    <?php
    // Reset pointer query peraturan
    if (mysqli_num_rows($qp) > 0) {
        mysqli_data_seek($qp, 0);
        while ($p = mysqli_fetch_assoc($qp)):
    ?>
            <div class="modal fade" id="editPer<?= $p['id_master_peraturan'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-sm modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header py-2">
                            <h6 class="modal-title fw-bold">Edit Peraturan</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST">
                            <div class="modal-body">
                                <input type="hidden" name="id_edit" value="<?= $p['id_master_peraturan'] ?>">
                                <div class="mb-2">
                                    <label class="small fw-bold">Isi Peraturan</label>
                                    <input type="text" name="nama_edit" class="form-control form-control-sm" value="<?= $p['nama_peraturan'] ?>" required>
                                </div>
                                <div class="mb-2">
                                    <label class="small fw-bold">Kategori</label>
                                    <select name="kategori_edit" class="form-select form-select-sm">
                                        <option value="Kamar" <?= $p['kategori'] == 'Kamar' ? 'selected' : '' ?>>Kamar</option>
                                        <option value="Kost" <?= $p['kategori'] == 'Kost' ? 'selected' : '' ?>>Umum/Kost</option>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer py-1">
                                <button type="submit" name="edit_peraturan" class="btn btn-primary btn-sm w-100">Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
    <?php endwhile;
    } ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>