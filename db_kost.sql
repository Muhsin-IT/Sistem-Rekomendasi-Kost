-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Waktu pembuatan: 04 Jan 2026 pada 18.12
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

--
-- Dumping data untuk tabel `galeri`
--

INSERT INTO `galeri` (`id_galeri`, `id_kost`, `id_kamar`, `nama_file`, `kategori_foto`, `is_360`) VALUES
(15, 4, NULL, '1766890823_410_Desain-Rumah-Kost-Putri-2-Lantai-8×12-meter.jpg', 'Tampak Depan', 0),
(17, 4, NULL, '1766890823_356_632_713483777.jpg', 'Tampak Jalan', 0),
(21, 4, 8, '1766892312_kmr_Dalam_Kamar_WhatsApp Image 2025-12-28 at 10.21.08 (1).jpeg', 'Dalam Kamar', 1),
(22, 4, 8, '1766892312_kmr_Kamar_Mandi_WhatsApp Image 2025-12-28 at 10.21.09.jpeg', 'Kamar Mandi', 0),
(23, 4, 8, '1766892312_kmr_Depan_Kamar_WhatsApp Image 2025-12-28 at 10.21.08.jpeg', 'Depan Kamar', 0),
(24, 1, 5, '1766894672_kmr_Dalam_Kamar_WhatsApp Image 2025-12-28 at 10.21.08 (1).jpeg', 'Dalam Kamar', 1),
(25, 1, 5, '1766894672_kmr_Kamar_Mandi_WhatsApp Image 2025-12-28 at 10.21.08.jpeg', 'Kamar Mandi', 0),
(26, 1, 5, '1766894672_kmr_Depan_Kamar_WhatsApp Image 2025-12-28 at 10.21.09.jpeg', 'Depan Kamar', 0),
(27, 1, 6, '1766894708_kmr_Dalam_Kamar_WhatsApp Image 2025-12-28 at 10.21.08.jpeg', 'Dalam Kamar', 1),
(28, 1, 6, '1766894708_kmr_Kamar_Mandi_WhatsApp Image 2025-12-28 at 10.21.09.jpeg', 'Kamar Mandi', 0),
(29, 1, 6, '1766894708_kmr_Depan_Kamar_WhatsApp Image 2025-12-28 at 10.21.08 (1).jpeg', 'Depan Kamar', 0),
(30, 4, NULL, '1766895971_397_WhatsApp Image 2025-12-28 at 10.21.08.jpeg', 'Dalam Bangunan', 1),
(31, 4, 11, '1766896646_kmr_Dalam_Kamar_WhatsApp Image 2025-12-28 at 10.21.08 (1).jpeg', 'Dalam Kamar', 1),
(32, 4, 11, '1766896646_kmr_Kamar_Mandi_WhatsApp Image 2025-12-28 at 10.21.09.jpeg', 'Kamar Mandi', 0),
(33, 4, 11, '1766896646_kmr_Depan_Kamar_WhatsApp Image 2025-12-28 at 10.21.08.jpeg', 'Depan Kamar', 0),
(35, 1, NULL, '1766902859_770_WhatsApp Image 2025-12-28 at 10.21.09.jpeg', 'Dalam Bangunan', 1),
(36, 1, NULL, '1766902859_519_WhatsApp Image 2025-12-28 at 10.21.08 (1).jpeg', 'Tampak Jalan', 0),
(37, 1, NULL, '1766924456_745_WhatsApp Image 2025-12-28 at 10.21.08.jpeg', 'Tampak Depan', 1),
(38, 1, 12, '1766939215_0_img_DalamKamar_Solusi-Kamar-Mandi-Umum-Ringkas-Nyaman-dan-Cepat-1-300x200.webp', 'Dalam Kamar', 0),
(39, 1, 12, '1766939215_0_360_DalamKamar_WhatsApp Image 2025-12-28 at 10.21.08.jpeg', 'Dalam Kamar', 1),
(40, 1, 12, '1766939215_0_img_KamarMandi_Hitam Putih Sinematik Teks Judul Cuplikan Video.gif', 'Kamar Mandi', 0),
(41, 1, 12, '1766939215_0_360_KamarMandi_WhatsApp Image 2025-12-28 at 10.21.08 (1).jpeg', 'Kamar Mandi', 1),
(42, 1, 12, '1766939215_1_360_KamarMandi_WhatsApp Image 2025-12-28 at 10.21.08.jpeg', 'Kamar Mandi', 1),
(43, 1, 12, '1766939215_0_img_DepanKamar_Desain-Rumah-Kost-Putri-2-Lantai-8×12-meter.webp', 'Depan Kamar', 0),
(44, 1, 12, '1766939215_0_360_DepanKamar_WhatsApp Image 2025-12-28 at 10.21.08.jpeg', 'Depan Kamar', 1),
(45, 1, 6, '1766941697_0_img_DalamKamar_Desain-Rumah-Kost-Putri-2-Lantai-8×12-meter.webp', 'Dalam Kamar', 0),
(46, 1, 6, '1766941697_1_img_DalamKamar_images.jpg', 'Dalam Kamar', 0),
(47, 1, 6, '1766941697_2_img_DalamKamar_kost-gaya-kekinian.jpg', 'Dalam Kamar', 0),
(48, 4, 8, '1767348612_0_img_DalamKamar_vector-icon-rest-area-illustration-260nw-2405274011.webp', 'Dalam Kamar', 0),
(49, 13, NULL, '1767423163_729_memilih-kos-kosan.webp', 'Tampak Depan', 0),
(50, 13, NULL, '1767423163_533_R.jpg', 'Dalam Bangunan', 0),
(51, 13, NULL, '1767423163_941_OIP.webp', 'Tampak Jalan', 0),
(52, 13, 13, '1767423466_0_img_DalamKamar_ChIJ65tlUYYLOjIRX7ft0jGaiwAphoto3-1024x768.jpg', 'Dalam Kamar', 0),
(53, 13, 13, '1767423466_0_img_KamarMandi_Cara-Memulai-Bisnis-Kos-Kosan.jpg', 'Kamar Mandi', 0),
(54, 13, 13, '1767423466_1_img_KamarMandi_OIP (1).webp', 'Kamar Mandi', 0),
(55, 13, 13, '1767423466_0_img_DepanKamar_Cara-Memulai-Bisnis-Kos-Kosan.jpg', 'Depan Kamar', 0),
(56, 13, 13, '1767525681_0_360_DalamKamar_WhatsApp Image 2025-12-28 at 10.21.08.jpeg', 'Dalam Kamar', 1);

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

--
-- Dumping data untuk tabel `kamar`
--

INSERT INTO `kamar` (`id_kamar`, `id_kost`, `nama_tipe_kamar`, `harga_per_bulan`, `stok_kamar`, `lebar_ruangan`, `sudah_termasuk_listrik`, `kapasitas`) VALUES
(5, 1, 'B2', 320000.00, 2, '5x5 m', 1, 1),
(6, 1, 'B5', 1200000.00, 2, '10x5 m', 1, 1),
(8, 4, 'A2', 2500000.00, 5, '3x6 m', 0, 1),
(11, 4, 'B1-B5', 500000.00, 4, '4x3 m', 0, 1),
(12, 1, 'VIP A1', 120000.00, 3, '4x5 m', 0, 1),
(13, 13, 'Kamar A5U', 450000.00, 20, '5x5 m', 0, 1);

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

--
-- Dumping data untuk tabel `kost`
--

INSERT INTO `kost` (`id_kost`, `id_pemilik`, `nama_kost`, `alamat`, `latitude`, `longitude`, `jenis_kost`) VALUES
(1, 1, 'Kos Pak Kunendro', 'Sidomoyo, Godean, Sleman, Daerah Istimewa Yogyakarta, Jawa, 55264, Indonesia', -7.76494682, 110.32245052, 'Campur'),
(4, 2, 'MU Kost 1', 'Banyuraden, Gamping, Sleman, Daerah Istimewa Yogyakarta, Jawa, 55294, Indonesia', -7.79330368, 110.33071980, 'Putra'),
(13, 3, 'kost Aneka Binatang', 'Lingkar UTY Barat, Sendangadi, Mlati, Sleman Regency, Special Region of Yogyakarta, Java, 55291, Indonesia', -7.74720000, 110.35540000, 'Putra');

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

--
-- Dumping data untuk tabel `master_fasilitas`
--

INSERT INTO `master_fasilitas` (`id_master_fasilitas`, `nama_fasilitas`, `kategori`, `id_pemilik`) VALUES
(1, 'AC', 'Parkir', NULL),
(2, 'Kasur', 'Kamar', NULL),
(5, 'Shower', 'Kamar Mandi', NULL),
(9, 'jemuran', 'Umum', NULL),
(12, 'Mushola', 'Umum', NULL),
(13, 'Dapur', 'Umum', NULL),
(14, 'Lapangan', 'Umum', NULL),
(16, 'Parkiran Mobil', 'Parkir', NULL),
(18, 'Wifi', 'Umum', 2),
(19, 'sdskncs', 'Kamar', 2),
(20, 'sdsfew', 'Kamar', 2),
(21, 'thyrynb', 'Kamar', 2),
(22, 'utbfb fdvd', 'Kamar', 2),
(23, 'egrfvv', 'Kamar', 2),
(24, 'dgv', 'Kamar', 2),
(25, 'parkir Motor', 'Parkir', 2),
(26, 'auisygutd', 'Parkir', 1),
(27, 'asdjh', 'Kamar', 1),
(28, 'asdbac', 'Kamar', 1),
(29, 'iasdbaf', 'Kamar', 1),
(30, 'ajsbas', 'Kamar', 1),
(31, 'Kompor', 'Umum', NULL),
(32, 'Parkiran Motor', 'Parkir', NULL),
(33, 'Wc Duduk', 'Kamar Mandi', NULL),
(34, 'Kamarmandi Dalam', 'Kamar', NULL),
(35, 'Bantal', 'Kamar', NULL),
(36, 'Selimut', 'Kamar', NULL),
(37, 'Guling', 'Kamar', NULL),
(38, 'Tv', 'Kamar', NULL),
(39, 'Dapur Dalam', 'Kamar', NULL),
(40, 'Bak Mandi', 'Kamar Mandi', NULL),
(41, 'Bak Air', 'Kamar Mandi', NULL),
(42, 'Cermin', 'Kamar Mandi', NULL);

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

--
-- Dumping data untuk tabel `master_peraturan`
--

INSERT INTO `master_peraturan` (`id_master_peraturan`, `nama_peraturan`, `kategori`, `id_pemilik`) VALUES
(1, 'Akses 24 Jam', 'Kost', NULL),
(3, 'Maks. 2 Orang/Kamar', 'Kost', NULL),
(4, 'Lawan Jenis Dilarang Masuk', 'Kamar', NULL),
(5, 'Dilarang membawa hewan', 'Kamar', NULL),
(6, 'Dilarng Membawa teman anjing', 'Kamar', NULL),
(8, 'dilarang toxix', 'Kamar', NULL),
(10, 'Jam Malam 22.00', 'Kost', NULL),
(11, 'Dilarang Berisik', 'Kost', NULL),
(12, 'Tamu Wajib Lapor', 'Kost', NULL),
(13, 'Di larang membawa miras', 'Kost', NULL),
(15, 'tamu harap lapor', 'Kost', 2),
(16, 'sdak', 'Kamar', 2),
(17, 'sdsdas', 'Kamar', 2),
(18, 'dsfffds', 'Kamar', 2),
(19, 'rgrdscx', 'Kamar', 2),
(20, 'ervdzcx', 'Kamar', 2),
(21, 'wfervcx', 'Kamar', 2),
(22, 'tes1.tes2', 'Kost', 2),
(23, 'tes3', 'Kost', 2),
(24, 'tes4', 'Kost', 2);

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

--
-- Dumping data untuk tabel `pengajuan_sewa`
--

INSERT INTO `pengajuan_sewa` (`id_pengajuan`, `id_user`, `id_kost`, `id_kamar`, `tanggal_pengajuan`, `tanggal_mulai_kos`, `durasi_bulan`, `status`) VALUES
(1, 7, 4, 11, '2026-01-04 22:35:56', '2026-01-13', 1, 'Diterima');

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

--
-- Dumping data untuk tabel `rel_fasilitas`
--

INSERT INTO `rel_fasilitas` (`id_rel_fasilitas`, `id_kamar`, `id_kost`, `id_master_fasilitas`) VALUES
(95, 11, NULL, 1),
(96, 11, NULL, 2),
(97, 11, NULL, 5),
(98, 11, NULL, 19),
(99, 11, NULL, 20),
(100, 11, NULL, 21),
(101, 11, NULL, 22),
(102, 11, NULL, 23),
(103, 11, NULL, 24),
(105, 5, NULL, 1),
(106, 5, NULL, 2),
(107, 5, NULL, 5),
(110, 12, NULL, 27),
(111, 12, NULL, 28),
(112, 12, NULL, 29),
(113, 12, NULL, 30),
(114, 12, NULL, 1),
(115, 12, NULL, 2),
(116, 12, NULL, 5),
(117, 12, NULL, 26),
(119, 12, NULL, 16),
(120, NULL, 1, 9),
(121, NULL, 1, 12),
(125, 6, NULL, 29),
(126, 6, NULL, 30),
(127, 6, NULL, 1),
(128, 6, NULL, 2),
(129, 6, NULL, 5),
(130, NULL, 4, 9),
(131, NULL, 4, 13),
(132, NULL, 4, 18),
(145, NULL, 13, 9),
(146, NULL, 13, 12),
(147, NULL, 13, 13),
(148, NULL, 13, 14),
(149, NULL, 13, 31),
(170, 13, NULL, 2),
(171, 13, NULL, 34),
(172, 13, NULL, 35),
(173, 13, NULL, 36),
(174, 13, NULL, 37),
(175, 13, NULL, 38),
(176, 13, NULL, 39),
(177, 13, NULL, 5),
(178, 13, NULL, 33),
(179, 13, NULL, 40),
(180, 13, NULL, 41),
(181, 13, NULL, 42),
(182, 13, NULL, 1),
(183, 13, NULL, 16),
(184, 13, NULL, 32),
(209, 8, NULL, 21),
(210, 8, NULL, 23),
(211, 8, NULL, 24),
(212, 8, NULL, 2),
(213, 8, NULL, 5),
(214, 8, NULL, 1);

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

--
-- Dumping data untuk tabel `rel_peraturan`
--

INSERT INTO `rel_peraturan` (`id_rel_peraturan`, `id_kost`, `id_kamar`, `id_master_peraturan`) VALUES
(63, NULL, 11, 4),
(64, NULL, 11, 5),
(65, NULL, 11, 6),
(66, NULL, 11, 8),
(67, NULL, 11, 16),
(68, NULL, 11, 17),
(69, NULL, 11, 18),
(70, NULL, 11, 19),
(71, NULL, 11, 20),
(72, NULL, 11, 21),
(81, NULL, 12, 4),
(82, NULL, 12, 5),
(83, NULL, 12, 6),
(84, NULL, 12, 8),
(85, 1, NULL, 1),
(86, 1, NULL, 10),
(87, 1, NULL, 11),
(88, 1, NULL, 12),
(93, NULL, 6, 4),
(94, NULL, 6, 5),
(95, NULL, 6, 6),
(96, NULL, 6, 8),
(97, 4, NULL, 13),
(98, 13, NULL, 1),
(99, 13, NULL, 3),
(100, 13, NULL, 10),
(101, 13, NULL, 11),
(102, 13, NULL, 12),
(103, 13, NULL, 13),
(112, NULL, 13, 4),
(113, NULL, 13, 5),
(114, NULL, 13, 6),
(115, NULL, 13, 8),
(130, NULL, 8, 4),
(131, NULL, 8, 5),
(132, NULL, 8, 8);

-- --------------------------------------------------------

--
-- Struktur dari tabel `review`
--

CREATE TABLE `review` (
  `id_review` int NOT NULL,
  `id_kamar` int DEFAULT NULL,
  `id_mahasiswa` int DEFAULT NULL,
  `rating` tinyint DEFAULT NULL,
  `komentar` text
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
  `no_hp` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id_user`, `username`, `password`, `nama_lengkap`, `role`, `no_hp`) VALUES
(1, 'muhsin', '$2y$10$0ksXJMXGKZIWB.9yZWTPzuCw/vbvCDJyPMNpcNhrQEnvXRCKRWfB2', 'muhammad muhsin', 'pemilik', '081234567833'),
(2, 'pemilik1', '$2y$10$0ksXJMXGKZIWB.9yZWTPzuCw/vbvCDJyPMNpcNhrQEnvXRCKRWfB2', 'pemilik 1', 'pemilik', '98765432234567'),
(3, 'pemilik2', '$2y$10$0ksXJMXGKZIWB.9yZWTPzuCw/vbvCDJyPMNpcNhrQEnvXRCKRWfB2', 'pemilik2', 'pemilik', '12345678098'),
(5, 'muhsin1', '$2y$10$0ksXJMXGKZIWB.9yZWTPzuCw/vbvCDJyPMNpcNhrQEnvXRCKRWfB2', 'muhsin1', 'pemilik', '876543'),
(7, 'muhsin123', '$2y$10$0ksXJMXGKZIWB.9yZWTPzuCw/vbvCDJyPMNpcNhrQEnvXRCKRWfB2', 'muhammad muhsin', 'mahasiswa', '12345678'),
(8, 'muhsin321', '$2y$10$0ksXJMXGKZIWB.9yZWTPzuCw/vbvCDJyPMNpcNhrQEnvXRCKRWfB2', 'muhammad muhsin', 'mahasiswa', '23456789'),
(9, 'admin', '$2y$10$0ksXJMXGKZIWB.9yZWTPzuCw/vbvCDJyPMNpcNhrQEnvXRCKRWfB2', 'Super Admin', 'adminsuper', '08123456789');

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
  ADD KEY `id_mahasiswa` (`id_mahasiswa`);

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
  MODIFY `id_galeri` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT untuk tabel `kamar`
--
ALTER TABLE `kamar`
  MODIFY `id_kamar` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `kost`
--
ALTER TABLE `kost`
  MODIFY `id_kost` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `master_fasilitas`
--
ALTER TABLE `master_fasilitas`
  MODIFY `id_master_fasilitas` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT untuk tabel `master_peraturan`
--
ALTER TABLE `master_peraturan`
  MODIFY `id_master_peraturan` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT untuk tabel `pengajuan_sewa`
--
ALTER TABLE `pengajuan_sewa`
  MODIFY `id_pengajuan` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `rel_fasilitas`
--
ALTER TABLE `rel_fasilitas`
  MODIFY `id_rel_fasilitas` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=215;

--
-- AUTO_INCREMENT untuk tabel `rel_peraturan`
--
ALTER TABLE `rel_peraturan`
  MODIFY `id_rel_peraturan` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=133;

--
-- AUTO_INCREMENT untuk tabel `review`
--
ALTER TABLE `review`
  MODIFY `id_review` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
  ADD CONSTRAINT `review_ibfk_2` FOREIGN KEY (`id_mahasiswa`) REFERENCES `users` (`id_user`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
