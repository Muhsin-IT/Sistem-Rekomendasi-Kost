<?php
session_start();
include '../koneksi.php';

// 1. CEK LOGIN MAHASISWA
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: ../login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// 2. LOGIKA BATALKAN PESANAN (Hanya jika status 'Menunggu')
if (isset($_GET['batal']) && isset($_GET['id'])) {
    $id_sewa = $_GET['id'];
    
    // Pastikan yang dihapus adalah milik user yang sedang login (Keamanan)
    $cek = mysqli_query($conn, "SELECT id_pengajuan FROM pengajuan_sewa WHERE id_pengajuan='$id_sewa' AND id_user='$id_user' AND status='Menunggu'");
    
    if (mysqli_num_rows($cek) > 0) {
        mysqli_query($conn, "DELETE FROM pengajuan_sewa WHERE id_pengajuan='$id_sewa'");
        echo "<script>alert('Pengajuan berhasil dibatalkan.'); window.location='riwayat_sewa.php';</script>";
    } else {
        echo "<script>alert('Gagal membatalkan. Mungkin status sudah berubah.'); window.location='riwayat_sewa.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Sewa - RadenStay</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { background-color: #f5f7fa; font-family: 'Poppins', sans-serif; }
        .card-history {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.2s;
            overflow: hidden;
            margin-bottom: 20px;
        }
        .card-history:hover { transform: translateY(-5px); }
        .status-badge {
            font-size: 0.8rem; padding: 5px 12px; border-radius: 20px; font-weight: 600;
        }
        .price-tag { color: #0d6efd; font-weight: bold; font-size: 1.1rem; }
    </style>
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php"><i class="fa-solid fa-bed me-2"></i>RadenStay</a>
        <div class="d-flex">
            <a href="index.php" class="btn btn-outline-light btn-sm me-2">Cari Kost</a>
            <a href="../logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            
            <h4 class="fw-bold mb-4 text-secondary"><i class="fa-solid fa-clock-rotate-left me-2"></i> Riwayat Pengajuan Sewa</h4>

            <?php
            // QUERY MENGAMBIL DATA LENGKAP
            // Gabungkan tabel: pengajuan -> kost -> kamar -> users (pemilik)
            $query = "SELECT p.*, k.nama_kost, k.alamat, k.kota, km.nama_tipe_kamar, km.harga_per_bulan, u.nama_lengkap as nama_pemilik, u.no_hp as hp_pemilik
                      FROM pengajuan_sewa p
                      JOIN kost k ON p.id_kost = k.id_kost
                      JOIN kamar km ON p.id_kamar = km.id_kamar
                      JOIN users u ON k.id_pemilik = u.id_user
                      WHERE p.id_user = '$id_user'
                      ORDER BY p.id_pengajuan DESC";
            
            $result = mysqli_query($conn, $query);

            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    // Hitung Total Bayar
                    $total_bayar = $row['harga_per_bulan'] * $row['durasi_bulan'];
                    
                    // Tentukan Warna Status
                    $status_class = 'bg-secondary';
                    $icon_status  = 'fa-circle-question';
                    
                    if ($row['status'] == 'Menunggu') {
                        $status_class = 'bg-warning text-dark';
                        $icon_status  = 'fa-hourglass-half';
                    } elseif ($row['status'] == 'Diterima') {
                        $status_class = 'bg-success';
                        $icon_status  = 'fa-circle-check';
                    } elseif ($row['status'] == 'Ditolak') {
                        $status_class = 'bg-danger';
                        $icon_status  = 'fa-circle-xmark';
                    }
            ?>
            
            <div class="card card-history bg-white">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        
                        <div class="col-md-5 mb-3 mb-md-0 border-end-md">
                            <h5 class="fw-bold mb-1"><?= $row['nama_kost'] ?></h5>
                            <p class="text-muted small mb-2"><i class="fa-solid fa-location-dot me-1"></i> <?= $row['kota'] ?></p>
                            <div class="d-flex align-items-center bg-light p-2 rounded">
                                <div class="me-3">
                                    <small class="d-block text-muted">Tipe Kamar</small>
                                    <span class="fw-bold text-dark"><?= $row['nama_tipe_kamar'] ?></span>
                                </div>
                                <div>
                                    <small class="d-block text-muted">Durasi</small>
                                    <span class="fw-bold text-dark"><?= $row['durasi_bulan'] ?> Bulan</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3 mb-md-0 border-end-md text-md-center">
                            <small class="text-muted">Rencana Masuk:</small>
                            <div class="fw-bold mb-2"><i class="fa-regular fa-calendar me-1"></i> <?= date('d M Y', strtotime($row['tanggal_mulai_kos'])) ?></div>
                            
                            <small class="text-muted">Total Tagihan:</small>
                            <div class="price-tag">Rp <?= number_format($total_bayar, 0, ',', '.') ?></div>
                        </div>

                        <div class="col-md-3 text-center">
                            <div class="mb-3">
                                <span class="badge status-badge <?= $status_class ?>">
                                    <i class="fa-solid <?= $icon_status ?> me-1"></i> <?= $row['status'] ?>
                                </span>
                            </div>

                            <?php if ($row['status'] == 'Menunggu'): ?>
                                <a href="riwayat_sewa.php?batal=true&id=<?= $row['id_pengajuan'] ?>" class="btn btn-outline-danger btn-sm w-100 rounded-pill" onclick="return confirm('Yakin ingin membatalkan pengajuan ini?')">
                                    <i class="fa-solid fa-ban me-1"></i> Batalkan
                                </a>
                            
                            <?php elseif ($row['status'] == 'Diterima'): ?>
                                <a href="https://wa.me/<?= $row['hp_pemilik'] ?>?text=Halo Kak, saya <?= $_SESSION['nama_lengkap'] ?> yang memesan kamar di <?= $row['nama_kost'] ?> (Tipe <?= $row['nama_tipe_kamar'] ?>). Mohon info pembayaran." target="_blank" class="btn btn-success btn-sm w-100 rounded-pill mb-1">
                                    <i class="fa-brands fa-whatsapp me-1"></i> Hubungi Pemilik
                                </a>
                                <small class="text-muted d-block" style="font-size: 10px;">*Segera lakukan pembayaran</small>
                            
                            <?php elseif ($row['status'] == 'Ditolak'): ?>
                                <button class="btn btn-secondary btn-sm w-100 rounded-pill" disabled>Selesai</button>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div>

            <?php 
                } // End While
            } else {
                // JIKA KOSONG
                echo '
                <div class="text-center py-5">
                    <img src="https://cdni.iconscout.com/illustration/premium/thumb/empty-cart-2130356-1800917.png" width="200" style="opacity:0.7">
                    <h5 class="text-muted mt-3">Belum ada riwayat sewa.</h5>
                    <a href="index.php" class="btn btn-primary mt-2">Cari Kost Sekarang</a>
                </div>';
            }
            ?>

        </div>
    </div>
</div>

</body>
</html>