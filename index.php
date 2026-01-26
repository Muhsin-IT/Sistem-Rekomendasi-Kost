<?php
session_start();
// Tidak perlu query data di sini lagi, semua lewat AJAX
$lat_unu = -7.787861880324053;
$long_unu = 110.33049620439317;
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
    <style>
        /* CSS HERO & SEARCH TETAP SAMA */
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

        /* LAYOUT & MAP */
        .split-container {
            display: flex;
            flex-direction: column;
            width: 100%;
            overflow-x: hidden;
        }

        .list-area {
            background: #fff;
            padding: 15px;
            width: 100% !important;
            height: auto;
            border-right: none;
        }

        .map-wrapper-desktop {
            display: none;
            background: #eee;
            position: relative;
        }

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

        .resizer {
            width: 24px;
            background-color: #f8f9fa;
            border-left: 1px solid #dee2e6;
            border-right: 1px solid #dee2e6;
            cursor: col-resize;
            display: none;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 9999 !important;
            height: 100%;
            align-self: stretch;
            flex-shrink: 0;
        }

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

        /* CARD STYLES */
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

        /* CHIP-STYLE BUTTONS (override Bootstrap btn-group for this container) */
        div.btn-group.w-100.shadow-sm[role="group"] {
            display: flex;
            gap: 8px;
            padding: 6px;
            background: transparent;
            border-radius: 999px;
            align-items: center;
            justify-content: flex-start;
            flex-wrap: wrap;
        }

        /* Pastikan selector lebih spesifik agar meng-override bootstrap */
        div.btn-group.w-100.shadow-sm[role="group"]>.btn {
            border-radius: 999px;
            background: #ffffff;
            color: #0d6efd;
            border: 1px solid #0d6efd;
            padding: 6px 14px;
            box-shadow: none;
            transition: background-color 0.18s ease, color 0.18s ease, transform 0.08s, box-shadow 0.18s;
            margin: 0;
            flex: none;
            white-space: nowrap;
        }

        /* Jaga jarak antar tombol (override bootstrap -1px) */
        div.btn-group.w-100.shadow-sm[role="group"]>.btn+.btn {
            margin-left: 8px;
        }

        /* Hover lembut */
        div.btn-group.w-100.shadow-sm[role="group"]>.btn:not(.active):hover {
            background: rgba(13, 110, 253, 0.06);
            transform: translateY(-1px);
        }

        /* Fokus aksesibilitas */
        div.btn-group.w-100.shadow-sm[role="group"]>.btn:focus {
            outline: none;
            box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.08);
        }

        /* Tombol aktif */
        div.btn-group.w-100.shadow-sm[role="group"]>.btn.active,
        div.btn-group.w-100.shadow-sm[role="group"]>.btn:active {
            background: #0d6efd;
            color: #ffffff !important;
            border-color: #0d6efd;
            box-shadow: 0 8px 22px rgba(13, 110, 253, 0.16);
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
                        <form action="search" method="GET" class="d-flex align-items-center">
                            <i class="bi bi-search text-muted ms-3 fs-5"></i>
                            <input class="form-control search-input ps-3" type="search" placeholder="Cari nama kost..." name="keyword" required>
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
                <div class="d-flex flex-column gap-2 mb-3">
                    <h6 class="fw-bold mb-0 text-secondary">Rekomendasi Kost</h6>
                    <div class="btn-group w-100 shadow-sm" role="group">
                        <button type="button" class="btn btn-outline-primary btn-sm active" onclick="loadKost('terbaru', this)">Terbaru</button>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="loadKost('termurah', this)">Termurah</button>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="loadKost('ai', this)">
                            <i class="bi bi-stars"></i> Rekomendasi AI
                        </button>
                    </div>
                </div>

                <div id="kost-list-container" class="d-flex flex-column gap-3">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 small text-muted">Memuat data kost...</p>
                    </div>
                </div>
            </div>

            <div class="resizer" id="dragHandle"><i class="bi bi-chevron-right text-secondary small" style="font-size: 10px;"></i></div>

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
        const latUNU = <?= $lat_unu ?>;
        const longUNU = <?= $long_unu ?>;
        let dataKost = []; // Data akan diisi oleh AJAX
        let userLat = null;
        let userLong = null;

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
            iconSize: [50, 55],
            iconAnchor: [25, 55],
            popupAnchor: [0, -55]
        });
        L.marker([latUNU, longUNU], {
            icon: unuIcon
        }).addTo(map).bindPopup("<b>Kampus UNU</b><br>Titik Awal");

        const markerIconDefault = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
            iconSize: [20, 33],
            iconAnchor: [10, 33],
            popupAnchor: [0, -30],
            shadowSize: [33, 33]
        });
        const markerIconActive = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
            iconSize: [30, 50],
            iconAnchor: [15, 50],
            popupAnchor: [0, -45],
            shadowSize: [50, 50]
        });

        let routingControl = null;
        let markers = [];
        let activeIndex = -1;

        // --- FUNGSI AJAX LOAD KOST ---
        function loadKost(filter, btn) {
            // Update UI Tombol
            if (btn) {
                document.querySelectorAll('.btn-group .btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            }

            const container = document.getElementById('kost-list-container');
            container.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';

            let url = `ajax_get_kost?filter=${filter}`;
            if (userLat && userLong) url += `&lat=${userLat}&long=${userLong}`;

            fetch(url)
                .then(response => response.json())
                .then(json => {
                    // 1. Update List HTML
                    container.innerHTML = json.html;

                    // 2. Update Data & Marker Peta
                    dataKost = json.map_data;
                    rebuildMarkers();
                })
                .catch(err => {
                    console.error(err);
                    container.innerHTML = '<p class="text-danger text-center">Gagal memuat data.</p>';
                });
        }

        // --- FUNGSI REBUILD MARKERS ---
        function rebuildMarkers() {
            // Hapus marker lama
            markers.forEach(m => map.removeLayer(m));
            markers = [];

            // Tambah marker baru sesuai data AJAX
            dataKost.forEach((k, index) => {
                let marker = L.marker([k.latitude, k.longitude], {
                    icon: markerIconDefault
                }).addTo(map);

                marker.on('click', () => fokusKeKost(k.latitude, k.longitude, index));
                marker.on('mouseover', () => marker.openPopup());
                marker.on('mouseout', () => {
                    if (activeIndex !== index) marker.closePopup();
                });

                marker.bindPopup(`<div class="text-center pt-2"><h6 class="fw-bold mb-1">${k.nama_kost}</h6><span class="badge bg-primary">${k.harga_format}</span></div>`);
                markers[index] = marker;
            });
        }

        // --- INIT SAAT HALAMAN LOAD ---
        document.addEventListener("DOMContentLoaded", function() {
            // Coba ambil lokasi dulu
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition((pos) => {
                    userLat = pos.coords.latitude;
                    userLong = pos.coords.longitude;
                    loadKost('terbaru'); // Load dengan GPS
                }, (err) => {
                    console.log("GPS off");
                    loadKost('terbaru'); // Load tanpa GPS
                });
            } else {
                loadKost('terbaru');
            }

            // Script Resizer (Sama seperti sebelumnya)
            setupResizer();
        });

        // --- FUNGSI INTERAKSI PETA (SAMA KAYA SEBELUMNYA) ---
        function handleCardClick(index, lat, lng, event) {
            if (event && (event.target.id === 'map' || event.target.closest('#map') || event.target.closest('.leaflet-control') || event.target.closest('.leaflet-popup'))) return;
            fokusKeKost(lat, lng, index);
        }

        function fokusKeKost(destLat, destLng, index) {
            const isMobile = window.innerWidth < 768;
            const mapEl = document.getElementById('map');
            const routeBox = document.getElementById('route-info');

            document.querySelectorAll('.card-kost').forEach(c => c.classList.remove('active'));
            const card = document.getElementById(`card-${index}`);
            if (card) card.classList.add('active');

            markers.forEach(m => m.setIcon(markerIconDefault));
            if (markers[index]) markers[index].setIcon(markerIconActive);

            if (isMobile) {
                if (activeIndex === index && mapEl.parentElement.id !== 'desktop-map-container') {
                    document.getElementById('desktop-map-container').appendChild(mapEl);
                    document.getElementById('desktop-map-container').appendChild(routeBox);
                    document.querySelectorAll('.mobile-map-placeholder').forEach(el => el.classList.remove('active'));
                    activeIndex = -1;
                    return;
                }
                document.querySelectorAll('.mobile-map-placeholder').forEach(el => el.classList.remove('active'));
                const targetContainer = document.getElementById(`mobile-map-target-${index}`);
                if (targetContainer) {
                    targetContainer.appendChild(mapEl);
                    targetContainer.appendChild(routeBox);
                    targetContainer.classList.add('active');
                    setTimeout(() => card.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    }), 50);
                }
            } else {
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
            setTimeout(() => map.invalidateSize(), 100);
            map.flyTo([destLat, destLng], 15, {
                duration: 1.0
            });
            setTimeout(() => {
                if (markers[index]) markers[index].openPopup();
            }, 1100);

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
            }).on('routesfound', function(e) {
                var summary = e.routes[0].summary;
                document.getElementById('route-info').style.display = 'block';
                document.getElementById('route-details').innerHTML = `Jarak: <b>${(summary.totalDistance / 1000).toFixed(1)} km</b> • Waktu: <b>${Math.round(summary.totalTime / 60)} mnt</b>`;
                setTimeout(() => {
                    if (markers[index]) markers[index].openPopup();
                }, 200);
            }).addTo(map);
        }

        function setupResizer() {
            const resizer = document.getElementById('dragHandle');
            const leftSide = document.querySelector('.list-area');
            const container = document.querySelector('.split-container');
            let x = 0;
            let leftWidth = 0;

            if (resizer) {
                const startDrag = function(e) {
                    if (!leftSide || !container) return;
                    x = (e.type === 'touchstart') ? e.touches[0].clientX : e.clientX;
                    leftWidth = leftSide.getBoundingClientRect().width;
                    resizer.classList.add('dragging');
                    document.body.style.cursor = 'col-resize';
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
                const onDrag = function(e) {
                    if (!leftSide || !container) return;
                    let clientX = (e.type === 'touchmove') ? e.touches[0].clientX : e.clientX;
                    let newLeftWidth = ((leftWidth + (clientX - x)) * 100) / container.getBoundingClientRect().width;
                    if (newLeftWidth < 20) newLeftWidth = 20;
                    if (newLeftWidth > 75) newLeftWidth = 75;
                    leftSide.style.width = `${newLeftWidth}%`;
                    leftSide.style.flexBasis = `${newLeftWidth}%`;
                    if (typeof map !== 'undefined') map.invalidateSize();
                };
                const stopDrag = function() {
                    resizer.classList.remove('dragging');
                    document.body.style.cursor = '';
                    document.getElementById('map').style.pointerEvents = 'auto';
                    document.removeEventListener('mousemove', onDrag);
                    document.removeEventListener('mouseup', stopDrag);
                    document.removeEventListener('touchmove', onDrag);
                    document.removeEventListener('touchend', stopDrag);
                };
                resizer.addEventListener('mousedown', startDrag);
                resizer.addEventListener('touchstart', startDrag, {
                    passive: false
                });
            }
        }
    </script>
</body>

</html>