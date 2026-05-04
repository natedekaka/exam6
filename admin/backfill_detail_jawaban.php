<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die("Access denied");
}

echo "<h2>Backfill detail_jawaban for Existing Hasil Ujian</h2>";
echo "<p>This script populates the detail_jawaban JSON column for existing records.</p>";

$sql = "SELECT h.id as id_hasil, h.id_ujian, h.nis, h.jawaban as old_jawaban
        FROM hasil_ujian h
        WHERE h.detail_jawaban IS NULL OR h.detail_jawaban = ''
        LIMIT 100";

$result = $conn->query($sql);
$count = 0;

while ($row = $result->fetch_assoc()) {
    $id_hasil = $row['id_hasil'];
    $id_ujian = $row['id_ujian'];
    $nis = $row['nis'];
    $old_jawaban = $row['old_jawaban']; // Format: "soal_id:jawaban,soal_id:jawaban"
    
    $soal_sql = "SELECT id, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, opsi_e, kunci_jawaban, poin 
                 FROM soal WHERE id_ujian = ? ORDER BY id";
    $stmt = $conn->prepare($soal_sql);
    $stmt->bind_param("i", $id_ujian);
    $stmt->execute();
    $soal_result = $stmt->get_result();
    
    $detail_jawaban = [];
    $parsed_jawaban = [];
    
    if (!empty($old_jawaban)) {
        $pairs = explode(',', $old_jawaban);
        foreach ($pairs as $pair) {
            if (strpos($pair, ':') !== false) {
                list($soal_id, $jawaban) = explode(':', $pair, 2);
                $parsed_jawaban[trim($soal_id)] = trim($jawaban);
            }
        }
    }
    
    while ($soal = $soal_result->fetch_assoc()) {
        $soal_id = $soal['id'];
        $jawaban_siswa = isset($parsed_jawaban[$soal_id]) ? $parsed_jawaban[$soal_id] : null;
        $kunci = $soal['kunci_jawaban'];
        $is_correct = ($jawaban_siswa !== null && strtoupper($jawaban_siswa) === strtoupper($kunci));
        $poin = floatval($soal['poin']);
        $poin_diperoleh = $is_correct ? $poin : 0;
        
        $detail_jawaban[] = [
            'soal_id' => $soal_id,
            'pertanyaan' => $soal['pertanyaan'],
            'opsi_a' => $soal['opsi_a'],
            'opsi_b' => $soal['opsi_b'],
            'opsi_c' => $soal['opsi_c'],
            'opsi_d' => $soal['opsi_d'],
            'opsi_e' => $soal['opsi_e'],
            'jawaban_siswa' => $jawaban_siswa,
            'kunci_jawaban' => $kunci,
            'is_correct' => $is_correct,
            'poin' => $poin,
            'poin_diperoleh' => $poin_diperoleh
        ];
    }
    
    if (!empty($detail_jawaban)) {
        $json = json_encode($detail_jawaban, JSON_UNESCAPED_UNICODE);
        
        $update_sql = "UPDATE hasil_ujian SET detail_jawaban = ? WHERE id = ?";
        $stmt2 = $conn->prepare($update_sql);
        $stmt2->bind_param("si", $json, $id_hasil);
        
        if ($stmt2->execute()) {
            $count++;
            echo "Updated hasil_id=$id_hasil (ujian_id=$id_ujian, nis=$nis)<br>";
        }
    }
}

echo "<hr><h3>Done! Updated $count records.</h3>";
echo "<p>Now go to <a href='analytics.php'>Analytics</a> and select an exam to see 'Analisis Butir Soal'.</p>";
?>
