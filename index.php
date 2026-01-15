<?php
session_start();
include 'koneksi.php';

// KOORDINAT UNU
$lat_unu = -7.787861880324053;
$long_unu = 110.33049620439317;

// FUNGSI HITUNG JARAK
function hitungJarak($lat1, $lon1, $lat2, $lon2)
{
    if (!$lat1 || !$lon1) return 0;
    $theta = $lon1 - $lon2;
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
    $dist = acos($dist);
    $dist = rad2deg($dist);
    return round($dist * 60 * 1.1515 * 1.609344, 1);
}

// QUERY DATA
$query = "SELECT k.*, 
          (SELECT MIN(harga_per_bulan) FROM kamar WHERE id_kost = k.id_kost) as harga_min,
          (SELECT nama_file FROM galeri WHERE id_kost = k.id_kost AND kategori_foto = 'Tampak Depan' LIMIT 1) as foto_depan,
          (SELECT AVG(rating) FROM review WHERE id_kost = k.id_kost) as rating_avg
          FROM kost k
          WHERE k.latitude != 0 AND k.longitude != 0
          ORDER BY k.id_kost DESC";
$result = mysqli_query($conn, $query);

$data_map = [];
while ($row = mysqli_fetch_assoc($result)) {
    $row['foto_tampil']  = $row['foto_depan'] ? "assets/img/galeri/" . $row['foto_depan'] : "https://via.placeholder.com/400x250?text=No+Image";
    $row['harga_format'] = $row['harga_min'] ? "Rp " . number_format($row['harga_min'], 0, ',', '.') : "Penuh/Hubungi";
    $row['rating_tampil'] = $row['rating_avg'] ? round($row['rating_avg'], 1) : 0;
    $row['jarak_kampus']  = hitungJarak($lat_unu, $long_unu, $row['latitude'], $row['longitude']);
    $data_map[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/img/logo/persegi.webp">
    <title>RadenStay - Info Kost UNU Jogja</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
    <link rel="stylesheet" href="style.css">

    <!-- <style>
        /* HERO SECTION (Style Lama) */
        .hero-section {
            background: linear-gradient(180deg, #ffffff 0%, #eef2f7 100%);
            padding: 3rem 0;
            border-bottom: 1px solid #e1e5eb;
        }

        .hero-title {
            font-weight: 800;
            color: #2c3e50;
            font-size: calc(1.8rem + 1.5vw);
        }

        .search-container {
            background: white;
            padding: 8px;
            border-radius: 50px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #eee;
        }

        .search-input {
            border: none;
            box-shadow: none !important;
        }

        .search-btn {
            background-color: #fd7e14;
            border: none;
            padding: 10px 25px;
            border-radius: 30px;
        }

        /* SPLIT VIEW LAYOUT */
        .split-container {
            display: flex;
            flex-direction: column;
            /* Mobile Default */
            height: auto;
        }

        /* AREA LIST (KIRI) */
        .list-area {
            width: 40%;
            height: 100%;
            overflow-y: auto;
            /* Hapus resize: horizontal; */
            border-right: none;
            /* Border dipindah ke resizer */
        }

        .resizer {
            width: 18px;
            /* Lebar area geser */
            background-color: #f8f9fa;
            border-left: 1px solid #dee2e6;
            border-right: 1px solid #dee2e6;
            cursor: col-resize;
            /* Cursor berubah jadi panah kiri-kanan */
            display: none;
            /* Hidden di Mobile */
            align-items: center;
            justify-content: center;
            z-index: 10;
            user-select: none;
            transition: background 0.2s;
        }

        .resizer:hover,
        .resizer.dragging {
            background-color: #e2e6ea;
            /* Warna saat di-hover/geser */
        }

        /* AREA PETA (KANAN - DESKTOP) */
        .map-wrapper-desktop {
            display: none;
            /* Hidden di Mobile */
            height: 500px;
            background: #eee;
            position: relative;
        }

        /* WADAH PETA MOBILE (Di dalam Card) */
        .mobile-map-placeholder {
            width: 100%;
            height: 0;
            transition: height 0.3s;
            overflow: hidden;
        }

        .mobile-map-placeholder.active {
            height: 350px;
            /* Tinggi Peta di Mobile saat muncul */
            margin-top: 10px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        /* MEDIA QUERY DESKTOP */
        @media (min-width: 768px) {

            .resizer {
                display: flex;
            }

            .split-container {
                flex-direction: row;
                height: calc(100vh - 80px);
                /* Full height minus navbar */
                overflow: hidden;
            }

            .list-area {
                width: 60%;
                /* Lebar Awal */
                height: 100%;
                overflow-y: auto;
                border-right: 1px solid #ddd;

                /* FITUR RESIZABLE (BISA DIGESER) */
                resize: horizontal;
                min-width: 300px;
                max-width: 90%;
            }

            .map-wrapper-desktop {
                display: block;
                /* Muncul di Desktop */
                flex-grow: 1;
                /* Mengisi sisa ruang */
                height: 100%;
            }

            /* Sembunyikan placeholder mobile di desktop agar layout tidak loncat */
            .mobile-map-placeholder {
                display: none !important;
            }
        }

        /* --- UPDATE STYLE FOTO KOST --- */
        .kost-img-fix {
            width: 100%;
            height: 100%;
            object-fit: cover;
            /* KUNCI: Agar foto tidak gepeng (dicrop otomatis) */
            border-radius: 8px;
            display: block;

            /* TAMPILAN HP (Layar Kecil): KOTAK (1:1) */
            aspect-ratio: 1 / 1;
        }

        /* TAMPILAN LAPTOP/PC (Layar Besar): PERSEGI PANJANG (4:3) */
        @media (min-width: 768px) {
            .kost-img-fix {
                aspect-ratio: 4 / 3;
                /* Ubah jadi 16/9 jika ingin lebih lebar */
                height: 100%;
                /* Mengikuti tinggi card */
                min-height: 130px;
                /* Jaga agar tidak terlalu pendek */
            }
        }

        /* ITEM CARD */
        .card-kost {
            cursor: pointer;
            border: 1px solid #f0f0f0;
            border-radius: 12px;
            transition: 0.2s;
        }

        .card-kost:hover,
        .card-kost.active {
            border-color: #0d6efd;
            background-color: #f8fbff;
        }

        .badge-gender {
            font-size: 0.7rem;
            padding: 3px 8px;
            border-radius: 4px;
        }

        .bg-putra {
            background: #e7f1ff;
            color: #0d6efd;
        }

        .bg-putri {
            background: #ffeef0;
            color: #dc3545;
        }

        .bg-campur {
            background: #e6fffa;
            color: #198754;
        }

        /* ROUTE INFO BOX */
        .route-info-box {
            position: absolute;
            bottom: 10px;
            left: 10px;
            z-index: 9999;
            background: white;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            display: none;
            min-width: 200px;
        }

        .leaflet-routing-container {
            display: none !important;
        }
    </style> -->
    <style>
        /* HERO SECTION */
        .hero-section {
            background: linear-gradient(180deg, #ffffff 0%, #eef2f7 100%);
            padding: 3rem 0;
            border-bottom: 1px solid #e1e5eb;
        }

        .hero-title {
            font-weight: 800;
            color: #2c3e50;
            font-size: calc(1.8rem + 1.5vw);
        }

        .search-container {
            background: white;
            padding: 8px;
            border-radius: 50px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #eee;
        }

        .search-input {
            border: none;
            box-shadow: none !important;
        }

        .search-btn {
            background-color: #fd7e14;
            border: none;
            padding: 10px 25px;
            border-radius: 30px;
        }

        /* LAYOUT UTAMA */
        .split-container {
            display: flex;
            flex-direction: column;
            width: 100%;
            overflow-x: hidden;
        }

        /* AREA LIST (KIRI) */
        .list-area {
            background: #fff;
            padding: 15px;
            width: 100% !important;
            height: auto;
            border-right: none;
        }

        /* AREA PETA (KANAN - DESKTOP) */
        .map-wrapper-desktop {
            display: none;
            background: #eee;
            position: relative;
        }

        /* WADAH PETA MOBILE */
        .mobile-map-placeholder {
            width: 100%;
            height: 0;
            transition: height 0.3s;
            overflow: hidden;
            position: relative;
        }

        .mobile-map-placeholder.active {
            height: 350px;
            margin-top: 10px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        /* --- RESIZER (BATANG GESER) - PERBAIKAN Z-INDEX --- */
        .resizer {
            width: 24px;
            /* Sedikit diperlebar agar mudah kena mouse/jari */
            background-color: #f8f9fa;
            border-left: 1px solid #dee2e6;
            border-right: 1px solid #dee2e6;
            cursor: col-resize;
            display: none;
            align-items: center;
            justify-content: center;

            /* KUNCI PERBAIKAN: Layer Paling Atas */
            position: relative;
            z-index: 9999 !important;
            height: 100%;
            align-self: stretch;
            flex-shrink: 0;
        }

        .resizer:hover,
        .resizer.dragging {
            background-color: #f8f9fa;
            color: inherit;
        }

        /* --- TAMPILAN DESKTOP (Layar > 768px) --- */
        @media (min-width: 768px) {
            .split-container {
                flex-direction: row;
                height: calc(100vh - 80px);
                overflow: hidden;
            }

            .list-area {
                width: 40% !important;
                height: 100%;
                overflow-y: auto;
                flex-shrink: 0;
            }

            .map-wrapper-desktop {
                display: block;
                flex-grow: 1;
                height: 100%;
            }

            .resizer {
                display: flex;
            }

            .mobile-map-placeholder {
                display: none !important;
            }
        }

        /* KARTU & LAINNYA */
        .card-kost {
            cursor: pointer;
            border: 1px solid #f0f0f0;
            border-radius: 12px;
            transition: 0.2s;
        }

        .card-kost:hover,
        .card-kost.active {
            border-color: #0d6efd;
            background-color: #f8fbff;
        }

        .badge-gender {
            font-size: 0.7rem;
            padding: 3px 8px;
            border-radius: 4px;
        }

        .bg-putra {
            background: #e7f1ff;
            color: #0d6efd;
        }

        .bg-putri {
            background: #ffeef0;
            color: #dc3545;
        }

        .bg-campur {
            background: #e6fffa;
            color: #198754;
        }

        .kost-img-fix {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
            display: block;
            aspect-ratio: 1 / 1;
        }

        @media (min-width: 768px) {
            .kost-img-fix {
                aspect-ratio: 4 / 3;
                height: 100%;
                min-height: 130px;
            }
        }
    </style>
</head>

<body>

    <?php include 'navbar.php'; ?>

    <header class="hero-section text-center text-lg-start">
        <div class="container py-2">
            <div class="row justify-content-center align-items-center gx-lg-5">
                <div class="col-lg-7 order-2 order-lg-1">
                    <h1 class="hero-title mb-3">Hunian Nyaman Mahasiswa <span class="text-primary">UNU Jogja</span></h1>
                    <p class="lead text-muted mb-4" style="font-weight: 400;">Temukan kost strategis, fasilitas lengkap, dan harga yang pas di kantong mahasiswa.</p>

                    <div class="search-container d-inline-block w-100" style="max-width: 550px;">
                        <form action="search.php" method="GET" class="d-flex align-items-center">
                            <i class="bi bi-search text-muted ms-3 fs-5"></i>
                            <input class="form-control search-input ps-3" type="search" placeholder="Cari nama kost, fasilitas (AC, WiFi)..." name="keyword" required>
                            <button class="btn btn-warning text-white fw-bold search-btn" type="submit">CARI</button>
                        </form>
                    </div>

                    <div class="mt-4 text-muted small">
                        <i class="bi bi-info-circle me-1"></i> Populer: "Kost Putra", "Parkiran Mobil", "Kamar Mandi Dalam"
                    </div>
                </div>
                <div class="col-lg-5 order-1 order-lg-2 mb-4 mb-lg-0 d-none d-lg-block">
                    <img src="https://img.freepik.com/free-vector/college-campus-concept-illustration_114360-1050.jpg" alt="Ilustrasi" class="img-fluid rounded-4 shadow-sm" style="opacity: 0.9;">
                </div>
            </div>
        </div>
    </header>

    <div class="container-fluid px-0 border-top">
        <div class="split-container">

            <div class="list-area">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0 text-secondary">Rekomendasi Kost Terbaru</h6>
                    <a href="rekomendasi_saw.php" class="btn btn-sm btn-outline-primary rounded-pill"><i class="bi bi-stars"></i> Rekomendasi AI</a>
                </div>

                <div class="d-flex flex-column gap-3">
                    <?php foreach ($data_map as $index => $row): ?>
                        <div class="card card-kost shadow-sm p-2" id="card-<?= $index ?>" onclick="handleCardClick(<?= $index ?>, <?= $row['latitude'] ?>, <?= $row['longitude'] ?>, event)">
                            <div class="row g-0 align-items-center">
                                <div class="col-4 col-md-5"> <img src="<?= $row['foto_tampil'] ?>" class="kost-img-fix" alt="Foto Kost">
                                </div>

                                <div class="col-8 col-md-7">
                                    <div class="card-body p-2 ps-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="badge-gender <?= ($row['jenis_kost'] == 'Putra' ? 'bg-putra' : ($row['jenis_kost'] == 'Putri' ? 'bg-putri' : 'bg-campur')) ?>"><?= $row['jenis_kost'] ?></span>
                                            <?php if ($row['rating_tampil'] > 0): ?>
                                                <small class="text-warning fw-bold"><i class="bi bi-star-fill"></i> <?= $row['rating_tampil'] ?></small>
                                            <?php endif; ?>
                                        </div>

                                        <h6 class="fw-bold mb-1 text-truncate"><?= $row['nama_kost'] ?></h6>

                                        <small class="text-muted d-block mb-2" style="font-size: 0.8rem;">
                                            <i class="bi bi-geo-alt-fill text-danger"></i> <?= substr($row['alamat'], 0, 15) ?>...
                                            <b>(<?= $row['jarak_kampus'] ?> km)</b>
                                        </small>

                                        <div class="d-flex justify-content-between align-items-end">
                                            <div>
                                                <small class="text-muted" style="font-size: 0.65rem">Mulai dari</small><br>
                                                <span class="text-primary fw-bold" style="font-size: 0.9rem;"><?= $row['harga_format'] ?></span>
                                            </div>
                                            <a href="detail_kost.php?id=<?= $row['id_kost'] ?>" class="btn btn-sm btn-primary rounded-pill py-0 px-2" style="font-size: 0.75rem">Detail</a>
                                        </div>

                                    </div>
                                </div>
                            </div>

                            <div id="mobile-map-target-<?= $index ?>" class="mobile-map-placeholder"></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <!-- ============================================================================================================= -->

            <div class="resizer" id="dragHandle">
                <i class="bi bi-chevron-right text-secondary small" style="font-size: 10px;"></i>
            </div>
            <!-- ============================================================================================================= -->
            <div class="map-wrapper-desktop" id="desktop-map-container">
                <div id="map" style="width: 100%; height: 100%;"></div>

                <div id="route-info" class="route-info-box">
                    <h6 class="fw-bold mb-1"><i class="bi bi-cursor-fill text-primary"></i> Rute & Estimasi</h6>
                    <div id="route-details" class="small text-dark"></div>
                </div>
            </div>

        </div>
        <?php include 'footer.php'; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
    <script>
        const dataKost = <?= json_encode($data_map) ?>;
        const latUNU = <?= $lat_unu ?>;
        const longUNU = <?= $long_unu ?>;

        // --- SETUP PETA ---
        const map = L.map('map', {
            zoomControl: false
        }).setView([latUNU, longUNU], 14);
        L.control.zoom({
            position: 'topright'
        }).addTo(map);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: 'RadenStay'
        }).addTo(map);

        const unuIcon = L.icon({
            iconUrl: 'assets/img/logo/pinunu3.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
            iconSize: [50, 55],
            iconAnchor: [25, 55], // Tengah bawah icon (setengah lebar, tinggi penuh)
            popupAnchor: [0, -55], // Popup muncul di atas icon
            shadowSize: [60, 60], // Ukuran shadow
            shadowAnchor: [20, 60] // Posisi shadow
        });
        L.marker([latUNU, longUNU], {
            icon: unuIcon
        }).addTo(map).bindPopup("<b>Kampus UNU</b><br>Titik Awal");

        // BUAT 2 JENIS ICON: DEFAULT & ACTIVE
        const markerIconDefault = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
            iconSize: [20, 33], // Ukuran kecil
            iconAnchor: [10, 33],
            popupAnchor: [0, -30],
            shadowSize: [33, 33]
        });

        const markerIconActive = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
            iconSize: [30, 50], // Ukuran besar
            iconAnchor: [15, 50],
            popupAnchor: [0, -45],
            shadowSize: [50, 50]
        });

        let routingControl = null;
        let markers = [];
        let activeIndex = -1;

        // --- LOOP MARKER ---
        dataKost.forEach((k, index) => {
            let marker = L.marker([k.latitude, k.longitude], {
                icon: markerIconDefault // Set icon default
            }).addTo(map);

            marker.on('click', function() {
                fokusKeKost(k.latitude, k.longitude, index);
            });

            marker.on('mouseover', function() {
                marker.openPopup();
            });

            marker.on('mouseout', function() {
                if (activeIndex !== index) {
                    marker.closePopup();
                }
            });

            marker.bindPopup(`
            <div class="text-center pt-2">
                <h6 class="fw-bold mb-1">${k.nama_kost}</h6>
                <span class="badge bg-primary">${k.harga_format}</span>
            </div>
        `);
            markers[index] = marker;
        });

        // --- FUNGSI CLICK CARD (FIX BUG MOBILE MAP TERTUTUP) ---
        function handleCardClick(index, lat, lng, event) {
            // Cek apakah user mengklik PETA atau tombol di dalamnya?
            // Jika YA, hentikan fungsi ini agar kartu tidak tertutup
            if (event && (
                    event.target.id === 'map' ||
                    event.target.closest('#map') ||
                    event.target.closest('.leaflet-control') ||
                    event.target.closest('.leaflet-marker-icon') ||
                    event.target.closest('.leaflet-popup')
                )) {
                return; // Jangan lakukan apa-apa
            }

            fokusKeKost(lat, lng, index);
        }

        function fokusKeKost(destLat, destLng, index) {
            const isMobile = window.innerWidth < 768;
            const mapEl = document.getElementById('map');
            const routeBox = document.getElementById('route-info');

            // Reset Highlight Card
            document.querySelectorAll('.card-kost').forEach(c => c.classList.remove('active'));
            const card = document.getElementById(`card-${index}`);
            if (card) card.classList.add('active');

            // RESET SEMUA MARKER KE DEFAULT
            markers.forEach((m, i) => {
                if (m) m.setIcon(markerIconDefault);
            });

            // SET MARKER AKTIF JADI BESAR & BIRU
            if (markers[index]) {
                markers[index].setIcon(markerIconActive);
            }

            if (isMobile) {
                // Mobile Logic
                // Jika kartu yang sama diklik lagi -> Tutup (Toggle)
                if (activeIndex === index && mapEl.parentElement.id !== 'desktop-map-container') {
                    // Kembalikan ke desktop container (sembunyi)
                    document.getElementById('desktop-map-container').appendChild(mapEl);
                    document.getElementById('desktop-map-container').appendChild(routeBox);
                    document.querySelectorAll('.mobile-map-placeholder').forEach(el => el.classList.remove('active'));
                    activeIndex = -1;
                    return;
                }

                // Pindah Peta ke Bawah Card Baru
                document.querySelectorAll('.mobile-map-placeholder').forEach(el => el.classList.remove('active'));
                const targetContainer = document.getElementById(`mobile-map-target-${index}`);

                if (targetContainer) {
                    targetContainer.appendChild(mapEl);
                    targetContainer.appendChild(routeBox);
                    targetContainer.classList.add('active');
                    // Pastikan pengguna melihat peta yang baru dibuka di mobile
                    setTimeout(() => {
                        card.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }, 50);
                }
            } else {
                // Desktop Logic
                if (card) card.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });

                const desktopContainer = document.getElementById('desktop-map-container');
                if (!desktopContainer.contains(mapEl)) {
                    desktopContainer.appendChild(mapEl);
                    desktopContainer.appendChild(routeBox);
                }
            }

            activeIndex = index;

            // Refresh Map Size & Routing
            setTimeout(() => {
                map.invalidateSize();
            }, 100);

            map.flyTo([destLat, destLng], 15, {
                duration: 1.0
            });

            // Buka popup marker setelah peta selesai animasi
            setTimeout(() => {
                if (markers[index]) {
                    markers[index].openPopup();
                }
            }, 1100); // Sedikit lebih lama dari durasi flyTo

            if (routingControl) map.removeControl(routingControl);
            routingControl = L.Routing.control({
                    waypoints: [L.latLng(latUNU, longUNU), L.latLng(destLat, destLng)],
                    routeWhileDragging: false,
                    addWaypoints: false,
                    draggableWaypoints: false,
                    fitSelectedRoutes: false,
                    lineOptions: {
                        styles: [{
                            color: '#0d6efd',
                            opacity: 0.8,
                            weight: 6
                        }]
                    },
                    createMarker: function() {
                        return null;
                    },
                    show: false
                })
                .on('routesfound', function(e) {
                    var summary = e.routes[0].summary;
                    let jarak = (summary.totalDistance / 1000).toFixed(1);
                    let waktu = Math.round(summary.totalTime / 60);
                    routeBox.style.display = 'block';
                    document.getElementById('route-details').innerHTML = `Jarak: <b>${jarak} km</b> • Waktu: <b>${waktu} mnt</b>`;

                    // Pastikan popup tetap terbuka setelah routing selesai
                    setTimeout(() => {
                        if (markers[index]) {
                            markers[index].openPopup();
                        }
                    }, 200);
                })
                .addTo(map);
        }

        // --- SCRIPT RESIZER (MOUSE & TOUCH SUPPORT) ---
        document.addEventListener('DOMContentLoaded', function() {
            const resizer = document.getElementById('dragHandle');
            const leftSide = document.querySelector('.list-area');
            const container = document.querySelector('.split-container');
            let x = 0;
            let leftWidth = 0;

            if (resizer) {
                // 1. Fungsi Start Drag (Unified Mouse & Touch)
                const startDrag = function(e) {
                    if (!leftSide || !container) return;
                    // Deteksi input Mouse atau Touch
                    if (e.type === 'touchstart') {
                        x = e.touches[0].clientX;
                    } else {
                        e.preventDefault(); // Mencegah seleksi teks di desktop
                        x = e.clientX;
                    }

                    leftWidth = leftSide.getBoundingClientRect().width;

                    resizer.classList.add('dragging');
                    document.body.style.cursor = 'col-resize';

                    // Matikan interaksi peta agar iframe tidak mencuri event drag
                    document.getElementById('map').style.pointerEvents = 'none';

                    if (e.type === 'touchstart') {
                        document.addEventListener('touchmove', onDrag, {
                            passive: false
                        });
                        document.addEventListener('touchend', stopDrag);
                    } else {
                        document.addEventListener('mousemove', onDrag);
                        document.addEventListener('mouseup', stopDrag);
                    }
                };

                // 2. Fungsi Move Drag
                const onDrag = function(e) {
                    if (!leftSide || !container) return;
                    let clientX;
                    if (e.type === 'touchmove') {
                        // e.preventDefault(); // Mencegah scroll layar saat geser di HP
                        clientX = e.touches[0].clientX;
                    } else {
                        clientX = e.clientX;
                    }

                    const dx = clientX - x;
                    const containerWidth = container.getBoundingClientRect().width;

                    let newLeftWidth = ((leftWidth + dx) * 100) / containerWidth;

                    // Batas Min 20% & Max 75%
                    if (newLeftWidth < 20) newLeftWidth = 20;
                    if (newLeftWidth > 75) newLeftWidth = 75;

                    leftSide.style.width = `${newLeftWidth}%`;
                    leftSide.style.flexBasis = `${newLeftWidth}%`;
                    // Penting: Render ulang peta saat ukuran berubah
                    if (typeof map !== 'undefined') map.invalidateSize();
                };

                // 3. Fungsi Stop Drag
                const stopDrag = function() {
                    resizer.classList.remove('dragging');
                    document.body.style.cursor = '';
                    document.getElementById('map').style.pointerEvents = 'auto'; // Hidupkan peta lagi

                    document.removeEventListener('mousemove', onDrag);
                    document.removeEventListener('mouseup', stopDrag);
                    document.removeEventListener('touchmove', onDrag);
                    document.removeEventListener('touchend', stopDrag);
                };

                // Pasang Listener
                resizer.addEventListener('mousedown', startDrag);
                resizer.addEventListener('touchstart', startDrag, {
                    passive: false
                });
            }

            // Fix Layout saat Resize Window
            window.addEventListener('resize', () => {
                const isMobile = window.innerWidth < 768;
                const mapEl = document.getElementById('map');
                const routeBox = document.getElementById('route-info');
                const desktopContainer = document.getElementById('desktop-map-container');

                if (isMobile) {
                    leftSide.style.width = ''; // Reset ke full width di mobile
                } else {
                    if (!desktopContainer.contains(mapEl)) {
                        desktopContainer.appendChild(mapEl);
                        desktopContainer.appendChild(routeBox);
                        document.querySelectorAll('.mobile-map-placeholder').forEach(el => el.classList.remove('active'));
                        map.invalidateSize();
                    }
                }
            });
        });
    </script>

</body>

</html>