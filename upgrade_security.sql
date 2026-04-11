-- Upgrade untuk fitur keamanan tambahan

-- 1. Tambah kolom di tabel ujian untuk fitur keamanan
ALTER TABLE `ujian` 
ADD COLUMN `kode_ujian` VARCHAR(20) DEFAULT NULL COMMENT 'Kode rahasia untuk masuk ujian',
ADD COLUMN `allow_ip` TEXT DEFAULT NULL COMMENT 'JSON array IP yang diizinkan',
ADD COLUMN `enable_browser_lock` ENUM('ya', 'tidak') DEFAULT 'tidak' COMMENT 'Aktifkan deteksi tab switching',
ADD COLUMN `max_violations` INT DEFAULT 3 COMMENT 'Maksimal pelanggaran sebelum auto submit',
ADD COLUMN `enable_device_check` ENUM('ya', 'tidak') DEFAULT 'tidak' COMMENT 'Cek device fingerprint';

-- 2. Tambah kolom di tabel jawaban_sEMENTARA untuk tracking
ALTER TABLE `jawaban_sEMENTARA`
ADD COLUMN `device_fingerprint` VARCHAR(64) DEFAULT NULL COMMENT 'Fingerprint device',
ADD COLUMN `ip_address` VARCHAR(45) DEFAULT NULL COMMENT 'IP address saat mengerjakan',
ADD COLUMN `violations` INT DEFAULT 0 COMMENT 'Jumlah pelanggaran browser';

-- 3. Tabel baru untuk log pelanggaran
CREATE TABLE IF NOT EXISTS `exam_violations` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `id_ujian` INT NOT NULL,
  `nis` VARCHAR(50) NOT NULL,
  `jenis_violation` VARCHAR(50) NOT NULL COMMENT 'tab_switch, copy_paste, right_click, dll',
  `detail` TEXT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_ujian_nis` (`id_ujian`, `nis`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 4. Tambah kolom di tabel hasil_ujian untuk tracking
ALTER TABLE `hasil_ujian`
ADD COLUMN `device_fingerprint` VARCHAR(64) DEFAULT NULL,
ADD COLUMN `ip_address` VARCHAR(45) DEFAULT NULL,
ADD COLUMN `total_violations` INT DEFAULT 0 COMMENT 'Total pelanggaran selama ujian';