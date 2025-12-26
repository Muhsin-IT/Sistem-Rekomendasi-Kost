<?php
session_start();
include 'koneksi.php';

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $next = isset($_GET['next']) ? $_GET['next'] : ''; // Tangkap halaman tujuan

    $result = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username'");

    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);
        if (password_verify($password, $row['password'])) {
            $_SESSION['login'] = true;
            $_SESSION['id_user'] = $row['id_user'];
            $_SESSION['role'] = $row['role'];

            // LOGIKA REDIRECT
            if (!empty($next)) {
                // Jika ada tujuan sebelumnya, balik ke sana
                header("Location: " . $next);
            } else {
                // Jika tidak ada, ke dashboard masing-masing
                if ($row['role'] == 'pemilik') {
                    header("Location: pemilik/dashboard.php");
                } else {
                    header("Location: mahasiswa/index.php");
                }
            }
            exit;
        }
    }
    $error = true;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <title>Login - Kost UNU Yogyakarta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-primary">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card p-4 shadow-lg">
                    <h3 class="text-center">Login</h3>
                    <hr>
                    <?php if (isset($error)) : ?>
                        <div class="alert alert-danger">Username atau Password salah!</div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label>Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" name="login" class="btn btn-primary w-100">Masuk</button>
                    </form>
                    <div class="text-center mt-3">
                        <small>Belum punya akun? <a href="daftar.php">Daftar</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>