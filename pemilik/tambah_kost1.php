<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'pemilik') {
    header("Location: ../login.php");
    exit;
}

$id_user_login = $_SESSION['id_user'];

// LOGIKA PHP: SIMPAN DATA
if (isset($_POST['simpan_kost'])) {
    $nama   = mysqli_real_escape_string($conn, $_POST['nama_kost']);
    $jenis  = $_POST['jenis_kost'];
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $lat    = $_POST['latitude'];
    $lng    = $_POST['longitude'];

    // Simpan ke tabel kost
    $query_kost = "INSERT INTO kost (id_pemilik, nama_kost, alamat, latitude, longitude, jenis_kost) 
                   VALUES ('$id_user_login', '$nama', '$alamat', '$lat', '$lng', '$jenis')";

    if (mysqli_query($conn, $query_kost)) {
        $id_kost_baru = mysqli_insert_id($conn);

        // Fungsi upload foto
        function uploadFoto($file, $id_k, $kat, $is360, $conn)
        {
            if (!empty($file['name'])) {
                $nama_file = time() . '_' . rand(100, 999) . '_' . $file['name'];
                move_uploaded_file($file['tmp_name'], "../assets/img/galeri/" . $nama_file);
                mysqli_query($conn, "INSERT INTO galeri (id_kost, nama_file, kategori_foto, is_360) VALUES ('$id_k', '$nama_file', '$kat', '$is360')");
            }
        }

        uploadFoto($_FILES['foto_depan'], $id_kost_baru, 'Tampak Depan', isset($_POST['is_360_depan']) ? 1 : 0, $conn);
        uploadFoto($_FILES['foto_dalam'], $id_kost_baru, 'Dalam Bangunan', isset($_POST['is_360_dalam']) ? 1 : 0, $conn);
        uploadFoto($_FILES['foto_jalan'], $id_kost_baru, 'Tampak Jalan', 0, $conn);

        // Simpan Fasilitas & Peraturan
        if (!empty($_POST['fasilitas'])) {
            foreach ($_POST['fasilitas'] as $id) mysqli_query($conn, "INSERT INTO rel_fasilitas (id_kost, id_master_fasilitas) VALUES ('$id_kost_baru', '$id')");
        }
        if (!empty($_POST['peraturan'])) {
            foreach ($_POST['peraturan'] as $id) mysqli_query($conn, "INSERT INTO rel_peraturan (id_kost, id_master_peraturan) VALUES ('$id_kost_baru', '$id')");
        }

        echo "<script>alert('Kost Berhasil Disimpan!'); window.location='dashboard.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <title>Tambah Kost</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>

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
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>
            <div class="col-md-10 content-area">
                <div class="card shadow-sm border-0 p-4">
                    <h4 class="fw-bold text-success mb-4">Input Data Kost</h4>
                    <form method="POST" enctype="multipart/form-data">

                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label class="form-label">Nama Kost</label>
                                <input type="text" name="nama_kost" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Jenis</label>
                                <select name="jenis_kost" class="form-select">
                                    <option value="Putra">Putra</option>
                                    <option value="Putri">Putri</option>
                                    <option value="Campur">Campur</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Lokasi Peta (Geser Pin untuk update alamat)</label>
                            <div id="map"></div>
                            <small class="text-danger fw-bold" id="status_map"></small>
                            <div class="row mt-2 g-2">
                                <div class="col-6"><input type="text" name="latitude" id="lat" class="form-control bg-light" placeholder="Lat" readonly></div>
                                <div class="col-6"><input type="text" name="longitude" id="lng" class="form-control bg-light" placeholder="Lng" readonly></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Alamat Lengkap (Otomatis)</label>
                            <textarea name="alamat" id="alamat_lengkap" class="form-control" rows="2" readonly></textarea>
                        </div>

                        <hr>
                        <div class="row mb-3">
                            <div class="col-md-4"><label>Foto Depan</label><input type="file" name="foto_depan" class="form-control" required></div>
                            <div class="col-md-4"><label>Foto Dalam</label><input type="file" name="foto_dalam" class="form-control" required></div>
                            <div class="col-md-4"><label>Foto Jalan</label><input type="file" name="foto_jalan" class="form-control" required></div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h6>Fasilitas Umum</h6>
                                <?php $fs = mysqli_query($conn, "SELECT * FROM master_fasilitas WHERE kategori='Umum' AND (id_pemilik IS NULL OR id_pemilik='$id_user_login')");
                                while ($f = mysqli_fetch_assoc($fs)) {
                                    echo "<div class='form-check'><input type='checkbox' class='form-check-input' name='fasilitas[]' value='$f[id_master_fasilitas]'> $f[nama_fasilitas]</div>";
                                } ?>
                            </div>
                            <div class="col-md-6">
                                <h6>Peraturan</h6>
                                <?php $ps = mysqli_query($conn, "SELECT * FROM master_peraturan WHERE kategori='Kost' AND (id_pemilik IS NULL OR id_pemilik='$id_user_login')");
                                while ($p = mysqli_fetch_assoc($ps)) {
                                    echo "<div class='form-check'><input type='checkbox' class='form-check-input' name='peraturan[]' value='$p[id_master_peraturan]'> $p[nama_peraturan]</div>";
                                } ?>
                            </div>
                        </div>

                        <button type="submit" name="simpan_kost" class="btn btn-success w-100 fw-bold">SIMPAN KOST</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // 1. SETUP PETA DASAR
        var startLat = -7.7472; // UNU Jogja
        var startLng = 110.3554;

        var map = L.map('map').setView([startLat, startLng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        // 2. MARKER (PIN)
        var marker = L.marker([startLat, startLng], {
            draggable: true
        }).addTo(map);

        // 3. FUNGSI FETCH ALAMAT (INILAH KUNCINYA!)
        // Kita tidak pakai plugin geocoder untuk ini, tapi fetch manual agar stabil.
        function getAddress(lat, lng) {
            document.getElementById('status_map').innerText = "Sedang mencari alamat...";
            document.getElementById('lat').value = lat;
            document.getElementById('lng').value = lng;

            var url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('alamat_lengkap').value = data.display_name; // Isi Alamat
                    document.getElementById('status_map').innerText = "";
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('status_map').innerText = "Gagal memuat alamat. Cek internet.";
                });
        }

        // 4. EVENT SAAT PIN DIGESER (DRAG END)
        marker.on('dragend', function(e) {
            var c = e.target.getLatLng();
            getAddress(c.lat, c.lng);
        });

        // 5. EVENT SAAT PETA DIKLIK
        map.on('click', function(e) {
            marker.setLatLng(e.latlng);
            getAddress(e.latlng.lat, e.latlng.lng);
        });

        // 6. PLUGIN SEARCH BAR (Hanya untuk mencari lokasi awal)
        L.Control.geocoder({
                defaultMarkGeocode: false,
                placeholder: "Cari lokasi..."
            })
            .on('markgeocode', function(e) {
                var bbox = e.geocode.bbox;
                var center = e.geocode.center;
                marker.setLatLng(center);
                map.fitBounds(bbox);
                getAddress(center.lat, center.lng);
            })
            .addTo(map);

        // Panggil fungsi sekali di awal
        getAddress(startLat, startLng);
    </script>
</body>

</html>