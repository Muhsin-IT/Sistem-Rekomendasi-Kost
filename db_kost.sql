-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Waktu pembuatan: 20 Jan 2026 pada 18.12
-- Versi server: 8.0.30
-- Versi PHP: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_kost_unu`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `galeri`
--

CREATE TABLE `galeri` (
  `id_galeri` int NOT NULL,
  `id_kost` int DEFAULT NULL,
  `id_kamar` int DEFAULT NULL,
  `nama_file` varchar(255) NOT NULL,
  `kategori_foto` varchar(50) DEFAULT NULL,
  `is_360` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `kamar`
--

CREATE TABLE `kamar` (
  `id_kamar` int NOT NULL,
  `id_kost` int DEFAULT NULL,
  `nama_tipe_kamar` varchar(100) DEFAULT NULL,
  `harga_per_bulan` decimal(12,2) DEFAULT NULL,
  `stok_kamar` int DEFAULT NULL,
  `lebar_ruangan` varchar(20) DEFAULT NULL,
  `sudah_termasuk_listrik` tinyint(1) DEFAULT '0',
  `kapasitas` int DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `kost`
--

CREATE TABLE `kost` (
  `id_kost` int NOT NULL,
  `id_pemilik` int DEFAULT NULL,
  `nama_kost` varchar(100) DEFAULT NULL,
  `alamat` text,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `jenis_kost` enum('Putra','Putri','Campur') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `master_fasilitas`
--

CREATE TABLE `master_fasilitas` (
  `id_master_fasilitas` int NOT NULL,
  `nama_fasilitas` varchar(100) NOT NULL,
  `kategori` enum('Kamar','Kamar Mandi','Umum','Parkir') DEFAULT NULL,
  `id_pemilik` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `master_peraturan`
--

CREATE TABLE `master_peraturan` (
  `id_master_peraturan` int NOT NULL,
  `nama_peraturan` varchar(255) NOT NULL,
  `kategori` enum('Kost','Kamar') DEFAULT 'Kost',
  `id_pemilik` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengajuan_sewa`
--

CREATE TABLE `pengajuan_sewa` (
  `id_pengajuan` int NOT NULL,
  `id_user` int NOT NULL,
  `id_kost` int NOT NULL,
  `id_kamar` int NOT NULL,
  `tanggal_pengajuan` datetime DEFAULT CURRENT_TIMESTAMP,
  `tanggal_mulai_kos` date NOT NULL,
  `durasi_bulan` int NOT NULL DEFAULT '1',
  `status` enum('Menunggu','Diterima','Ditolak','Dibatalkan') DEFAULT 'Menunggu'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `rel_fasilitas`
--

CREATE TABLE `rel_fasilitas` (
  `id_rel_fasilitas` int NOT NULL,
  `id_kamar` int DEFAULT NULL,
  `id_kost` int DEFAULT NULL,
  `id_master_fasilitas` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `rel_peraturan`
--

CREATE TABLE `rel_peraturan` (
  `id_rel_peraturan` int NOT NULL,
  `id_kost` int DEFAULT NULL,
  `id_kamar` int DEFAULT NULL,
  `id_master_peraturan` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `review`
--

CREATE TABLE `review` (
  `id_review` int NOT NULL,
  `id_kost` int DEFAULT NULL,
  `id_kamar` int DEFAULT NULL,
  `id_user` int DEFAULT NULL,
  `rating` tinyint DEFAULT NULL,
  `skor_akurasi` tinyint DEFAULT '0',
  `komentar` text,
  `jenis_reviewer` enum('sewa','survei') DEFAULT NULL,
  `tanggal_review` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `survei`
--

CREATE TABLE `survei` (
  `id_survei` int NOT NULL,
  `id_user` int NOT NULL,
  `id_kost` int NOT NULL,
  `tgl_survei` date NOT NULL,
  `jam_survei` time NOT NULL,
  `status` enum('Menunggu','Diterima','Ditolak','Selesai') DEFAULT 'Menunggu'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id_user` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) DEFAULT NULL,
  `role` enum('adminsuper','pemilik','mahasiswa') NOT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `foto_profil` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `galeri`
--
ALTER TABLE `galeri`
  ADD PRIMARY KEY (`id_galeri`),
  ADD KEY `id_kost` (`id_kost`),
  ADD KEY `id_kamar` (`id_kamar`);

--
-- Indeks untuk tabel `kamar`
--
ALTER TABLE `kamar`
  ADD PRIMARY KEY (`id_kamar`),
  ADD KEY `id_kost` (`id_kost`);

--
-- Indeks untuk tabel `kost`
--
ALTER TABLE `kost`
  ADD PRIMARY KEY (`id_kost`),
  ADD KEY `id_pemilik` (`id_pemilik`);

--
-- Indeks untuk tabel `master_fasilitas`
--
ALTER TABLE `master_fasilitas`
  ADD PRIMARY KEY (`id_master_fasilitas`),
  ADD KEY `fk_fasil_pemilik` (`id_pemilik`);

--
-- Indeks untuk tabel `master_peraturan`
--
ALTER TABLE `master_peraturan`
  ADD PRIMARY KEY (`id_master_peraturan`),
  ADD KEY `fk_perat_pemilik` (`id_pemilik`);

--
-- Indeks untuk tabel `pengajuan_sewa`
--
ALTER TABLE `pengajuan_sewa`
  ADD PRIMARY KEY (`id_pengajuan`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_kost` (`id_kost`),
  ADD KEY `id_kamar` (`id_kamar`);

--
-- Indeks untuk tabel `rel_fasilitas`
--
ALTER TABLE `rel_fasilitas`
  ADD PRIMARY KEY (`id_rel_fasilitas`),
  ADD KEY `id_kamar` (`id_kamar`),
  ADD KEY `id_kost` (`id_kost`),
  ADD KEY `id_master_fasilitas` (`id_master_fasilitas`);

--
-- Indeks untuk tabel `rel_peraturan`
--
ALTER TABLE `rel_peraturan`
  ADD PRIMARY KEY (`id_rel_peraturan`),
  ADD KEY `id_kost` (`id_kost`),
  ADD KEY `id_master_peraturan` (`id_master_peraturan`),
  ADD KEY `fk_rel_per_kamar` (`id_kamar`);

--
-- Indeks untuk tabel `review`
--
ALTER TABLE `review`
  ADD PRIMARY KEY (`id_review`),
  ADD KEY `id_kamar` (`id_kamar`),
  ADD KEY `id_mahasiswa` (`id_user`),
  ADD KEY `id_kost` (`id_kost`);

--
-- Indeks untuk tabel `survei`
--
ALTER TABLE `survei`
  ADD PRIMARY KEY (`id_survei`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_kost` (`id_kost`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `galeri`
--
ALTER TABLE `galeri`
  MODIFY `id_galeri` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `kamar`
--
ALTER TABLE `kamar`
  MODIFY `id_kamar` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `kost`
--
ALTER TABLE `kost`
  MODIFY `id_kost` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `master_fasilitas`
--
ALTER TABLE `master_fasilitas`
  MODIFY `id_master_fasilitas` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `master_peraturan`
--
ALTER TABLE `master_peraturan`
  MODIFY `id_master_peraturan` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `pengajuan_sewa`
--
ALTER TABLE `pengajuan_sewa`
  MODIFY `id_pengajuan` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `rel_fasilitas`
--
ALTER TABLE `rel_fasilitas`
  MODIFY `id_rel_fasilitas` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `rel_peraturan`
--
ALTER TABLE `rel_peraturan`
  MODIFY `id_rel_peraturan` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `review`
--
ALTER TABLE `review`
  MODIFY `id_review` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `survei`
--
ALTER TABLE `survei`
  MODIFY `id_survei` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int NOT NULL AUTO_INCREMENT;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `galeri`
--
ALTER TABLE `galeri`
  ADD CONSTRAINT `galeri_ibfk_1` FOREIGN KEY (`id_kost`) REFERENCES `kost` (`id_kost`) ON DELETE CASCADE,
  ADD CONSTRAINT `galeri_ibfk_2` FOREIGN KEY (`id_kamar`) REFERENCES `kamar` (`id_kamar`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `kamar`
--
ALTER TABLE `kamar`
  ADD CONSTRAINT `kamar_ibfk_1` FOREIGN KEY (`id_kost`) REFERENCES `kost` (`id_kost`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `kost`
--
ALTER TABLE `kost`
  ADD CONSTRAINT `kost_ibfk_1` FOREIGN KEY (`id_pemilik`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `master_fasilitas`
--
ALTER TABLE `master_fasilitas`
  ADD CONSTRAINT `fk_fasil_pemilik` FOREIGN KEY (`id_pemilik`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `master_peraturan`
--
ALTER TABLE `master_peraturan`
  ADD CONSTRAINT `fk_perat_pemilik` FOREIGN KEY (`id_pemilik`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pengajuan_sewa`
--
ALTER TABLE `pengajuan_sewa`
  ADD CONSTRAINT `pengajuan_sewa_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `pengajuan_sewa_ibfk_2` FOREIGN KEY (`id_kost`) REFERENCES `kost` (`id_kost`) ON DELETE CASCADE,
  ADD CONSTRAINT `pengajuan_sewa_ibfk_3` FOREIGN KEY (`id_kamar`) REFERENCES `kamar` (`id_kamar`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `rel_fasilitas`
--
ALTER TABLE `rel_fasilitas`
  ADD CONSTRAINT `rel_fasilitas_ibfk_1` FOREIGN KEY (`id_kamar`) REFERENCES `kamar` (`id_kamar`) ON DELETE CASCADE,
  ADD CONSTRAINT `rel_fasilitas_ibfk_2` FOREIGN KEY (`id_kost`) REFERENCES `kost` (`id_kost`) ON DELETE CASCADE,
  ADD CONSTRAINT `rel_fasilitas_ibfk_3` FOREIGN KEY (`id_master_fasilitas`) REFERENCES `master_fasilitas` (`id_master_fasilitas`);

--
-- Ketidakleluasaan untuk tabel `rel_peraturan`
--
ALTER TABLE `rel_peraturan`
  ADD CONSTRAINT `fk_rel_per_kamar` FOREIGN KEY (`id_kamar`) REFERENCES `kamar` (`id_kamar`) ON DELETE CASCADE,
  ADD CONSTRAINT `rel_peraturan_ibfk_1` FOREIGN KEY (`id_kost`) REFERENCES `kost` (`id_kost`) ON DELETE CASCADE,
  ADD CONSTRAINT `rel_peraturan_ibfk_2` FOREIGN KEY (`id_master_peraturan`) REFERENCES `master_peraturan` (`id_master_peraturan`);

--
-- Ketidakleluasaan untuk tabel `review`
--
ALTER TABLE `review`
  ADD CONSTRAINT `review_ibfk_1` FOREIGN KEY (`id_kamar`) REFERENCES `kamar` (`id_kamar`),
  ADD CONSTRAINT `review_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`),
  ADD CONSTRAINT `review_ibfk_kost` FOREIGN KEY (`id_kost`) REFERENCES `kost` (`id_kost`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `survei`
--
ALTER TABLE `survei`
  ADD CONSTRAINT `survei_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `survei_ibfk_2` FOREIGN KEY (`id_kost`) REFERENCES `kost` (`id_kost`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
