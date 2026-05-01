<?php
require_once 'config/database.php';

$alters = [
    'jawaban_sEMENTARA' => [
        "ALTER TABLE `jawaban_sEMENTARA` ADD COLUMN `ip_address` varchar(45) DEFAULT NULL AFTER `updated_at`",
        "ALTER TABLE `jawaban_sEMENTARA` ADD COLUMN `device_fingerprint` varchar(64) DEFAULT NULL AFTER `ip_address`"
    ],
    'hasil_ujian' => [
        "ALTER TABLE `hasil_ujian` ADD COLUMN `ip_address` varchar(45) DEFAULT NULL AFTER `detail_jawaban`",
        "ALTER TABLE `hasil_ujian` ADD COLUMN `device_fingerprint` varchar(64) DEFAULT NULL AFTER `ip_address`"
    ]
];

foreach ($alters as $table => $sqls) {
    foreach ($sqls as $sql) {
        if ($conn->query($sql)) {
            echo "SUCCESS: $table - " . substr($sql, 0, 50) . "...\n";
        } else {
            if (strpos($conn->error, 'Duplicate column') !== false) {
                echo "SKIPPED (exists): $table\n";
            } else {
                echo "ERROR $table: " . $conn->error . "\n";
            }
        }
    }
}

$conn->close();
echo "\nMigration complete.\n";
