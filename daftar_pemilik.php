<?php
include 'koneksi.php';

if (isset($_POST['daftar'])) {
    $nama     = mysqli_real_escape_string($conn, $_POST['nama']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $hp       = mysqli_real_escape_string($conn, $_POST['hp']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Hash Password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Cek Username
    $cek = mysqli_query($conn, "SELECT username FROM users WHERE username = '$username'");
    if (mysqli_num_rows($cek) > 0) {
        $error_msg = "Username sudah digunakan.";
        $error = true;
    } else {
        // Insert Role PEMILIK
        $query = "INSERT INTO users (nama_lengkap, username, password, no_hp, role) 
                  VALUES ('$nama', '$username', '$password_hash', '$hp', 'pemilik')";

        if (mysqli_query($conn, $query)) {
            echo "<script>
                    alert('Pendaftaran Owner Berhasil! Silakan Login.');
                    document.location.href='login_pemilik.php';
                  </script>";
        } else {
            $error_msg = "Gagal mendaftar.";
            $error = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Partner Owner - Kost UNU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            /* Background Gelap Elegan */
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('assets/img/logo/bg-hero-wide.webp');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
        }

        .card-daftar {
            /* Desain Gelap Transparan */
            border: 1px solid rgba(255, 215, 0, 0.3);
            /* Border Emas Tipis */
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.8);
            background: rgba(0, 0, 0, 0.65);
            /* Hitam Transparan */
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            color: white;
        }

        .text-title {
            color: #ffc107;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 1);
        }

        .label-custom {
            color: #ccc;
            font-weight: 600;
            letter-spacing: 1px;
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
            border-color: #ffc107;
            box-shadow: none;
            color: white;
        }

        ::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        .btn-daftar {
            background: #ffc107;
            /* Emas */
            color: #000;
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-weight: 700;
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);
            transition: all 0.3s;
        }

        .btn-daftar:hover {
            background: #e0a800;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 193, 7, 0.5);
        }

        .logo-circle {
            width: 70px;
            height: 70px;
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
            border: 2px solid #ffc107;
            box-shadow: 0 0 15px rgba(255, 193, 7, 0.3);
        }

        /* Floating Button Mahasiswa */
        .btn-mhs-floating {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background-color: white;
            color: #0d6efd;
            padding: 12px 25px;
            border-radius: 50px;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
            font-weight: 600;
            transition: all 0.3s;
            z-index: 1000;
        }

        .btn-mhs-floating:hover {
            transform: scale(1.05);
            background: #f0f0f0;
        }
    </style>
</head>

<body>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card card-daftar p-4">
                    <div class="text-center mb-4 mt-2">
                        <div class="logo-circle rounded-circle d-inline-flex align-items-center justify-content-center mb-3">
                            <i class="fa-solid fa-house-laptop fa-2x"></i>
                        </div>
                        <h4 class="fw-bold text-title">Partner Owner</h4>
                        <p class="text-white-50 small">Bergabunglah untuk mengelola kost Anda</p>
                    </div>

                    <?php if (isset($error)) : ?>
                        <div class="alert alert-warning py-2 small"><?= $error_msg; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label label-custom small">NAMA PEMILIK</label>
                            <input type="text" name="nama" class="form-control" placeholder="Nama lengkap Anda..." required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label label-custom small">NO. WHATSAPP</label>
                            <input type="number" name="hp" class="form-control" placeholder="08xxxxxxxxxx" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label label-custom small">USERNAME LOGIN</label>
                            <input type="text" name="username" class="form-control" placeholder="Buat username unik..." required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label label-custom small">PASSWORD</label>
                            <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
                        </div>
                        <button type="submit" name="daftar" class="btn btn-daftar w-100">
                            GABUNG SEKARANG
                        </button>
                    </form>

                    <div class="text-center mt-4">
                        <small class="text-white-50">
                            Sudah punya akun? <a href="login_pemilik.php" class="fw-bold text-warning text-decoration-none">Login Owner</a>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <a href="daftar.php" class="btn-mhs-floating">
        <i class="fa-solid fa-user-graduate me-2"></i> Daftar Sebagai Pengguna
    </a>

</body>

</html>