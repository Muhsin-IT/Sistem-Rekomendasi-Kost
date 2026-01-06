<?php
session_start();
// Tidak perlu cek login ketat, karena halaman Tentang biasanya publik
// Tapi kita start session agar Navbar tahu user sedang login atau tidak
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/img/logo/persegi.webp">
    <title>Tentang Kami - RadenStay</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
            overflow-x: hidden;
        }

        /* Hero Section */
        .hero-about {
            background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
            color: white;
            padding: 80px 0 60px;
            border-radius: 0 0 50px 50px;
            margin-bottom: 50px;
            position: relative;
            overflow: hidden;
        }

        .hero-about::before {
            content: "";
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .hero-about::after {
            content: "";
            position: absolute;
            bottom: -30px;
            left: -30px;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        /* Card Team */
        .team-card {
            border: none;
            border-radius: 15px;
            background: white;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            text-align: center;
            padding: 30px 20px;
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .team-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(13, 110, 253, 0.15);
        }

        .team-avatar {
            width: 100px;
            height: 100px;
            margin: 0 auto 20px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #0d6efd;
            border: 3px solid white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            object-fit: cover;
        }

        .team-role {
            font-size: 0.85rem;
            color: #0dcaf0;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        /* Animasi Fade In */
        .fade-in-up {
            animation: fadeInUp 0.8s ease-out forwards;
            opacity: 0;
            transform: translateY(30px);
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Delay animasi untuk kartu tim */
        .delay-1 {
            animation-delay: 0.2s;
        }

        .delay-2 {
            animation-delay: 0.4s;
        }

        .delay-3 {
            animation-delay: 0.6s;
        }

        .delay-4 {
            animation-delay: 0.8s;
        }

        .delay-5 {
            animation-delay: 1.0s;
        }

        .feature-icon-box {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>

    <?php include 'navbar.php'; ?>

    <section class="hero-about text-center fade-in-up">
        <div class="container">
            <h1 class="fw-bold display-5 mb-3">Tentang RadenStay</h1>
            <p class="lead opacity-75 mx-auto" style="max-width: 600px;">
                Platform rekomendasi kost terbaik di sekitar Universitas Nahdlatul Ulama Yogyakarta.
                Temukan hunian nyaman untuk masa depan cerah.
            </p>
        </div>
    </section>

    <div class="container mb-5">
        <div class="row align-items-center g-5">
            <div class="col-lg-6 fade-in-up delay-1">
                <img src="https://img.freepik.com/free-vector/house-searching-concept-illustration_114360-466.jpg" alt="Ilustrasi Cari Kost" class="img-fluid rounded-4 shadow-sm">
            </div>
            <div class="col-lg-6 fade-in-up delay-1">
                <h6 class="text-primary fw-bold text-uppercase ls-1">Kenapa Kami Ada?</h6>
                <h2 class="fw-bold mb-4">Memudahkan Mahasiswa Mencari Hunian Impian</h2>
                <p class="text-muted mb-4">
                    RadenStay hadir sebagai solusi bagi mahasiswa UNU Yogyakarta yang kesulitan mencari informasi kost yang valid, terjangkau, dan nyaman. Kami menghubungkan pemilik kost langsung dengan pencari kost melalui sistem yang transparan.
                </p>

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start">
                            <div class="feature-icon-box me-3">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold">Terpercaya</h6>
                                <p class="small text-muted mb-0">Data kost divalidasi untuk keamanan bersama.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start">
                            <div class="feature-icon-box me-3">
                                <i class="bi bi-lightning-charge"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold">Cepat & Mudah</h6>
                                <p class="small text-muted mb-0">Booking kost hanya dalam beberapa klik.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="container my-5 fade-in-up delay-5">
        <div class="bg-dark text-white rounded-4 p-5 text-center position-relative overflow-hidden">
            <div class="position-relative z-2">
                <h2 class="fw-bold mb-3">Siap Mencari Kost Impianmu?</h2>
                <p class="mb-4 opacity-75">Jangan ragu lagi, temukan tempat tinggal impian di sekitar kampus sekarang juga.</p>
                <a href="index.php" class="btn btn-primary btn-lg rounded-pill px-5 fw-bold shadow">Cari Kost Sekarang</a>
            </div>
            <div class="position-absolute top-0 start-0 translate-middle rounded-circle bg-secondary opacity-25" style="width: 300px; height: 300px;"></div>
            <div class="position-absolute bottom-0 end-0 translate-middle-x rounded-circle bg-primary opacity-25" style="width: 200px; height: 200px;"></div>
        </div>
    </div>

    <div class="container my-5 py-5">
        <div class="text-center mb-5 fade-in-up delay-2">
            <h6 class="text-primary fw-bold text-uppercase">Tim Pengembang</h6>
            <h2 class="fw-bold">Di Balik Layar RadenStay</h2>
            <p class="text-muted">Mahasiswa Informatika - Fakultas Teknologi Informasi UNU Yogyakarta</p>
        </div>

        <div class="row justify-content-center g-4">

            <div class="col-md-6 col-lg-4 fade-in-up delay-3">
                <div class="team-card">
                    <div class="team-avatar">
                        <img src="https://ui-avatars.com/api/?name=Nailul+Ashfiya&background=0d6efd&color=fff" class="w-100 h-100 rounded-circle" alt="Foto">
                    </div>
                    <h5 class="fw-bold mb-1">Nailul Ashfiya</h5>
                    <p class="team-role">231111003</p>
                    <p class="small text-muted">Tim Pengembang</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4 fade-in-up delay-3">
                <div class="team-card">
                    <div class="team-avatar">
                        <img src="https://ui-avatars.com/api/?name=Kholid+Ramadhan&background=0dcaf0&color=fff" class="w-100 h-100 rounded-circle" alt="Foto">
                    </div>
                    <h5 class="fw-bold mb-1">M. Kholid Ramadhan</h5>
                    <p class="team-role">231111017</p>
                    <p class="small text-muted">Tim Pengembang</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4 fade-in-up delay-3">
                <div class="team-card">
                    <div class="team-avatar">
                        <img src="https://ui-avatars.com/api/?name=Malikhatus+Saniyah&background=6610f2&color=fff" class="w-100 h-100 rounded-circle" alt="Foto">
                    </div>
                    <h5 class="fw-bold mb-1">Malikhatus Saniyah</h5>
                    <p class="team-role">231111024</p>
                    <p class="small text-muted">Tim Pengembang</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4 fade-in-up delay-4">
                <div class="team-card border-primary">
                    <!-- 👆 style="border: 2px solid #0d6efd;" -->
                    <!-- <div class="position-absolute top-0 end-0 bg-primary text-white px-3 py-1 rounded-bl-3 small" style="border-bottom-left-radius: 10px;">Dev</div> -->
                    <div class="team-avatar">
                        <img src="https://ui-avatars.com/api/?name=Muhammad+Muhsin&background=fd7e14&color=fff" class="w-100 h-100 rounded-circle" alt="Foto">
                    </div>
                    <h5 class="fw-bold mb-1">Muhammad Muhsin</h5>
                    <p class="team-role">231111034</p>
                    <p class="small text-muted">Tim Pengembang</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4 fade-in-up delay-4">
                <div class="team-card">
                    <div class="team-avatar">
                        <img src="https://ui-avatars.com/api/?name=Raffi+Fadhila&background=20c997&color=fff" class="w-100 h-100 rounded-circle" alt="Foto">
                    </div>
                    <h5 class="fw-bold mb-1">M. Raffi Fadhila</h5>
                    <p class="team-role">231111068</p>
                    <p class="small text-muted">Tim Pengembang</p>
                </div>
            </div>

        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>