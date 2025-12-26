<?php
// Mendapatkan nama file saat ini untuk logika class active
$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
    /* body {
        background-color: #f8f9fa;
    }

    .sidebar {
        min-height: 100vh;
        background: #198754;
        color: white;
        padding: 20px;
    }

    .sidebar a {
        color: white;
        text-decoration: none;
        display: block;
        padding: 10px;
        border-radius: 5px;
    }

    .sidebar a:hover {
        background: #146c43;
    }

    .card-stat {
        border: none;
        border-left: 5px solid #198754;
    } */

    .sidebar {
        min-height: 100vh;
        background: #198754;
        color: white;
        padding: 20px;
        position: fixed;
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

    /* Agar konten tidak tertutup sidebar yang di-fixed */
    .col-md-10 {
        margin-left: 16.666667%;
    }
</style>
</style>

<div class="col-md-2 sidebar d-none d-md-block">
    <h4 class="text-center mb-4 fw-bold">Kost UNU</h4>
    <p class="text-center small mb-4 text-white-50">Panel Pemilik</p>
    <hr>

    <a href="dashboard.php" class="<?= ($current_page == 'dashboard.php') ? 'bg-white text-success fw-bold' : ''; ?>">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <a href="tambah_kost.php" class="<?= ($current_page == 'tambah_kost.php') ? 'bg-white text-success fw-bold' : ''; ?>">
        <i class="bi bi-plus-circle"></i> Tambah Kost
    </a>

    <a href="manajemen_pilihan.php" class="<?= ($current_page == 'manajemen_pilihan.php') ? 'bg-white text-success fw-bold' : ''; ?>">
        <i class="bi bi-gear"></i> Kelola Fasilitas/Aturan
    </a>

    <a href="pesanan.php" class="<?= ($current_page == 'pesanan.php') ? 'bg-white text-success fw-bold' : ''; ?>">
        <i class="bi bi-cart"></i> Pesanan/Booking
    </a>

    <a href="profil.php" class="<?= ($current_page == 'profil.php') ? 'bg-white text-success fw-bold' : ''; ?>">
        <i class="bi bi-person"></i> Profil Saya
    </a>

    <hr>
    <a href="../logout.php" class="text-warning" onclick="return confirm('Yakin ingin keluar?')">
        <i class="bi bi-box-arrow-left"></i> Logout
    </a>
</div>