<?php
include 'koneksi.php';

if (isset($_POST['daftar'])) {
    $nama = $_POST['nama_lengkap'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $no_hp = $_POST['no_hp'];
    $role = $_POST['role'];

    $query = "INSERT INTO users (username, password, nama_lengkap, no_hp, role) 
              VALUES ('$username', '$password', '$nama', '$no_hp', '$role')";

    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Pendaftaran Berhasil!'); window.location='login.php';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <title>Daftar - Kost UNU Yogyakarta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-success text-white text-center">
                        <h4>Form Pendaftaran</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label>Nama Lengkap</label>
                                <input type="text" name="nama_lengkap" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Username</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Nomor HP (WhatsApp)</label>
                                <input type="text" name="no_hp" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Daftar Sebagai</label>
                                <select name="role" class="form-select" required>
                                    <option value="mahasiswa">Mahasiswa (Pencari Kost)</option>
                                    <option value="pemilik">Pemilik Kost</option>
                                </select>
                            </div>
                            <button type="submit" name="daftar" class="btn btn-success w-100">Daftar Sekarang</button>
                        </form>
                        <p class="mt-3 text-center">Sudah punya akun? <a href="login.php">Login di sini</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>