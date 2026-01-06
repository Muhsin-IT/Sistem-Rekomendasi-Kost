<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['login'])) {
    header("Location: login");
    exit;
}

$id_user = $_SESSION['id_user'];

// --- LOGIKA BATALKAN ---
if (isset($_GET['aksi']) && $_GET['aksi'] == 'batal' && isset($_GET['id'])) {
    $id_sewa = $_GET['id'];
    $cek = mysqli_query($conn, "SELECT id_kamar FROM pengajuan_sewa WHERE id_pengajuan='$id_sewa' AND id_user='$id_user' AND status='Menunggu'");
    if (mysqli_num_rows($cek) > 0) {
        $data = mysqli_fetch_assoc($cek);
        $id_kamar_batal = $data['id_kamar'];
        if (mysqli_query($conn, "DELETE FROM pengajuan_sewa WHERE id_pengajuan='$id_sewa'")) {
            mysqli_query($conn, "UPDATE kamar SET stok_kamar = stok_kamar + 1 WHERE id_kamar = '$id_kamar_batal'");
            echo "<script>alert('Pengajuan dibatalkan.'); window.location='riwayat_sewa';</script>";
        }
    }
}
if (isset($_GET['aksi']) && $_GET['aksi'] == 'batal_survei' && isset($_GET['id'])) {
    $id_survei = $_GET['id'];
    mysqli_query($conn, "DELETE FROM survei WHERE id_survei='$id_survei' AND id_user='$id_user' AND status='Menunggu'");
    echo "<script>alert('Survei dibatalkan.'); window.location='riwayat_sewa';</script>";
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Aktivitas - RadenStay</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background-color: #f5f7fa;
        }
    </style>
</head>

<body>

    <?php include 'navbar.php'; ?>

    <div class="container my-5">
        <h4 class="fw-bold mb-4 text-secondary"><i class="bi bi-clock-history me-2"></i> Riwayat Aktivitas</h4>

        <div class="card border-0 shadow-sm mb-5">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0 text-primary">Jadwal Survei</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Kost</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // JOIN ke tabel Review untuk cek apakah sudah pernah review (r.id_review)
                            $q_survei = mysqli_query($conn, "SELECT s.*, k.nama_kost, k.alamat, k.latitude, k.longitude, u.no_hp, r.id_review
                                                         FROM survei s 
                                                         JOIN kost k ON s.id_kost = k.id_kost 
                                                         JOIN users u ON k.id_pemilik = u.id_user
                                                         LEFT JOIN review r ON (s.id_kost = r.id_kost AND r.id_user = '$id_user')
                                                         WHERE s.id_user = '$id_user' 
                                                         ORDER BY s.id_survei DESC");

                            if (mysqli_num_rows($q_survei) > 0):
                                while ($s = mysqli_fetch_assoc($q_survei)):
                            ?>
                                    <tr>
                                        <td class="ps-4">
                                            <span class="fw-bold d-block"><?= $s['nama_kost'] ?></span>
                                            <small class="text-muted"><?= substr($s['alamat'], 0, 30) ?>...</small>
                                        </td>
                                        <td><?= date('d M', strtotime($s['tgl_survei'])) ?></td>
                                        <td>
                                            <span class="badge <?= $s['status'] == 'Diterima' ? 'bg-success' : ($s['status'] == 'Menunggu' ? 'bg-warning text-dark' : 'bg-secondary') ?>">
                                                <?= $s['status'] ?>
                                            </span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <?php if ($s['status'] == 'Diterima' || $s['status'] == 'Selesai'): ?>
                                                <a href="https://wa.me/<?= $s['no_hp'] ?>" target="_blank" class="btn btn-sm btn-success rounded-pill"><i class="bi bi-whatsapp"></i></a>

                                                <?php if ($s['id_review']): ?>
                                                    <button class="btn btn-sm btn-secondary rounded-pill" disabled><i class="bi bi-check-circle"></i> Sudah Dinilai</button>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill"
                                                        onclick="bukaModalReview('<?= $s['id_kost'] ?>', '<?= addslashes($s['nama_kost']) ?>', <?= $s['latitude'] ?>, <?= $s['longitude'] ?>, 'survei')">
                                                        <i class="bi bi-star"></i> Nilai Akurasi
                                                    </button>
                                                <?php endif; ?>

                                            <?php elseif ($s['status'] == 'Menunggu'): ?>
                                                <a href="riwayat_sewa?aksi=batal_survei&id=<?= $s['id_survei'] ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Batal?')">Batal</a>
                                            <?php else: ?> - <?php endif; ?>
                                        </td>
                                    </tr>
                            <?php endwhile;
                            else: echo "<tr><td colspan='4' class='text-center py-3'>Kosong</td></tr>";
                            endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0 text-success">Sewa Kamar</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Kost</th>
                                <th>Mulai</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // JOIN ke Review juga
                            $q_sewa = mysqli_query($conn, "SELECT p.*, k.nama_kost, k.latitude, k.longitude, u.no_hp, r.id_review
                                  FROM pengajuan_sewa p
                                  JOIN kost k ON p.id_kost = k.id_kost
                                  JOIN users u ON k.id_pemilik = u.id_user
                                  LEFT JOIN review r ON (p.id_kost = r.id_kost AND r.id_user = '$id_user')
                                  WHERE p.id_user = '$id_user' ORDER BY p.id_pengajuan DESC");

                            if (mysqli_num_rows($q_sewa) > 0):
                                while ($r = mysqli_fetch_assoc($q_sewa)):
                            ?>
                                    <tr>
                                        <td class="ps-4"><span class="fw-bold"><?= $r['nama_kost'] ?></span></td>
                                        <td><?= date('d M Y', strtotime($r['tanggal_mulai_kos'])) ?></td>
                                        <td><span class="badge <?= $r['status'] == 'Diterima' ? 'bg-success' : 'bg-warning text-dark' ?>"><?= $r['status'] ?></span></td>
                                        <td class="text-end pe-4">
                                            <?php if ($r['status'] == 'Diterima'): ?>
                                                <a href="https://wa.me/<?= $r['no_hp'] ?>" target="_blank" class="btn btn-sm btn-success rounded-pill"><i class="bi bi-whatsapp"></i></a>

                                                <?php if ($r['id_review']): ?>
                                                    <button class="btn btn-sm btn-secondary rounded-pill" disabled><i class="bi bi-check-circle"></i> Sudah Dinilai</button>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill"
                                                        onclick="bukaModalReview('<?= $r['id_kost'] ?>', '<?= addslashes($r['nama_kost']) ?>', <?= $r['latitude'] ?>, <?= $r['longitude'] ?>, 'sewa')">
                                                        <i class="bi bi-star"></i> Beri Ulasan
                                                    </button>
                                                <?php endif; ?>

                                            <?php elseif ($r['status'] == 'Menunggu'): ?>
                                                <a href="riwayat_sewa?aksi=batal&id=<?= $r['id_pengajuan'] ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Batal?')">Batal</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                            <?php endwhile;
                            else: echo "<tr><td colspan='4' class='text-center py-3'>Kosong</td></tr>";
                            endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalReview" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fs-6">Nilai: <span id="namaKostReview" class="fw-bold"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="proses_ulasan">
                    <div class="modal-body">
                        <input type="hidden" name="id_kost" id="idKostReview">
                        <input type="hidden" name="user_lat" id="userLat">
                        <input type="hidden" name="user_long" id="userLong">

                        <div id="gpsContainer" class="alert alert-secondary small py-2 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="spinner-border spinner-border-sm me-2" id="loadingGps" role="status"></div>
                                <span id="gpsText">Mendeteksi lokasi & alamat...</span>
                            </div>
                            <div id="jarakText" class="fw-bold mt-1 text-primary" style="font-size: 0.9em;"></div>
                        </div>

                        <div class="mb-3 border-bottom pb-3">
                            <label class="form-label fw-bold small">Akurasi Foto/Info (C5)</label>
                            <div class="btn-group w-100" role="group">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <input type="radio" class="btn-check" name="rating_akurasi" id="ak<?= $i ?>" value="<?= $i ?>" required>
                                    <label class="btn btn-outline-warning" for="ak<?= $i ?>"><?= $i ?></label>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Kepuasan Umum (C6)</label>
                            <div class="btn-group w-100" role="group">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <input type="radio" class="btn-check" name="rating_umum" id="um<?= $i ?>" value="<?= $i ?>" required>
                                    <label class="btn btn-outline-success" for="um<?= $i ?>"><?= $i ?></label>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <textarea name="komentar" class="form-control" rows="2" placeholder="Tulis pengalamanmu..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary w-100" id="btnKirimReview" disabled>
                            <i class="bi bi-lock-fill"></i> Lokasi Belum Terverifikasi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Variabel Global untuk menyimpan data Kost saat modal dibuka
        let targetLat = 0;
        let targetLong = 0;
        let userType = ''; // 'sewa' atau 'survei'

        function bukaModalReview(id, nama, lat, long, type) {
            document.getElementById('idKostReview').value = id;
            document.getElementById('namaKostReview').innerText = nama;

            targetLat = lat;
            targetLong = long;
            userType = type;

            // Reset UI
            document.getElementById('gpsContainer').className = 'alert alert-secondary small py-2 mb-3';
            document.getElementById('gpsText').innerHTML = 'Mendeteksi lokasi...';
            document.getElementById('jarakText').innerHTML = '';
            document.getElementById('loadingGps').style.display = 'inline-block';
            disableButton('Menunggu Lokasi...');

            var myModal = new bootstrap.Modal(document.getElementById('modalReview'));
            myModal.show();

            // Mulai Cari Lokasi
            getLocation();
        }

        function getLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(processPosition, showError, {
                    enableHighAccuracy: true
                });
            } else {
                showError({
                    message: "Browser tidak support GPS"
                });
            }
        }

        // Fungsi Utama Proses Lokasi
        function processPosition(position) {
            let uLat = position.coords.latitude;
            let uLong = position.coords.longitude;

            // Set ke Input Hidden
            document.getElementById('userLat').value = uLat;
            document.getElementById('userLong').value = uLong;

            // 1. Hitung Jarak (Haversine Formula)
            let jarakMeter = hitungJarak(uLat, uLong, targetLat, targetLong);

            // 2. Ambil Alamat (Reverse Geocoding Nominatim)
            // URL API OpenStreetMap
            let url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${uLat}&lon=${uLong}&zoom=18&addressdetails=1`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    let alamatBersih = data.display_name || "Alamat tidak ditemukan";
                    // Persingkat alamat biar rapi
                    let alamatShort = alamatBersih.split(',').slice(0, 3).join(',');

                    // Tampilkan di UI
                    document.getElementById('gpsText').innerHTML = `<i class='bi bi-geo-alt-fill'></i> ${alamatShort}`;
                    document.getElementById('loadingGps').style.display = 'none';

                    // 3. Validasi Jarak
                    validasiJarak(jarakMeter);
                })
                .catch(err => {
                    console.error(err);
                    document.getElementById('gpsText').innerHTML = "Lokasi: " + uLat.toFixed(5) + ", " + uLong.toFixed(5);
                    document.getElementById('loadingGps').style.display = 'none';
                    validasiJarak(jarakMeter);
                });
        }

        function validasiJarak(meter) {
            let statusBox = document.getElementById('gpsContainer');
            let jarakText = document.getElementById('jarakText');

            if (userType === 'sewa') {
                // Jika Anak Kost (Sewa), Bebas review
                statusBox.className = 'alert alert-success small py-2 mb-3';
                jarakText.innerHTML = `Status: Penyewa (Bebas Review dari mana saja)`;
                enableButton();
            } else {
                // Jika Survei, Wajib < 50m
                if (meter <= 50) {
                    statusBox.className = 'alert alert-success small py-2 mb-3';
                    jarakText.innerHTML = `Jarak: ${meter} meter (Dalam jangkauan)`;
                    enableButton();
                } else {
                    statusBox.className = 'alert alert-danger small py-2 mb-3';
                    jarakText.innerHTML = `Jarak: ${meter} meter (Kejauhan! Max 50m)`;
                    disableButton('Lokasi Terlalu Jauh');
                }
            }
        }

        // Rumus Jarak (Haversine) - Javascript Version
        function hitungJarak(lat1, lon1, lat2, lon2) {
            if ((lat1 == lat2) && (lon1 == lon2)) return 0;
            var radlat1 = Math.PI * lat1 / 180;
            var radlat2 = Math.PI * lat2 / 180;
            var theta = lon1 - lon2;
            var radtheta = Math.PI * theta / 180;
            var dist = Math.sin(radlat1) * Math.sin(radlat2) + Math.cos(radlat1) * Math.cos(radlat2) * Math.cos(radtheta);
            if (dist > 1) dist = 1;
            dist = Math.acos(dist);
            dist = dist * 180 / Math.PI;
            dist = dist * 60 * 1.1515;
            dist = dist * 1.609344 * 1000; // Meter
            return Math.round(dist);
        }

        function enableButton() {
            let btn = document.getElementById('btnKirimReview');
            btn.disabled = false;
            btn.innerHTML = 'Kirim Ulasan';
            btn.className = 'btn btn-primary w-100';
        }

        function disableButton(msg) {
            let btn = document.getElementById('btnKirimReview');
            btn.disabled = true;
            btn.innerHTML = `<i class="bi bi-x-circle"></i> ${msg}`;
            btn.className = 'btn btn-secondary w-100';
        }

        function showError(error) {
            document.getElementById('gpsText').innerHTML = "Gagal ambil lokasi. Izinkan GPS!";
            document.getElementById('loadingGps').style.display = 'none';
            disableButton('GPS Error');
        }
    </script>
</body>

</html>