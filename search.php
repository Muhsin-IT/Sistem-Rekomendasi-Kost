<?php
session_start();
include 'koneksi.php';

$keyword = isset($_GET['keyword']) ? mysqli_real_escape_string($conn, $_GET['keyword']) : '';

// ==================================================================================
// 1. QUERY PENCARIAN CANGGIH (NAMA, ALAMAT, FASILITAS, PERATURAN)
// ==================================================================================
// Kita gunakan DISTINCT agar jika keyword ditemukan berkali-kali (misal di fasilitas),
// kost tersebut tidak muncul ganda (duplikat).

$query = "SELECT DISTINCT k.* FROM kost k
          -- Hubungkan ke Tabel Fasilitas
          LEFT JOIN rel_fasilitas rf ON k.id_kost = rf.id_kost
          LEFT JOIN master_fasilitas mf ON rf.id_master_fasilitas = mf.id_master_fasilitas
          -- Hubungkan ke Tabel Peraturan
          LEFT JOIN rel_peraturan rp ON k.id_kost = rp.id_kost
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
$data_untuk_python = [];
$data_kost_lengkap = [];

// Koordinat UNU Yogyakarta (Pusat referensi jarak C2)
$lat_unu = -7.7699;
$long_unu = 110.380;

// Fungsi hitung jarak (Haversine)
function hitungJarak($lat1, $lon1, $lat2, $lon2)
{
    if (!$lat1 || !$lon1) return 10;
    $theta = $lon1 - $lon2;
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
    $dist = acos($dist);
    $dist = rad2deg($dist);
    return round($dist * 60 * 1.1515 * 1.609344, 2); // Km
}

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $id = $row['id_kost'];

        // --- HITUNG KRITERIA (C1 - C6) ---

        // C1: Harga (Ambil harga termurah)
        $q_hrg = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MIN(harga_per_bulan) as min_harga FROM kamar WHERE id_kost='$id'"));
        $c1 = $q_hrg['min_harga'] ?? 10000000;

        // C2: Jarak
        $c2 = hitungJarak($row['latitude'], $row['longitude'], $lat_unu, $long_unu);

        // C3: Fasilitas (Jumlah)
        $q_fas = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as jum FROM rel_fasilitas WHERE id_kost='$id'"));
        $c3 = $q_fas['jum'];

        // C4: Peraturan (Jumlah - dianggap Benefit/Ketertiban)
        $q_per = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as jum FROM rel_peraturan WHERE id_kost='$id'"));
        $c4 = $q_per['jum'];

        // C5 & C6: Akurasi & Rating
        $q_rev = mysqli_fetch_assoc(mysqli_query($conn, "SELECT AVG(skor_akurasi) as avg_c5, AVG(rating) as avg_c6 FROM review WHERE id_kost='$id'"));
        $c5 = $q_rev['avg_c5'] ?? 0;
        $c6 = $q_rev['avg_c6'] ?? 0;

        // Masukkan data ke array
        $data_untuk_python[] = [
            'id_kost' => $id,
            'C1' => $c1,
            'C2' => $c2,
            'C3' => $c3,
            'C4' => $c4,
            'C5' => $c5,
            'C6' => $c6
        ];

        // Simpan data untuk ditampilkan nanti
        $data_kost_lengkap[$id] = $row;
        $data_kost_lengkap[$id]['harga_tampil'] = $c1;
        $data_kost_lengkap[$id]['rating_tampil'] = $c6;
    }

    // ==================================================================================
    // 3. KIRIM KE PYTHON API
    // ==================================================================================
    // PENTING: Ganti URL Ngrok ini setiap kali Anda menjalankan ulang Colab
    $api_url = "https://6d4104b1c4b5.ngrok-free.app/hitung_saw";

    $payload = json_encode(['data_kost' => $data_untuk_python]);

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $hasil_ranking = [];
    $is_ai_active = false;

    if ($http_code == 200) {
        $json_res = json_decode($response, true);
        if (isset($json_res['status']) && $json_res['status'] == 'success') {
            $hasil_ranking = $json_res['hasil'];
            $is_ai_active = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <title>Hasil Pencarian - RadenStay</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container my-5">
        <h4 class="mb-4">
            Hasil Pencarian: "<strong><?= htmlspecialchars($keyword) ?></strong>"
            <?php if ($is_ai_active): ?>
                <span class="badge bg-success ms-2"><i class="bi bi-robot"></i> Python SAW Active</span>
            <?php else: ?>
                <span class="badge bg-secondary ms-2">Python API Offline</span>
            <?php endif; ?>
        </h4>

        <?php if (empty($data_kost_lengkap)): ?>
            <div class="alert alert-warning text-center py-5">
                <i class="bi bi-search display-1 text-muted mb-3 d-block"></i>
                <h5>Kost tidak ditemukan.</h5>
                <p class="text-muted">Coba kata kunci lain seperti "WiFi", "AC", "Putra", atau nama jalan.</p>
                <a href="index.php" class="btn btn-outline-primary mt-2">Kembali ke Beranda</a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php
                // Tentukan Urutan (Berdasarkan Ranking Python ATAU Default ID)
                $urutan_ids = [];
                if (!empty($hasil_ranking)) {
                    foreach ($hasil_ranking as $r) $urutan_ids[] = $r['id_kost'];
                } else {
                    $urutan_ids = array_keys($data_kost_lengkap);
                }

                // TAMPILKAN CARD
                foreach ($urutan_ids as $id):
                    if (!isset($data_kost_lengkap[$id])) continue;
                    $k = $data_kost_lengkap[$id];

                    // Ambil foto depan
                    $qf = mysqli_query($conn, "SELECT nama_file FROM galeri WHERE id_kost='$id' LIMIT 1");
                    $f = mysqli_fetch_assoc($qf);
                    $img = $f ? "assets/img/galeri/" . $f['nama_file'] : "https://via.placeholder.com/400x250?text=No+Image";
                ?>
                    <div class="col-md-4">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="position-relative">
                                <img src="<?= $img ?>" class="card-img-top" style="height: 200px; object-fit: cover;">

                                <span class="position-absolute top-0 start-0 bg-white text-dark px-2 py-1 m-2 rounded small fw-bold shadow-sm">
                                    <?= $k['jenis_kost'] ?>
                                </span>

                                <?php if (!empty($hasil_ranking)):
                                    $skor = 0;
                                    foreach ($hasil_ranking as $hr) if ($hr['id_kost'] == $id) $skor = $hr['skor_akhir'];
                                ?>
                                    <span class="position-absolute bottom-0 end-0 bg-success text-white px-2 py-1 m-2 rounded small fw-bold shadow-sm">
                                        Skor: <?= number_format($skor, 3) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <h5 class="fw-bold mb-1"><?= $k['nama_kost'] ?></h5>
                                <p class="text-muted small mb-3"><i class="bi bi-geo-alt"></i> <?= substr($k['alamat'], 0, 40) ?>...</p>

                                <h6 class="text-primary fw-bold mb-3">
                                    Rp <?= number_format($k['harga_tampil'], 0, ',', '.') ?>
                                    <small class="text-muted fw-normal">/ bln</small>
                                </h6>

                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-warning"><i class="bi bi-star-fill"></i> <?= round($k['rating_tampil'], 1) ?></small>
                                    <a href="detail_kost.php?id=<?= $id ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">Lihat Detail</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>