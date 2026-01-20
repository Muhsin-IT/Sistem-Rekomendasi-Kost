/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.6.22-MariaDB, for debian-linux-gnu (aarch64)
--
-- Host: localhost    Database: db_kost
-- ------------------------------------------------------
-- Server version	10.6.22-MariaDB-0ubuntu0.22.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `galeri`
--

DROP TABLE IF EXISTS `galeri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `galeri` (
  `id_galeri` int(11) NOT NULL AUTO_INCREMENT,
  `id_kost` int(11) DEFAULT NULL,
  `id_kamar` int(11) DEFAULT NULL,
  `nama_file` varchar(255) NOT NULL,
  `kategori_foto` varchar(50) DEFAULT NULL,
  `is_360` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id_galeri`),
  KEY `id_kost` (`id_kost`),
  KEY `id_kamar` (`id_kamar`),
  CONSTRAINT `galeri_ibfk_1` FOREIGN KEY (`id_kost`) REFERENCES `kost` (`id_kost`) ON DELETE CASCADE,
  CONSTRAINT `galeri_ibfk_2` FOREIGN KEY (`id_kamar`) REFERENCES `kamar` (`id_kamar`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=150 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kamar`
--

DROP TABLE IF EXISTS `kamar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `kamar` (
  `id_kamar` int(11) NOT NULL AUTO_INCREMENT,
  `id_kost` int(11) DEFAULT NULL,
  `nama_tipe_kamar` varchar(100) DEFAULT NULL,
  `harga_per_bulan` decimal(12,2) DEFAULT NULL,
  `stok_kamar` int(11) DEFAULT NULL,
  `lebar_ruangan` varchar(20) DEFAULT NULL,
  `sudah_termasuk_listrik` tinyint(1) DEFAULT 0,
  `kapasitas` int(11) DEFAULT 1,
  PRIMARY KEY (`id_kamar`),
  KEY `id_kost` (`id_kost`),
  CONSTRAINT `kamar_ibfk_1` FOREIGN KEY (`id_kost`) REFERENCES `kost` (`id_kost`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kost`
--

DROP TABLE IF EXISTS `kost`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `kost` (
  `id_kost` int(11) NOT NULL AUTO_INCREMENT,
  `id_pemilik` int(11) DEFAULT NULL,
  `nama_kost` varchar(100) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `jenis_kost` enum('Putra','Putri','Campur') DEFAULT NULL,
  PRIMARY KEY (`id_kost`),
  KEY `id_pemilik` (`id_pemilik`),
  CONSTRAINT `kost_ibfk_1` FOREIGN KEY (`id_pemilik`) REFERENCES `users` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `master_fasilitas`
--

DROP TABLE IF EXISTS `master_fasilitas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `master_fasilitas` (
  `id_master_fasilitas` int(11) NOT NULL AUTO_INCREMENT,
  `nama_fasilitas` varchar(100) NOT NULL,
  `kategori` enum('Kamar','Kamar Mandi','Umum','Parkir') DEFAULT NULL,
  `id_pemilik` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_master_fasilitas`),
  KEY `fk_fasil_pemilik` (`id_pemilik`),
  CONSTRAINT `fk_fasil_pemilik` FOREIGN KEY (`id_pemilik`) REFERENCES `users` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=114 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `master_peraturan`
--

DROP TABLE IF EXISTS `master_peraturan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `master_peraturan` (
  `id_master_peraturan` int(11) NOT NULL AUTO_INCREMENT,
  `nama_peraturan` varchar(255) NOT NULL,
  `kategori` enum('Kost','Kamar') DEFAULT 'Kost',
  `id_pemilik` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_master_peraturan`),
  KEY `fk_perat_pemilik` (`id_pemilik`),
  CONSTRAINT `fk_perat_pemilik` FOREIGN KEY (`id_pemilik`) REFERENCES `users` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pengajuan_sewa`
--

DROP TABLE IF EXISTS `pengajuan_sewa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pengajuan_sewa` (
  `id_pengajuan` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `id_kost` int(11) NOT NULL,
  `id_kamar` int(11) NOT NULL,
  `tanggal_pengajuan` datetime DEFAULT current_timestamp(),
  `tanggal_mulai_kos` date NOT NULL,
  `durasi_bulan` int(11) NOT NULL DEFAULT 1,
  `status` enum('Menunggu','Diterima','Ditolak','Dibatalkan') DEFAULT 'Menunggu',
  PRIMARY KEY (`id_pengajuan`),
  KEY `id_user` (`id_user`),
  KEY `id_kost` (`id_kost`),
  KEY `id_kamar` (`id_kamar`),
  CONSTRAINT `pengajuan_sewa_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
  CONSTRAINT `pengajuan_sewa_ibfk_2` FOREIGN KEY (`id_kost`) REFERENCES `kost` (`id_kost`) ON DELETE CASCADE,
  CONSTRAINT `pengajuan_sewa_ibfk_3` FOREIGN KEY (`id_kamar`) REFERENCES `kamar` (`id_kamar`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rel_fasilitas`
--

DROP TABLE IF EXISTS `rel_fasilitas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rel_fasilitas` (
  `id_rel_fasilitas` int(11) NOT NULL AUTO_INCREMENT,
  `id_kamar` int(11) DEFAULT NULL,
  `id_kost` int(11) DEFAULT NULL,
  `id_master_fasilitas` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_rel_fasilitas`),
  KEY `id_kamar` (`id_kamar`),
  KEY `id_kost` (`id_kost`),
  KEY `id_master_fasilitas` (`id_master_fasilitas`),
  CONSTRAINT `rel_fasilitas_ibfk_1` FOREIGN KEY (`id_kamar`) REFERENCES `kamar` (`id_kamar`) ON DELETE CASCADE,
  CONSTRAINT `rel_fasilitas_ibfk_2` FOREIGN KEY (`id_kost`) REFERENCES `kost` (`id_kost`) ON DELETE CASCADE,
  CONSTRAINT `rel_fasilitas_ibfk_3` FOREIGN KEY (`id_master_fasilitas`) REFERENCES `master_fasilitas` (`id_master_fasilitas`)
) ENGINE=InnoDB AUTO_INCREMENT=557 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rel_peraturan`
--

DROP TABLE IF EXISTS `rel_peraturan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rel_peraturan` (
  `id_rel_peraturan` int(11) NOT NULL AUTO_INCREMENT,
  `id_kost` int(11) DEFAULT NULL,
  `id_kamar` int(11) DEFAULT NULL,
  `id_master_peraturan` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_rel_peraturan`),
  KEY `id_kost` (`id_kost`),
  KEY `id_master_peraturan` (`id_master_peraturan`),
  KEY `fk_rel_per_kamar` (`id_kamar`),
  CONSTRAINT `fk_rel_per_kamar` FOREIGN KEY (`id_kamar`) REFERENCES `kamar` (`id_kamar`) ON DELETE CASCADE,
  CONSTRAINT `rel_peraturan_ibfk_1` FOREIGN KEY (`id_kost`) REFERENCES `kost` (`id_kost`) ON DELETE CASCADE,
  CONSTRAINT `rel_peraturan_ibfk_2` FOREIGN KEY (`id_master_peraturan`) REFERENCES `master_peraturan` (`id_master_peraturan`)
) ENGINE=InnoDB AUTO_INCREMENT=295 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `review`
--

DROP TABLE IF EXISTS `review`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `review` (
  `id_review` int(11) NOT NULL AUTO_INCREMENT,
  `id_kost` int(11) DEFAULT NULL,
  `id_kamar` int(11) DEFAULT NULL,
  `id_user` int(11) DEFAULT NULL,
  `rating` tinyint(4) DEFAULT NULL,
  `skor_akurasi` tinyint(4) DEFAULT 0,
  `komentar` text DEFAULT NULL,
  `jenis_reviewer` enum('sewa','survei') DEFAULT NULL,
  `tanggal_review` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_review`),
  KEY `id_kamar` (`id_kamar`),
  KEY `id_mahasiswa` (`id_user`),
  KEY `id_kost` (`id_kost`),
  CONSTRAINT `review_ibfk_1` FOREIGN KEY (`id_kamar`) REFERENCES `kamar` (`id_kamar`),
  CONSTRAINT `review_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`),
  CONSTRAINT `review_ibfk_kost` FOREIGN KEY (`id_kost`) REFERENCES `kost` (`id_kost`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `survei`
--

DROP TABLE IF EXISTS `survei`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `survei` (
  `id_survei` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `id_kost` int(11) NOT NULL,
  `tgl_survei` date NOT NULL,
  `jam_survei` time NOT NULL,
  `status` enum('Menunggu','Diterima','Ditolak','Selesai') DEFAULT 'Menunggu',
  PRIMARY KEY (`id_survei`),
  KEY `id_user` (`id_user`),
  KEY `id_kost` (`id_kost`),
  CONSTRAINT `survei_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
  CONSTRAINT `survei_ibfk_2` FOREIGN KEY (`id_kost`) REFERENCES `kost` (`id_kost`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id_user` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) DEFAULT NULL,
  `role` enum('adminsuper','pemilik','mahasiswa') NOT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `foto_profil` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-01-21  2:11:39
