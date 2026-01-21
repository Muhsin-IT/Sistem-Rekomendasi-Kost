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

// --- LOGIKA EDIT ULASAN ---
if (isset($_GET['aksi']) && $_GET['aksi'] == 'edit_review' && isset($_GET['id'])) {
    $id_review = $_GET['id'];
    $review_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM review WHERE id_review='$id_review' AND id_user='$id_user'"));
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/img/logo/persegi.webp">
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
                            $q_survei = mysqli_query($conn, "SELECT s.*, k.nama_kost, k.alamat, k.latitude, k.longitude, u.no_hp, r.id_review, r.rating, r.skor_akurasi
                                                         FROM survei s 
                                                         JOIN kost k ON s.id_kost = k.id_kost 
                                                         JOIN users u ON k.id_pemilik = u.id_user
                                                         LEFT JOIN review r ON (s.id_kost = r.id_kost AND r.id_user = '$id_user')
                                                         WHERE s.id_user = '$id_user' 
                                                         ORDER BY s.id_survei DESC");

                            if (mysqli_num_rows($q_survei) > 0):
                                while ($s = mysqli_fetch_assoc($q_survei)):
                            ?>
                                    <!-- Tambahkan onclick di baris ini untuk tabel Survei -->
                                    <tr style="cursor: pointer;" onclick="if(!event.target.closest('a')) window.location='detail_kost?id=<?= $s['id_kost'] ?>'">
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
                                                <?php if ($s['id_review']): ?>
                                                    <a href="detail_kost?id=<?= $s['id_kost'] ?>&edit_review=<?= $s['id_review'] ?>#ulasan" class="btn btn-sm btn-outline-warning rounded-pill">
                                                        <i class="bi bi-pencil"></i> Edit Ulasan
                                                    </a>
                                                <?php else: ?>
                                                    <?php
                                                    // cari kamar pertama dari kost agar survei bisa diarahkan ke halaman kamar
                                                    $first_kamar_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id_kamar FROM kamar WHERE id_kost='{$s['id_kost']}' LIMIT 1"));
                                                    $target_kamar_id = $first_kamar_row ? $first_kamar_row['id_kamar'] : null;
                                                    if ($target_kamar_id):
                                                    ?>
                                                        <a href="detail_kamar?id=<?= $target_kamar_id ?>&reviewer=survei#ulasan" class="btn btn-sm btn-outline-primary rounded-pill">
                                                            <i class="bi bi-star"></i> Nilai Akurasi
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="detail_kost?id=<?= $s['id_kost'] ?>&reviewer=survei#ulasan" class="btn btn-sm btn-outline-primary rounded-pill">
                                                            <i class="bi bi-star"></i> Nilai Akurasi
                                                        </a>
                                                    <?php endif; ?>
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
                            $q_sewa = mysqli_query($conn, "SELECT p.*, k.nama_kost, k.latitude, k.longitude, u.no_hp, r.id_review, r.rating, r.skor_akurasi
                                  FROM pengajuan_sewa p
                                  JOIN kost k ON p.id_kost = k.id_kost
                                  JOIN users u ON k.id_pemilik = u.id_user
                                  LEFT JOIN review r ON (p.id_kost = r.id_kost AND r.id_user = '$id_user')
                                  WHERE p.id_user = '$id_user' ORDER BY p.id_pengajuan DESC");

                            if (mysqli_num_rows($q_sewa) > 0):
                                while ($r = mysqli_fetch_assoc($q_sewa)):
                            ?>
                                    <!-- Tambahkan onclick di baris ini untuk tabel Sewa -->
                                    <tr style="cursor: pointer;" onclick="if(!event.target.closest('a')) window.location='detail_kamar.php?id=<?= $r['id_kamar'] ?>'">
                                        <td class="ps-4">
                                            <a href="detail_sewa.php?id=<?= $r['id_pengajuan'] ?>" class="text-decoration-none text-dark fw-bold hover-primary">
                                                <?= $r['nama_kost'] ?> <i class="bi bi-box-arrow-up-right ms-1 text-muted small"></i>
                                            </a>
                                        </td>
                                        <td><?= date('d M Y', strtotime($r['tanggal_mulai_kos'])) ?></td>
                                        <td><span class="badge <?= $r['status'] == 'Diterima' ? 'bg-success' : 'bg-warning text-dark' ?>"><?= $r['status'] ?></span></td>
                                        <td class="text-end pe-4">
                                            <?php if ($r['status'] == 'Diterima'): ?>
                                                <?php if ($r['id_review']): ?>
                                                    <a href="detail_kost?id=<?= $r['id_kost'] ?>&edit_review=<?= $r['id_review'] ?>#ulasan" class="btn btn-sm btn-outline-warning rounded-pill">
                                                        <i class="bi bi-pencil"></i> Edit Ulasan
                                                    </a>
                                                <?php else: ?>
                                                    <!-- arahkan ke detail_kamar supaya id_kamar terisi -->
                                                    <a href="detail_kamar?id=<?= $r['id_kamar'] ?>&reviewer=sewa#ulasan" class="btn btn-sm btn-outline-primary rounded-pill">
                                                        <i class="bi bi-star"></i> Beri Ulasan
                                                    </a>
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

    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>