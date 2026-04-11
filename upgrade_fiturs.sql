-- Tambah kolom untuk fitur timer, acak soal, review, tampilkan skor, dan acak opsi
ALTER TABLE `ujian` 
ADD COLUMN `waktu_tersedia` INT DEFAULT 0 COMMENT 'Waktu ujian dalam menit (0 = tidak terbatas)',
ADD COLUMN `acak_soal` ENUM('ya', 'tidak') DEFAULT 'tidak',
ADD COLUMN `acak_opsi` ENUM('ya', 'tidak') DEFAULT 'tidak' COMMENT 'Acak urutan opsi jawaban',
ADD COLUMN `tampilkan_review` ENUM('ya', 'tidak') DEFAULT 'tidak',
ADD COLUMN `tampilkan_skor` ENUM('ya', 'tidak') DEFAULT 'ya' COMMENT 'Tampilkan skor setelah submit';

-- Tambah kolom untuk riwayat soal yang diacak
ALTER TABLE `hasil_ujian`
ADD COLUMN `detail_jawaban` TEXT NULL COMMENT 'JSON detail jawaban untuk review';
