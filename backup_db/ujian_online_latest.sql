-- MySQL dump 10.13  Distrib 8.0.45, for Linux (x86_64)
--
-- Host: localhost    Database: ujian_online
-- ------------------------------------------------------
-- Server version	8.0.45

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admin_users`
--

DROP TABLE IF EXISTS `admin_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `role` enum('super_admin','admin') DEFAULT 'admin',
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_users`
--

LOCK TABLES `admin_users` WRITE;
/*!40000 ALTER TABLE `admin_users` DISABLE KEYS */;
INSERT INTO `admin_users` VALUES (1,'admin','$2y$12$uZF2KVRdMyqOKyBhpvCFBe72ia/.CWYZseASp75gvzC9XZ/OVoEGy','Administrator','2026-03-04 08:33:28','super_admin','2026-04-12 10:02:50'),(2,'admin2','$2y$10$5ZDbvVbTGIXmQ0ss1iWa1eW2MxR0FcnphzrHGKZGMoYVxBhIw0W3y','admin122','2026-04-12 06:53:56','admin','2026-04-12 06:55:17'),(3,'admin3','$2y$10$E/t/iNocqOjGsG49wpZ7Lu6hh1UtcDXfi5kJjrcnDqcVfJQ2WY1xy','admin133','2026-04-12 06:54:37','admin',NULL);
/*!40000 ALTER TABLE `admin_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hasil_ujian`
--

DROP TABLE IF EXISTS `hasil_ujian`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hasil_ujian` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_ujian` int NOT NULL,
  `nis` varchar(50) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `kelas` varchar(50) NOT NULL,
  `total_skor` int DEFAULT '0',
  `waktu_submit` datetime DEFAULT CURRENT_TIMESTAMP,
  `detail_jawaban` text,
  `device_fingerprint` varchar(64) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `total_violations` int DEFAULT '0',
  `submitted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_ujian` (`id_ujian`),
  KEY `idx_ujian_nis` (`id_ujian`,`nis`),
  CONSTRAINT `hasil_ujian_ibfk_1` FOREIGN KEY (`id_ujian`) REFERENCES `ujian` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hasil_ujian`
--

LOCK TABLES `hasil_ujian` WRITE;
/*!40000 ALTER TABLE `hasil_ujian` DISABLE KEYS */;
INSERT INTO `hasil_ujian` VALUES (16,4,'1234567','Daniarsyah','X-1',100,'2026-04-11 21:52:06',NULL,NULL,NULL,0,NULL),(17,5,'123456','nate','X-1',10,'2026-04-11 22:40:11',NULL,NULL,NULL,0,NULL),(18,5,'1234567','nate','X-1',10,'2026-04-11 22:48:57',NULL,NULL,NULL,0,NULL),(19,4,'123456','nate','X-1',100,'2026-04-11 22:49:31',NULL,NULL,NULL,0,NULL),(20,4,'12345678','nate2','X-1',100,'2026-04-11 22:50:29',NULL,NULL,NULL,0,NULL),(21,5,'12345678','nate','X-1',10,'2026-04-11 22:51:20',NULL,NULL,NULL,0,NULL),(22,5,'123456789','nate','x-1',10,'2026-04-11 22:51:50',NULL,NULL,NULL,0,NULL),(23,5,'112345','nate','x-1',10,'2026-04-11 22:53:21',NULL,NULL,NULL,0,NULL),(24,5,'12344','nate','X-1',10,'2026-04-12 06:02:09','[{\"soal_id\":7,\"pertanyaan\":\"apa ini\",\"jawaban_siswa\":\"a\",\"kunci_jawaban\":\"a\",\"is_correct\":true,\"poin\":10,\"poin_diperoleh\":10,\"opsi_a\":\"batu\",\"opsi_b\":\"kertas\",\"opsi_c\":\"gunting\",\"opsi_d\":\"gelas\",\"opsi_e\":\"piring\"},{\"soal_id\":8,\"pertanyaan\":\"siap ini\",\"jawaban_siswa\":\"b\",\"kunci_jawaban\":\"c\",\"is_correct\":false,\"poin\":10,\"poin_diperoleh\":0,\"opsi_a\":\"nate\",\"opsi_b\":\"dani\",\"opsi_c\":\"ndut\",\"opsi_d\":\"kering\",\"opsi_e\":\"orang\"}]',NULL,NULL,0,NULL),(25,4,'12344','Nate','X-1',50,'2026-04-12 06:03:23','[{\"soal_id\":5,\"pertanyaan\":\"jaringan Komputer adalah ...\",\"jawaban_siswa\":\"a\",\"kunci_jawaban\":\"a\",\"is_correct\":true,\"poin\":50,\"poin_diperoleh\":50,\"opsi_a\":\"Komputer yang saling terhubung dan berbagi resource\",\"opsi_b\":\"Orang yang saling terhubung dan berbagi resource\",\"opsi_c\":\"Binatang yang saling terhubung dan berbagi resource\",\"opsi_d\":\"Gelas yang saling terhubung dan berbagi resource\",\"opsi_e\":\"layangan yang saling terhubung dan berbagi resource\"},{\"soal_id\":6,\"pertanyaan\":\"Internet adalah ...\",\"jawaban_siswa\":\"a\",\"kunci_jawaban\":\"c\",\"is_correct\":false,\"poin\":50,\"poin_diperoleh\":0,\"opsi_a\":\"Sekumpulan Binatang\",\"opsi_b\":\"Sekumpupuan orang\",\"opsi_c\":\"Sekumpulan jaringan\",\"opsi_d\":\"Sekumpulan gelas\",\"opsi_e\":\"Sekumpulan piring\"}]',NULL,NULL,0,NULL),(26,5,'12344','Nate','X-1',20,'2026-04-12 06:13:03','[{\"soal_id\":7,\"pertanyaan\":\"apa ini\",\"jawaban_siswa\":\"a\",\"kunci_jawaban\":\"a\",\"is_correct\":true,\"poin\":10,\"poin_diperoleh\":10,\"opsi_a\":\"batu\",\"opsi_b\":\"kertas\",\"opsi_c\":\"gunting\",\"opsi_d\":\"gelas\",\"opsi_e\":\"piring\"},{\"soal_id\":8,\"pertanyaan\":\"siap ini\",\"jawaban_siswa\":\"c\",\"kunci_jawaban\":\"c\",\"is_correct\":true,\"poin\":10,\"poin_diperoleh\":10,\"opsi_a\":\"nate\",\"opsi_b\":\"dani\",\"opsi_c\":\"ndut\",\"opsi_d\":\"kering\",\"opsi_e\":\"orang\"}]',NULL,NULL,0,NULL),(27,5,'12344','Nate','X-1',10,'2026-04-12 06:13:32','[{\"soal_id\":7,\"pertanyaan\":\"apa ini\",\"jawaban_siswa\":\"a\",\"kunci_jawaban\":\"a\",\"is_correct\":true,\"poin\":10,\"poin_diperoleh\":10,\"opsi_a\":\"batu\",\"opsi_b\":\"kertas\",\"opsi_c\":\"gunting\",\"opsi_d\":\"gelas\",\"opsi_e\":\"piring\"},{\"soal_id\":8,\"pertanyaan\":\"siap ini\",\"jawaban_siswa\":\"e\",\"kunci_jawaban\":\"c\",\"is_correct\":false,\"poin\":10,\"poin_diperoleh\":0,\"opsi_a\":\"nate\",\"opsi_b\":\"dani\",\"opsi_c\":\"ndut\",\"opsi_d\":\"kering\",\"opsi_e\":\"orang\"}]',NULL,NULL,0,NULL),(28,5,'12344','Nate','X-1',20,'2026-04-12 06:15:04','[{\"soal_id\":7,\"pertanyaan\":\"apa ini\",\"jawaban_siswa\":\"a\",\"kunci_jawaban\":\"a\",\"is_correct\":true,\"poin\":10,\"poin_diperoleh\":10,\"opsi_a\":\"batu\",\"opsi_b\":\"kertas\",\"opsi_c\":\"gunting\",\"opsi_d\":\"gelas\",\"opsi_e\":\"piring\"},{\"soal_id\":8,\"pertanyaan\":\"siap ini\",\"jawaban_siswa\":\"c\",\"kunci_jawaban\":\"c\",\"is_correct\":true,\"poin\":10,\"poin_diperoleh\":10,\"opsi_a\":\"nate\",\"opsi_b\":\"dani\",\"opsi_c\":\"ndut\",\"opsi_d\":\"kering\",\"opsi_e\":\"orang\"}]',NULL,NULL,0,NULL),(29,4,'123456789','nate','x-2',100,'2026-04-12 10:02:01','[{\"soal_id\":5,\"pertanyaan\":\"jaringan Komputer adalah ...\",\"jawaban_siswa\":\"a\",\"kunci_jawaban\":\"a\",\"is_correct\":true,\"poin\":50,\"poin_diperoleh\":50,\"opsi_a\":\"Komputer yang saling terhubung dan berbagi resource\",\"opsi_b\":\"Orang yang saling terhubung dan berbagi resource\",\"opsi_c\":\"Binatang yang saling terhubung dan berbagi resource\",\"opsi_d\":\"Gelas yang saling terhubung dan berbagi resource\",\"opsi_e\":\"layangan yang saling terhubung dan berbagi resource\"},{\"soal_id\":6,\"pertanyaan\":\"Internet adalah ...\",\"jawaban_siswa\":\"c\",\"kunci_jawaban\":\"c\",\"is_correct\":true,\"poin\":50,\"poin_diperoleh\":50,\"opsi_a\":\"Sekumpulan Binatang\",\"opsi_b\":\"Sekumpupuan orang\",\"opsi_c\":\"Sekumpulan jaringan\",\"opsi_d\":\"Sekumpulan gelas\",\"opsi_e\":\"Sekumpulan piring\"}]',NULL,NULL,0,NULL),(30,4,'123456789','nate','X-2',100,'2026-04-12 10:03:11','[{\"soal_id\":5,\"pertanyaan\":\"jaringan Komputer adalah ...\",\"jawaban_siswa\":\"a\",\"kunci_jawaban\":\"a\",\"is_correct\":true,\"poin\":50,\"poin_diperoleh\":50,\"opsi_a\":\"Komputer yang saling terhubung dan berbagi resource\",\"opsi_b\":\"Orang yang saling terhubung dan berbagi resource\",\"opsi_c\":\"Binatang yang saling terhubung dan berbagi resource\",\"opsi_d\":\"Gelas yang saling terhubung dan berbagi resource\",\"opsi_e\":\"layangan yang saling terhubung dan berbagi resource\"},{\"soal_id\":6,\"pertanyaan\":\"Internet adalah ...\",\"jawaban_siswa\":\"c\",\"kunci_jawaban\":\"c\",\"is_correct\":true,\"poin\":50,\"poin_diperoleh\":50,\"opsi_a\":\"Sekumpulan Binatang\",\"opsi_b\":\"Sekumpupuan orang\",\"opsi_c\":\"Sekumpulan jaringan\",\"opsi_d\":\"Sekumpulan gelas\",\"opsi_e\":\"Sekumpulan piring\"}]',NULL,NULL,0,NULL);
/*!40000 ALTER TABLE `hasil_ujian` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `izin_remedi`
--

DROP TABLE IF EXISTS `izin_remedi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `izin_remedi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_ujian` int NOT NULL,
  `nis` varchar(50) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `kelas` varchar(50) DEFAULT NULL,
  `diberikan_oleh` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_remedi_ujian_nis` (`id_ujian`,`nis`),
  KEY `idx_nis` (`nis`),
  KEY `idx_ujian` (`id_ujian`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `izin_remedi`
--

LOCK TABLES `izin_remedi` WRITE;
/*!40000 ALTER TABLE `izin_remedi` DISABLE KEYS */;
INSERT INTO `izin_remedi` VALUES (1,5,'12344','nate','X-1','admin','2026-04-12 06:12:14'),(2,4,'123456789','nate','x-2','admin','2026-04-12 10:03:01');
/*!40000 ALTER TABLE `izin_remedi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jawaban_sementara`
--

DROP TABLE IF EXISTS `jawaban_sementara`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jawaban_sementara` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_ujian` int NOT NULL,
  `nis` varchar(50) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `kelas` varchar(50) DEFAULT NULL,
  `answers` json DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_ujian_nis` (`id_ujian`,`nis`),
  KEY `idx_nis` (`nis`),
  KEY `idx_ujian` (`id_ujian`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jawaban_sementara`
--

LOCK TABLES `jawaban_sementara` WRITE;
/*!40000 ALTER TABLE `jawaban_sementara` DISABLE KEYS */;
INSERT INTO `jawaban_sementara` VALUES (2,2,'1213123',NULL,NULL,'[]','2026-04-11 00:22:48','2026-04-11 00:23:05'),(6,2,'3',NULL,NULL,'{\"2\": \"b\"}','2026-04-11 00:23:06','2026-04-11 00:23:06');
/*!40000 ALTER TABLE `jawaban_sementara` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `konfigurasi_sekolah`
--

DROP TABLE IF EXISTS `konfigurasi_sekolah`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `konfigurasi_sekolah` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_sekolah` varchar(255) NOT NULL DEFAULT 'SMA Negeri 6 Cimahi',
  `logo` varchar(255) DEFAULT NULL,
  `warna_primer` varchar(20) DEFAULT '#667eea',
  `warna_sekunder` varchar(20) DEFAULT '#764ba2',
  `tampilkan_riwayat` enum('ya','tidak') DEFAULT 'ya',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `konfigurasi_sekolah`
--

LOCK TABLES `konfigurasi_sekolah` WRITE;
/*!40000 ALTER TABLE `konfigurasi_sekolah` DISABLE KEYS */;
INSERT INTO `konfigurasi_sekolah` VALUES (1,'SMA Negeri 6 Cimahi','logo_1775949076.png','#667eea','#764ba2','ya','2026-04-10 23:47:09','2026-04-11 23:11:16');
/*!40000 ALTER TABLE `konfigurasi_sekolah` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `soal`
--

DROP TABLE IF EXISTS `soal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `soal` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_ujian` int NOT NULL,
  `pertanyaan` text NOT NULL,
  `gambar_pertanyaan` varchar(255) DEFAULT NULL,
  `opsi_a` varchar(255) NOT NULL,
  `gambar_a` varchar(255) DEFAULT NULL,
  `opsi_b` varchar(255) NOT NULL,
  `gambar_b` varchar(255) DEFAULT NULL,
  `opsi_c` varchar(255) NOT NULL,
  `gambar_c` varchar(255) DEFAULT NULL,
  `opsi_d` varchar(255) NOT NULL,
  `gambar_d` varchar(255) DEFAULT NULL,
  `opsi_e` varchar(255) NOT NULL,
  `gambar_e` varchar(255) DEFAULT NULL,
  `kunci_jawaban` enum('a','b','c','d','e') NOT NULL,
  `poin` int DEFAULT '10',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_ujian` (`id_ujian`),
  CONSTRAINT `soal_ibfk_1` FOREIGN KEY (`id_ujian`) REFERENCES `ujian` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `soal`
--

LOCK TABLES `soal` WRITE;
/*!40000 ALTER TABLE `soal` DISABLE KEYS */;
INSERT INTO `soal` VALUES (5,4,'jaringan Komputer adalah ...',NULL,'Komputer yang saling terhubung dan berbagi resource',NULL,'Orang yang saling terhubung dan berbagi resource',NULL,'Binatang yang saling terhubung dan berbagi resource',NULL,'Gelas yang saling terhubung dan berbagi resource',NULL,'layangan yang saling terhubung dan berbagi resource',NULL,'a',50,'2026-04-11 21:38:22'),(6,4,'Internet adalah ...',NULL,'Sekumpulan Binatang',NULL,'Sekumpupuan orang',NULL,'Sekumpulan jaringan',NULL,'Sekumpulan gelas',NULL,'Sekumpulan piring',NULL,'c',50,'2026-04-11 21:38:31'),(7,5,'apa ini',NULL,'batu',NULL,'kertas',NULL,'gunting',NULL,'gelas',NULL,'piring',NULL,'a',10,'2026-04-11 22:24:38'),(8,5,'siap ini','soal_be66f3d09ba9dc6b.jpg','nate','opsia_85942c4d65fa30c7.png','dani',NULL,'ndut',NULL,'kering',NULL,'orang',NULL,'c',10,'2026-04-11 23:13:19');
/*!40000 ALTER TABLE `soal` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ujian`
--

DROP TABLE IF EXISTS `ujian`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ujian` (
  `id` int NOT NULL AUTO_INCREMENT,
  `judul_ujian` varchar(255) NOT NULL,
  `deskripsi` text,
  `status` enum('aktif','nonaktif') DEFAULT 'nonaktif',
  `tgl_dibuat` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `kode_ujian` varchar(20) DEFAULT NULL,
  `allow_ip` text,
  `enable_browser_lock` enum('ya','tidak') DEFAULT 'tidak',
  `max_violations` int DEFAULT '3',
  `enable_device_check` enum('ya','tidak') DEFAULT 'tidak',
  `waktu_tersedia` int DEFAULT '0',
  `acak_soal` enum('ya','tidak') DEFAULT 'tidak',
  `acak_opsi` enum('ya','tidak') DEFAULT 'tidak',
  `tampilkan_review` enum('ya','tidak') DEFAULT 'tidak',
  `tampilkan_skor` enum('ya','tidak') DEFAULT 'ya',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ujian`
--

LOCK TABLES `ujian` WRITE;
/*!40000 ALTER TABLE `ujian` DISABLE KEYS */;
INSERT INTO `ujian` VALUES (4,'Ulangan ke-1 JKI','Ulangan ke-1 JKI','aktif','2026-04-11 21:32:18','2026-04-11 22:22:41','D4N14R',NULL,'tidak',3,'tidak',0,'tidak','tidak','tidak','ya'),(5,'JKI-2','JKI-2','nonaktif','2026-04-11 22:23:09','2026-04-12 09:54:34','123456',NULL,'tidak',3,'tidak',0,'tidak','tidak','tidak','ya'),(6,'JKI3','JKI3','nonaktif','2026-04-12 06:59:44','2026-04-12 09:54:30','',NULL,'tidak',3,'tidak',0,'tidak','tidak','tidak','ya'),(7,'JKI4','JKI4','nonaktif','2026-04-12 07:28:21','2026-04-12 09:54:26','',NULL,'tidak',3,'tidak',0,'tidak','tidak','tidak','ya');
/*!40000 ALTER TABLE `ujian` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-12 10:08:38
