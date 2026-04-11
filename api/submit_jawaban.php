<?php
// api/submit_jawaban.php - AJAX API untuk submit jawaban ujian

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config/database.php';
require_once '../config/init_sekolah.php';

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['action'])) {
        throw new Exception('Invalid request');
    }

    $action = $input['action'];

    switch ($action) {
        case 'auto_save':
            $response = handleAutoSave($conn, $input);
            break;
            
        case 'submit_final':
            $response = handleSubmitFinal($conn, $input);
            break;
            
        case 'check_session':
            $response = handleCheckSession($conn, $input);
            break;
            
        case 'get_saved':
            $response = handleGetSaved($conn, $input);
            break;
            
        default:
            throw new Exception('Unknown action');
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);
$conn->close();

function handleAutoSave($conn, $input) {
    $response = ['success' => false, 'message' => ''];
    
    if (!isset($input['id_ujian']) || !isset($input['nis']) || !isset($input['answers'])) {
        throw new Exception('Missing required fields');
    }
    
    $id_ujian = (int)$input['id_ujian'];
    $nis = trim($input['nis']);
    $answers = $input['answers'];
    
    if (empty($nis)) {
        throw new Exception('NIS is required');
    }
    
    $tableExists = $conn->query("SHOW TABLES LIKE 'jawaban_sementara'");
    if ($tableExists->num_rows === 0) {
        $conn->query("
            CREATE TABLE IF NOT EXISTS `jawaban_sementara` (
                `id` int NOT NULL AUTO_INCREMENT,
                `id_ujian` int NOT NULL,
                `nis` varchar(50) NOT NULL,
                `nama` varchar(100) DEFAULT NULL,
                `kelas` varchar(50) DEFAULT NULL,
                `answers` json DEFAULT NULL,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `unique_ujian_nis` (`id_ujian`, `nis`),
                INDEX `idx_nis` (`nis`),
                INDEX `idx_ujian` (`id_ujian`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        ");
    }
    
    $answersJson = json_encode($answers);
    
    $stmt = $conn->prepare("
        INSERT INTO jawaban_sementara (id_ujian, nis, answers)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE answers = ?, updated_at = NOW()
    ");
    $stmt->bind_param("isss", $id_ujian, $nis, $answersJson, $answersJson);
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Jawaban tersimpan';
        $response['saved_count'] = count($answers);
    } else {
        throw new Exception('Failed to save: ' . $stmt->error);
    }
    $stmt->close();
    
    return $response;
}

function handleSubmitFinal($conn, $input) {
    $response = ['success' => false, 'message' => ''];
    
    if (!isset($input['id_ujian']) || !isset($input['nis']) || 
        !isset($input['nama']) || !isset($input['kelas']) || !isset($input['answers'])) {
        throw new Exception('Missing required fields');
    }
    
    $id_ujian = (int)$input['id_ujian'];
    $nis = trim($input['nis']);
    $nama = trim($input['nama']);
    $kelas = trim($input['kelas']);
    $answers = $input['answers'];
    
    if (empty($nis) || empty($nama) || empty($kelas)) {
        throw new Exception('Identitas tidak lengkap');
    }
    
    $stmt = $conn->prepare("SELECT * FROM soal WHERE id_ujian = ?");
    $stmt->bind_param("i", $id_ujian);
    $stmt->execute();
    $result = $stmt->get_result();
    $soal_list = [];
    while ($row = $result->fetch_assoc()) {
        $soal_list[$row['id']] = $row;
    }
    $stmt->close();
    
    if (empty($soal_list)) {
        throw new Exception('Soal tidak ditemukan');
    }
    
    $total_skor = 0;
    $detail_jawaban = [];
    
    foreach ($soal_list as $soal_id => $soal) {
        $jawaban = isset($answers[$soal_id]) ? $answers[$soal_id] : '';
        $is_correct = ($jawaban === $soal['kunci_jawaban']);
        
        if ($is_correct) {
            $total_skor += $soal['poin'];
        }
        
        $detail_jawaban[] = [
            'soal_id' => $soal_id,
            'pertanyaan' => $soal['pertanyaan'],
            'jawaban_siswa' => $jawaban,
            'kunci_jawaban' => $soal['kunci_jawaban'],
            'is_correct' => $is_correct,
            'poin' => $soal['poin'],
            'poin_diperoleh' => $is_correct ? $soal['poin'] : 0,
            'opsi_a' => $soal['opsi_a'],
            'opsi_b' => $soal['opsi_b'],
            'opsi_c' => $soal['opsi_c'],
            'opsi_d' => $soal['opsi_d'],
            'opsi_e' => $soal['opsi_e']
        ];
    }
    
    $detail_jawaban_json = json_encode($detail_jawaban);
    
    $checkCols = $conn->query("SHOW COLUMNS FROM hasil_ujian LIKE 'detail_jawaban'");
    if ($checkCols && $checkCols->num_rows > 0) {
        $stmt = $conn->prepare("INSERT INTO hasil_ujian (id_ujian, nis, nama, kelas, total_skor, detail_jawaban) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssis", $id_ujian, $nis, $nama, $kelas, $total_skor, $detail_jawaban_json);
    } else {
        $stmt = $conn->prepare("INSERT INTO hasil_ujian (id_ujian, nis, nama, kelas, total_skor) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isssi", $id_ujian, $nis, $nama, $kelas, $total_skor);
    }
    
    if ($stmt->execute()) {
        $insert_id = $stmt->insert_id;
        
        $conn->query("DELETE FROM jawaban_sementara WHERE id_ujian = $id_ujian AND nis = '$nis'");
        
        $response['success'] = true;
        $response['message'] = 'Jawaban berhasil disubmit';
        $response['skor'] = $total_skor;
        $response['total_soal'] = count($soal_list);
        $response['jawaban_benar'] = count(array_filter($detail_jawaban, fn($d) => $d['is_correct']));
    } else {
        throw new Exception('Gagal menyimpan jawaban: ' . $stmt->error);
    }
    $stmt->close();
    
    return $response;
}

function handleCheckSession($conn, $input) {
    $response = ['success' => true, 'exists' => false];
    
    if (!isset($input['id_ujian']) || !isset($input['nis'])) {
        throw new Exception('Missing required fields');
    }
    
    $id_ujian = (int)$input['id_ujian'];
    $nis = $conn->real_escape_string($input['nis']);
    
    $stmt = $conn->prepare("SELECT id, nis, nama, kelas FROM hasil_ujian WHERE id_ujian = ? AND nis = ? LIMIT 1");
    $stmt->bind_param("is", $id_ujian, $nis);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $response['exists'] = true;
        $response['message'] = 'Anda sudah mengerjakan ujian ini';
    }
    $stmt->close();
    
    return $response;
}

function handleGetSaved($conn, $input) {
    $response = ['success' => true, 'answers' => []];
    
    if (!isset($input['id_ujian']) || !isset($input['nis'])) {
        throw new Exception('Missing required fields');
    }
    
    $id_ujian = (int)$input['id_ujian'];
    $nis = $conn->real_escape_string($input['nis']);
    
    $tableExists = $conn->query("SHOW TABLES LIKE 'jawaban_sementara'");
    if ($tableExists->num_rows === 0) {
        return $response;
    }
    
    $stmt = $conn->prepare("SELECT answers, nama, kelas FROM jawaban_sementara WHERE id_ujian = ? AND nis = ?");
    $stmt->bind_param("is", $id_ujian, $nis);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $response['answers'] = json_decode($row['answers'], true) ?: [];
        $response['nama'] = $row['nama'];
        $response['kelas'] = $row['kelas'];
    }
    $stmt->close();
    
    return $response;
}
