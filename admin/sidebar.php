<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
    body {
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
    }
</style>
<div class="col-md-2 sidebar d-none d-md-block" style="background: #1e293b;">
    <h4 class="text-center mb-4 fw-bold">Kost UNU</h4>
    <p class="text-center small mb-4 text-white-50">Admin Super</p>
    <hr>

    <a href="dashboard.php" class="<?= ($current_page == 'dashboard.php') ? 'bg-white text-dark fw-bold' : ''; ?>">
        <i class="bi bi-graph-up"></i> Statistik Utama
    </a>

    <a href="kelola_user.php" class="<?= ($current_page == 'kelola_user.php') ? 'bg-white text-dark fw-bold' : ''; ?>">
        <i class="bi bi-people"></i> Data Pengguna
    </a>

    <a href="semua_kost.php" class="<?= ($current_page == 'semua_kost.php') ? 'bg-white text-dark fw-bold' : ''; ?>">
        <i class="bi bi-building"></i> Semua Properti
    </a>

    <hr>
    <a href="../logout.php" class="text-warning">
        <i class="bi bi-box-arrow-left"></i> Logout
    </a>
</div>