<?php
// ajax_get_kost.php
session_start();
include 'koneksi.php';

// Ambil parameter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'terbaru';
$user_lat = isset($_GET['lat']) ? $_GET['lat'] : null;
$user_long = isset($_GET['long']) ? $_GET['long'] : null;

// Default Lokasi (UNU)
$lat_unu = -7.787861880324053;
$long_unu = 110.33049620439317;
$titik_lat = $user_lat ? $user_lat : $lat_unu;
$titik_long = $user_long ? $user_long : $long_unu;

function hitungJarak($lat1, $lon1, $lat2, $lon2)
{
    if (!$lat1 || !$lon1 || !$lat2 || !$lon2) return 0;
    $theta = $lon1 - $lon2;
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
    $dist = acos($dist);
    $dist = rad2deg($dist);
    return round($dist * 60 * 1.1515 * 1.609344, 1);
}

// QUERY DASAR
$query_base = "SELECT k.*, 
              (SELECT MIN(harga_per_bulan) FROM kamar WHERE id_kost = k.id_kost) as harga_min,
              (SELECT nama_file FROM galeri WHERE id_kost = k.id_kost AND kategori_foto = 'Tampak Depan' LIMIT 1) as foto_depan,
              (SELECT AVG(rating) FROM review WHERE id_kost = k.id_kost) as rating_avg
              FROM kost k
              WHERE k.latitude != 0 AND k.longitude != 0";

$data_final = [];

// === LOGIKA FILTER ===
if ($filter == 'ai') {
    // 1. Ambil Semua Data untuk dikirim ke Python
    $raw_data = mysqli_query($conn, $query_base);
    $data_python = [];
    $map_kost = [];

    while ($row = mysqli_fetch_assoc($raw_data)) {
        $id = $row['id_kost'];
        // Hitung Kriteria SAW
        $c1 = $row['harga_min'] ?? 10000000;
        $c2 = hitungJarak($row['latitude'], $row['longitude'], $titik_lat, $titik_long);

        // Query Fasilitas & Peraturan & Review (Sama seperti search.php)
        $q_fas = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT id_master_fasilitas) as c FROM rel_fasilitas WHERE id_kost='$id'"));
        $c3 = $q_fas['c'];
        $q_per = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT id_master_peraturan) as c FROM rel_peraturan WHERE id_kost='$id'"));
        $c4 = $q_per['c'];
        $q_rev = mysqli_fetch_assoc(mysqli_query($conn, "SELECT AVG(skor_akurasi) as c5, AVG(rating) as c6 FROM review WHERE id_kost='$id'"));
        $c5 = $q_rev['c5'] ?? 0;
        $c6 = $q_rev['c6'] ?? 0;

        $data_python[] = ['id_kost' => $id, 'C1' => $c1, 'C2' => $c2, 'C3' => $c3, 'C4' => $c4, 'C5' => $c5, 'C6' => $c6];
        $row['jarak_kampus'] = $c2; // Simpan jarak
        $map_kost[$id] = $row;
    }

    // 2. Kirim ke Python API
    $api_url = "http://127.0.0.1:5001/hitung-saw";
    $kriteria = [
        "C1" => ["atribut" => "cost", "bobot" => 0.20],
        "C2" => ["atribut" => "cost", "bobot" => 0.17],
        "C3" => ["atribut" => "benefit", "bobot" => 0.15],
        "C4" => ["atribut" => "benefit", "bobot" => 0.14],
        "C5" => ["atribut" => "benefit", "bobot" => 0.18],
        "C6" => ["atribut" => "benefit", "bobot" => 0.17]
    ];

    $payload = json_encode(['alternatif' => $data_python, 'kriteria' => $kriteria]);

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 4);
    $resp = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code == 200) {
        $json_res = json_decode($resp, true);
        if (isset($json_res['status']) && $json_res['status'] == 'success') {
            foreach ($json_res['data'] as $ranked) {
                if (isset($map_kost[$ranked['id_kost']])) {
                    $data_final[] = $map_kost[$ranked['id_kost']];
                }
            }
        }
    }
    // Fallback jika python mati: ambil data default
    if (empty($data_final)) $data_final = array_values($map_kost);
} else {
    // FILTER BIASA (SQL)
    $order = "ORDER BY k.id_kost DESC";
    if ($filter == 'termurah') $order = "ORDER BY harga_min ASC";

    $res = mysqli_query($conn, $query_base . " " . $order . " LIMIT 10");
    while ($row = mysqli_fetch_assoc($res)) {
        $row['jarak_kampus'] = hitungJarak($lat_unu, $long_unu, $row['latitude'], $row['longitude']);
        $data_final[] = $row;
    }
}

// === FORMAT DATA UNTUK OUTPUT ===
$output_html = "";
$map_data_js = []; // Data bersih untuk JS Leaflet

foreach ($data_final as $index => $row) {
    // Format Data untuk Tampilan
    $foto = $row['foto_depan'] ? "assets/img/galeri/" . $row['foto_depan'] : "https://via.placeholder.com/400x250?text=No+Image";
    $harga = $row['harga_min'] ? "Rp " . number_format($row['harga_min'], 0, ',', '.') : "Hubungi Pemilik";
    $rating = $row['rating_avg'] ? round($row['rating_avg'], 1) : 0;

    // Badge
    $bg_gender = ($row['jenis_kost'] == 'Putra') ? 'bg-putra' : (($row['jenis_kost'] == 'Putri') ? 'bg-putri' : 'bg-campur');
    $badge_ai = ($filter == 'ai') ? '<span class="badge bg-success position-absolute top-0 start-0 m-2 shadow-sm"><i class="bi bi-stars"></i> Rekomendasi AI</span>' : '';

    // HTML CARD
    // Perhatikan: onclick menggunakan index baru ($index)
    $output_html .= '
    <div class="card card-kost shadow-sm p-2 mb-3" id="card-' . $index . '" onclick="handleCardClick(' . $index . ', ' . $row['latitude'] . ', ' . $row['longitude'] . ', event)">
        <div class="row g-0 align-items-center">
            <div class="col-4 col-md-5">
                <div class="position-relative h-100">
                    ' . $badge_ai . '
                    <img src="' . $foto . '" class="kost-img-fix" alt="Foto">
                </div>
            </div>
            <div class="col-8 col-md-7">
                <div class="card-body p-2 ps-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="badge-gender ' . $bg_gender . '">' . $row['jenis_kost'] . '</span>
                        ' . ($rating > 0 ? '<small class="text-warning fw-bold"><i class="bi bi-star-fill"></i> ' . $rating . '</small>' : '') . '
                    </div>
                    <h6 class="fw-bold mb-1 text-truncate">' . $row['nama_kost'] . '</h6>
                    <small class="text-muted d-block mb-2" style="font-size: 0.8rem;">
                        <i class="bi bi-geo-alt-fill text-danger"></i> ' . substr($row['alamat'], 0, 15) . '... 
                        <b>(' . $row['jarak_kampus'] . ' km)</b>
                    </small>
                    <div class="d-flex justify-content-between align-items-end">
                        <div>
                            <small class="text-muted" style="font-size: 0.65rem">Mulai dari</small><br>
                            <span class="text-primary fw-bold" style="font-size: 0.9rem;">' . $harga . '</span>
                        </div>
                        <a href="detail_kost?id=' . $row['id_kost'] . '" class="btn btn-sm btn-primary rounded-pill py-0 px-2" style="font-size: 0.75rem">Detail</a>
                    </div>
                </div>
            </div>
        </div>
        <div id="mobile-map-target-' . $index . '" class="mobile-map-placeholder"></div>
    </div>';

    // Data untuk Peta JS
    $map_data_js[] = [
        'id' => $row['id_kost'],
        'latitude' => $row['latitude'],
        'longitude' => $row['longitude'],
        'nama_kost' => $row['nama_kost'],
        'harga_format' => $harga
    ];
}

if (empty($data_final)) {
    $output_html = '<div class="text-center py-5"><p class="text-muted">Tidak ada data ditemukan.</p></div>';
}

// Return JSON (HTML untuk List, Data Array untuk Peta)
echo json_encode(['html' => $output_html, 'map_data' => $map_data_js]);
