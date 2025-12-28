<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top py-2 py-md-3">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index">
            <img src="assets/img/logo/radenStay(2).png" alt="RadenStay Logo" height="35" class="me-2 d-inline-block align-text-top">
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center text-center text-lg-start mt-3 mt-lg-0">
                <li class="nav-item"><a class="nav-link fw-semibold px-3 text-dark" href="index">Beranda</a></li>
                <li class="nav-item"><a class="nav-link fw-semibold px-3 text-dark" href="tentang">Tentang</a></li>

                <?php if (isset($_SESSION['login'])): ?>
                    <li class="nav-item dropdown ms-lg-3 mt-2 mt-lg-0">
                        <a class="nav-link dropdown-toggle btn btn-outline-primary px-4 rounded-pill" href="#" role="button" data-bs-toggle="dropdown">
                            Hi, <?= strtok($_SESSION['nama'] ?? 'User', ' ') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 text-center text-lg-start">
                            <li><a class="dropdown-item" href="<?= $_SESSION['role'] ?>/dashboard"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a></li>
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