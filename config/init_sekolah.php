<?php
// config/init_sekolah.php - Inisialisasi data sekolah

require_once 'database.php';

$konfigurasi_cache = null;

function initKonfigurasiSekolah($conn) {
    global $konfigurasi_cache;
    
    $table_check = $conn->query("SHOW TABLES LIKE 'konfigurasi_sekolah'");

    if ($table_check->num_rows === 0) {
        $conn->query("CREATE TABLE IF NOT EXISTS konfigurasi_sekolah (
            id INT PRIMARY KEY AUTO_INCREMENT,
            nama_sekolah VARCHAR(255) NOT NULL DEFAULT 'SMA Negeri 6 Cimahi',
            logo VARCHAR(255) DEFAULT NULL,
            warna_primer VARCHAR(20) DEFAULT '#667eea',
            warna_sekunder VARCHAR(20) DEFAULT '#764ba2',
            tampilkan_riwayat ENUM('ya','tidak') DEFAULT 'ya',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $conn->query("INSERT INTO konfigurasi_sekolah (nama_sekolah) VALUES ('SMA Negeri 6 Cimahi')");
    } else {
        $col_exists = $conn->query("SHOW COLUMNS FROM konfigurasi_sekolah LIKE 'tampilkan_riwayat'");
        if ($col_exists->num_rows === 0) {
            $conn->query("ALTER TABLE konfigurasi_sekolah ADD COLUMN tampilkan_riwayat ENUM('ya','tidak') DEFAULT 'ya'");
        }
    }
}

function getKonfigurasiSekolah($conn) {
    global $konfigurasi_cache;
    
    if ($konfigurasi_cache !== null) {
        return $konfigurasi_cache;
    }
    
    $result = $conn->query("SELECT * FROM konfigurasi_sekolah LIMIT 1");
    $konfigurasi_cache = $result->fetch_assoc();
    return $konfigurasi_cache;
}

function updateKonfigurasiSekolah($conn, $nama_sekolah, $logo, $warna_primer, $warna_sekunder, $tampilkan_riwayat = 'ya') {
    global $konfigurasi_cache;
    
    $stmt = $conn->prepare("UPDATE konfigurasi_sekolah SET nama_sekolah = ?, logo = ?, warna_primer = ?, warna_sekunder = ?, tampilkan_riwayat = ? WHERE id = 1");
    $stmt->bind_param("sssss", $nama_sekolah, $logo, $warna_primer, $warna_sekunder, $tampilkan_riwayat);
    $result = $stmt->execute();
    $stmt->close();
    
    $konfigurasi_cache = null;
    
    return $result;
}

initKonfigurasiSekolah($conn);

function initConcurrencyControl($conn) {
    $result = $conn->query("SHOW COLUMNS FROM ujian LIKE 'updated_at'");
    if ($result->num_rows === 0) {
        $conn->query("ALTER TABLE ujian ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    }
    
    $result = $conn->query("SHOW COLUMNS FROM soal LIKE 'updated_at'");
    if ($result->num_rows === 0) {
        $conn->query("ALTER TABLE soal ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    }
}

initConcurrencyControl($conn);
?>
