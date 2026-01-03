<?php
// Mendapatkan nama file yang sedang dibuka (misal: index.php)
$page = basename($_SERVER['PHP_SELF']);
?>

<style>
    .sidebar {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(15px);
        border-right: 1px solid rgba(255, 255, 255, 0.2);
        min-height: 100vh;
        color: white;
    }

    .nav-link {
        color: rgba(255, 255, 255, 0.8);
        margin-bottom: 5px;
        border-radius: 10px;
        transition: all 0.3s;
    }

    .nav-link:hover,
    .nav-link.active {
        background: rgba(13, 110, 253, 0.6);
        color: white;
        transform: translateX(5px);
    }
</style>

<div class="col-md-3 col-lg-2 d-none d-md-block sidebar p-3">
    <div class="text-center mb-4 mt-2">
        <div class="bg-white text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width:50px;height:50px;">
            <i class="fa-solid fa-shield-halved fa-xl"></i>
        </div>
        <h5 class="fw-bold">Super Admin</h5>
        <small class="text-white-50">RadenStay Panel</small>
    </div>
    <hr class="text-white">
    <ul class="nav flex-column">

        <li class="nav-item">
            <a class="nav-link <?= ($page == 'index.php') ? 'active' : '' ?>" href="index.php">
                <i class="fa-solid fa-gauge me-2"></i> Dashboard
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?= ($page == 'data_pengguna.php') ? 'active' : '' ?>" href="data_pengguna.php">
                <i class="fa-solid fa-users me-2"></i> Data Pengguna
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?= ($page == 'data_kost.php') ? 'active' : '' ?>" href="data_kost.php">
                <i class="fa-solid fa-building me-2"></i> Data Kost
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($page == 'master_data.php') ? 'active' : '' ?>" href="master_data.php">
                <i class="fa-solid fa-building me-2"></i> Master Data
            </a>
        </li>

        <li class="nav-item mt-4">
            <a class="nav-link text-warning" href="../logout.php">
                <i class="fa-solid fa-right-from-bracket me-2"></i> Logout
            </a>
        </li>

    </ul>
</div>