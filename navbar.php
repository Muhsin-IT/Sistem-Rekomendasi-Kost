<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
// Cek apakah halaman saat ini mengandung kata 'profil'
$isProfilPage = strpos($currentPage, 'profil') !== false;
?>
<style>
    /* Styling Bottom Nav (Tampilan HP) */
    .bottom-nav {
        display: none;
        background: linear-gradient(90deg, #9696ffff, #edf3ffff);
        border-top: 1px solid rgba(255, 255, 255, 0.15);
        box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.25);
        padding: 8px 12px;
        gap: 10px;
    }

    .bottom-nav a {
        flex: 1;
        text-decoration: none;
        color: #000000ff;
        font-size: 0.75rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2px;
    }

    .bottom-nav a i {
        font-size: 1.25rem;
    }

    .bottom-nav a.active,
    .bottom-nav a:hover {
        color: #1500ffff;
    }

    /* Styling untuk active link pada navbar atas */
    .navbar-nav .nav-link.active {
        color: #1500ffff !important;
        font-weight: 700 !important;
        position: relative;
    }

    .navbar-nav .nav-link.active::after {
        content: '';
        position: absolute;
        bottom: -8px;
        left: 50%;
        transform: translateX(-50%);
        width: 30px;
        height: 3px;
        background-color: #1500ffff;
        border-radius: 2px;
    }

    .navbar-nav .nav-link:hover {
        color: #1500ffff !important;
        transition: color 0.3s ease;
    }

    /* Z-index untuk navbar agar berada di atas resizer */
    .main-navbar {
        z-index: 10000 !important;
    }

    @media (max-width: 991.98px) {
        body {
            padding-bottom: 80px;
            /* Beri ruang agar konten tidak tertutup bottom nav */
        }

        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            display: flex;
            z-index: 1030;
        }
    }
</style>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top py-2 py-md-3 main-navbar">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index">
            <img src="assets/img/logo/radenStay(2).png" alt="RadenStay Logo" height="35" class="me-2 d-inline-block align-text-top">
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center text-center text-lg-start mt-3 mt-lg-0">
                <li class="nav-item"><a class="nav-link fw-semibold px-3 text-dark <?= $currentPage === 'index.php' ? 'active' : '' ?>" href="index">Beranda</a></li>
                <li class="nav-item"><a class="nav-link fw-semibold px-3 text-dark <?= $currentPage === 'tentang.php' ? 'active' : '' ?>" href="tentang">Tentang</a></li>
                <li class="nav-item"><a class="nav-link fw-semibold px-3 text-dark <?= $currentPage === 'riwayat_sewa.php' ? 'active' : '' ?>" href="riwayat_sewa">Pesanan</a></li>

                <?php if (isset($_SESSION['login'])): ?>
                    <li class="nav-item dropdown ms-lg-3 mt-2 mt-lg-0">
                        <a class="nav-link dropdown-toggle btn btn-outline-primary px-4 rounded-pill" href="#" role="button" data-bs-toggle="dropdown">
                            Hi, <?= strtok($_SESSION['nama_lengkap'] ?? 'User', ' ') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 text-center text-lg-start">
                            <li><a class="dropdown-item" href="profil"><i class="bi bi-person me-2"></i> Profil Saya</a></li>

                            <?php if ($_SESSION['role'] == 'admin'): ?>
                                <li><a class="dropdown-item" href="admin/index"><i class="bi bi-speedometer2 me-2"></i> Dashboard Admin</a></li>
                            <?php elseif ($_SESSION['role'] == 'pemilik'): ?>
                                <li><a class="dropdown-item" href="pemilik/dashboard"><i class="bi bi-speedometer2 me-2"></i> Dashboard Pemilik</a></li>
                            <?php endif; ?>

                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item text-danger" href="logout"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item ms-lg-3 mt-3 mt-lg-0 d-grid d-lg-block gap-2">
                        <a class="btn btn-link text-decoration-none fw-bold text-primary me-lg-2" href="login">Masuk</a>
                        <a class="btn btn-warning text-white px-4 rounded-pill fw-bold shadow-sm" href="daftar" style="background-color: #fd7e14; border:none;">Daftar</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="bottom-nav d-lg-none">
    <a href="index" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">
        <i class="bi bi-house-door-fill"></i>
        <small>Beranda</small>
    </a>
    <a href="tentang" class="<?= $currentPage === 'tentang.php' ? 'active' : '' ?>">
        <i class="bi bi-info-circle-fill"></i>
        <small>Tentang</small>
    </a>
    <a href="riwayat_sewa" class="<?= $currentPage === 'riwayat_sewa.php' ? 'active' : '' ?>">
        <i class="bi bi-receipt-cutoff"></i>
        <small>Pesanan</small>
    </a>

    <?php if (isset($_SESSION['login'])): ?>
        <a href="profil" class="<?= $isProfilPage ? 'active' : '' ?>">
            <i class="bi bi-person-circle"></i>
            <small>Profil</small>
        </a>
    <?php else: ?>
        <a href="login" class="<?= $currentPage === 'login.php' ? 'active' : '' ?>">
            <i class="bi bi-box-arrow-in-right"></i>
            <small>Masuk</small>
        </a>
    <?php endif; ?>
</div>