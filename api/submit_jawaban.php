<?php
// api/submit_jawaban.php - AJAX API untuk submit jawaban ujian

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config/database.php';
require_once '../config/init_sekolah.php';

function validateUniqueAttempt($conn, $id_ujian, $nis) {
    $stmt = $conn->prepare("SELECT id FROM hasil_ujian WHERE id_ujian = ? AND nis = ? LIMIT 1");
    $stmt->bind_param("is", $id_ujian, $nis);
    $stmt->execute();
    $result = $stmt->get_result();
    $has_completed = $result->num_rows > 0;
    $stmt->close();
    
    if ($has_completed) {
        $stmt = $conn->prepare("SELECT id FROM izin_remedi WHERE id_ujian = ? AND nis = ? LIMIT 1");
        $stmt->bind_param("is", $id_ujian, $nis);
        $stmt->execute();
        $result = $stmt->get_result();
        $has_remedi_permission = $result->num_rows > 0;
        $stmt->close();
        
        return $has_remedi_permission;
    }
    
    return true;
}

function validateTemporaryUnique($conn, $id_ujian, $nis) {
    $stmt = $conn->prepare("SELECT id FROM jawaban_sEMENTARA WHERE id_ujian = ? AND nis = ? LIMIT 1");
    $stmt->bind_param("is", $id_ujian, $nis);
    $stmt->execute();
    $result = $stmt->get_result();
    $has_temporary = $result->num_rows > 0;
    $stmt->close();
    
    if ($has_temporary) {
        return true;
    }
    
    $stmt = $conn->prepare("SELECT id FROM hasil_ujian WHERE id_ujian = ? AND nis = ? LIMIT 1");
    $stmt->bind_param("is", $id_ujian, $nis);
    $stmt->execute();
    $result = $stmt->get_result();
    $has_completed = $result->num_rows > 0;
    $stmt->close();
    
    if ($has_completed) {
        $stmt = $conn->prepare("SELECT id FROM izin_remedi WHERE id_ujian = ? AND nis = ? LIMIT 1");
        $stmt->bind_param("is", $id_ujian, $nis);
        $stmt->execute();
        $result = $stmt->get_result();
        $has_remedi_permission = $result->num_rows > 0;
        $stmt->close();
        
        return $has_remedi_permission;
    }
    
    return true;
}

function verifyCSRF($token, $expected) {
    if (empty($token) || empty($expected)) return false;
    return hash_equals($expected, $token);
}

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
    
    // CSRF validation - skip for generate_token and check_exam_code
    $exemptActions = ['generate_token', 'check_exam_code', 'check_ip'];
    if (!in_array($action, $exemptActions)) {
        $csrfToken = $input['csrf_token'] ?? '';
        $expectedToken = $input['expected_token'] ?? '';
        
        // Log for debugging
        error_log("CSRF Check - Action: $action, Token: " . strlen($csrfToken) . " chars, Expected: " . strlen($expectedToken) . " chars");
        
        if (empty($csrfToken) || empty($expectedToken)) {
            throw new Exception('CSRF token missing');
        }
        
        if (!hash_equals($expectedToken, $csrfToken)) {
            throw new Exception('Invalid CSRF token');
        }
    }

    switch ($action) {
        case 'generate_token':
            $newToken = bin2hex(random_bytes(32));
            $response['success'] = true;
            $response['csrf_token'] = $newToken;
            break;
            
        case 'check_completion':
            $response = handleCheckCompletion($conn, $input);
            break;
            
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
            
        case 'log_violation':
            $response = handleLogViolation($conn, $input);
            break;
            
        case 'get_violations':
            $response = handleGetViolations($conn, $input);
            break;
            
        case 'check_exam_code':
            $response = handleCheckExamCode($conn, $input);
            break;
            
        case 'check_ip':
            $response = handleCheckIP($conn, $input);
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

function handleCheckCompletion($conn, $input) {
    $response = ['success' => true, 'completed' => false, 'has_saved' => false, 'saved_data' => null];
    
    if (!isset($input['id_ujian']) || !isset($input['nis'])) {
        throw new Exception('Missing required fields');
    }
    
    $id_ujian = (int)$input['id_ujian'];
    $nis = trim($input['nis']);
    
    if (empty($nis)) {
        throw new Exception('NIS is required');
    }
    
    $stmt = $conn->prepare("SELECT id, total_skor, created_at FROM hasil_ujian WHERE id_ujian = ? AND nis = ? LIMIT 1");
    $stmt->bind_param("is", $id_ujian, $nis);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $response['completed'] = true;
        $response['message'] = 'Anda sudah mengerjakan ujian ini';
        $response['result'] = [
            'skor' => $row['total_skor'],
            'tanggal' => $row['created_at']
        ];
    }
    $stmt->close();
    
    if (!$response['completed']) {
        $tableExists = $conn->query("SHOW TABLES LIKE 'jawaban_sementara'");
        if ($tableExists && $tableExists->num_rows > 0) {
            $stmt = $conn->prepare("SELECT answers, nama, kelas, updated_at FROM jawaban_sementara WHERE id_ujian = ? AND nis = ?");
            $stmt->bind_param("is", $id_ujian, $nis);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $response['has_saved'] = true;
                $response['saved_data'] = [
                    'answers' => json_decode($row['answers'], true) ?: [],
                    'nama' => $row['nama'],
                    'kelas' => $row['kelas'],
                    'last_update' => $row['updated_at']
                ];
            }
            $stmt->close();
        }
    }
    
    return $response;
}

function handleAutoSave($conn, $input) {
    $response = ['success' => false, 'message' => ''];
    
    if (!isset($input['id_ujian']) || !isset($input['nis']) || !isset($input['answers'])) {
        throw new Exception('Missing required fields');
    }
    
    $id_ujian = (int)$input['id_ujian'];
    $nis = trim($input['nis']);
    $answers = $input['answers'];
    $nama = isset($input['nama']) ? trim($input['nama']) : null;
    $kelas = isset($input['kelas']) ? trim($input['kelas']) : null;
    
    if (empty($nis)) {
        throw new Exception('NIS is required');
    }
    
    if (!validateUniqueAttempt($conn, $id_ujian, $nis)) {
        throw new Exception('Anda sudah menyelesaikan ujian ini. Tidak dapat mengubah jawaban.');
    }
    
    $answersJson = json_encode($answers);
    $namaValue = $nama ?? '';
    $kelasValue = $kelas ?? '';
    
    $sql = "INSERT INTO jawaban_sEMENTARA (id_ujian, nis, nama, kelas, answers) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE nama = VALUES(nama), kelas = VALUES(kelas), answers = VALUES(answers), updated_at = NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $id_ujian, $nis, $namaValue, $kelasValue, $answersJson);
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Jawaban tersimpan';
        $response['saved_count'] = is_array($answers) ? count($answers) : 0;
    } else {
        $createTable = $conn->query("SHOW TABLES LIKE 'jawaban_sEMENTARA'");
        if ($createTable->num_rows === 0) {
            $conn->query("
                CREATE TABLE IF NOT EXISTS `jawaban_sEMENTARA` (
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
            $stmt->execute();
            $response['success'] = true;
            $response['message'] = 'Jawaban tersimpan';
            $response['saved_count'] = is_array($answers) ? count($answers) : 0;
        } else {
            throw new Exception('Failed to save: ' . $stmt->error);
        }
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
    
    if (!validateUniqueAttempt($conn, $id_ujian, $nis)) {
        throw new Exception('Anda sudah menyelesaikan ujian ini. Tidak dapat mengubah jawaban.');
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
    
    error_log("Submit - Answers received: " . json_encode($answers));
    error_log("Submit - Total questions: " . count($soal_list));
    
    $total_skor = 0;
    $detail_jawaban = [];
    
    foreach ($soal_list as $soal_id => $soal) {
        $jawaban = isset($answers[(string)$soal_id]) ? $answers[(string)$soal_id] : (isset($answers[$soal_id]) ? $answers[$soal_id] : '');
        $is_correct = (strtolower($jawaban) === strtolower($soal['kunci_jawaban']));
        
        error_log("Question $soal_id: student answer = '$jawaban', correct = '{$soal['kunci_jawaban']}', is_correct = " . ($is_correct ? 'true' : 'false'));
        
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

function handleLogViolation($conn, $input) {
    $response = ['success' => true, 'message' => ''];
    
    if (!isset($input['id_ujian']) || !isset($input['nis']) || !isset($input['jenis'])) {
        throw new Exception('Missing required fields');
    }
    
    $id_ujian = (int)$input['id_ujian'];
    $nis = trim($input['nis']);
    $jenis = trim($input['jenis']);
    $detail = isset($input['detail']) ? trim($input['detail']) : '';
    $device = isset($input['device_fingerprint']) ? trim($input['device_fingerprint']) : '';
    $ip = isset($input['ip_address']) ? trim($input['ip_address']) : '';
    
    $conn->query("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
    ");
    
    $stmt = $conn->prepare("INSERT INTO exam_violations (id_ujian, nis, jenis_violation, detail) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $id_ujian, $nis, $jenis, $detail);
    $stmt->execute();
    $stmt->close();
    
    $result = $conn->query("SELECT COUNT(*) as total FROM exam_violations WHERE id_ujian = $id_ujian AND nis = '$nis'");
    $row = $result->fetch_assoc();
    $response['violation_count'] = (int)$row['total'];
    
    return $response;
}

function handleGetViolations($conn, $input) {
    $response = ['success' => true, 'violations' => [], 'total' => 0];
    
    if (!isset($input['id_ujian']) || !isset($input['nis'])) {
        throw new Exception('Missing required fields');
    }
    
    $id_ujian = (int)$input['id_ujian'];
    $nis = $conn->real_escape_string($input['nis']);
    
    $result = $conn->query("SELECT * FROM exam_violations WHERE id_ujian = $id_ujian AND nis = '$nis' ORDER BY created_at DESC LIMIT 50");
    while ($row = $result->fetch_assoc()) {
        $response['violations'][] = $row;
    }
    $response['total'] = count($response['violations']);
    
    return $response;
}

function handleCheckExamCode($conn, $input) {
    $response = ['success' => true, 'valid' => false, 'message' => ''];
    
    if (!isset($input['id_ujian']) || !isset($input['kode_ujian'])) {
        throw new Exception('Missing required fields');
    }
    
    $id_ujian = (int)$input['id_ujian'];
    $kode = trim($input['kode_ujian']);
    
    try {
        $stmt = $conn->prepare("SELECT kode_ujian FROM ujian WHERE id = ?");
        $stmt->bind_param("i", $id_ujian);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $kodeDb = $row['kode_ujian'] ?? '';
            
            if (empty($kodeDb)) {
                $response['valid'] = true;
            } elseif (strcasecmp($kodeDb, $kode) === 0) {
                $response['valid'] = true;
            } else {
                $response['message'] = 'Kode ujian salah';
            }
        } else {
            $response['message'] = 'Ujian tidak ditemukan';
        }
        $stmt->close();
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
    }
    
    return $response;
}

function handleCheckIP($conn, $input) {
    $response = ['success' => true, 'allowed' => true, 'message' => ''];
    
    if (!isset($input['id_ujian'])) {
        throw new Exception('Missing required fields');
    }
    
    $id_ujian = (int)$input['id_ujian'];
    $ip = isset($input['ip_address']) ? $input['ip_address'] : '';
    
    $stmt = $conn->prepare("SELECT allow_ip FROM ujian WHERE id = ?");
    $stmt->bind_param("i", $id_ujian);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc() && !empty($row['allow_ip'])) {
        $allowedIPs = json_decode($row['allow_ip'], true);
        if (is_array($allowedIPs) && count($allowedIPs) > 0) {
            $response['allowed'] = in_array($ip, $allowedIPs);
            if (!$response['allowed']) {
                $response['message'] = 'IP Anda tidak diizinkan untuk mengakses ujian ini';
            }
        }
    }
    $stmt->close();
    
    return $response;
}
