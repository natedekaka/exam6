-- Tabel untuk menyimpan siswa yang diizinkan remedi (mengulang ujian)
CREATE TABLE IF NOT EXISTS `izin_remedi` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `id_ujian` INT NOT NULL,
    `nis` VARCHAR(50) NOT NULL,
    `nama` VARCHAR(100) DEFAULT NULL,
    `kelas` VARCHAR(50) DEFAULT NULL,
    `diberikan_oleh` VARCHAR(100) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_remedi_ujian_nis` (`id_ujian`, `nis`),
    INDEX `idx_nis` (`nis`),
    INDEX `idx_ujian` (`id_ujian`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;