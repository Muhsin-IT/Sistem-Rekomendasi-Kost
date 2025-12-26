<?php
session_start();
include '../koneksi.php';

// Proteksi login
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'pemilik') {
    header("Location: ../login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// 1. Ambil data user terbaru
$query = mysqli_query($conn, "SELECT * FROM users WHERE id_user = '$id_user'");
$user = mysqli_fetch_assoc($query);

// 2. Ambil statistik singkat
$stat_kost = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM kost WHERE id_pemilik = '$id_user'"));
$stat_kamar = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM kamar JOIN kost ON kamar.id_kost = kost.id_kost WHERE kost.id_pemilik = '$id_user'"));

// 3. Logika Update Profil
if (isset($_POST['update_profil'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $no_hp = mysqli_real_escape_string($conn, $_POST['no_hp']);

    $update = "UPDATE users SET nama_lengkap = '$nama', no_hp = '$no_hp' WHERE id_user = '$id_user'";
    if (mysqli_query($conn, $update)) {
        $_SESSION['nama'] = $nama; // Update session nama agar di navbar ikut berubah
        echo "<script>alert('Profil berhasil diperbarui!'); window.location='profil.php';</script>";
    }
}

// 4. Logika Ganti Password
if (isset($_POST['update_password'])) {
    $pass_baru = $_POST['password_baru'];
    $konfirmasi = $_POST['konfirmasi_password'];

    if ($pass_baru === $konfirmasi) {
        $hash_pass = password_hash($pass_baru, PASSWORD_DEFAULT);
        mysqli_query($conn, "UPDATE users SET password = '$hash_pass' WHERE id_user = '$id_user'");
        echo "<script>alert('Password berhasil diganti!');</script>";
    } else {
        echo "<script>alert('Konfirmasi password tidak cocok!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Profil Saya - Kost UNU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>

<body>

    <!-- <nav class="navbar navbar-dark bg-success mb-4">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php"><i class="bi bi-arrow-left"></i> Kembali ke Dashboard</a>
        </div>
    </nav> -->

    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <div class="col-md-10 p-4">
                <div class="col-md-4 mb-4">
                    <div class="card border-0 shadow-sm text-center p-4">
                        <div class="mx-auto bg-success text-white rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                            <i class="bi bi-person-badge fs-1"></i>
                        </div>
                        <h4><?= $user['nama_lengkap']; ?></h4>
                        <p class="text-muted small">@<?= $user['username']; ?> | Pemilik Kost</p>
                        <hr>
                        <div class="row">
                            <div class="col-6">
                                <h5 class="fw-bold"><?= $stat_kost['total']; ?></h5>
                                <small class="text-muted">Kost</small>
                            </div>
                            <div class="col-6">
                                <h5 class="fw-bold"><?= $stat_kamar['total']; ?></h5>
                                <small class="text-muted">Kamar</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0">Edit Profil Pribadi</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Username (Tidak bisa diubah)</label>
                                    <input type="text" class="form-control bg-light" value="<?= $user['username']; ?>" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nama Lengkap</label>
                                    <input type="text" name="nama_lengkap" class="form-control" value="<?= $user['nama_lengkap']; ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nomor WhatsApp (Aktif)</label>
                                    <input type="text" name="no_hp" class="form-control" value="<?= $user['no_hp']; ?>" placeholder="Contoh: 08123456789" required>
                                    <small class="text-muted text-italic">*Gunakan format angka langsung (08...)</small>
                                </div>
                                <button type="submit" name="update_profil" class="btn btn-success">Simpan Perubahan</button>
                            </form>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 text-danger">Ganti Password</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Password Baru</label>
                                        <input type="password" name="password_baru" class="form-control" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Konfirmasi Password</label>
                                        <input type="password" name="konfirmasi_password" class="form-control" required>
                                    </div>
                                </div>
                                <button type="submit" name="update_password" class="btn btn-outline-danger">Perbarui Password</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>