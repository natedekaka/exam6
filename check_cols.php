<?php
require_once 'config/database.php';

$tables = ['jawaban_sEMENTARA', 'hasil_ujian'];
foreach ($tables as $table) {
    echo "<h3>$table</h3>";
    
    // Check ip_address
    $r = $conn->query("SHOW COLUMNS FROM `$table` LIKE 'ip_address'");
    $hasIp = ($r && $r->num_rows > 0);
    echo "ip_address: " . ($hasIp ? "EXISTS" : "MISSING") . "<br>";
    
    // Check device_fingerprint
    $r2 = $conn->query("SHOW COLUMNS FROM `$table` LIKE 'device_fingerprint'");
    $hasFp = ($r2 && $r2->num_rows > 0);
    echo "device_fingerprint: " . ($hasFp ? "EXISTS" : "MISSING") . "<br><br>";
    
    // Add missing columns
    if (!$hasIp) {
        $sql = "ALTER TABLE `$table` ADD COLUMN `ip_address` varchar(45) DEFAULT NULL AFTER " . 
               ($table == 'hasil_ujian' ? 'detail_jawaban' : 'updated_at');
        if ($conn->query($sql)) echo "✓ Added ip_address<br>";
        else echo "✗ Error adding ip_address: " . $conn->error . "<br>";
    }
    if (!$hasFp) {
        $sql = "ALTER TABLE `$table` ADD COLUMN `device_fingerprint` varchar(64) DEFAULT NULL AFTER `ip_address`";
        if ($conn->query($sql)) echo "✓ Added device_fingerprint<br>";
        else echo "✗ Error adding device_fingerprint: " . $conn->error . "<br>";
    }
}

$conn->close();
echo "<p><strong>Done.</strong></p>";
