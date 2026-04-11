<?php
// admin/tambah_soal.php - Bank Soal dengan Upload Gambar (Secure & Responsive)

session_start();

header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: ../uploads/;");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['csrf_token_time']) || time() - $_SESSION['csrf_token_time'] > 3600) {
    unset($_SESSION['csrf_token']);
    $_SESSION['csrf_token_time'] = time();
}

require_once '../config/database.php';
require_once '../config/init_sekolah.php';

$sekolah = getKonfigurasiSekolah($conn);

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function validateFileUpload($file) {
    $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'];
    $maxSize = 2 * 1024 * 1024;
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if ($file['size'] > $maxSize) {
        return ['error' => 'File terlalu besar. Maksimal 2MB'];
    }
    
    if (!isset($allowed[$ext])) {
        return ['error' => 'Format file tidak diizinkan'];
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime, $allowed)) {
        return ['error' => 'Tipe file tidak valid'];
    }
    
    return ['valid' => true, 'ext' => $ext];
}

$upload_dir = '../uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

function uploadGambar($file, $prefix) {
    $validation = validateFileUpload($file);
    if (isset($validation['error'])) {
        return ['error' => $validation['error']];
    }
    
    $ext = $validation['ext'];
    $filename = $prefix . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $target = '../uploads/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $target)) {
        return ['success' => $filename];
    }
    return ['error' => 'Gagal upload file'];
}

function hapusGambar($filename) {
    if ($filename && file_exists('../uploads/' . $filename)) {
        unlink('../uploads/' . $filename);
    }
}

generateCsrfToken();

$message = '';
$message_type = '';

$ujian_list = $conn->query("SELECT id, judul_ujian, status FROM ujian ORDER BY judul_ujian");

$selected_ujian = isset($_GET['ujian']) ? (int)$_GET['ujian'] : ($ujian_list->fetch_assoc()['id'] ?? 0);
if ($selected_ujian > 0) {
    $ujian_list->data_seek(0);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_soal'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Token keamanan tidak valid';
        $message_type = 'danger';
    } else {
        $id_ujian = (int)$_POST['id_ujian'];
        $pertanyaan = trim($_POST['pertanyaan']);
        $opsi_a = trim($_POST['opsi_a']);
        $opsi_b = trim($_POST['opsi_b']);
        $opsi_c = trim($_POST['opsi_c']);
        $opsi_d = trim($_POST['opsi_d']);
        $opsi_e = trim($_POST['opsi_e']);
        $kunci = in_array($_POST['kunci_jawaban'], ['a','b','c','d','e']) ? $_POST['kunci_jawaban'] : 'a';
        $poin = max(1, (int)$_POST['poin']);
        $edit_id = isset($_POST['edit_id']) ? (int)$_POST['edit_id'] : 0;
        $original_updated = $_POST['original_updated'] ?? '';
        
        if (empty($pertanyaan) || empty($opsi_a) || empty($opsi_b) || empty($opsi_c) || empty($opsi_d) || empty($opsi_e)) {
            $message = 'Semua field wajib diisi';
            $message_type = 'danger';
        } else {
            $gambar_pertanyaan = null;
            $gambar_a = null;
            $gambar_b = null;
            $gambar_c = null;
            $gambar_d = null;
            $gambar_e = null;
            
            if ($edit_id > 0) {
                $stmt = $conn->prepare("SELECT * FROM soal WHERE id = ?");
                $stmt->bind_param("i", $edit_id);
                $stmt->execute();
                $old_soal = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                
                if ($old_soal && $original_updated !== $old_soal['updated_at']) {
                    $message = 'Data soal telah diubah oleh pengguna lain. Silakan refresh dan coba lagi.';
                    $message_type = 'danger';
                } elseif (!$old_soal) {
                    $message = 'Soal tidak ditemukan';
                    $message_type = 'danger';
                } else {
                    $gambar_pertanyaan = $old_soal['gambar_pertanyaan'] ?? null;
                    $gambar_a = $old_soal['gambar_a'] ?? null;
                    $gambar_b = $old_soal['gambar_b'] ?? null;
                    $gambar_c = $old_soal['gambar_c'] ?? null;
                    $gambar_d = $old_soal['gambar_d'] ?? null;
                    $gambar_e = $old_soal['gambar_e'] ?? null;
                }
            }
            
            if (empty($message)) {
                if (!empty($_FILES['gambar_pertanyaan']['name'])) {
                    $result = uploadGambar($_FILES['gambar_pertanyaan'], 'soal');
                    if (isset($result['error'])) {
                        $message = $result['error'];
                        $message_type = 'danger';
                    } else {
                        $gambar_pertanyaan = $result['success'];
                    }
                }
                
                if (empty($message) && !empty($_FILES['gambar_a']['name'])) {
                    $result = uploadGambar($_FILES['gambar_a'], 'opsia');
                    if (isset($result['error'])) {
                        $message = $result['error'];
                        $message_type = 'danger';
                    } else {
                        $gambar_a = $result['success'];
                    }
                }
                
                if (empty($message) && !empty($_FILES['gambar_b']['name'])) {
                    $result = uploadGambar($_FILES['gambar_b'], 'opsib');
                    if (isset($result['error'])) {
                        $message = $result['error'];
                        $message_type = 'danger';
                    } else {
                        $gambar_b = $result['success'];
                    }
                }
                
                if (empty($message) && !empty($_FILES['gambar_c']['name'])) {
                    $result = uploadGambar($_FILES['gambar_c'], 'opsic');
                    if (isset($result['error'])) {
                        $message = $result['error'];
                        $message_type = 'danger';
                    } else {
                        $gambar_c = $result['success'];
                    }
                }
                
                if (empty($message) && !empty($_FILES['gambar_d']['name'])) {
                    $result = uploadGambar($_FILES['gambar_d'], 'opsid');
                    if (isset($result['error'])) {
                        $message = $result['error'];
                        $message_type = 'danger';
                    } else {
                        $gambar_d = $result['success'];
                    }
                }
                
                if (empty($message) && !empty($_FILES['gambar_e']['name'])) {
                    $result = uploadGambar($_FILES['gambar_e'], 'opsie');
                    if (isset($result['error'])) {
                        $message = $result['error'];
                        $message_type = 'danger';
                    } else {
                        $gambar_e = $result['success'];
                    }
                }
            }
            
            if (empty($message)) {
                if ($edit_id > 0) {
                    $stmt = $conn->prepare("UPDATE soal SET pertanyaan=?, gambar_pertanyaan=?, opsi_a=?, gambar_a=?, opsi_b=?, gambar_b=?, opsi_c=?, gambar_c=?, opsi_d=?, gambar_d=?, opsi_e=?, gambar_e=?, kunci_jawaban=?, poin=? WHERE id=?");
                    $stmt->bind_param("sssssssssssssii", $pertanyaan, $gambar_pertanyaan, $opsi_a, $gambar_a, $opsi_b, $gambar_b, $opsi_c, $gambar_c, $opsi_d, $gambar_d, $opsi_e, $gambar_e, $kunci, $poin, $edit_id);
                    $message = "Soal berhasil diperbarui!";
                } else {
                    $stmt = $conn->prepare("INSERT INTO soal (id_ujian, pertanyaan, gambar_pertanyaan, opsi_a, gambar_a, opsi_b, gambar_b, opsi_c, gambar_c, opsi_d, gambar_d, opsi_e, gambar_e, kunci_jawaban, poin) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("isssssssssssssi", $id_ujian, $pertanyaan, $gambar_pertanyaan, $opsi_a, $gambar_a, $opsi_b, $gambar_b, $opsi_c, $gambar_c, $opsi_d, $gambar_d, $opsi_e, $gambar_e, $kunci, $poin);
                    $message = "Soal berhasil ditambahkan!";
                }
                
                if ($stmt->execute()) {
                    $message_type = 'success';
                }
                $stmt->close();
            }
        }
    }
}

if (isset($_GET['hapus']) && isset($_GET['token'])) {
    $id = (int)$_GET['hapus'];
    
    if (!validateCsrfToken($_GET['token'])) {
        $message = 'Token keamanan tidak valid';
        $message_type = 'danger';
    } else {
        $stmt = $conn->prepare("SELECT s.* FROM soal s WHERE s.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $soal = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($soal) {
            hapusGambar($soal['gambar_pertanyaan']);
            hapusGambar($soal['gambar_a']);
            hapusGambar($soal['gambar_b']);
            hapusGambar($soal['gambar_c']);
            hapusGambar($soal['gambar_d']);
            hapusGambar($soal['gambar_e']);
            
            $stmt = $conn->prepare("DELETE FROM soal WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $message = "Soal berhasil dihapus!";
                $message_type = 'success';
            }
            $stmt->close();
        }
    }
}

$soal_list = [];
if ($selected_ujian > 0) {
    $stmt = $conn->prepare("SELECT * FROM soal WHERE id_ujian = ? ORDER BY id");
    $stmt->bind_param("i", $selected_ujian);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $soal_list[] = $row;
    }
    $stmt->close();
}

$edit_soal = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT s.* FROM soal s WHERE s.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit_result = $stmt->get_result();
    $edit_soal = $edit_result->fetch_assoc();
    $stmt->close();
    if ($edit_soal) {
        $selected_ujian = $edit_soal['id_ujian'];
    }
}

$csrf_token = $_SESSION['csrf_token'];

if (isset($_SESSION['import_message'])) {
    $message = $_SESSION['import_message'];
    $message_type = $_SESSION['import_message_type'] ?? 'danger';
    unset($_SESSION['import_message'], $_SESSION['import_message_type']);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Bank Soal - Manajemen Ujian Online">
    <title>Bank Soal</title>
    <link href="../vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../vendor/bootstrap-icons/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --secondary: #64748b;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --dark: #1e293b;
            --light: #f8fafc;
            --border: #e2e8f0;
            --sidebar-width: 260px;
        }
        
        * { font-family: 'Inter', sans-serif; }
        
        body { 
            background-color: #f1f5f9; 
            min-height: 100vh;
        }
        
        .sidebar { 
            width: var(--sidebar-width); 
            min-height: 100vh; 
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
            transition: transform 0.3s ease;
        }
        
        .sidebar-brand { 
            padding: 1.5rem; 
            border-bottom: 1px solid rgba(255,255,255,0.1); 
        }
        .sidebar-brand h5 { 
            color: #fff; 
            font-weight: 600; 
            margin: 0; 
        }
        
        .school-logo {
            width: 55px;
            height: 55px;
            background: rgba(255,255,255,0.15);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }
        
        .sidebar-brand h5 {
            color: #fff;
            font-weight: 600;
            margin: 0;
        }
        
        .sidebar-menu {
            padding: 1rem 0;
        }
        
        .sidebar a { 
            color: rgba(255,255,255,0.7); 
            text-decoration: none; 
            padding: 0.875rem 1.5rem; 
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
            font-size: 0.9375rem;
        }
        
        .sidebar a:hover { 
            background: rgba(255,255,255,0.05);
            color: #fff;
        }
        
        .sidebar a.active { 
            background: rgba(79, 70, 229, 0.2);
            color: #fff;
            border-left-color: var(--primary);
        }
        
        .sidebar a i {
            font-size: 1.125rem;
            width: 1.5rem;
            text-align: center;
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            transition: margin-left 0.3s ease;
        }
        
        .page-header {
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .page-header h3 {
            margin: 0;
            font-weight: 600;
            color: var(--dark);
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background: #fff;
            border-bottom: 1px solid var(--border);
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .card-body.scrollable-table {
            max-height: 500px;
            overflow-y: auto;
            padding: 0 !important;
        }
        
        .card-body.scrollable-table::-webkit-scrollbar {
            width: 8px;
        }
        
        .card-body.scrollable-table::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        .card-body.scrollable-table::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        
        .card-body.scrollable-table::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        
        .form-label {
            font-weight: 500;
            color: var(--dark);
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }
        
        .form-control, .form-select {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 0.625rem 0.875rem;
            font-size: 0.9375rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }
        
        .btn {
            border-radius: 8px;
            padding: 0.625rem 1.25rem;
            font-weight: 500;
            font-size: 0.9375rem;
            transition: all 0.2s ease;
        }
        
        .btn-primary {
            background: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary:hover {
            background: var(--primary-hover);
            border-color: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }
        
        .btn-secondary {
            background: var(--secondary);
            border-color: var(--secondary);
        }
        
        .btn-warning {
            background: var(--warning);
            border-color: var(--warning);
            color: #fff;
        }
        
        .btn-danger {
            background: var(--danger);
            border-color: var(--danger);
        }
        
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.8125rem;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            background: #f8fafc;
            border-bottom: 2px solid var(--border);
            color: var(--secondary);
            font-weight: 600;
            font-size: 0.8125rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 1rem;
        }
        
        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--border);
        }
        
        .table tbody tr:hover {
            background: #f8fafc;
        }
        
        .badge {
            font-weight: 500;
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
        }
        
        .preview-img { 
            max-width: 120px; 
            max-height: 80px; 
            object-fit: contain; 
            border: 1px solid var(--border); 
            border-radius: 6px; 
        }
        
        .gambar-preview { 
            max-width: 60px; 
            max-height: 50px; 
            object-fit: contain; 
            border-radius: 4px;
            margin-top: 0.5rem;
        }
        
        .file-upload-wrapper {
            position: relative;
        }
        
        .file-upload-wrapper input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .file-upload-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 0.875rem;
            border: 1px dashed var(--border);
            border-radius: 8px;
            color: var(--secondary);
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .file-upload-wrapper:hover .file-upload-label {
            border-color: var(--primary);
            color: var(--primary);
            background: rgba(79, 70, 229, 0.02);
        }
        
        .opsi-card {
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.2s;
        }
        
        .opsi-card:hover {
            border-color: var(--primary);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .opsi-label {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            font-weight: 600;
            font-size: 0.875rem;
            margin-right: 0.5rem;
        }
        
        .opsi-a { background: #dbeafe; color: #1d4ed8; }
        .opsi-b { background: #dcfce7; color: #15803d; }
        .opsi-c { background: #fef3c7; color: #b45309; }
        .opsi-d { background: #fce7f3; color: #be185d; }
        .opsi-e { background: #e0e7ff; color: #4338ca; }
        
        .mobile-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1001;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 0.625rem;
            font-size: 1.25rem;
        }
        
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }
        
        .toast-container {
            position: fixed;
            top: 1.5rem;
            right: 1.5rem;
            z-index: 9999;
        }
        
        .toast {
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 4rem 1rem 1rem;
            }
            
            .mobile-toggle {
                display: flex;
            }
            
            .overlay.show {
                display: block;
            }
            
            .page-header {
                padding: 1rem;
                flex-direction: column;
                align-items: flex-start;
            }
            
            .table-responsive {
                border-radius: 8px;
            }
        }
        
        @media (max-width: 768px) {
            .opsi-card {
                padding: 0.75rem;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
            
            .btn:last-child {
                margin-bottom: 0;
            }
            
            .page-header .btn {
                width: auto;
            }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .question-box {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border: 2px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .question-box:hover {
            border-color: var(--primary);
        }
        
        .toast-header.bg-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            align-items: center;
        }
        
        .action-btn-group {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
            text-decoration: none;
            border: none;
            background: none;
            cursor: pointer;
        }
        
        .action-btn-group:hover {
            text-decoration: none;
        }
        
        .action-btn {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            transition: all 0.2s ease;
            font-size: 1.1rem;
            text-decoration: none;
        }
        
        .action-btn-label {
            font-size: 0.65rem;
            font-weight: 500;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .action-btn-group:hover .action-btn {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .action-btn-edit {
            background: #fef3c7;
            color: #d97706 !important;
        }
        
        .action-btn-edit:hover {
            background: #fde68a;
            color: #b45309 !important;
        }
        
        .action-btn-delete {
            background: #f3f4f6;
            color: #6b7280 !important;
        }
        
        .action-btn-delete:hover {
            background: #fee2e2;
            color: #dc2626 !important;
        }
    </style>
</head>
<body>
    <button class="mobile-toggle" onclick="toggleSidebar()">
        <i class="bi bi-list"></i>
    </button>
    
    <div class="overlay" onclick="toggleSidebar()"></div>

    <div class="sidebar">
        <div class="sidebar-brand text-center">
            <div class="school-logo mb-2">
                <?php if ($sekolah['logo'] && file_exists('../uploads/' . $sekolah['logo'])): ?>
                    <img src="../uploads/<?= $sekolah['logo'] ?>" alt="Logo" style="width: 100%; height: 100%; object-fit: contain; border-radius: 50%;">
                <?php else: ?>
                    <i class="bi bi-mortarboard-fill" style="font-size: 1.8rem;"></i>
                <?php endif; ?>
            </div>
            <div class="text-white fw-bold" style="font-size: 0.85rem;"><?= htmlspecialchars($sekolah['nama_sekolah']) ?></div>
            <h5 class="mt-2"><i class="bi bi-gear me-1"></i>Admin Panel</h5>
        </div>
        <div class="sidebar-menu">
            <a href="index.php"><i class="bi bi-grid-1x2-fill"></i> Manajemen Ujian</a>
            <a href="tambah_soal.php" class="active"><i class="bi bi-question-circle-fill"></i> Bank Soal</a>
            <a href="rekap_nilai.php"><i class="bi bi-bar-chart-fill"></i> Rekap Nilai</a>
            <a href="profil_sekolah.php"><i class="bi bi-building"></i> Profil Sekolah</a>
            <a href="logout.php" class="text-warning mt-3"><i class="bi bi-box-arrow-right"></i> Logout (<?= htmlspecialchars($_SESSION['admin_username']) ?>)</a>
        </div>
    </div>

    <div class="main-content">
        <div class="page-header animate-fade-in">
            <div class="d-flex align-items-center gap-3">
                <h3><i class="bi bi-journal-text me-2"></i>Bank Soal</h3>
                <?php if ($selected_ujian > 0): ?>
                <span class="badge bg-primary fs-6"><?= count($soal_list) ?> soal</span>
                <?php endif; ?>
            </div>
            <div class="d-flex gap-2">
                <a href="download_template.php" class="btn btn-outline-primary btn-sm" target="_blank">
                    <i class="bi bi-download me-1"></i> Download Template
                </a>
                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#importModal">
                    <i class="bi bi-upload me-1"></i> Import DOCX
                </button>
            </div>
        </div>
        
        <?php if ($message): ?>
        <div class="toast-container">
            <div class="toast show" role="alert" data-bs-delay="5000">
                <div class="toast-header <?= ($message_type === 'danger' && strpos($message, 'pengguna lain') !== false) ? 'bg-danger' : 'bg-'.$message_type ?> text-white">
                    <i class="bi bi-<?= ($message_type === 'danger' && strpos($message, 'pengguna lain') !== false) ? 'exclamation-triangle-fill' : ($message_type === 'success' ? 'check-circle' : 'exclamation-circle') ?>-fill me-2"></i>
                    <strong class="me-auto"><?= ($message_type === 'danger' && strpos($message, 'pengguna lain') !== false) ? 'Konflik Data!' : ($message_type === 'success' ? 'Berhasil' : 'Peringatan') ?></strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    <?= htmlspecialchars($message) ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="card animate-fade-in">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label class="form-label"><i class="bi bi-file-earmark-text me-1"></i>Pilih Ujian</label>
                        <select name="ujian" class="form-select" onchange="this.form.submit()">
                            <option value="">-- Pilih Ujian --</option>
                            <?php 
                            $ujian_list->data_seek(0);
                            while ($ujian = $ujian_list->fetch_assoc()): 
                            ?>
                            <option value="<?= $ujian['id'] ?>" <?= $selected_ujian == $ujian['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ujian['judul_ujian']) ?> (<?= $ujian['status'] ?>)
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($selected_ujian > 0): ?>
        
        <div class="card animate-fade-in">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-<?= $edit_soal ? 'pencil-square' : 'plus-circle' ?> me-2"></i><?= $edit_soal ? 'Edit Soal' : 'Tambah Soal Baru' ?></span>
                <?php if ($edit_soal): ?>
                <a href="tambah_soal.php?ujian=<?= $selected_ujian ?>" class="btn btn-sm btn-secondary">
                    <i class="bi bi-x-lg"></i> Batal
                </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" id="soalForm" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <?php if ($edit_soal): ?>
                        <input type="hidden" name="edit_id" value="<?= $edit_soal['id'] ?>">
                        <input type="hidden" name="original_updated" value="<?= $edit_soal['updated_at'] ?>">
                    <?php else: ?>
                        <input type="hidden" name="id_ujian" value="<?= $selected_ujian ?>">
                    <?php endif; ?>
                    
                    <div class="question-box">
                        <label class="form-label fw-bold"><i class="bi bi-chat-left-text me-2"></i>Pertanyaan</label>
                        <textarea name="pertanyaan" class="form-control mb-3" rows="8" placeholder="Masukkan pertanyaan soal..." required><?= $edit_soal ? htmlspecialchars($edit_soal['pertanyaan']) : '' ?></textarea>
                        
                        <label class="form-label"><i class="bi bi-image me-1"></i>Gambar Pertanyaan (opsional)</label>
                        <div class="file-upload-wrapper mb-2">
                            <input type="file" name="gambar_pertanyaan" accept="image/*" onchange="updateFileName(this, 'label-pertanyaan')">
                            <div class="file-upload-label" id="label-pertanyaan">
                                <i class="bi bi-cloud-upload"></i> Klik untuk upload gambar
                            </div>
                        </div>
                        <?php if ($edit_soal && $edit_soal['gambar_pertanyaan']): ?>
                            <div class="mt-2">
                                <img src="../uploads/<?= $edit_soal['gambar_pertanyaan'] ?>" class="preview-img" alt="Gambar Pertanyaan">
                                <small class="text-muted d-block">Gambar sudah ada</small>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="opsi-card">
                                <label class="form-label fw-bold"><span class="opsi-label opsi-a">A</span>Opsi A</label>
                                <textarea name="opsi_a" class="form-control mb-2" rows="3" placeholder="Masukkan opsi A..." required><?= $edit_soal ? htmlspecialchars($edit_soal['opsi_a']) : '' ?></textarea>
                                <div class="file-upload-wrapper">
                                    <input type="file" name="gambar_a" accept="image/*" onchange="updateFileName(this, 'label-a')">
                                    <div class="file-upload-label" id="label-a">
                                        <i class="bi bi-image"></i> Gambar Opsi A
                                    </div>
                                </div>
                                <?php if ($edit_soal && $edit_soal['gambar_a']): ?>
                                    <img src="../uploads/<?= $edit_soal['gambar_a'] ?>" class="gambar-preview" alt="Gambar A">
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="opsi-card">
                                <label class="form-label fw-bold"><span class="opsi-label opsi-b">B</span>Opsi B</label>
                                <textarea name="opsi_b" class="form-control mb-2" rows="3" placeholder="Masukkan opsi B..." required><?= $edit_soal ? htmlspecialchars($edit_soal['opsi_b']) : '' ?></textarea>
                                <div class="file-upload-wrapper">
                                    <input type="file" name="gambar_b" accept="image/*" onchange="updateFileName(this, 'label-b')">
                                    <div class="file-upload-label" id="label-b">
                                        <i class="bi bi-image"></i> Gambar Opsi B
                                    </div>
                                </div>
                                <?php if ($edit_soal && $edit_soal['gambar_b']): ?>
                                    <img src="../uploads/<?= $edit_soal['gambar_b'] ?>" class="gambar-preview" alt="Gambar B">
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="opsi-card">
                                <label class="form-label fw-bold"><span class="opsi-label opsi-c">C</span>Opsi C</label>
                                <textarea name="opsi_c" class="form-control mb-2" rows="3" placeholder="Masukkan opsi C..." required><?= $edit_soal ? htmlspecialchars($edit_soal['opsi_c']) : '' ?></textarea>
                                <div class="file-upload-wrapper">
                                    <input type="file" name="gambar_c" accept="image/*" onchange="updateFileName(this, 'label-c')">
                                    <div class="file-upload-label" id="label-c">
                                        <i class="bi bi-image"></i> Gambar Opsi C
                                    </div>
                                </div>
                                <?php if ($edit_soal && $edit_soal['gambar_c']): ?>
                                    <img src="../uploads/<?= $edit_soal['gambar_c'] ?>" class="gambar-preview" alt="Gambar C">
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="opsi-card">
                                <label class="form-label fw-bold"><span class="opsi-label opsi-d">D</span>Opsi D</label>
                                <textarea name="opsi_d" class="form-control mb-2" rows="3" placeholder="Masukkan opsi D..." required><?= $edit_soal ? htmlspecialchars($edit_soal['opsi_d']) : '' ?></textarea>
                                <div class="file-upload-wrapper">
                                    <input type="file" name="gambar_d" accept="image/*" onchange="updateFileName(this, 'label-d')">
                                    <div class="file-upload-label" id="label-d">
                                        <i class="bi bi-image"></i> Gambar Opsi D
                                    </div>
                                </div>
                                <?php if ($edit_soal && $edit_soal['gambar_d']): ?>
                                    <img src="../uploads/<?= $edit_soal['gambar_d'] ?>" class="gambar-preview" alt="Gambar D">
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="opsi-card">
                                <label class="form-label fw-bold"><span class="opsi-label opsi-e">E</span>Opsi E</label>
                                <textarea name="opsi_e" class="form-control mb-2" rows="3" placeholder="Masukkan opsi E..." required><?= $edit_soal ? htmlspecialchars($edit_soal['opsi_e']) : '' ?></textarea>
                                <div class="file-upload-wrapper">
                                    <input type="file" name="gambar_e" accept="image/*" onchange="updateFileName(this, 'label-e')">
                                    <div class="file-upload-label" id="label-e">
                                        <i class="bi bi-image"></i> Gambar Opsi E
                                    </div>
                                </div>
                                <?php if ($edit_soal && $edit_soal['gambar_e']): ?>
                                    <img src="../uploads/<?= $edit_soal['gambar_e'] ?>" class="gambar-preview" alt="Gambar E">
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="opsi-card">
                                <label class="form-label fw-bold"><i class="bi bi-check2-square me-1"></i>Kunci Jawaban</label>
                                <select name="kunci_jawaban" class="form-select">
                                    <option value="a" <?= $edit_soal && $edit_soal['kunci_jawaban'] === 'a' ? 'selected' : '' ?>>A</option>
                                    <option value="b" <?= $edit_soal && $edit_soal['kunci_jawaban'] === 'b' ? 'selected' : '' ?>>B</option>
                                    <option value="c" <?= $edit_soal && $edit_soal['kunci_jawaban'] === 'c' ? 'selected' : '' ?>>C</option>
                                    <option value="d" <?= $edit_soal && $edit_soal['kunci_jawaban'] === 'd' ? 'selected' : '' ?>>D</option>
                                    <option value="e" <?= $edit_soal && $edit_soal['kunci_jawaban'] === 'e' ? 'selected' : '' ?>>E</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="opsi-card">
                                <label class="form-label fw-bold"><i class="bi bi-star me-1"></i>Poin</label>
                                <input type="number" name="poin" class="form-control" value="<?= $edit_soal ? (int)$edit_soal['poin'] : 10 ?>" min="1" max="100">
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" name="simpan_soal" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> <?= $edit_soal ? 'Perbarui' : 'Simpan' ?> Soal
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card animate-fade-in">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-list-ol me-2"></i>Daftar Soal</span>
                <span class="badge bg-primary"><?= count($soal_list) ?> soal</span>
            </div>
            <div class="card-body scrollable-table">
                <?php if (count($soal_list) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 60px;">No</th>
                                <th>Pertanyaan</th>
                                <th class="text-center" style="width: 80px;">Gambar</th>
                                <th class="text-center" style="width: 80px;">Kunci</th>
                                <th class="text-center" style="width: 70px;">Poin</th>
                                <th class="text-center" style="width: 120px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($soal_list as $soal): ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td>
                                    <div class="text-truncate" style="max-width: 300px;" title="<?= htmlspecialchars($soal['pertanyaan']) ?>">
                                        <?= htmlspecialchars(mb_strimwidth($soal['pertanyaan'], 0, 80, '...')) ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <?php if ($soal['gambar_pertanyaan'] || $soal['gambar_a'] || $soal['gambar_b'] || $soal['gambar_c'] || $soal['gambar_d'] || $soal['gambar_e']): ?>
                                        <i class="bi bi-image-fill text-success"></i>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-<?= $soal['kunci_jawaban'] === 'a' ? 'primary' : ($soal['kunci_jawaban'] === 'b' ? 'success' : ($soal['kunci_jawaban'] === 'c' ? 'warning' : ($soal['kunci_jawaban'] === 'd' ? 'danger' : 'info'))) ?>">
                                        <?= strtoupper($soal['kunci_jawaban']) ?>
                                    </span>
                                </td>
                                <td class="text-center"><?= $soal['poin'] ?></td>
                                <td class="text-center">
                                    <div class="action-buttons">
                                        <a href="?ujian=<?= $selected_ujian ?>&edit=<?= $soal['id'] ?>" 
                                           class="action-btn-group" 
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top" 
                                           title="Edit">
                                            <span class="action-btn action-btn-edit">
                                                <i class="bi bi-pencil" style="font-size: 1rem;"></i>
                                            </span>
                                            <span class="action-btn-label">Edit</span>
                                        </a>
                                        <button type="button" 
                                            class="action-btn-group btn-hapus-soal" 
                                            data-id="<?= $soal['id'] ?>" 
                                            data-token="<?= $csrf_token ?>"
                                            data-bs-toggle="tooltip" 
                                            data-bs-placement="top" 
                                            title="Hapus">
                                            <span class="action-btn action-btn-delete">
                                                <i class="bi bi-trash3" style="font-size: 1rem;"></i>
                                            </span>
                                            <span class="action-btn-label">Hapus</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-2">Belum ada soal untuk ujian ini</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php else: ?>
        <div class="card animate-fade-in">
            <div class="card-body text-center py-5">
                <i class="bi bi-folder2-open text-muted" style="font-size: 4rem;"></i>
                <p class="text-muted mt-3">Silakan pilih ujian terlebih dahulu untuk mengelola bank soal</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="../vendor/bootstrap/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('show');
            document.querySelector('.overlay').classList.toggle('show');
        }
        
        function updateFileName(input, labelId) {
            const label = document.getElementById(labelId);
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                const maxSize = 2 * 1024 * 1024;
                
                if (!validTypes.includes(file.type)) {
                    alert('Format file tidak valid. Gunakan JPG, PNG, GIF, atau WebP');
                    input.value = '';
                    return;
                }
                
                if (file.size > maxSize) {
                    alert('File terlalu besar. Maksimal 2MB');
                    input.value = '';
                    return;
                }
                
                label.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i> ' + file.name;
            }
        }
        
        function updateDocxName(input, labelId) {
            const label = document.getElementById(labelId);
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const validTypes = ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                const maxSize = 5 * 1024 * 1024;
                
                if (!validTypes.includes(file.type)) {
                    alert('Format file tidak valid. Gunakan file .docx');
                    input.value = '';
                    return;
                }
                
                if (file.size > maxSize) {
                    alert('File terlalu besar. Maksimal 5MB');
                    input.value = '';
                    return;
                }
                
                label.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i> ' + file.name;
            }
        }
        
        document.getElementById('soalForm').addEventListener('submit', function(e) {
            const pertanyaan = document.querySelector('textarea[name="pertanyaan"]').value.trim();
            const opsiA = document.querySelector('textarea[name="opsi_a"]').value.trim();
            const opsiB = document.querySelector('textarea[name="opsi_b"]').value.trim();
            const opsiC = document.querySelector('textarea[name="opsi_c"]').value.trim();
            const opsiD = document.querySelector('textarea[name="opsi_d"]').value.trim();
            const opsiE = document.querySelector('textarea[name="opsi_e"]').value.trim();
            
            if (!pertanyaan || !opsiA || !opsiB || !opsiC || !opsiD || !opsiE) {
                e.preventDefault();
                alert('Semua field wajib diisi');
                return;
            }
            
            if (pertanyaan.length < 5) {
                e.preventDefault();
                alert('Pertanyaan terlalu pendek');
                return;
            }
        });
        
        document.querySelectorAll('textarea').forEach(function(textarea) {
            textarea.addEventListener('input', function() {
                this.value = this.value.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
            });
        });
        
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            })
            
            const toastEl = document.querySelector('.toast');
            if (toastEl) {
                const toast = new bootstrap.Toast(toastEl);
                toast.show();
            }
            
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            const deleteBtn = document.querySelectorAll('.btn-hapus-soal');
            const deleteLink = document.getElementById('deleteLink');
            
            deleteBtn.forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const token = this.getAttribute('data-token');
                    deleteLink.href = '?ujian=<?= $selected_ujian ?>&hapus=' + id + '&token=' + token;
                    deleteModal.show();
                });
            });
            
            deleteLink.addEventListener('click', function(e) {
                deleteModal.hide();
            });
        });
    </script>
    
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border: none; border-radius: 16px; overflow: hidden;">
                <div class="modal-header justify-content-center pt-4 pb-0 border-0">
                    <div class="delete-icon-wrapper">
                        <div class="delete-icon">
                            <i class="bi bi-trash-fill"></i>
                        </div>
                    </div>
                </div>
                <div class="modal-body text-center px-4 pb-4">
                    <h4 class="fw-bold mb-2" style="color: #1e293b;">Hapus Soal?</h4>
                    <p class="text-muted mb-0">Soal yang dihapus tidak dapat dikembalikan. Apakah Anda yakin?</p>
                </div>
                <div class="modal-footer justify-content-center border-0 pb-4">
                    <button type="button" class="btn btn-secondary btn-batal" data-bs-dismiss="modal" style="padding: 10px 30px; border-radius: 25px; font-weight: 500;">
                        <i class="bi bi-x-lg me-1"></i> Batal
                    </button>
                    <a href="#" id="deleteLink" class="btn btn-danger btn-hapus" style="padding: 10px 30px; border-radius: 25px; font-weight: 500;">
                        <i class="bi bi-trash-fill me-1"></i> Hapus
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        .delete-icon-wrapper {
            width: 80px;
            height: 80px;
            margin: 0 auto;
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: bounce 0.5s ease;
        }
        
        @keyframes bounce {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .delete-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
        }
        
        .delete-icon i {
            font-size: 1.5rem;
            color: white;
        }
        
        .btn-batal {
            background: #f1f5f9;
            border: none;
            color: #64748b;
            transition: all 0.2s;
        }
        
        .btn-batal:hover {
            background: #e2e8f0;
            color: #475569;
        }
        
        .btn-hapus {
            border: none;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
            transition: all 0.2s;
        }
        
        .btn-hapus:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4);
        }
    </style>

    <div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border: none; border-radius: 16px;">
                <div class="modal-header" style="border-bottom: 1px solid #e2e8f0; padding: 1.25rem 1.5rem;">
                    <h5 class="modal-title fw-bold"><i class="bi bi-upload me-2"></i>Import Soal dari DOCX</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" enctype="multipart/form-data" action="import_soal.php">
                    <div class="modal-body" style="padding: 1.5rem;">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="id_ujian" value="<?= $selected_ujian ?>">
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Pilih File DOCX</label>
                            <div class="file-upload-wrapper">
                                <input type="file" name="file_docx" accept=".docx" required onchange="updateDocxName(this, 'label-import')">
                                <div class="file-upload-label" id="label-import">
                                    <i class="bi bi-cloud-upload"></i> Klik untuk upload file .docx
                                </div>
                            </div>
                            <small class="text-muted d-block mt-2">Format: .docx (Word 2007 ke atas)</small>
                        </div>
                        
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Format soal dalam DOCX:</strong><br>
                            PERTANYAAN: ...<br>
                            OPSI_A: ...<br>
                            OPSI_B: ...<br>
                            OPSI_C: ...<br>
                            OPSI_D: ...<br>
                            OPSI_E: ...<br>
                            KUNCI: A/B/C/D/E<br>
                            POIN: 10<br>
                            GAMBAR_PERTANYAAN: nama_file.jpg (opsional)<br>
                            GAMBAR_A: nama_file.jpg (opsional)<br><br>
                            <em>Pisahkan setiap soal dengan 1 baris kosong</em><br>
                            <em>Untuk gambar: embed di DOCX atau tulis nama file saja</em>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top: 1px solid #e2e8f0; padding: 1rem 1.5rem;">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="import_soal" class="btn btn-success">
                            <i class="bi bi-upload me-1"></i> Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
