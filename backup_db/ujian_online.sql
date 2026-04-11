-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: db
-- Waktu pembuatan: 04 Mar 2026 pada 11.06
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
  `waktu_submit` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `hasil_ujian`
--

INSERT INTO `hasil_ujian` (`id`, `id_ujian`, `nis`, `nama`, `kelas`, `total_skor`, `waktu_submit`) VALUES
(3, 2, '12121', 'Muhammad Rizki Malik Abdilah', 'IPA1', 0, '2026-03-04 09:28:30'),
(4, 2, '1212', 'dina', 'XII-2', 0, '2026-03-04 09:28:53'),
(5, 2, '1212', 'dina', 'XII-2', 0, '2026-03-04 09:29:02'),
(6, 2, '1212', 'nadi', 'ipa2', 10, '2026-03-04 09:32:31'),
(7, 2, '3', 'Muhammad Rizki Malik Abdilah', 'X-5', 0, '2026-03-04 09:38:43'),
(8, 2, '1212', 'Nate2', 'X ipa 1', 10, '2026-03-04 10:03:23');

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
  `poin` int DEFAULT '10'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `soal`
--

INSERT INTO `soal` (`id`, `id_ujian`, `pertanyaan`, `gambar_pertanyaan`, `opsi_a`, `gambar_a`, `opsi_b`, `gambar_b`, `opsi_c`, `gambar_c`, `opsi_d`, `gambar_d`, `opsi_e`, `gambar_e`, `kunci_jawaban`, `poin`) VALUES
(2, 2, 'siapa nama saya', 'soal_bb0b5b295bc18cef.png', 'daniarsyah', NULL, 'dan2', NULL, 'dan3', NULL, 'dan4', NULL, 'dan5', NULL, 'a', 10);

-- --------------------------------------------------------

--
-- Struktur dari tabel `ujian`
--

CREATE TABLE `ujian` (
  `id` int NOT NULL,
  `judul_ujian` varchar(255) NOT NULL,
  `deskripsi` text,
  `status` enum('aktif','nonaktif') DEFAULT 'nonaktif',
  `tgl_dibuat` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `ujian`
--

INSERT INTO `ujian` (`id`, `judul_ujian`, `deskripsi`, `status`, `tgl_dibuat`) VALUES
(2, 'Ulangan Ke-1 JKI', 'Ulangan ke-1 JKI', 'aktif', '2026-03-04 08:44:15');

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `soal`
--
ALTER TABLE `soal`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `ujian`
--
ALTER TABLE `ujian`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
