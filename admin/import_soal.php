<?php
session_start();

header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: ../uploads/;");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['csrf_token_time']) || time() - $_SESSION['csrf_token_time'] > 3600) {
    unset($_SESSION['csrf_token']);
    $_SESSION['csrf_token_time'] = time();
}

require_once '../config/database.php';

function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function validateImageFile($filename) {
    $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return isset($allowed[$ext]) ? $ext : false;
}

function uploadImage($sourcePath, $prefix) {
    $ext = validateImageFile(basename($sourcePath));
    if (!$ext) {
        return ['error' => 'Format gambar tidak valid'];
    }
    
    $filename = $prefix . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $target = '../uploads/' . $filename;
    
    if (copy($sourcePath, $target)) {
        return ['success' => $filename];
    }
    return ['error' => 'Gagal upload gambar'];
}

function extractTextFromDocx($filePath) {
    $zip = new ZipArchive();
    
    if ($zip->open($filePath) !== TRUE) {
        return false;
    }
    
    $content = '';
    
    $possibleFiles = [
        'word/document.xml',
        'content.xml'
    ];
    
    $xml = null;
    foreach ($possibleFiles as $file) {
        $index = $zip->locateName($file);
        if ($index !== false) {
            $xml = $zip->getFromIndex($index);
            break;
        }
    }
    
    if ($xml) {
        $zip->close();
        
        $xml = preg_replace('/<[^>]+>/', "\n", $xml);
        $xml = str_replace(["\n\n", "\r\n\r\n"], "\n", $xml);
        $xml = strip_tags($xml);
        $xml = html_entity_decode($xml, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $content = trim($xml);
    } else {
        $zip->close();
    }
    
    return $content;
}

function extractImagesFromDocx($docxPath, $uploadDir) {
    $images = [];
    $zip = new ZipArchive();
    
    if ($zip->open($docxPath) === TRUE) {
        $numFiles = $zip->numFiles;
        
        for ($i = 0; $i < $numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (preg_match('/^word\/media\/image/', $name)) {
                $ext = pathinfo($name, PATHINFO_EXTENSION);
                $tempFile = tempnam(sys_get_temp_dir(), 'img');
                $tempFileWithExt = $tempFile . '.' . $ext;
                
                $data = $zip->getFromIndex($i);
                file_put_contents($tempFileWithExt, $data);
                
                $images[] = $tempFileWithExt;
            }
        }
        $zip->close();
    }
    
    return $images;
}

function parseSoalFromText($text) {
    $soalList = [];
    $blocks = preg_split('/\n\s*\n/', $text);
    
    foreach ($blocks as $block) {
        $block = trim($block);
        if (empty($block) || strpos($block, 'PERTANYAAN:') === false) {
            continue;
        }
        
        $lines = explode("\n", $block);
        $soal = [
            'pertanyaan' => '',
            'opsi_a' => '',
            'opsi_b' => '',
            'opsi_c' => '',
            'opsi_d' => '',
            'opsi_e' => '',
            'kunci' => 'a',
            'poin' => 10,
            'gambar_pertanyaan' => '',
            'gambar_a' => '',
            'gambar_b' => '',
            'gambar_c' => '',
            'gambar_d' => '',
            'gambar_e' => ''
        ];
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (stripos($line, 'PERTANYAAN:') === 0) {
                $soal['pertanyaan'] = trim(substr($line, strlen('PERTANYAAN:')));
            } elseif (stripos($line, 'OPSI_A:') === 0) {
                $soal['opsi_a'] = trim(substr($line, strlen('OPSI_A:')));
            } elseif (stripos($line, 'OPSI_B:') === 0) {
                $soal['opsi_b'] = trim(substr($line, strlen('OPSI_B:')));
            } elseif (stripos($line, 'OPSI_C:') === 0) {
                $soal['opsi_c'] = trim(substr($line, strlen('OPSI_C:')));
            } elseif (stripos($line, 'OPSI_D:') === 0) {
                $soal['opsi_d'] = trim(substr($line, strlen('OPSI_D:')));
            } elseif (stripos($line, 'OPSI_E:') === 0) {
                $soal['opsi_e'] = trim(substr($line, strlen('OPSI_E:')));
            } elseif (stripos($line, 'KUNCI:') === 0) {
                $kunci = strtoupper(trim(substr($line, strlen('KUNCI:'))));
                if (in_array($kunci, ['A', 'B', 'C', 'D', 'E'])) {
                    $soal['kunci'] = strtolower($kunci);
                }
            } elseif (stripos($line, 'POIN:') === 0) {
                $poin = (int)trim(substr($line, strlen('POIN:')));
                if ($poin > 0) {
                    $soal['poin'] = $poin;
                }
            } elseif (stripos($line, 'GAMBAR_PERTANYAAN:') === 0) {
                $soal['gambar_pertanyaan'] = trim(substr($line, strlen('GAMBAR_PERTANYAAN:')));
            } elseif (stripos($line, 'GAMBAR_A:') === 0) {
                $soal['gambar_a'] = trim(substr($line, strlen('GAMBAR_A:')));
            } elseif (stripos($line, 'GAMBAR_B:') === 0) {
                $soal['gambar_b'] = trim(substr($line, strlen('GAMBAR_B:')));
            } elseif (stripos($line, 'GAMBAR_C:') === 0) {
                $soal['gambar_c'] = trim(substr($line, strlen('GAMBAR_C:')));
            } elseif (stripos($line, 'GAMBAR_D:') === 0) {
                $soal['gambar_d'] = trim(substr($line, strlen('GAMBAR_D:')));
            } elseif (stripos($line, 'GAMBAR_E:') === 0) {
                $soal['gambar_e'] = trim(substr($line, strlen('GAMBAR_E:')));
            }
        }
        
        if (!empty($soal['pertanyaan']) && !empty($soal['opsi_a']) && !empty($soal['opsi_b']) &&
            !empty($soal['opsi_c']) && !empty($soal['opsi_d']) && !empty($soal['opsi_e'])) {
            $soalList[] = $soal;
        }
    }
    
    return $soalList;
}

$message = '';
$message_type = 'danger';
$redirect_url = 'tambah_soal.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_soal'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Token keamanan tidak valid';
    } elseif (!isset($_POST['id_ujian']) || empty($_POST['id_ujian'])) {
        $message = 'Pilih ujian terlebih dahulu';
    } elseif (!isset($_FILES['file_docx']) || $_FILES['file_docx']['error'] !== UPLOAD_ERR_OK) {
        $message = 'Pilih file DOCX yang akan diimport';
    } else {
        $file = $_FILES['file_docx'];
        $allowedExt = ['docx'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowedExt)) {
            $message = 'Format file harus .docx';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $message = 'File terlalu besar. Maksimal 5MB';
        } else {
            $content = extractTextFromDocx($file['tmp_name']);
            
            if (empty($content)) {
                $message = 'Gagal membaca file DOCX';
            } else {
                $soalList = parseSoalFromText($content);
                
                if (empty($soalList)) {
                    $message = 'Tidak ada soal yang valid dalam file. Periksa format template!';
                } else {
                    $id_ujian = (int)$_POST['id_ujian'];
                    $success_count = 0;
                    $error_count = 0;
                    
                    $upload_dir = '../uploads/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $extractedImages = extractImagesFromDocx($file['tmp_name'], $upload_dir);
                    
                    $stmt = $conn->prepare("INSERT INTO soal (id_ujian, pertanyaan, gambar_pertanyaan, opsi_a, gambar_a, opsi_b, gambar_b, opsi_c, gambar_c, opsi_d, gambar_d, opsi_e, gambar_e, kunci_jawaban, poin) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    
                    foreach ($soalList as $soal) {
                        $pertanyaan = sanitizeInput($soal['pertanyaan']);
                        $opsi_a = sanitizeInput($soal['opsi_a']);
                        $opsi_b = sanitizeInput($soal['opsi_b']);
                        $opsi_c = sanitizeInput($soal['opsi_c']);
                        $opsi_d = sanitizeInput($soal['opsi_d']);
                        $opsi_e = sanitizeInput($soal['opsi_e']);
                        $kunci = $soal['kunci'];
                        $poin = $soal['poin'];
                        
                        $gambar_pertanyaan = null;
                        $gambar_a = null;
                        $gambar_b = null;
                        $gambar_c = null;
                        $gambar_d = null;
                        $gambar_e = null;
                        
                        $imageFields = [
                            'gambar_pertanyaan' => $soal['gambar_pertanyaan'],
                            'gambar_a' => $soal['gambar_a'],
                            'gambar_b' => $soal['gambar_b'],
                            'gambar_c' => $soal['gambar_c'],
                            'gambar_d' => $soal['gambar_d'],
                            'gambar_e' => $soal['gambar_e']
                        ];
                        
                        foreach ($imageFields as $field => $imgName) {
                            if (!empty($imgName) && !empty($extractedImages)) {
                                $ext = strtolower(pathinfo($imgName, PATHINFO_EXTENSION));
                                if (validateImageFile($imgName)) {
                                    $prefix = str_replace('gambar_', '', $field);
                                    $result = uploadImage($extractedImages[0], $prefix);
                                    if (isset($result['success'])) {
                                        $$field = $result['success'];
                                        array_shift($extractedImages);
                                    }
                                }
                            }
                        }
                        
                        $stmt->bind_param("isssssssssssssi", 
                            $id_ujian, 
                            $pertanyaan, 
                            $gambar_pertanyaan,
                            $opsi_a, 
                            $gambar_a,
                            $opsi_b, 
                            $gambar_b,
                            $opsi_c, 
                            $gambar_c,
                            $opsi_d, 
                            $gambar_d,
                            $opsi_e, 
                            $gambar_e,
                            $kunci, 
                            $poin
                        );
                        
                        if ($stmt->execute()) {
                            $success_count++;
                        } else {
                            $error_count++;
                        }
                    }
                    
                    $stmt->close();
                    
                    if ($success_count > 0) {
                        $message = "Berhasil import $success_count soal!";
                        $message_type = 'success';
                    } else {
                        $message = "Gagal mengimport soal";
                    }
                    
                    $redirect_url .= '?ujian=' . $id_ujian;
                }
            }
        }
    }
} else {
    $message = 'Metode request tidak valid';
}

$_SESSION['import_message'] = $message;
$_SESSION['import_message_type'] = $message_type;

header('Location: ' . $redirect_url);
exit;
