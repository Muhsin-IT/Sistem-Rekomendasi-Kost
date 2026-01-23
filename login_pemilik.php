<?php
// GANTI session_start(); DENGAN KONFIGURASI SESSION DENGAN LIFETIME 30 HARI
$lifetime = 30 * 24 * 60 * 60; // 30 hari dalam detik
ini_set('session.gc_maxlifetime', $lifetime);
session_set_cookie_params([
    'lifetime' => $lifetime,
    'path' => '/',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();
include 'koneksi.php';

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $result = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username'");

    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);
        if (password_verify($password, $row['password'])) {

            // VALIDASI KHUSUS PEMILIK
            if ($row['role'] == 'pemilik') {
                $_SESSION['login'] = true;
                $_SESSION['id_user'] = $row['id_user'];
                $_SESSION['role'] = $row['role'];

                header("Location: pemilik/dashboard.php");
                exit;
            } elseif ($row['role'] == 'adminsuper') {
                $_SESSION['login'] = true;
                $_SESSION['id_user'] = $row['id_user'];
                $_SESSION['role'] = 'admin';
                $_SESSION['nama_lengkap'] = $row['nama_lengkap'];

                // Simpan session agar tidak hilang saat redirect
                session_write_close();

                // Arahkan ke dashboard admin
                header("Location: admin/index.php");
                exit;
            } else {
                $error_msg = "Akun ini adalah Mahasiswa. Silakan login di halaman Mahasiswa.";
                $error = true;
            }
        } else {
            $error_msg = "Password salah!";
            $error = true;
        }
    } else {
        $error_msg = "Username tidak ditemukan!";
        $error = true;
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/img/logo/persegi.webp">
    <title>Login Owner - Kost UNU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            /* Gradient Gelap/Elegan untuk Owner */
            background: linear-gradient(135deg, #232526 0%, #414345 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
        }

        .card-login {
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            background: rgba(30, 30, 30, 0.9);
            color: white;
        }

        .btn-login {
            background: #ffc107;
            /* Warna Emas */
            color: #000;
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-weight: 700;
        }

        .btn-login:hover {
            background: #e0a800;
            color: #000;
        }

        .form-control {
            border-radius: 10px;
            padding: 12px;
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
        }

        .form-control:focus {
            background-color: rgba(255, 255, 255, 0.2);
            box-shadow: none;
            color: white;
            border-color: #ffc107;
        }

        /* Placeholder styling agar terlihat di background gelap */
        ::placeholder {
            color: rgba(255, 255, 255, 0.5) !important;
        }

        /* TOMBOL POJOK KANAN BAWAH */
        .btn-mhs-floating {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background-color: #fff;
            color: #0d6efd;
            padding: 12px 20px;
            border-radius: 50px;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-mhs-floating:hover {
            background-color: #e2e6ea;
            transform: scale(1.05);
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card card-login p-4">
                    <div class="text-center mb-4">
                        <div class="bg-warning text-dark rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <i class="fa-solid fa-key fa-xl"></i>
                        </div>
                        <h4 class="fw-bold">Login Owner</h4>
                        <p class="text-white-50 small">Kelola kost Anda dengan mudah</p>
                    </div>

                    <?php if (isset($error)) : ?>
                        <div class="alert alert-warning py-2 text-center small border-0">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i> <?= $error_msg; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label small text-warning fw-bold">USERNAME PEMILIK</label>
                            <input type="text" name="username" class="form-control" placeholder="Username owner..." required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small text-warning fw-bold">PASSWORD</label>
                            <input type="password" name="password" class="form-control" placeholder="********" required>
                        </div>
                        <button type="submit" name="login" class="btn btn-login w-100">
                            MASUK DASHBOARD
                        </button>
                    </form>
                    <div class="text-center mt-4">
                        <small class="text-white" style="text-shadow: 0 1px 2px rgba(0,0,0,0.8);">Belum punya akun? <a href="daftar_pemilik" class="link-footer fw-bold text-info">Daftar Owner</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <a href="login.php" class="btn-mhs-floating">
        <i class="fa-solid fa-arrow-left"></i>
        Login Mahasiswa
    </a>

</body>

</html>