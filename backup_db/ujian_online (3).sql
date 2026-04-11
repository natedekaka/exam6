-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: db
-- Waktu pembuatan: 08 Mar 2026 pada 06.05
-- Versi server: 8.0.45
-- Versi PHP: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Basis data: `ujian_online`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password`, `nama_lengkap`, `created_at`) VALUES
(1, 'admin', '$2y$12$uZF2KVRdMyqOKyBhpvCFBe72ia/.CWYZseASp75gvzC9XZ/OVoEGy', 'Administrator', '2026-03-04 08:33:28');

-- --------------------------------------------------------

--
-- Struktur dari tabel `hasil_ujian`
--

CREATE TABLE `hasil_ujian` (
  `id` int NOT NULL,
  `id_ujian` int NOT NULL,
  `nis` varchar(50) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `kelas` varchar(50) NOT NULL,
  `total_skor` int DEFAULT '0',
  `waktu_submit` datetime DEFAULT CURRENT_TIMESTAMP,
  `detail_jawaban` text COMMENT 'JSON detail jawaban untuk review'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `hasil_ujian`
--

INSERT INTO `hasil_ujian` (`id`, `id_ujian`, `nis`, `nama`, `kelas`, `total_skor`, `waktu_submit`, `detail_jawaban`) VALUES
(24, 14, '121', 'nate', 'x-1', 20, '2026-03-06 10:03:17', '[{\"soal_id\":10,\"pertanyaan\":\"siapa nama kamu\",\"jawaban_siswa\":\"a\",\"kunci_jawaban\":\"a\",\"is_correct\":true,\"poin\":10,\"poin_diperoleh\":10,\"opsi_a\":\"adi\",\"opsi_b\":\"ada\",\"opsi_c\":\"ida\",\"opsi_d\":\"ana\",\"opsi_e\":\"ani\"},{\"soal_id\":11,\"pertanyaan\":\"siapa nama dia\",\"jawaban_siswa\":\"c\",\"kunci_jawaban\":\"e\",\"is_correct\":false,\"poin\":10,\"poin_diperoleh\":0,\"opsi_a\":\"dada\",\"opsi_b\":\"dede\",\"opsi_c\":\"didi\",\"opsi_d\":\"dodo\",\"opsi_e\":\"dudu\"},{\"soal_id\":13,\"pertanyaan\":\"apa warna baju kamu\",\"jawaban_siswa\":\"c\",\"kunci_jawaban\":\"c\",\"is_correct\":true,\"poin\":10,\"poin_diperoleh\":10,\"opsi_a\":\"putih\",\"opsi_b\":\"merah\",\"opsi_c\":\"hijau\",\"opsi_d\":\"biru\",\"opsi_e\":\"coklat\"}]'),
(25, 14, '11', 'nate', 'x-2', 10, '2026-03-06 10:04:25', '[{\"soal_id\":10,\"pertanyaan\":\"siapa nama kamu\",\"jawaban_siswa\":\"b\",\"kunci_jawaban\":\"a\",\"is_correct\":false,\"poin\":10,\"poin_diperoleh\":0,\"opsi_a\":\"adi\",\"opsi_b\":\"ada\",\"opsi_c\":\"ida\",\"opsi_d\":\"ana\",\"opsi_e\":\"ani\"},{\"soal_id\":13,\"pertanyaan\":\"apa warna baju kamu\",\"jawaban_siswa\":\"c\",\"kunci_jawaban\":\"c\",\"is_correct\":true,\"poin\":10,\"poin_diperoleh\":10,\"opsi_a\":\"putih\",\"opsi_b\":\"merah\",\"opsi_c\":\"hijau\",\"opsi_d\":\"biru\",\"opsi_e\":\"coklat\"},{\"soal_id\":11,\"pertanyaan\":\"siapa nama dia\",\"jawaban_siswa\":\"a\",\"kunci_jawaban\":\"e\",\"is_correct\":false,\"poin\":10,\"poin_diperoleh\":0,\"opsi_a\":\"dada\",\"opsi_b\":\"dede\",\"opsi_c\":\"didi\",\"opsi_d\":\"dodo\",\"opsi_e\":\"dudu\"}]'),
(28, 14, '1', 'det', 'xf', 10, '2026-03-06 21:07:39', '[{\"soal_id\":10,\"pertanyaan\":\"siapa nama kamu\",\"jawaban_siswa\":\"b\",\"kunci_jawaban\":\"a\",\"is_correct\":false,\"poin\":10,\"poin_diperoleh\":0,\"opsi_a\":\"adi\",\"opsi_b\":\"ada\",\"opsi_c\":\"ida\",\"opsi_d\":\"ana\",\"opsi_e\":\"ani\"},{\"soal_id\":11,\"pertanyaan\":\"siapa nama dia\",\"jawaban_siswa\":\"b\",\"kunci_jawaban\":\"e\",\"is_correct\":false,\"poin\":10,\"poin_diperoleh\":0,\"opsi_a\":\"dada\",\"opsi_b\":\"dede\",\"opsi_c\":\"didi\",\"opsi_d\":\"dodo\",\"opsi_e\":\"dudu\"},{\"soal_id\":13,\"pertanyaan\":\"apa warna baju kamu\",\"jawaban_siswa\":\"c\",\"kunci_jawaban\":\"c\",\"is_correct\":true,\"poin\":10,\"poin_diperoleh\":10,\"opsi_a\":\"putih\",\"opsi_b\":\"merah\",\"opsi_c\":\"hijau\",\"opsi_d\":\"biru\",\"opsi_e\":\"coklat\"}]'),
(30, 15, '1', 'nate4', 'X-1', 0, '2026-03-07 00:11:44', '[{\"soal_id\":14,\"pertanyaan\":\"apa nama wana meja sekolah\",\"jawaban_siswa\":\"d\",\"kunci_jawaban\":\"a\",\"is_correct\":false,\"poin\":10,\"poin_diperoleh\":0,\"opsi_a\":\"merah\",\"opsi_b\":\"kuning\",\"opsi_c\":\"hijau\",\"opsi_d\":\"biru\",\"opsi_e\":\"kuning\"},{\"soal_id\":15,\"pertanyaan\":\"apa warna hati\",\"jawaban_siswa\":\"d\",\"kunci_jawaban\":\"a\",\"is_correct\":false,\"poin\":10,\"poin_diperoleh\":0,\"opsi_a\":\"merah\",\"opsi_b\":\"putih\",\"opsi_c\":\"biru\",\"opsi_d\":\"jingga\",\"opsi_e\":\"biru\"}]'),
(31, 15, '123', 'nate', 'nate', 20, '2026-03-07 00:12:21', '[{\"soal_id\":15,\"pertanyaan\":\"apa warna hati\",\"jawaban_siswa\":\"a\",\"kunci_jawaban\":\"a\",\"is_correct\":true,\"poin\":10,\"poin_diperoleh\":10,\"opsi_a\":\"merah\",\"opsi_b\":\"putih\",\"opsi_c\":\"biru\",\"opsi_d\":\"jingga\",\"opsi_e\":\"biru\"},{\"soal_id\":14,\"pertanyaan\":\"apa nama wana meja sekolah\",\"jawaban_siswa\":\"a\",\"kunci_jawaban\":\"a\",\"is_correct\":true,\"poin\":10,\"poin_diperoleh\":10,\"opsi_a\":\"merah\",\"opsi_b\":\"kuning\",\"opsi_c\":\"hijau\",\"opsi_d\":\"biru\",\"opsi_e\":\"kuning\"}]'),
(32, 15, '123', 'nate', 'nate', 20, '2026-03-07 00:12:35', '[{\"soal_id\":14,\"pertanyaan\":\"apa nama wana meja sekolah\",\"jawaban_siswa\":\"a\",\"kunci_jawaban\":\"a\",\"is_correct\":true,\"poin\":10,\"poin_diperoleh\":10,\"opsi_a\":\"merah\",\"opsi_b\":\"kuning\",\"opsi_c\":\"hijau\",\"opsi_d\":\"biru\",\"opsi_e\":\"kuning\"},{\"soal_id\":15,\"pertanyaan\":\"apa warna hati\",\"jawaban_siswa\":\"a\",\"kunci_jawaban\":\"a\",\"is_correct\":true,\"poin\":10,\"poin_diperoleh\":10,\"opsi_a\":\"merah\",\"opsi_b\":\"putih\",\"opsi_c\":\"biru\",\"opsi_d\":\"jingga\",\"opsi_e\":\"biru\"}]');

-- --------------------------------------------------------

--
-- Struktur dari tabel `konfigurasi_sekolah`
--

CREATE TABLE `konfigurasi_sekolah` (
  `id` int NOT NULL,
  `nama_sekolah` varchar(255) NOT NULL DEFAULT 'SMA Negeri 6 Cimahi',
  `logo` varchar(255) DEFAULT NULL,
  `warna_primer` varchar(20) DEFAULT '#667eea',
  `warna_sekunder` varchar(20) DEFAULT '#764ba2',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `konfigurasi_sekolah`
--

INSERT INTO `konfigurasi_sekolah` (`id`, `nama_sekolah`, `logo`, `warna_primer`, `warna_sekunder`, `created_at`, `updated_at`) VALUES
(1, 'SMA Negeri 6 Cimahi', 'logo_1772687271.png', '#667eea', '#764ba2', '2026-03-05 05:03:49', '2026-03-05 05:10:45');

-- --------------------------------------------------------

--
-- Struktur dari tabel `soal`
--

CREATE TABLE `soal` (
  `id` int NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `soal`
--

INSERT INTO `soal` (`id`, `id_ujian`, `pertanyaan`, `gambar_pertanyaan`, `opsi_a`, `gambar_a`, `opsi_b`, `gambar_b`, `opsi_c`, `gambar_c`, `opsi_d`, `gambar_d`, `opsi_e`, `gambar_e`, `kunci_jawaban`, `poin`, `updated_at`) VALUES
(10, 14, 'siapa nama kamu', NULL, 'adi', NULL, 'ada', NULL, 'ida', NULL, 'ana', NULL, 'ani', NULL, 'a', 10, '2026-03-06 10:00:36'),
(11, 14, 'siapa nama dia', NULL, 'dada', NULL, 'dede', NULL, 'didi', NULL, 'dodo', NULL, 'dudu', NULL, 'e', 10, '2026-03-06 10:01:01'),
(13, 14, 'apa warna baju kamu', NULL, 'putih', NULL, 'merah', NULL, 'hijau', NULL, 'biru', NULL, 'coklat', NULL, 'c', 10, '2026-03-06 10:01:36'),
(14, 15, 'apa nama wana meja sekolah', NULL, 'merah', NULL, 'kuning', NULL, 'hijau', NULL, 'biru', NULL, 'kuning', NULL, 'a', 10, '2026-03-06 21:10:25'),
(15, 15, 'apa warna hati', NULL, 'merah', NULL, 'putih', NULL, 'biru', NULL, 'jingga', NULL, 'biru', NULL, 'a', 10, '2026-03-06 21:11:04');

-- --------------------------------------------------------

--
-- Struktur dari tabel `ujian`
--

CREATE TABLE `ujian` (
  `id` int NOT NULL,
  `judul_ujian` varchar(255) NOT NULL,
  `deskripsi` text,
  `status` enum('aktif','nonaktif') DEFAULT 'nonaktif',
  `tgl_dibuat` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `waktu_tersedia` int DEFAULT '0',
  `acak_soal` varchar(10) DEFAULT 'tidak',
  `acak_opsi` enum('ya','tidak') DEFAULT 'tidak',
  `tampilkan_review` varchar(10) DEFAULT 'tidak',
  `tampilkan_skor` enum('ya','tidak') DEFAULT 'ya' COMMENT 'Tampilkan skor setelah submit'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `ujian`
--

INSERT INTO `ujian` (`id`, `judul_ujian`, `deskripsi`, `status`, `tgl_dibuat`, `updated_at`, `waktu_tersedia`, `acak_soal`, `acak_opsi`, `tampilkan_review`, `tampilkan_skor`) VALUES
(14, 'ulangan ke-1 JKI', 'ulangan JKI co', 'aktif', '2026-03-06 09:59:25', '2026-03-07 00:09:32', 20, 'ya', 'ya', 'ya', 'ya'),
(15, 'ulangan ke-2', 'ulangan ke-2', 'aktif', '2026-03-06 21:09:14', '2026-03-07 00:10:45', 20, 'ya', 'ya', 'ya', 'ya');

--
-- Indeks untuk tabel yang dibuang
--

--
-- Indeks untuk tabel `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indeks untuk tabel `hasil_ujian`
--
ALTER TABLE `hasil_ujian`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_ujian` (`id_ujian`);

--
-- Indeks untuk tabel `konfigurasi_sekolah`
--
ALTER TABLE `konfigurasi_sekolah`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `soal`
--
ALTER TABLE `soal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_ujian` (`id_ujian`);

--
-- Indeks untuk tabel `ujian`
--
ALTER TABLE `ujian`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `hasil_ujian`
--
ALTER TABLE `hasil_ujian`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT untuk tabel `konfigurasi_sekolah`
--
ALTER TABLE `konfigurasi_sekolah`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `soal`
--
ALTER TABLE `soal`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT untuk tabel `ujian`
--
ALTER TABLE `ujian`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `hasil_ujian`
--
ALTER TABLE `hasil_ujian`
  ADD CONSTRAINT `hasil_ujian_ibfk_1` FOREIGN KEY (`id_ujian`) REFERENCES `ujian` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `soal`
--
ALTER TABLE `soal`
  ADD CONSTRAINT `soal_ibfk_1` FOREIGN KEY (`id_ujian`) REFERENCES `ujian` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
