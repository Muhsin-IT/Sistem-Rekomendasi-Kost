<?php
session_start();
include 'koneksi.php';

// 1. CEK LOGIN
if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// 2. LOGIKA UPDATE PROFIL
if (isset($_POST['update_profil'])) {
    $nama     = mysqli_real_escape_string($conn, $_POST['nama']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $hp       = mysqli_real_escape_string($conn, $_POST['hp']);
    $password = $_POST['password'];

    // Cek Username (Apakah sudah dipakai orang lain?)
    $cek_user = mysqli_query($conn, "SELECT id_user FROM users WHERE username = '$username' AND id_user != '$id_user'");
    if (mysqli_num_rows($cek_user) > 0) {
        echo "<script>alert('Username sudah digunakan user lain!');</script>";
    } else {
        // Query Dasar
        $query = "UPDATE users SET nama_lengkap = '$nama', username = '$username', no_hp = '$hp'";

        // Jika Password Diisi, Update Password Juga
        if (!empty($password)) {
            $pass_hash = password_hash($password, PASSWORD_DEFAULT);
            $query .= ", password = '$pass_hash'";
        }

        $query .= " WHERE id_user = '$id_user'";

        if (mysqli_query($conn, $query)) {
            // Update Session Nama
            $_SESSION['nama_lengkap'] = $nama;
            echo "<script>alert('Profil berhasil diperbarui!'); window.location='profil.php';</script>";
        } else {
            echo "<script>alert('Gagal update: " . mysqli_error($conn) . "');</script>";
        }
    }
}

// 3. AMBIL DATA USER
$u = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id_user = '$id_user'"));
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/img/logo/persegi.webp">
    <title>Profil Saya - RadenStay</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background-color: #f5f7fa;
        }

        .card-profile {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            background: white;
            overflow: hidden;
        }

        .profile-header {
            background: linear-gradient(45deg, #0d6efd, #0dcaf0);
            padding: 40px 20px;
            text-align: center;
            color: white;
        }

        .avatar-circle {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #0d6efd;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            margin-bottom: 15px;
        }
    </style>
</head>

<body>

    <?php include 'navbar.php'; ?>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">

                <div class="card card-profile">
                    <div class="profile-header">
                        <div class="avatar-circle">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <h4 class="fw-bold mb-0"><?= $u['nama_lengkap'] ?></h4>
                        <p class="mb-0 opacity-75">@<?= $u['username'] ?> • <?= ucfirst($u['role']) ?></p>
                    </div>

                    <div class="card-body p-4">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">Nama Lengkap</label>
                                <input type="text" name="nama" class="form-control" value="<?= $u['nama_lengkap'] ?>" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label small fw-bold text-muted">Username</label>
                                    <input type="text" name="username" class="form-control" value="<?= $u['username'] ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label small fw-bold text-muted">No. WhatsApp</label>
                                    <input type="number" name="hp" class="form-control" value="<?= $u['no_hp'] ?>" required>
                                </div>
                            </div>

                            <hr class="my-4">
                            <h6 class="fw-bold text-danger mb-3"><i class="bi bi-shield-lock me-2"></i> Ganti Password</h6>

                            <div class="mb-4">
                                <label class="form-label small fw-bold text-muted">Password Baru <span class="fw-normal fst-italic">(Kosongkan jika tidak ingin mengganti)</span></label>
                                <input type="password" name="password" class="form-control" placeholder="******">
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" name="update_profil" class="btn btn-primary fw-bold py-2 rounded-pill">
                                    <i class="bi bi-save me-2"></i> Simpan Perubahan
                                </button>
                                <a href="logout.php" class="btn btn-outline-danger fw-bold py-2 rounded-pill" onclick="return confirm('Yakin ingin keluar?')">
                                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>