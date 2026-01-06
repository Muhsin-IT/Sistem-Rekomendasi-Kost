<?php
session_start();
include 'koneksi.php'; // Pastikan path koneksi benar

// 1. CEK LOGIN
if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// 2. LOGIKA BATALKAN SEWA
if (isset($_GET['aksi']) && $_GET['aksi'] == 'batal' && isset($_GET['id'])) {
    $id_sewa = $_GET['id'];

    // Ambil ID Kamar sebelum hapus untuk kembalikan stok
    $cek = mysqli_query($conn, "SELECT id_kamar FROM pengajuan_sewa WHERE id_pengajuan='$id_sewa' AND id_user='$id_user' AND status='Menunggu'");

    if (mysqli_num_rows($cek) > 0) {
        $data = mysqli_fetch_assoc($cek);
        $id_kamar_batal = $data['id_kamar'];

        $hapus = mysqli_query($conn, "DELETE FROM pengajuan_sewa WHERE id_pengajuan='$id_sewa'");

        if ($hapus) {
            // Kembalikan Stok
            mysqli_query($conn, "UPDATE kamar SET stok_kamar = stok_kamar + 1 WHERE id_kamar = '$id_kamar_batal'");
            echo "<script>alert('Pengajuan dibatalkan & Stok dikembalikan.'); window.location='riwayat_sewa.php';</script>";
        }
    }
}

// 3. LOGIKA BATALKAN SURVEI (Opsional, hapus data survei)
if (isset($_GET['aksi']) && $_GET['aksi'] == 'batal_survei' && isset($_GET['id'])) {
    $id_survei = $_GET['id'];
    mysqli_query($conn, "DELETE FROM survei WHERE id_survei='$id_survei' AND id_user='$id_user' AND status='Menunggu'");
    echo "<script>alert('Jadwal survei dibatalkan.'); window.location='riwayat_sewa.php';</script>";
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

        <h4 class="fw-bold mb-4 text-secondary"><i class="bi bi-clock-history me-2"></i> Riwayat Aktivitas Saya</h4>

        <div class="card border-0 shadow-sm mb-5">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-eye me-2"></i> Jadwal Survei Lokasi</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Kost Tujuan</th>
                                <th>Rencana Tanggal</th>
                                <th>Jam</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $q_survei = mysqli_query($conn, "SELECT s.*, k.nama_kost, k.alamat, u.no_hp as hp_pemilik 
                                                         FROM survei s 
                                                         JOIN kost k ON s.id_kost = k.id_kost 
                                                         JOIN users u ON k.id_pemilik = u.id_user
                                                         WHERE s.id_user = '$id_user' 
                                                         ORDER BY s.id_survei DESC");

                            if (mysqli_num_rows($q_survei) > 0):
                                while ($s = mysqli_fetch_assoc($q_survei)):
                            ?>
                                    <tr>
                                        <td class="ps-4">
                                            <span class="fw-bold d-block"><?= $s['nama_kost'] ?></span>
                                            <small class="text-muted"><i class="bi bi-geo-alt"></i> <?= substr($s['alamat'], 0, 30) ?>...</small>
                                        </td>
                                        <td><?= date('d M Y', strtotime($s['tgl_survei'])) ?></td>
                                        <td><?= date('H:i', strtotime($s['jam_survei'])) ?></td>
                                        <td>
                                            <?php if ($s['status'] == 'Menunggu'): ?>
                                                <span class="badge bg-warning text-dark">Menunggu</span>
                                            <?php elseif ($s['status'] == 'Diterima'): ?>
                                                <span class="badge bg-success">Disetujui</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Ditolak</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <?php if ($s['status'] == 'Diterima'): ?>
                                                <a href="https://wa.me/<?= $s['hp_pemilik'] ?>?text=Halo Kak, saya mau konfirmasi jadi survei ke <?= $s['nama_kost'] ?> tanggal <?= $s['tgl_survei'] ?> jam <?= $s['jam_survei'] ?>" target="_blank" class="btn btn-sm btn-success rounded-pill">
                                                    <i class="bi bi-whatsapp"></i> Chat
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill"
                                                    onclick="bukaModalReview('<?= $s['id_kost'] ?>', '<?= $s['nama_kost'] ?>')">
                                                    <i class="bi bi-star"></i> Nilai Akurasi
                                                </button>
                                            <?php elseif ($s['status'] == 'Menunggu'): ?>
                                                <a href="riwayat_sewa.php?aksi=batal_survei&id=<?= $s['id_survei'] ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Batalkan jadwal survei ini?')">Batal</a>
                                            <?php else: ?>
                                                <small class="text-muted">-</small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile;
                            else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">Belum ada jadwal survei.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>


        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0 text-success"><i class="bi bi-house-check me-2"></i> Pengajuan Sewa Kamar</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Kost/Kamar</th>
                                <th>Mulai Kost</th>
                                <th>Durasi</th>
                                <th>Biaya</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $q_sewa = mysqli_query($conn, "SELECT p.*, k.nama_kost, k.alamat, km.nama_tipe_kamar, km.harga_per_bulan, u.no_hp as hp_pemilik
                                  FROM pengajuan_sewa p
                                  JOIN kost k ON p.id_kost = k.id_kost
                                  JOIN kamar km ON p.id_kamar = km.id_kamar
                                  JOIN users u ON k.id_pemilik = u.id_user
                                  WHERE p.id_user = '$id_user'
                                  ORDER BY p.id_pengajuan DESC");

                            if (mysqli_num_rows($q_sewa) > 0):
                                while ($row = mysqli_fetch_assoc($q_sewa)):
                                    $total = $row['harga_per_bulan'] * $row['durasi_bulan'];
                            ?>
                                    <tr>
                                        <td class="ps-4">
                                            <span class="fw-bold d-block"><?= $row['nama_kost'] ?></span>
                                            <small class="text-muted"><?= $row['nama_tipe_kamar'] ?></small>
                                        </td>
                                        <td><?= date('d M Y', strtotime($row['tanggal_mulai_kos'])) ?></td>
                                        <td><?= $row['durasi_bulan'] ?> Bulan</td>
                                        <td class="fw-bold text-primary">Rp <?= number_format($total, 0, ',', '.') ?></td>
                                        <td>
                                            <?php if ($row['status'] == 'Menunggu'): ?>
                                                <span class="badge bg-warning text-dark">Menunggu</span>
                                            <?php elseif ($row['status'] == 'Diterima'): ?>
                                                <span class="badge bg-success">Diterima</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger"><?= $row['status'] ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <?php if ($row['status'] == 'Menunggu'): ?>
                                                <a href="riwayat_sewa.php?aksi=batal&id=<?= $row['id_pengajuan'] ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Batalkan sewa? Stok kamar akan dikembalikan.')">Batal</a>
                                            <?php elseif ($row['status'] == 'Diterima'): ?>
                                                <a href="https://wa.me/<?= $row['hp_pemilik'] ?>?text=Halo Kak, pengajuan sewa saya di <?= $row['nama_kost'] ?> sudah DITERIMA. Mohon info pembayaran." target="_blank" class="btn btn-sm btn-success rounded-pill"><i class="bi bi-whatsapp"></i> Bayar</a>
                                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill"
                                                    onclick="bukaModalReview('<?= $row['id_kost'] ?>', '<?= $row['nama_kost'] ?>')">
                                                    <i class="bi bi-star"></i> Beri Ulasan
                                                </button>
                                            <?php else: ?>
                                                <small class="text-muted">Selesai</small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile;
                            else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Belum ada pengajuan sewa.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- Modal Ulasan -->
    <div class="modal fade" id="modalReview" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fs-6">Ulasan & Akurasi: <span id="namaKostReview" class="fw-bold"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="proses_ulasan.php" onsubmit="return validasiForm()">
                    <div class="modal-body">
                        <input type="hidden" name="id_kost" id="idKostReview">

                        <input type="hidden" name="user_lat" id="userLat">
                        <input type="hidden" name="user_long" id="userLong">

                        <div id="gpsStatus" class="alert alert-warning small py-2 mb-3">
                            <i class="bi bi-geo-alt-fill"></i> Sedang mendeteksi lokasi Anda... <br>
                            (Wajib berada di lokasi jika via jalur Survei)
                        </div>

                        <div class="mb-3 border-bottom pb-3">
                            <label class="form-label fw-bold small text-primary">1. Seberapa AKURAT info/foto di web dengan aslinya?</label>
                            <div class="rating-stars text-center">
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="rating_akurasi" id="ak1" value="1" required>
                                    <label class="btn btn-outline-warning" for="ak1">1</label>

                                    <input type="radio" class="btn-check" name="rating_akurasi" id="ak2" value="2">
                                    <label class="btn btn-outline-warning" for="ak2">2</label>

                                    <input type="radio" class="btn-check" name="rating_akurasi" id="ak3" value="3">
                                    <label class="btn btn-outline-warning" for="ak3">3</label>

                                    <input type="radio" class="btn-check" name="rating_akurasi" id="ak4" value="4">
                                    <label class="btn btn-outline-warning" for="ak4">4</label>

                                    <input type="radio" class="btn-check" name="rating_akurasi" id="ak5" value="5">
                                    <label class="btn btn-outline-warning" for="ak5">5 (Sesuai)</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-success">2. Kepuasan Umum / Kenyamanan</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="rating_umum" id="um1" value="1" required>
                                <label class="btn btn-outline-success" for="um1">1</label>

                                <input type="radio" class="btn-check" name="rating_umum" id="um2" value="2">
                                <label class="btn btn-outline-success" for="um2">2</label>

                                <input type="radio" class="btn-check" name="rating_umum" id="um3" value="3">
                                <label class="btn btn-outline-success" for="um3">3</label>

                                <input type="radio" class="btn-check" name="rating_umum" id="um4" value="4">
                                <label class="btn btn-outline-success" for="um4">4</label>

                                <input type="radio" class="btn-check" name="rating_umum" id="um5" value="5">
                                <label class="btn btn-outline-success" for="um5">5 (Puas)</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Komentar</label>
                            <textarea name="komentar" class="form-control" rows="3" placeholder="Ceritakan pengalamanmu..."></textarea>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary w-100" id="btnKirimReview" disabled>Kirim Ulasan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Fungsi Buka Modal & Ambil Lokasi
        function bukaModalReview(idKost, namaKost) {
            // Set Data Kost ke Modal
            document.getElementById('idKostReview').value = idKost;
            document.getElementById('namaKostReview').innerText = namaKost;

            // Tampilkan Modal
            var myModal = new bootstrap.Modal(document.getElementById('modalReview'));
            myModal.show();

            // Jalankan Geolocation
            getLocation();
        }

        function getLocation() {
            var status = document.getElementById("gpsStatus");
            var btn = document.getElementById("btnKirimReview");

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(showPosition, showError);
            } else {
                status.innerHTML = "Browser ini tidak mendukung Geolocation.";
                status.className = "alert alert-danger small py-2 mb-3";
            }
        }

        function showPosition(position) {
            // Sukses Ambil Lokasi
            document.getElementById("userLat").value = position.coords.latitude;
            document.getElementById("userLong").value = position.coords.longitude;

            var status = document.getElementById("gpsStatus");
            status.innerHTML = "<i class='bi bi-check-circle-fill'></i> Lokasi terdeteksi! (" + position.coords.latitude.toFixed(4) + ", " + position.coords.longitude.toFixed(4) + ")";
            status.className = "alert alert-success small py-2 mb-3";

            // Aktifkan tombol kirim
            document.getElementById("btnKirimReview").disabled = false;
        }

        function showError(error) {
            var status = document.getElementById("gpsStatus");
            var msg = "";
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    msg = "User menolak permintaan lokasi. (Wajib izinkan untuk fitur ini)";
                    break;
                case error.POSITION_UNAVAILABLE:
                    msg = "Informasi lokasi tidak tersedia.";
                    break;
                case error.TIMEOUT:
                    msg = "Waktu permintaan lokasi habis.";
                    break;
                case error.UNKNOWN_ERROR:
                    msg = "Terjadi error yang tidak diketahui.";
                    break;
            }
            status.innerHTML = "<i class='bi bi-x-circle-fill'></i> " + msg;
            status.className = "alert alert-danger small py-2 mb-3";
        }
    </script>

    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>