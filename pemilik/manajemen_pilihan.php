<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'pemilik') {
    header("Location: ../login.php");
    exit;
}
// -------------------------------------------------------------------------------------------------------
// --- LOGIKA TAMBAH FASILITAS MASSAL ---
if (isset($_POST['tambah_fasilitaS'])) {
    $input_fasilitas = $_POST['nama_fasilitas'];
    $kategori = $_POST['kategori_f'];

    $data_fasilitas = explode(',', $input_fasilitas);
    $berhasil = 0;

    foreach ($data_fasilitas as $f) {
        $f_clean = trim($f);
        if (!empty($f_clean)) {
            $query = "INSERT INTO master_fasilitas (nama_fasilitas, kategori) VALUES ('$f_clean', '$kategori')";
            if (mysqli_query($conn, $query)) {
                $berhasil++;
            }
        }
    }
    if ($berhasil > 0) {
        echo "<script>alert('$berhasil fasilitas berhasil ditambahkan!'); window.location='manajemen_pilihan.php';</script>";
    }
}

// --- LOGIKA EDIT FASILITAS ---
if (isset($_POST['edit_fasilitas'])) {
    $id_f = $_POST['id_master_fasilitas'];
    $nama_f = mysqli_real_escape_string($conn, $_POST['nama_fasilitas']);
    $kat_f = $_POST['kategori_f'];

    mysqli_query($conn, "UPDATE master_fasilitas SET nama_fasilitas = '$nama_f', kategori = '$kat_f' WHERE id_master_fasilitas = '$id_f'");
    echo "<script>alert('Fasilitas diperbarui!'); window.location='manajemen_pilihan.php';</script>";
}
// -------------------------------------------------------------------------------------------------------
// --- LOGIKA TAMBAH PERATURAN MASSAL ---
if (isset($_POST['tambah_peraturan'])) {
    $input_peraturan = $_POST['nama_peraturan']; // Mengambil string (ex: "No Smoking, No Pet, Max 1 Person")
    $kategori = $_POST['kategori_p'];

    // Memecah string berdasarkan koma
    $data_peraturan = explode(',', $input_peraturan);
    $berhasil = 0;

    foreach ($data_peraturan as $p) {
        $p_clean = trim($p); // Menghilangkan spasi di awal/akhir kalimat
        if (!empty($p_clean)) {
            $query = "INSERT INTO master_peraturan (nama_peraturan, kategori) VALUES ('$p_clean', '$kategori')";
            if (mysqli_query($conn, $query)) {
                $berhasil++;
            }
        }
    }

    if ($berhasil > 0) {
        echo "<script>alert('$berhasil peraturan berhasil ditambahkan!'); window.location='manajemen_pilihan.php';</script>";
    } else {
        echo "<script>alert('Gagal menambah peraturan. Cek apakah peraturan sudah ada!');</script>";
    }
}

// --- LOGIKA HAPUS PERATURAN ---
if (isset($_GET['hapus_p'])) {
    $id_p = $_GET['hapus_p'];
    $delete = mysqli_query($conn, "DELETE FROM master_peraturan WHERE id_master_peraturan = '$id_p'");
    if ($delete) {
        echo "<script>alert('Peraturan dihapus!'); window.location='manajemen_pilihan.php';</script>";
    }
}

// --- LOGIKA EDIT PERATURAN ---
if (isset($_POST['edit_peraturan'])) {
    $id_edit = $_POST['id_master_peraturan'];
    $nama_edit = mysqli_real_escape_string($conn, $_POST['nama_peraturan']);
    $kat_edit = $_POST['kategori_p'];

    $update = mysqli_query($conn, "UPDATE master_peraturan SET nama_peraturan = '$nama_edit', kategori = '$kat_edit' WHERE id_master_peraturan = '$id_edit'");
    if ($update) {
        echo "<script>alert('Peraturan diperbarui!'); window.location='manajemen_pilihan.php';</script>";
    }
}
?>
<!-- ----------------------------------------------------------------------------------------------------------------------------- -->
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Manajemen Pilihan - Kost UNU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        body {
            background-color: #f8f9fa;
        }

        /* Style Sidebar agar konsisten */
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
            transition: 0.3s;
        }

        .sidebar a:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .content-area {
            margin-left: 16.666667%;
            padding: 30px;
        }
    </style>
</head>

<body>

    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <div class="col-md-10 content-area">
                <h2 class="mb-4">Manajemen Peraturan</h2>

                <div class="row">
                    <div class="col-md-4">
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-header bg-primary text-white">Tambah Peraturan Baru</div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">Nama Peraturan</label>
                                        <textarea name="nama_peraturan" class="form-control" placeholder="Contoh: Akses 24 Jam, Dilarang Merokok, Tidak Bawa Hewan" rows="3" required></textarea>
                                        <small class="text-muted">Pisahkan dengan koma (,) untuk input massal.</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Kategori</label>
                                        <select name="kategori_p" class="form-select">
                                            <option value="Kost">Peraturan Kost (Gedung)</option>
                                            <option value="Kamar">Peraturan Khusus Kamar</option>
                                        </select>
                                    </div>
                                    <button type="submit" name="tambah_peraturan" class="btn btn-primary w-100">Tambah</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Peraturan</th>
                                            <th>Kategori</th>
                                            <th class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $ps = mysqli_query($conn, "SELECT * FROM master_peraturan");
                                        while ($p = mysqli_fetch_assoc($ps)): ?>
                                            <tr>
                                                <td><?= $p['nama_peraturan']; ?></td>
                                                <td>
                                                    <span class="badge <?= $p['kategori'] == 'Kost' ? 'bg-info' : 'bg-warning text-dark'; ?>">
                                                        <?= $p['kategori']; ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $p['id_master_peraturan']; ?>">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <a href="manajemen_pilihan.php?hapus_p=<?= $p['id_master_peraturan']; ?>"
                                                        class="btn btn-sm btn-outline-danger"
                                                        onclick="return confirm('Hapus peraturan ini?')">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>

                                            <div class="modal fade" id="editModal<?= $p['id_master_peraturan']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="POST">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Edit Peraturan</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <input type="hidden" name="id_master_peraturan" value="<?= $p['id_master_peraturan']; ?>">
                                                                <div class="mb-3">
                                                                    <label>Nama Peraturan</label>
                                                                    <input type="text" name="nama_peraturan" class="form-control" value="<?= $p['nama_peraturan']; ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label>Kategori</label>
                                                                    <select name="kategori_p" class="form-select">
                                                                        <option value="Kost" <?= $p['kategori'] == 'Kost' ? 'selected' : ''; ?>>Kost (Gedung)</option>
                                                                        <option value="Kamar" <?= $p['kategori'] == 'Kamar' ? 'selected' : ''; ?>>Khusus Kamar</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="submit" name="edit_peraturan" class="btn btn-primary">Simpan</button>
                                                            </div>
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

                <div class="row mt-5">
                    <div class="col-md-12">
                        <hr>
                        <h4 class="mb-4 text-success"><i class="bi bi-tools"></i> Manajemen Fasilitas</h4>
                    </div>

                    <div class="col-md-4">
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-header bg-success text-white">Tambah Fasilitas Baru</div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">Nama Fasilitas</label>
                                        <textarea name="nama_fasilitas" class="form-control" placeholder="contoh: AC, WiFi, Kasur, Kamar Mandi Dalam" rows="3" required></textarea>
                                        <small class="text-muted">Pisahkan dengan koma (,) untuk input massal.</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Kategori</label>
                                        <select name="kategori_f" class="form-select">
                                            <option value="Kamar">Fasilitas Kamar (Privat)</option>
                                            <option value="Umum">Fasilitas Umum (Bersama)</option>
                                            <option value="Parkir">Fasilitas Parkir</option>
                                        </select>
                                    </div>
                                    <button type="submit" name="tambah_fasilitaS" class="btn btn-success w-100">Tambah Fasilitas</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nama Fasilitas</th>
                                            <th>Kategori</th>
                                            <th class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $fs = mysqli_query($conn, "SELECT * FROM master_fasilitas ORDER BY kategori DESC");
                                        while ($f = mysqli_fetch_assoc($fs)): ?>
                                            <tr>
                                                <td><?= $f['nama_fasilitas']; ?></td>
                                                <td>
                                                    <span class="badge bg-light text-dark border">
                                                        <i class="bi bi-tag"></i> <?= $f['kategori']; ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#editFasilitas<?= $f['id_master_fasilitas']; ?>">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <a href="manajemen_pilihan.php?hapus_f=<?= $f['id_master_fasilitas']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus fasilitas ini?')">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>

                                            <div class="modal fade" id="editFasilitas<?= $f['id_master_fasilitas']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="POST">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Edit Fasilitas</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <input type="hidden" name="id_master_fasilitas" value="<?= $f['id_master_fasilitas']; ?>">
                                                                <div class="mb-3">
                                                                    <label>Nama Fasilitas</label>
                                                                    <input type="text" name="nama_fasilitas" class="form-control" value="<?= $f['nama_fasilitas']; ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label>Kategori</label>
                                                                    <select name="kategori_f" class="form-select">
                                                                        <option value="Kamar" <?= $f['kategori'] == 'Kamar' ? 'selected' : ''; ?>>Kamar (Privat)</option>
                                                                        <option value="Umum" <?= $f['kategori'] == 'Umum' ? 'selected' : ''; ?>>Umum (Bersama)</option>
                                                                        <option value="Parkir" <?= $f['kategori'] == 'Parkir' ? 'selected' : ''; ?>>Parkir</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="submit" name="edit_fasilitas" class="btn btn-success">Simpan</button>
                                                            </div>
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>