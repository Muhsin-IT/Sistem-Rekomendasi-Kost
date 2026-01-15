<?php
session_start();
include 'koneksi.php';

$keyword = isset($_GET['keyword']) ? mysqli_real_escape_string($conn, $_GET['keyword']) : '';

// INISIALISASI VARIABEL AGAR TIDAK ERROR SAAT KOSONG
$data_untuk_python = [];
$data_kost_lengkap = [];
$hasil_ranking = [];
$is_ai_active = false;

// ==================================================================================
// 1. QUERY PENCARIAN CANGGIH (TERMASUK FASILITAS KAMAR)
// ==================================================================================
// Logika: Join Kost -> Kamar -> Fasilitas (Cek apakah fasilitas ada di Kost ATAU di Kamar)

$query = "SELECT DISTINCT k.* FROM kost k
          -- 1. Join Kamar (Agar bisa cek fasilitas di dalam kamar)
          LEFT JOIN kamar km ON k.id_kost = km.id_kost
          
          -- 2. Join Fasilitas (Cek Fasilitas Kost ATAU Fasilitas Kamar)
          LEFT JOIN rel_fasilitas rf ON (rf.id_kost = k.id_kost OR rf.id_kamar = km.id_kamar)
          LEFT JOIN master_fasilitas mf ON rf.id_master_fasilitas = mf.id_master_fasilitas
          
          -- 3. Join Peraturan (Cek Peraturan Kost ATAU Peraturan Kamar)
          LEFT JOIN rel_peraturan rp ON (rp.id_kost = k.id_kost OR rp.id_kamar = km.id_kamar)
          LEFT JOIN master_peraturan mp ON rp.id_master_peraturan = mp.id_master_peraturan
          
          WHERE 
            k.nama_kost LIKE '%$keyword%' OR 
            k.alamat LIKE '%$keyword%' OR
            mf.nama_fasilitas LIKE '%$keyword%' OR
            mp.nama_peraturan LIKE '%$keyword%'";

$result = mysqli_query($conn, $query);

// ==================================================================================
// 2. PERSIAPAN DATA UNTUK PYTHON (SAW)
// ==================================================================================

// Koordinat UNU Yogyakarta
$lat_unu = -7.787861880324053;
$long_unu = 110.33049620439317;

function hitungJarak($lat1, $lon1, $lat2, $lon2)
{
    if (!$lat1 || !$lon1) return 10;
    $theta = $lon1 - $lon2;
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
    $dist = acos($dist);
    $dist = rad2deg($dist);
    return round($dist * 60 * 1.1515 * 1.609344, 2);
}

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $id = $row['id_kost'];

        // C1: Harga
        $q_hrg = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MIN(harga_per_bulan) as min_harga FROM kamar WHERE id_kost='$id'"));
        $c1 = $q_hrg['min_harga'] ?? 10000000;

        // C2: Jarak
        $c2 = hitungJarak($row['latitude'], $row['longitude'], $lat_unu, $long_unu);

        // C3: Fasilitas (Total di Kost + Total di Kamar)
        // Kita hitung semua fasilitas unik yang terkait dengan kost ini
        $q_fas = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT id_master_fasilitas) as jum FROM rel_fasilitas WHERE id_kost='$id' OR id_kamar IN (SELECT id_kamar FROM kamar WHERE id_kost='$id')"));
        $c3 = $q_fas['jum'];

        // C4: Peraturan
        $q_per = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT id_master_peraturan) as jum FROM rel_peraturan WHERE id_kost='$id' OR id_kamar IN (SELECT id_kamar FROM kamar WHERE id_kost='$id')"));
        $c4 = $q_per['jum'];

        // C5 & C6: Akurasi & Rating
        $q_rev = mysqli_fetch_assoc(mysqli_query($conn, "SELECT AVG(skor_akurasi) as avg_c5, AVG(rating) as avg_c6 FROM review WHERE id_kost='$id'"));
        $c5 = $q_rev['avg_c5'] ?? 0;
        $c6 = $q_rev['avg_c6'] ?? 0;

        $data_untuk_python[] = [
            'id_kost' => $id,
            'C1' => $c1,
            'C2' => $c2,
            'C3' => $c3,
            'C4' => $c4,
            'C5' => $c5,
            'C6' => $c6
        ];

        $data_kost_lengkap[$id] = $row;
        $data_kost_lengkap[$id]['harga_tampil'] = $c1;
        $data_kost_lengkap[$id]['rating_tampil'] = $c6;
        $data_kost_lengkap[$id]['jarak_kampus'] = $c2;
    }

    // ==================================================================================
    // 3. KIRIM KE PYTHON API
    // ==================================================================================
    // GANTI URL NGROK DISINI
    $api_url = "http://127.0.0.1:5001/hitung-saw";

    $konfigurasi_kriteria = [
        "C1" => ["atribut" => "cost",    "bobot" => 0.25], // Harga
        "C2" => ["atribut" => "cost",    "bobot" => 0.15], // Jarak
        "C3" => ["atribut" => "benefit", "bobot" => 0.20], // Fasilitas
        "C4" => ["atribut" => "benefit", "bobot" => 0.10], // Peraturan
        "C5" => ["atribut" => "benefit", "bobot" => 0.10], // Akurasi
        "C6" => ["atribut" => "benefit", "bobot" => 0.20]  // Rating
    ];

    $payload_array = [
        'alternatif' => $data_untuk_python,  // Data kost (C1-C6)
        'kriteria'   => $konfigurasi_kriteria // Rumus bobot
    ];

    $payload = json_encode(['data_kost' => $payload_array]);


    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    // Timeout setelah 5 detik
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);


    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);


    if ($curl_error) {
        // Tampilkan error untuk debugging
        // echo "<div class='alert alert-danger'>DEBUG: Python Mati/Error - $curl_error</div>";
    } else {
        if ($http_code == 200) {
            $json_res = json_decode($response, true);

            // --- PERBAIKAN 3: BACA KUNCI 'data' (BUKAN 'hasil') ---
            if (isset($json_res['status']) && $json_res['status'] == 'success') {
                $hasil_ranking = $json_res['data']; // Python app.py mengembalikan 'data'
                $is_ai_active = true;
            }
        }
    }
    curl_close($ch);
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hasil Pencarian - RadenStay</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        :root {
            --nav-height: 64px;
        }

        body {
            background: #f8f9fa;
            overflow-x: hidden;
        }

        .search-bar-fixed {
            position: sticky;
            top: var(--nav-height);
            z-index: 1040;
            background: rgba(248, 249, 250, 0.95);
            backdrop-filter: blur(6px);
            border-bottom: 1px solid #e9ecef;
            padding: 0.75rem 0;
        }

        /* SPLIT VIEW LAYOUT */
        .split-container {
            display: flex;
            flex-direction: column;
            width: 100%;
            overflow-x: hidden;
        }

        .list-area {
            background: #f8f9fa;
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

        .resizer:hover,
        .resizer.dragging {
            background-color: #e2e6ea;
        }

        /* DESKTOP VIEW */
        @media (min-width: 768px) {
            .split-container {
                flex-direction: row;
                height: calc(100vh - var(--nav-height) - 60px);
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

        /* CARD STYLE */
        .card-kost {
            cursor: pointer;
            border: 1px solid #f0f0f0;
            border-radius: 12px;
            transition: 0.2s;
            background: white;
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

        @media (max-width: 576px) {
            :root {
                --nav-height: 56px;
            }
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="search-bar-fixed">
        <div class="container">
            <form class="input-group input-group-lg" method="GET" action="search.php">
                <input type="text" class="form-control" name="keyword" placeholder="Cari kost lagi..." value="<?= htmlspecialchars($keyword) ?>" />
                <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
            </form>
        </div>
    </div>

    <div class="container-fluid px-0">
        <div class="split-container">
            <div class="list-area">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h6 class="fw-bold mb-1">
                            Hasil: "<strong><?= htmlspecialchars($keyword) ?></strong>"
                        </h6>
                        <?php if ($is_ai_active): ?>
                            <small class="badge bg-success"><i class="bi bi-robot"></i> Python SAW Active</small>
                        <?php else: ?>
                            <small class="badge bg-secondary">Python API Offline</small>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (empty($data_kost_lengkap)): ?>
                    <div class="alert alert-warning text-center py-4">
                        <i class="bi bi-search display-4 text-muted mb-3 d-block"></i>
                        <h6>Kost tidak ditemukan.</h6>
                        <p class="text-muted small mb-0">Coba kata kunci lain atau <a href="index.php">kembali ke beranda</a>.</p>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                        <?php
                        $urutan_ids = [];
                        if (!empty($hasil_ranking)) {
                            foreach ($hasil_ranking as $r) $urutan_ids[] = $r['id_kost'];
                        } else {
                            $urutan_ids = array_keys($data_kost_lengkap);
                        }

                        foreach ($urutan_ids as $index => $id):
                            if (!isset($data_kost_lengkap[$id])) continue;
                            $k = $data_kost_lengkap[$id];

                            $qf = mysqli_query($conn, "SELECT nama_file FROM galeri WHERE id_kost='$id' AND kategori_foto='Tampak Depan' LIMIT 1");
                            $f = mysqli_fetch_assoc($qf);
                            $img = $f ? "assets/img/galeri/" . $f['nama_file'] : "https://via.placeholder.com/400x250?text=No+Image";
                        ?>
                            <div class="card card-kost shadow-sm p-2" id="card-<?= $index ?>" onclick="handleCardClick(<?= $index ?>, <?= $k['latitude'] ?>, <?= $k['longitude'] ?>, event)">
                                <div class="row g-0 align-items-center">
                                    <div class="col-4 col-md-5">
                                        <img src="<?= $img ?>" class="kost-img-fix" alt="Foto Kost">
                                    </div>

                                    <div class="col-8 col-md-7">
                                        <div class="card-body p-2 ps-3">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="badge-gender <?= ($k['jenis_kost'] == 'Putra' ? 'bg-putra' : ($k['jenis_kost'] == 'Putri' ? 'bg-putri' : 'bg-campur')) ?>"><?= $k['jenis_kost'] ?></span>
                                                <?php if ($k['rating_tampil'] > 0): ?>
                                                    <small class="text-warning fw-bold"><i class="bi bi-star-fill"></i> <?= round($k['rating_tampil'], 1) ?></small>
                                                <?php endif; ?>
                                            </div>

                                            <h6 class="fw-bold mb-1 text-truncate"><?= $k['nama_kost'] ?></h6>

                                            <small class="text-muted d-block mb-2" style="font-size: 0.8rem;">
                                                <i class="bi bi-geo-alt-fill text-danger"></i> <?= substr($k['alamat'], 0, 15) ?>...
                                                <b>(<?= $k['jarak_kampus'] ?> km)</b>
                                            </small>

                                            <div class="d-flex justify-content-between align-items-end">
                                                <div>
                                                    <small class="text-muted" style="font-size: 0.65rem">Mulai dari</small><br>
                                                    <span class="text-primary fw-bold" style="font-size: 0.9rem;">Rp <?= number_format($k['harga_tampil'], 0, ',', '.') ?></span>
                                                </div>
                                                <a href="detail_kost.php?id=<?= $id ?>" class="btn btn-sm btn-primary rounded-pill py-0 px-2" style="font-size: 0.75rem">Detail</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div id="mobile-map-target-<?= $index ?>" class="mobile-map-placeholder"></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="resizer" id="dragHandle">
                <i class="bi bi-chevron-right text-secondary small" style="font-size: 10px;"></i>
            </div>

            <div class="map-wrapper-desktop" id="desktop-map-container">
                <div id="map" style="width: 100%; height: 100%;"></div>
                <div id="route-info" class="route-info-box">
                    <h6 class="fw-bold mb-1"><i class="bi bi-cursor-fill text-primary"></i> Rute & Estimasi</h6>
                    <div id="route-details" class="small text-dark"></div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
    <script>
        const dataKost = <?= json_encode(array_values($data_kost_lengkap)) ?>;
        const latUNU = <?= $lat_unu ?>;
        const longUNU = <?= $long_unu ?>;

        // SETUP PETA
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
            iconUrl: 'https://cdn-icons-png.flaticon.com/512/1673/1673188.png',
            iconSize: [40, 40],
            popupAnchor: [0, -20]
        });
        L.marker([latUNU, longUNU], {
            icon: unuIcon
        }).addTo(map).bindPopup("<b>Kampus UNU</b><br>Titik Awal");

        // BUAT 2 JENIS ICON: DEFAULT & ACTIVE
        const markerIconDefault = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
            iconSize: [20, 33],
            iconAnchor: [10, 33],
            popupAnchor: [0, -30],
            shadowSize: [33, 33]
        });

        const markerIconActive = L.icon({
            iconUrl: 'assets/img/logo/pinunu3.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
            iconSize: [30, 50],
            iconAnchor: [15, 50],
            popupAnchor: [0, -45],
            shadowSize: [50, 50]
        });

        let routingControl = null;
        let markers = [];
        let activeIndex = -1;

        // LOOP MARKER
        dataKost.forEach((k, index) => {
            if (!k.latitude || !k.longitude) return;

            let marker = L.marker([k.latitude, k.longitude], {
                icon: markerIconDefault
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
                    <span class="badge bg-primary">Rp ${Number(k.harga_tampil).toLocaleString('id-ID')}</span>
                </div>
            `);
            markers[index] = marker;
        });

        function handleCardClick(index, lat, lng, event) {
            if (event && (
                    event.target.id === 'map' ||
                    event.target.closest('#map') ||
                    event.target.closest('.leaflet-control') ||
                    event.target.closest('.leaflet-marker-icon') ||
                    event.target.closest('.leaflet-popup')
                )) {
                return;
            }
            fokusKeKost(lat, lng, index);
        }

        function fokusKeKost(destLat, destLng, index) {
            const isMobile = window.innerWidth < 768;
            const mapEl = document.getElementById('map');
            const routeBox = document.getElementById('route-info');

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
                    setTimeout(() => {
                        card.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }, 50);
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

            setTimeout(() => {
                map.invalidateSize();
            }, 100);

            map.flyTo([destLat, destLng], 15, {
                duration: 1.0
            });

            setTimeout(() => {
                if (markers[index]) {
                    markers[index].openPopup();
                }
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
                })
                .on('routesfound', function(e) {
                    var summary = e.routes[0].summary;
                    let jarak = (summary.totalDistance / 1000).toFixed(1);
                    let waktu = Math.round(summary.totalTime / 60);
                    routeBox.style.display = 'block';
                    document.getElementById('route-details').innerHTML = `Jarak: <b>${jarak} km</b> • Waktu: <b>${waktu} mnt</b>`;

                    setTimeout(() => {
                        if (markers[index]) {
                            markers[index].openPopup();
                        }
                    }, 200);
                })
                .addTo(map);
        }

        // RESIZER SCRIPT
        document.addEventListener('DOMContentLoaded', function() {
            const resizer = document.getElementById('dragHandle');
            const leftSide = document.querySelector('.list-area');
            const container = document.querySelector('.split-container');
            let x = 0;
            let leftWidth = 0;

            if (resizer) {
                const startDrag = function(e) {
                    if (!leftSide || !container) return;
                    if (e.type === 'touchstart') {
                        x = e.touches[0].clientX;
                    } else {
                        e.preventDefault();
                        x = e.clientX;
                    }

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
                    let clientX;
                    if (e.type === 'touchmove') {
                        clientX = e.touches[0].clientX;
                    } else {
                        clientX = e.clientX;
                    }

                    const dx = clientX - x;
                    const containerWidth = container.getBoundingClientRect().width;
                    let newLeftWidth = ((leftWidth + dx) * 100) / containerWidth;

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

            window.addEventListener('resize', () => {
                const isMobile = window.innerWidth < 768;
                const mapEl = document.getElementById('map');
                const routeBox = document.getElementById('route-info');
                const desktopContainer = document.getElementById('desktop-map-container');

                if (isMobile) {
                    leftSide.style.width = '';
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