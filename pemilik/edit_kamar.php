<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'pemilik') {
    header("Location: ../login.php");
    exit;
}

// 1. Ambil ID Kamar dari URL
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}

$id_kamar = $_GET['id'];

// 2. Ambil data kamar saat ini
$query_kamar = mysqli_query($conn, "SELECT * FROM kamar WHERE id_kamar = '$id_kamar'");
$data_kamar = mysqli_fetch_assoc($query_kamar);
$id_kost = $data_kamar['id_kost'];

// 3. Ambil fasilitas yang sudah dipilih sebelumnya (untuk tanda centang/checked)
$fasilitas_aktif = [];
$get_f = mysqli_query($conn, "SELECT id_master_fasilitas FROM rel_fasilitas WHERE id_kamar = '$id_kamar'");
while ($f = mysqli_fetch_assoc($get_f)) {
    $fasilitas_aktif[] = $f['id_master_fasilitas'];
}

// 4. Proses Update jika tombol ditekan
if (isset($_POST['update_kamar'])) {
    $nama_tipe = mysqli_real_escape_string($conn, $_POST['nama_tipe']);
    $harga     = $_POST['harga'];
    $stok      = $_POST['stok'];
    $lebar     = mysqli_real_escape_string($conn, $_POST['lebar_ruangan']);
    $listrik   = $_POST['listrik'];

    // Update tabel kamar
    $update_q = "UPDATE kamar SET 
                 nama_tipe_kamar = '$nama_tipe', 
                 harga_per_bulan = '$harga', 
                 stok_kamar = '$stok', 
                 lebar_ruangan = '$lebar', 
                 sudah_termasuk_listrik = '$listrik' 
                 WHERE id_kamar = '$id_kamar'";

    if (mysqli_query($conn, $update_q)) {
        // Update Fasilitas: Hapus yang lama, masukkan yang baru terpilih
        mysqli_query($conn, "DELETE FROM rel_fasilitas WHERE id_kamar = '$id_kamar'");

        if (!empty($_POST['fasilitas'])) {
            foreach ($_POST['fasilitas'] as $id_f) {
                mysqli_query($conn, "INSERT INTO rel_fasilitas (id_kamar, id_kost, id_master_fasilitas) 
                                     VALUES ('$id_kamar', NULL, '$id_f')");
            }
        }

        echo "<script>alert('Data kamar berhasil diperbarui!'); window.location='kelola_kamar.php?id=$id_kost';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <title>Edit Kamar - Kost UNU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container my-5">
        <div class="card shadow border-0">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">Edit Tipe Kamar: <?= $data_kamar['nama_tipe_kamar']; ?></h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label>Nama Tipe Kamar</label>
                        <input type="text" name="nama_tipe" class="form-control" value="<?= $data_kamar['nama_tipe_kamar']; ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label>Harga Per Bulan</label>
                            <input type="number" name="harga" class="form-control" value="<?= (int)$data_kamar['harga_per_bulan']; ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Stok Kamar</label>
                            <input type="number" name="stok" class="form-control" value="<?= $data_kamar['stok_kamar']; ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Lebar Ruangan</label>
                            <input type="text" name="lebar_ruangan" class="form-control" value="<?= $data_kamar['lebar_ruangan']; ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Termasuk Listrik?</label>
                        <select name="listrik" class="form-select">
                            <option value="1" <?= $data_kamar['sudah_termasuk_listrik'] == 1 ? 'selected' : ''; ?>>Ya</option>
                            <option value="0" <?= $data_kamar['sudah_termasuk_listrik'] == 0 ? 'selected' : ''; ?>>Tidak</option>
                        </select>
                    </div>

                    <hr>
                    <h6>Update Fasilitas Kamar:</h6>
                    <div class="row mb-4">
                        <?php
                        $f_res = mysqli_query($conn, "SELECT * FROM master_fasilitas WHERE kategori IN ('Kamar', 'Kamar Mandi')");
                        while ($f = mysqli_fetch_assoc($f_res)):
                            // Cek apakah ID fasilitas ini ada dalam array fasilitas_aktif
                            $checked = in_array($f['id_master_fasilitas'], $fasilitas_aktif) ? 'checked' : '';
                        ?>
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="fasilitas[]" value="<?= $f['id_master_fasilitas'] ?>" <?= $checked; ?>>
                                    <label class="form-check-label"><?= $f['nama_fasilitas'] ?></label>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" name="update_kamar" class="btn btn-warning w-100">Simpan Perubahan</button>
                        <a href="kelola_kamar.php?id=<?= $id_kost; ?>" class="btn btn-secondary w-100">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>