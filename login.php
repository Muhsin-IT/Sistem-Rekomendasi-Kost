<?php
session_start();
include 'koneksi.php';

// =================================================================
// 1. LOGIKA PENYIMPANAN URL (TIDAK DIUBAH)
// =================================================================
if (isset($_GET['next']) && !empty($_GET['next'])) {

    $url_tujuan = $_GET['next'];

    if (isset($_GET['id'])) {
        if (strpos($url_tujuan, '?') !== false) {
            $url_tujuan .= "&id=" . $_GET['id'];
        } else {
            $url_tujuan .= "?id=" . $_GET['id'];
        }
    }

    $_SESSION['redirect_after_login'] = $url_tujuan;
}

// =================================================================
// 2. PROSES LOGIN
// =================================================================
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $result = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username'");

    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);

        if (password_verify($password, $row['password'])) {

            if ($row['role'] == 'mahasiswa') {
                $_SESSION['login'] = true;
                $_SESSION['id_user'] = $row['id_user'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['nama_lengkap'] = $row['nama_lengkap'];

                $tujuan = "mahasiswa/index"; // Default

                if (isset($_SESSION['redirect_after_login']) && !empty($_SESSION['redirect_after_login'])) {
                    $tujuan = urldecode($_SESSION['redirect_after_login']);
                    $tujuan = str_replace('.php', '', $tujuan);
                    unset($_SESSION['redirect_after_login']);
                }

                session_write_close();

                header("Location: " . $tujuan);
                exit;
            } else {
                $error_msg = "Akun Anda terdaftar sebagai Pemilik Kost.";
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
    <title>Login Mahasiswa - Kost UNU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            /* 1. BACKGROUND IMAGE */
            background-image: url('assets/img/logo/bg-hero-wide.webp');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-repeat: no-repeat;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
            position: relative;
        }

        /* 2. OVERLAY LEBIH GELAP SEDIKIT (Agar teks putih lebih jelas) */
        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            /* Gelap 60% */
            z-index: -1;
        }

        /* 3. SETTING TRANSPARANSI KARTU LOGIN (GLASSMORPHISM) */
        .card-login {
            /* Border tipis transparan */
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
            /* Bayangan lebih kuat */

            /* -- BAGIAN KUNCI TRANSPARANSI -- */
            /* Putih dengan transparansi 45% (Sangat Bening) */
            background: rgba(255, 255, 255, 0.05);

            /* Blur diperkuat agar teks tetap terbaca di atas background ramai */
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        .text-login-title {
            color: #fff;
            /* Judul jadi Putih agar kontras */
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }

        .text-login-desc {
            color: rgba(255, 255, 255, 0.8) !important;
            /* Deskripsi agak putih pudar */
        }

        .label-custom {
            color: #fff;
            /* Label form jadi putih */
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
            font-weight: 600;
        }

        .btn-login {
            background: #0d6efd;
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }

        .btn-login:hover {
            background: #0b5ed7;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(13, 110, 253, 0.6);
        }

        .form-control {
            border-radius: 10px;
            padding: 12px;
            /* Input field dibuat agak putih solid supaya user tau dimana ngetik */
            background-color: rgba(255, 255, 255, 0.9);
            border: none;
        }

        .form-control:focus {
            background-color: #fff;
            box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.4);
        }

        .btn-owner-floating {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 12px 20px;
            border-radius: 50px;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
            font-weight: 600;
            transition: all 0.3s;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 8px;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-owner-floating:hover {
            background-color: #000;
            transform: scale(1.05);
            color: #ffc107;
        }

        .logo-circle {
            width: 80px;
            height: 80px;
            background: rgba(13, 110, 253, 0.9);
            color: white;
            border: 3px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        /* Link di bawah form */
        .link-footer {
            color: #fff;
            text-decoration: none;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.8);
        }

        .link-footer:hover {
            color: #ffc107;
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card card-login p-4">
                    <div class="text-center mb-4 mt-2">
                        <div class="logo-circle rounded-circle d-inline-flex align-items-center justify-content-center mb-3">
                            <i class="fa-solid fa-user-graduate fa-2x"></i>
                        </div>
                        <h4 class="fw-bold text-login-title">Login Mahasiswa</h4>
                        <p class="text-login-desc small">Masuk untuk mencari kost impian</p>
                    </div>

                    <?php if (isset($error)) : ?>
                        <div class="alert alert-danger py-2 text-center small border-0 shadow-sm" style="opacity: 0.9;">
                            <i class="fa-solid fa-circle-exclamation me-1"></i> <?= $error_msg; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label label-custom small">USERNAME</label>
                            <input type="text" name="username" class="form-control" placeholder="Masukkan username..." required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label label-custom small">PASSWORD</label>
                            <input type="password" name="password" class="form-control" placeholder="********" required>
                        </div>
                        <button type="submit" name="login" class="btn btn-login w-100 text-white">
                            MASUK SEKARANG
                        </button>
                    </form>

                    <div class="text-center mt-4">
                        <small class="text-white" style="text-shadow: 0 1px 2px rgba(0,0,0,0.8);">Belum punya akun? <a href="daftar" class="link-footer fw-bold text-info">Daftar</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <a href="login_pemilik" class="btn-owner-floating">
        <i class="fa-solid fa-user-tie"></i>
        Login Owner
    </a>

</body>

</html>