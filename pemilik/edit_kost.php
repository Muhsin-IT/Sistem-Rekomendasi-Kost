<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'pemilik') {
    header("Location: ../login.php");
    exit;
}

$id_user_login = $_SESSION['id_user'];
$id_kost = $_GET['id'];

// 1. AMBIL DATA LAMA
$q_data = mysqli_query($conn, "SELECT * FROM kost WHERE id_kost='$id_kost' AND id_pemilik='$id_user_login'");
$k = mysqli_fetch_assoc($q_data);

// Jika koordinat masih kosong (data lama), set default ke UNU Jogja
$lat_awal = ($k['latitude'] != 0 && $k['latitude'] != "") ? $k['latitude'] : -7.7472;
$lng_awal = ($k['longitude'] != 0 && $k['longitude'] != "") ? $k['longitude'] : 110.3554;

// 2. AMBIL GALERI FOTO
$fotos = [];
$q_foto = mysqli_query($conn, "SELECT * FROM galeri WHERE id_kost='$id_kost' AND id_kamar IS NULL");
while ($f = mysqli_fetch_assoc($q_foto)) {
    $fotos[$f['kategori_foto']] = $f;
}

// 3. AMBIL FASILITAS & PERATURAN TERPILIH
$selected_f = [];
$q_sf = mysqli_query($conn, "SELECT id_master_fasilitas FROM rel_fasilitas WHERE id_kost='$id_kost' AND id_kamar IS NULL");
while ($sf = mysqli_fetch_assoc($q_sf)) {
    $selected_f[] = $sf['id_master_fasilitas'];
}

$selected_p = [];
$q_sp = mysqli_query($conn, "SELECT id_master_peraturan FROM rel_peraturan WHERE id_kost='$id_kost' AND id_kamar IS NULL");
while ($sp = mysqli_fetch_assoc($q_sp)) {
    $selected_p[] = $sp['id_master_peraturan'];
}


// LOGIKA UPDATE
if (isset($_POST['update_kost'])) {
    $nama   = mysqli_real_escape_string($conn, $_POST['nama_kost']);
    $jenis  = $_POST['jenis_kost'];
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $lat    = $_POST['latitude'];
    $lng    = $_POST['longitude'];

    // Update Data Utama
    mysqli_query($conn, "UPDATE kost SET nama_kost='$nama', alamat='$alamat', latitude='$lat', longitude='$lng', jenis_kost='$jenis' WHERE id_kost='$id_kost'");

    // Fungsi Update Foto
    function updateFoto($file, $id_k, $kat, $is360, $conn, $foto_lama)
    {
        if (!empty($file['name'])) {
            $nama_file = time() . '_' . rand(100, 999) . '_' . $file['name'];
            if (move_uploaded_file($file['tmp_name'], "../assets/img/galeri/" . $nama_file)) {
                // Hapus file lama
                if ($foto_lama && file_exists("../assets/img/galeri/" . $foto_lama['nama_file'])) {
                    unlink("../assets/img/galeri/" . $foto_lama['nama_file']);
                }
                // Update DB: Hapus record lama, insert baru
                mysqli_query($conn, "DELETE FROM galeri WHERE id_kost='$id_k' AND kategori_foto='$kat'");
                mysqli_query($conn, "INSERT INTO galeri (id_kost, nama_file, kategori_foto, is_360) VALUES ('$id_k', '$nama_file', '$kat', '$is360')");
            }
        } elseif ($foto_lama) {
            // Update status 360 saja jika tidak ganti foto
            mysqli_query($conn, "UPDATE galeri SET is_360='$is360' WHERE id_kost='$id_k' AND kategori_foto='$kat'");
        }
    }

    updateFoto($_FILES['foto_depan'], $id_kost, 'Tampak Depan', isset($_POST['is_360_depan']) ? 1 : 0, $conn, $fotos['Tampak Depan'] ?? null);
    updateFoto($_FILES['foto_dalam'], $id_kost, 'Dalam Bangunan', isset($_POST['is_360_dalam']) ? 1 : 0, $conn, $fotos['Dalam Bangunan'] ?? null);
    updateFoto($_FILES['foto_jalan'], $id_kost, 'Tampak Jalan', 0, $conn, $fotos['Tampak Jalan'] ?? null);

    // Update Fasilitas (Reset lalu Insert Ulang)
    mysqli_query($conn, "DELETE FROM rel_fasilitas WHERE id_kost='$id_kost' AND id_kamar IS NULL");
    if (!empty($_POST['fasilitas'])) {
        foreach ($_POST['fasilitas'] as $id) mysqli_query($conn, "INSERT INTO rel_fasilitas (id_kost, id_master_fasilitas) VALUES ('$id_kost', '$id')");
    }

    // Update Peraturan
    mysqli_query($conn, "DELETE FROM rel_peraturan WHERE id_kost='$id_kost' AND id_kamar IS NULL");
    if (!empty($_POST['peraturan'])) {
        foreach ($_POST['peraturan'] as $id) mysqli_query($conn, "INSERT INTO rel_peraturan (id_kost, id_master_peraturan) VALUES ('$id_kost', '$id')");
    }

    echo "<script>alert('Data Kost Berhasil Diperbarui!'); window.location='dashboard.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <title>Edit Kost</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/esri-leaflet@3.0.10/dist/esri-leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/esri-leaflet-geocoder@3.1.4/dist/esri-leaflet-geocoder.css">
    <script src="https://unpkg.com/esri-leaflet-geocoder@3.1.4/dist/esri-leaflet-geocoder.js"></script>

    <style>
        .sidebar {
            min-height: 100vh;
            background: #198754;
            color: white;
            padding: 20px;
            position: fixed;
            width: 16.6%;
        }

        .content-area {
            margin-left: 17%;
            padding: 30px;
            background: #f8f9fa;
            min-height: 100vh;
        }

        #map {
            height: 400px;
            width: 100%;
            border-radius: 8px;
            border: 2px solid #ccc;
            z-index: 1;
        }

        .geocoder-control-input {
            border: 2px solid #198754 !important;
            height: 35px !important;
        }

        .preview-img {
            height: 120px;
            width: 100%;
            object-fit: cover;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>
            <div class="col-md-10 content-area">
                <div class="card shadow-sm border-0 p-4">
                    <h4 class="fw-bold text-success mb-4">Edit Data Kost</h4>
                    <form method="POST" enctype="multipart/form-data">

                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label class="form-label">Nama Kost</label>
                                <input type="text" name="nama_kost" class="form-control" value="<?= $k['nama_kost'] ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Jenis</label>
                                <select name="jenis_kost" class="form-select">
                                    <option value="Putra" <?= $k['jenis_kost'] == 'Putra' ? 'selected' : '' ?>>Putra</option>
                                    <option value="Putri" <?= $k['jenis_kost'] == 'Putri' ? 'selected' : '' ?>>Putri</option>
                                    <option value="Campur" <?= $k['jenis_kost'] == 'Campur' ? 'selected' : '' ?>>Campur</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Lokasi Peta (Geser Pin untuk update)</label>
                            <div id="map"></div>
                            <small class="text-danger fw-bold" id="status_map"></small>
                            <div class="row mt-2 g-2">
                                <div class="col-6"><input type="text" name="latitude" id="lat" class="form-control bg-light" value="<?= $k['latitude'] ?>" readonly></div>
                                <div class="col-6"><input type="text" name="longitude" id="lng" class="form-control bg-light" value="<?= $k['longitude'] ?>" readonly></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Alamat Lengkap</label>
                            <textarea name="alamat" id="alamat_lengkap" class="form-control" rows="2" readonly><?= $k['alamat'] ?></textarea>
                        </div>

                        <hr>
                        <div class="row mb-3 text-center">
                            <?php
                            $cats = ['Tampak Depan' => 'foto_depan', 'Dalam Bangunan' => 'foto_dalam', 'Tampak Jalan' => 'foto_jalan'];
                            foreach ($cats as $label => $name):
                                $src = isset($fotos[$label]) ? "../assets/img/galeri/" . $fotos[$label]['nama_file'] : "https://via.placeholder.com/150";
                            ?>
                                <div class="col-md-4">
                                    <label class="small fw-bold"><?= $label ?></label>
                                    <img id="p_<?= $name ?>" src="<?= $src ?>" class="preview-img mb-2">
                                    <input type="file" name="<?= $name ?>" class="form-control form-control-sm" onchange="preview(this, 'p_<?= $name ?>')">
                                    <?php if ($label != 'Tampak Jalan'): ?>
                                        <div class="form-check text-start mt-1">
                                            <input type="checkbox" name="is_360_<?= explode('_', $name)[1] ?>" value="1" <?= (isset($fotos[$label]) && $fotos[$label]['is_360']) ? 'checked' : '' ?>>
                                            <small>Mode 360°</small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h6>Fasilitas Umum</h6>
                                <?php $fs = mysqli_query($conn, "SELECT * FROM master_fasilitas WHERE kategori='Umum' AND (id_pemilik IS NULL OR id_pemilik='$id_user_login')");
                                while ($f = mysqli_fetch_assoc($fs)) {
                                    $chk = in_array($f['id_master_fasilitas'], $selected_f) ? 'checked' : '';
                                    echo "<div class='form-check'><input type='checkbox' class='form-check-input' name='fasilitas[]' value='$f[id_master_fasilitas]' $chk> $f[nama_fasilitas]</div>";
                                } ?>
                            </div>
                            <div class="col-md-6">
                                <h6>Peraturan</h6>
                                <?php $ps = mysqli_query($conn, "SELECT * FROM master_peraturan WHERE kategori='Kost' AND (id_pemilik IS NULL OR id_pemilik='$id_user_login')");
                                while ($p = mysqli_fetch_assoc($ps)) {
                                    $chk = in_array($p['id_master_peraturan'], $selected_p) ? 'checked' : '';
                                    echo "<div class='form-check'><input type='checkbox' class='form-check-input' name='peraturan[]' value='$p[id_master_peraturan]' $chk> $p[nama_peraturan]</div>";
                                } ?>
                            </div>
                        </div>

                        <button type="submit" name="update_kost" class="btn btn-primary w-100 fw-bold">SIMPAN PERUBAHAN</button>
                        <a href="dashboard.php" class="btn btn-light w-100 border mt-2">Batal</a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function preview(input, id) {
            if (input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById(id).src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // --- MAPS SCRIPT (Start at Saved Location) ---
        // Kita ambil koordinat dari PHP
        var startLat = <?= $lat_awal ?>;
        var startLng = <?= $lng_awal ?>;

        var map = L.map('map').setView([startLat, startLng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        var marker = L.marker([startLat, startLng], {
            draggable: true
        }).addTo(map);

        function getAddress(lat, lng) {
            document.getElementById('status_map').innerText = "Sedang mencari detail alamat...";
            document.getElementById('lat').value = lat;
            document.getElementById('lng').value = lng;

            var url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`;

            fetch(url)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('alamat_lengkap').value = data.display_name;
                    document.getElementById('status_map').innerText = "";
                })
                .catch(err => console.error(err));
        }

        marker.on('dragend', function(e) {
            var c = e.target.getLatLng();
            getAddress(c.lat, c.lng);
        });

        map.on('click', function(e) {
            marker.setLatLng(e.latlng);
            getAddress(e.latlng.lat, e.latlng.lng);
        });

        // ESRI Search
        var searchControl = L.esri.Geocoding.geosearch({
            position: 'topright',
            placeholder: 'Cari tempat...',
            useMapBounds: false,
            providers: [L.esri.Geocoding.arcgisOnlineProvider({
                apikey: null
            })]
        }).addTo(map);

        searchControl.on('results', function(data) {
            if (data.results.length > 0) {
                var result = data.results[0];
                marker.setLatLng(result.latlng);
                map.setView(result.latlng, 17);
                getAddress(result.latlng.lat, result.latlng.lng);
            }
        });
    </script>
</body>

</html>