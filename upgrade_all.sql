-- Complete upgrade untuk semua fitur Exam6
-- Jalankan di phpMyAdmin atau command line

-- 1. Tambah kolom untuk fitur timer, acak soal, review, skor (abaikan jika sudah ada)
ALTER TABLE `ujian` ADD COLUMN `waktu_tersedia` INT DEFAULT 0;
ALTER TABLE `ujian` ADD COLUMN `acak_soal` ENUM('ya', 'tidak') DEFAULT 'tidak';
ALTER TABLE `ujian` ADD COLUMN `acak_opsi` ENUM('ya', 'tidak') DEFAULT 'tidak';
ALTER TABLE `ujian` ADD COLUMN `tampilkan_review` ENUM('ya', 'tidak') DEFAULT 'tidak';
ALTER TABLE `ujian` ADD COLUMN `tampilkan_skor` ENUM('ya', 'tidak') DEFAULT 'ya';

-- 2. Kolom keamanan
ALTER TABLE `ujian` ADD COLUMN `kode_ujian` VARCHAR(20) DEFAULT NULL;
ALTER TABLE `ujian` ADD COLUMN `allow_ip` TEXT DEFAULT NULL;
ALTER TABLE `ujian` ADD COLUMN `enable_browser_lock` ENUM('ya', 'tidak') DEFAULT 'tidak';
ALTER TABLE `ujian` ADD COLUMN `max_violations` INT DEFAULT 3;
ALTER TABLE `ujian` ADD COLUMN `enable_device_check` ENUM('ya', 'tidak') DEFAULT 'tidak';

-- 3. Tabel baru untuk log pelanggaran
CREATE TABLE IF NOT EXISTS `exam_violations` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `id_ujian` INT NOT NULL,
  `nis` VARCHAR(50) NOT NULL,
  `jenis_violation` VARCHAR(50) NOT NULL,
  `detail` TEXT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_ujian_nis` (`id_ujian`, `nis`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Tambah kolom di hasil_ujian
ALTER TABLE `hasil_ujian` ADD COLUMN `detail_jawaban` TEXT NULL;
ALTER TABLE `hasil_ujian` ADD COLUMN `device_fingerprint` VARCHAR(64) DEFAULT NULL;
ALTER TABLE `hasil_ujian` ADD COLUMN `ip_address` VARCHAR(45) DEFAULT NULL;
ALTER TABLE `hasil_ujian` ADD COLUMN `total_violations` INT DEFAULT 0;