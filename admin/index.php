<?php
// admin/index.php - Dashboard Admin (Manajemen Ujian)

session_start();

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';
require_once '../config/init_sekolah.php';

$sekolah = getKonfigurasiSekolah($conn);

$message = '';
$message_type = '';

// Cek kolom baru dengan cara aman
$has_new_columns = false;
$has_tampilkan_skor = false;
$has_acak_opsi = false;
try {
    $result_cols = $conn->query("SHOW COLUMNS FROM ujian LIKE 'acak_soal'");
    if ($result_cols && $result_cols->num_rows > 0) {
        $row = $result_cols->fetch_assoc();
        $result_cols->free();
        $col_type = strtolower($row['Type'] ?? '');
        if (strpos($col_type, 'varchar') !== false || strpos($col_type, 'enum') !== false) {
            $has_new_columns = true;
        }
    }
    $result_cols2 = $conn->query("SHOW COLUMNS FROM ujian LIKE 'tampilkan_skor'");
    if ($result_cols2 && $result_cols2->num_rows > 0) {
        $has_tampilkan_skor = true;
    }
    $result_cols3 = $conn->query("SHOW COLUMNS FROM ujian LIKE 'acak_opsi'");
    if ($result_cols3 && $result_cols3->num_rows > 0) {
        $has_acak_opsi = true;
    }
} catch (Exception $e) {
    $has_new_columns = false;
    $has_tampilkan_skor = false;
    $has_acak_opsi = false;
}

if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $status = $_GET['status'] === 'aktif' ? 'nonaktif' : 'aktif';
    
    $stmt = $conn->prepare("UPDATE ujian SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    if ($stmt->execute()) {
        $message = "Status ujian berhasil diubah!";
        $message_type = 'success';
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_ujian'])) {
    $judul = trim($_POST['judul_ujian'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $status = in_array($_POST['status'] ?? 'nonaktif', ['aktif', 'nonaktif']) ? $_POST['status'] : 'nonaktif';
    $waktu_tersedia = isset($_POST['waktu_tersedia']) ? (int)$_POST['waktu_tersedia'] : 0;
    
    // Validasi ketat untuk acak_soal dan tampilkan_review
    $acak_soal = 'tidak';
    if (isset($_POST['acak_soal']) && ($_POST['acak_soal'] === 'ya' || $_POST['acak_soal'] === 'tidak')) {
        $acak_soal = $_POST['acak_soal'];
    }
    
    $acak_opsi = 'tidak';
    if ($has_acak_opsi && isset($_POST['acak_opsi']) && ($_POST['acak_opsi'] === 'ya' || $_POST['acak_opsi'] === 'tidak')) {
        $acak_opsi = $_POST['acak_opsi'];
    }
    
    $tampilkan_review = 'tidak';
    if (isset($_POST['tampilkan_review']) && ($_POST['tampilkan_review'] === 'ya' || $_POST['tampilkan_review'] === 'tidak')) {
        $tampilkan_review = $_POST['tampilkan_review'];
    }
    
    $tampilkan_skor = 'ya';
    if ($has_tampilkan_skor && isset($_POST['tampilkan_skor']) && ($_POST['tampilkan_skor'] === 'ya' || $_POST['tampilkan_skor'] === 'tidak')) {
        $tampilkan_skor = $_POST['tampilkan_skor'];
    }
    
    $edit_id = isset($_POST['edit_id']) ? (int)$_POST['edit_id'] : 0;
    $original_updated = $_POST['original_updated'] ?? '';
    
    if (empty($judul)) {
        $message = "Judul ujian wajib diisi!";
        $message_type = 'danger';
    } else {
        if ($edit_id > 0) {
            $stmt = $conn->prepare("SELECT updated_at FROM ujian WHERE id = ?");
            $stmt->bind_param("i", $edit_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $current_data = $result->fetch_assoc();
            $stmt->close();
            
            if ($current_data && $original_updated !== $current_data['updated_at']) {
                $message = "Data telah diubah oleh pengguna lain. Silakan refresh dan coba lagi.";
                $message_type = 'danger';
            } else {
                if ($has_acak_opsi) {
                    $stmt = $conn->prepare("UPDATE ujian SET judul_ujian = ?, deskripsi = ?, status = ?, waktu_tersedia = ?, acak_soal = ?, acak_opsi = ?, tampilkan_review = ?, tampilkan_skor = ? WHERE id = ?");
                    $stmt->bind_param("sssissssi", $judul, $deskripsi, $status, $waktu_tersedia, $acak_soal, $acak_opsi, $tampilkan_review, $tampilkan_skor, $edit_id);
                } elseif ($has_tampilkan_skor) {
                    $stmt = $conn->prepare("UPDATE ujian SET judul_ujian = ?, deskripsi = ?, status = ?, waktu_tersedia = ?, acak_soal = ?, tampilkan_review = ?, tampilkan_skor = ? WHERE id = ?");
                    $stmt->bind_param("sssisssi", $judul, $deskripsi, $status, $waktu_tersedia, $acak_soal, $tampilkan_review, $tampilkan_skor, $edit_id);
                } elseif ($has_new_columns) {
                    $stmt = $conn->prepare("UPDATE ujian SET judul_ujian = ?, deskripsi = ?, status = ?, waktu_tersedia = ?, acak_soal = ?, tampilkan_review = ? WHERE id = ?");
                    $stmt->bind_param("sssiisi", $judul, $deskripsi, $status, $waktu_tersedia, $acak_soal, $tampilkan_review, $edit_id);
                } else {
                    $stmt = $conn->prepare("UPDATE ujian SET judul_ujian = ?, deskripsi = ?, status = ? WHERE id = ?");
                    $stmt->bind_param("sssi", $judul, $deskripsi, $status, $edit_id);
                }
                $message = "Ujian berhasil diperbarui!";
            }
        } else {
            if ($has_acak_opsi) {
                $stmt = $conn->prepare("INSERT INTO ujian (judul_ujian, deskripsi, status, waktu_tersedia, acak_soal, acak_opsi, tampilkan_review, tampilkan_skor) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssissss", $judul, $deskripsi, $status, $waktu_tersedia, $acak_soal, $acak_opsi, $tampilkan_review, $tampilkan_skor);
            } elseif ($has_tampilkan_skor) {
                $stmt = $conn->prepare("INSERT INTO ujian (judul_ujian, deskripsi, status, waktu_tersedia, acak_soal, tampilkan_review, tampilkan_skor) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssisss", $judul, $deskripsi, $status, $waktu_tersedia, $acak_soal, $tampilkan_review, $tampilkan_skor);
            } elseif ($has_new_columns) {
                $stmt = $conn->prepare("INSERT INTO ujian (judul_ujian, deskripsi, status, waktu_tersedia, acak_soal, tampilkan_review) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssiis", $judul, $deskripsi, $status, $waktu_tersedia, $acak_soal, $tampilkan_review);
            } else {
                $stmt = $conn->prepare("INSERT INTO ujian (judul_ujian, deskripsi, status) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $judul, $deskripsi, $status);
            }
            $message = "Ujian berhasil ditambahkan!";
        }
        
        if (empty($message_type)) {
            if ($stmt->execute()) {
                $message_type = 'success';
            }
            $stmt->close();
        }
    }
}

if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $stmt = $conn->prepare("DELETE FROM ujian WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header('Location: index.php?deleted=1');
        exit;
    }
    $stmt->close();
}

$result = $conn->query("SELECT * FROM ujian ORDER BY tgl_dibuat DESC");

$edit_ujian = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM ujian WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit_result = $stmt->get_result();
    $edit_ujian = $edit_result->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Dashboard Admin - Manajemen Ujian</title>
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
            --dark: #1e293b;
            --light: #f8fafc;
            --border: #e2e8f0;
            --sidebar-width: 260px;
        }
        
        * { font-family: 'Inter', sans-serif; }
        
        body { background-color: #f1f5f9; min-height: 100vh; }
        
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
        
        .sidebar-brand { padding: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-brand h5 { color: #fff; font-weight: 600; margin: 0; }
        
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
        
        .sidebar a:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .sidebar a.active { background: rgba(79, 70, 229, 0.2); color: #fff; border-left-color: var(--primary); }
        
        .main-content { margin-left: var(--sidebar-width); padding: 2rem; transition: margin-left 0.3s ease; }
        
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
        
        .page-header h3 { margin: 0; font-weight: 600; color: var(--dark); }
        
        .card { border: none; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 1.5rem; }
        .card-header { background: #fff; border-bottom: 1px solid var(--border); padding: 1.25rem 1.5rem; font-weight: 600; color: var(--dark); }
        .card-body { padding: 1.5rem; }
        
        .form-control, .form-select {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 0.625rem 0.875rem;
            font-size: 0.9375rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .btn { border-radius: 8px; padding: 0.625rem 1.25rem; font-weight: 500; transition: all 0.2s ease; }
        .btn-primary { background: var(--primary); border-color: var(--primary); }
        .btn-primary:hover { background: var(--primary-hover); border-color: var(--primary-hover); }
        
        .table thead th { background: #f8fafc; border-bottom: 2px solid var(--border); color: var(--secondary); font-weight: 600; font-size: 0.8125rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 1rem; }
        .table tbody td { padding: 1rem; vertical-align: middle; border-bottom: 1px solid var(--border); }
        .table tbody tr:hover { background: #f8fafc; }
        
        .badge { font-weight: 500; padding: 0.375rem 0.75rem; border-radius: 6px; font-size: 0.75rem; }
        
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
        
        .overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999; }
        
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
            font-family: bootstrap-icons !important;
        }
        
        .action-btn i {
            font-family: bootstrap-icons !important;
            font-style: normal;
            font-variant: normal;
            text-transform: none;
            line-height: 1;
            font-size: 1.1rem;
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
        
        .action-btn-toggle-on {
            background: #dcfce7;
            color: #16a34a !important;
        }
        
        .action-btn-toggle-on:hover {
            background: #bbf7d0;
            color: #15803d !important;
        }
        
        .action-btn-toggle-off {
            background: #fee2e2;
            color: #dc2626 !important;
        }
        
        .action-btn-toggle-off:hover {
            background: #fecaca;
            color: #b91c1c !important;
        }
        
        .action-btn-bank {
            background: #dbeafe;
            color: #2563eb !important;
        }
        
        .action-btn-bank:hover {
            background: #bfdbfe;
            color: #1d4ed8 !important;
        }
        
        .action-btn-delete {
            background: #f3f4f6;
            color: #6b7280 !important;
        }
        
        .action-btn-delete:hover {
            background: #fee2e2;
            color: #dc2626 !important;
        }
        
        .btn-action-label {
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 4rem 1rem 1rem; }
            .mobile-toggle { display: flex; }
            .overlay.show { display: block; }
            .page-header { padding: 1rem; flex-direction: column; align-items: flex-start; }
        }
        
        @media (max-width: 768px) {
            .card-body { padding: 1rem; }
            .table { font-size: 0.875rem; }
            .table thead th, .table tbody td { padding: 0.5rem; }
            .btn { width: 100%; margin-bottom: 0.5rem; }
            .action-buttons .btn { width: auto; margin-bottom: 0; }
        }
        
        .animate-fade-in { animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        .alert-danger-conflict {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border: 1px solid #fecaca;
            border-left: 4px solid #ef4444;
        }

        .delete-modal .modal-content {
            border: none;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .delete-modal .modal-header {
            border-bottom: none;
            padding: 1.5rem 1.5rem 0;
        }

        .delete-modal .modal-body {
            padding: 1rem 1.5rem 1.5rem;
            text-align: center;
        }

        .delete-modal .modal-footer {
            border-top: none;
            padding: 0 1.5rem 1.5rem;
            justify-content: center;
            gap: 0.75rem;
        }

        .delete-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .delete-icon i {
            font-size: 2.5rem;
            color: #ef4444;
        }

        .toast-notification {
            position: fixed;
            top: 1.5rem;
            right: 1.5rem;
            z-index: 9999;
            min-width: 300px;
        }

        .toast-notification.show {
            animation: slideIn 0.4s ease, fadeOut 0.4s ease 2.6s forwards;
        }

        @keyframes slideIn {
            from { transform: translateX(120%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }

        .toast-success {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            border: 1px solid #a7f3d0;
            border-left: 4px solid #10b981;
            border-radius: 12px;
        }

        .toast-success .toast-icon {
            color: #10b981;
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
            <a href="index.php" class="active"><i class="bi bi-grid-1x2-fill"></i> Manajemen Ujian</a>
            <a href="tambah_soal.php"><i class="bi bi-question-circle-fill"></i> Bank Soal</a>
            <a href="rekap_nilai.php"><i class="bi bi-bar-chart-fill"></i> Rekap Nilai</a>
            <a href="profil_sekolah.php"><i class="bi bi-building"></i> Profil Sekolah</a>
            <a href="logout.php" class="text-warning mt-3"><i class="bi bi-box-arrow-right"></i> Logout (<?= htmlspecialchars($_SESSION['admin_username']) ?>)</a>
        </div>
    </div>

    <div class="main-content">
        <div class="page-header animate-fade-in">
            <h3><i class="bi bi-clipboard-data me-2"></i>Manajemen Ujian - SMA Negeri 6 Cimahi</h3>
            <span class="badge bg-primary fs-6"><?= $result->num_rows ?> ujian</span>
        </div>
        
        <?php if ($message): ?>
        <div class="alert <?= ($message_type === 'danger' && strpos($message, 'pengguna lain') !== false) ? 'alert-danger-conflict' : 'alert-'.$message_type ?> alert-dismissible fade show animate-fade-in" role="alert">
            <?php if ($message_type === 'danger' && strpos($message, 'pengguna lain') !== false): ?>
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div>
                        <strong>Konflik Data!</strong><br>
                        <?= htmlspecialchars($message) ?>
                    </div>
                </div>
            <?php else: ?>
                <?= htmlspecialchars($message) ?>
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="card animate-fade-in">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-<?= $edit_ujian ? 'pencil-square' : 'plus-circle' ?> me-2"></i><?= $edit_ujian ? 'Edit Ujian' : 'Tambah Ujian Baru' ?></span>
                <?php if ($edit_ujian): ?>
                <a href="index.php" class="btn btn-sm btn-secondary">
                    <i class="bi bi-x-lg"></i> Batal
                </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form method="POST" autocomplete="off">
                    <?php if ($edit_ujian): ?>
                        <input type="hidden" name="edit_id" value="<?= $edit_ujian['id'] ?>">
                        <input type="hidden" name="original_updated" value="<?= $edit_ujian['updated_at'] ?>">
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Judul Ujian <span class="text-danger">*</span></label>
                            <input type="text" name="judul_ujian" class="form-control" required 
                                   value="<?= $edit_ujian ? htmlspecialchars($edit_ujian['judul_ujian']) : '' ?>"
                                   placeholder="Contoh: Ujian Matematika Semester 1">
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <option value="nonaktif" <?= $edit_ujian && $edit_ujian['status'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                                <option value="aktif" <?= $edit_ujian && $edit_ujian['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                            </select>
                        </div>

                        <?php if ($has_new_columns): ?>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Waktu (menit)</label>
                            <input type="number" name="waktu_tersedia" class="form-control" 
                                   value="<?= $edit_ujian ? htmlspecialchars($edit_ujian['waktu_tersedia'] ?? 0) : 0 ?>"
                                   placeholder="0 = tidak terbatas" min="0">
                            <small class="text-muted">0 = tidak terbatas</small>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Acak Urutan Soal</label>
                            <select name="acak_soal" class="form-select">
                                <option value="tidak" <?= $edit_ujian && ($edit_ujian['acak_soal'] ?? 'tidak') === 'tidak' ? 'selected' : '' ?>>Tidak</option>
                                <option value="ya" <?= $edit_ujian && ($edit_ujian['acak_soal'] ?? 'tidak') === 'ya' ? 'selected' : '' ?>>Ya - Acak soal</option>
                            </select>
                        </div>

                        <?php if ($has_acak_opsi): ?>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Acak Opsi Jawaban</label>
                            <select name="acak_opsi" class="form-select">
                                <option value="tidak" <?= $edit_ujian && ($edit_ujian['acak_opsi'] ?? 'tidak') === 'tidak' ? 'selected' : '' ?>>Tidak</option>
                                <option value="ya" <?= $edit_ujian && ($edit_ujian['acak_opsi'] ?? 'tidak') === 'ya' ? 'selected' : '' ?>>Ya - Acak opsi A/B/C/D/E</option>
                            </select>
                        </div>
                        <?php endif; ?>

                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Tampilkan Review</label>
                            <select name="tampilkan_review" class="form-select">
                                <option value="tidak" <?= $edit_ujian && ($edit_ujian['tampilkan_review'] ?? 'tidak') === 'tidak' ? 'selected' : '' ?>>Tidak</option>
                                <option value="ya" <?= $edit_ujian && ($edit_ujian['tampilkan_review'] ?? 'tidak') === 'ya' ? 'selected' : '' ?>>Ya - Tampilkan setelah submit</option>
                            </select>
                        </div>

                        <?php if ($has_tampilkan_skor): ?>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Tampilkan Skor</label>
                            <select name="tampilkan_skor" class="form-select">
                                <option value="ya" <?= $edit_ujian && ($edit_ujian['tampilkan_skor'] ?? 'ya') === 'ya' ? 'selected' : '' ?>>Ya - Tampilkan skor setelah submit</option>
                                <option value="tidak" <?= $edit_ujian && ($edit_ujian['tampilkan_skor'] ?? 'ya') === 'tidak' ? 'selected' : '' ?>>Tidak - Sembunyikan skor</option>
                            </select>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="3" 
                                  placeholder="Masukkan deskripsi ujian..."><?= $edit_ujian ? htmlspecialchars($edit_ujian['deskripsi']) : '' ?></textarea>
                    </div>
                    
                    <button type="submit" name="simpan_ujian" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> <?= $edit_ujian ? 'Perbarui' : 'Simpan' ?>
                    </button>
                </form>
            </div>
        </div>

        <div class="card animate-fade-in">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-list-ol me-2"></i>Daftar Ujian</span>
                <span class="badge bg-primary"><?= $result->num_rows ?> ujian</span>
            </div>
            <div class="card-body p-0">
                <?php if ($result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 50px;">No</th>
                                <th>Judul</th>
                                <th class="text-center" style="width: 80px;">Status</th>
                                <?php if ($has_new_columns): ?>
                                <th class="text-center" style="width: 80px;">Waktu</th>
                                <th class="text-center" style="width: 80px;">Acak</th>
                                <?php if ($has_acak_opsi): ?>
                                <th class="text-center" style="width: 80px;">Opsi</th>
                                <?php endif; ?>
                                <th class="text-center" style="width: 80px;">Review</th>
                                <?php if ($has_tampilkan_skor): ?>
                                <th class="text-center" style="width: 80px;">Skor</th>
                                <?php endif; ?>
                                <?php endif; ?>
                                <th class="text-center" style="width: 100px;">Tgl Dibuat</th>
                                <th style="min-width: 180px;">Link</th>
                                <th class="text-center" style="width: 150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($row['judul_ujian']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars(mb_strimwidth($row['deskripsi'] ?? '', 0, 50, '...')) ?></small>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-<?= $row['status'] === 'aktif' ? 'success' : 'secondary' ?>">
                                        <?= strtoupper($row['status']) ?>
                                    </span>
                                </td>
                                <?php if ($has_new_columns): ?>
                                <td class="text-center">
                                    <?php if (($row['waktu_tersedia'] ?? 0) > 0): ?>
                                        <span class="badge bg-info"><?= $row['waktu_tersedia'] ?> menit</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if (($row['acak_soal'] ?? 'tidak') === 'ya'): ?>
                                        <span class="badge bg-warning"><i class="bi bi-shuffle"></i></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <?php if ($has_acak_opsi): ?>
                                <td class="text-center">
                                    <?php if (($row['acak_opsi'] ?? 'tidak') === 'ya'): ?>
                                        <span class="badge bg-info"><i class="bi bi-shuffle"></i></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                                <td class="text-center">
                                    <?php if (($row['tampilkan_review'] ?? 'tidak') === 'ya'): ?>
                                        <span class="badge bg-success"><i class="bi bi-check"></i></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <?php if ($has_tampilkan_skor): ?>
                                <td class="text-center">
                                    <?php if (($row['tampilkan_skor'] ?? 'ya') === 'ya'): ?>
                                        <span class="badge bg-success"><i class="bi bi-check"></i></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><i class="bi bi-x"></i></span>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                                <?php endif; ?>
                                <td class="text-center text-muted"><?= date('d/m/Y', strtotime($row['tgl_dibuat'])) ?></td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control" value="<?= '../ujian.php?id=' . $row['id'] ?>" id="link<?= $row['id'] ?>" readonly>
                                        <button class="btn btn-outline-secondary" type="button" onclick="copyLink(<?= $row['id'] ?>)" title="Copy Link">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                        <a href="../ujian.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-outline-primary" title="Buka">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?edit=<?= $row['id'] ?>" 
                                           class="action-btn-group" 
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top" 
                                           title="Edit Ujian">
                                            <span class="action-btn action-btn-edit">
                                                <i class="bi bi-pencil" style="font-size: 1rem;"></i>
                                            </span>
                                            <span class="action-btn-label">Edit</span>
                                        </a>
                                        <a href="?toggle=1&id=<?= $row['id'] ?>&status=<?= $row['status'] ?>" 
                                           class="action-btn-group" 
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top" 
                                           title="<?= $row['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                            <span class="action-btn <?= $row['status'] === 'aktif' ? 'action-btn-toggle-on' : 'action-btn-toggle-off' ?>">
                                                <i class="bi bi-<?= $row['status'] === 'aktif' ? 'toggle-on' : 'toggle-off' ?>" style="font-size: 1rem;"></i>
                                            </span>
                                            <span class="action-btn-label"><?= $row['status'] === 'aktif' ? 'Aktif' : 'Nonaktif' ?></span>
                                        </a>
                                        <a href="tambah_soal.php?ujian=<?= $row['id'] ?>" 
                                           class="action-btn-group" 
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top" 
                                           title="Bank Soal">
                                            <span class="action-btn action-btn-bank">
                                                <i class="bi bi-journal-bookmark" style="font-size: 1rem;"></i>
                                            </span>
                                            <span class="action-btn-label">Soal</span>
                                        </a>
                                        <a href="javascript:void(0)" 
                                           class="action-btn-group" 
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top" 
                                           title="Hapus Ujian"
                                           onclick="showDeleteModal(<?= $row['id'] ?>, '<?= htmlspecialchars($row['judul_ujian'], ENT_QUOTES) ?>')">
                                            <span class="action-btn action-btn-delete">
                                                <i class="bi bi-trash3" style="font-size: 1rem;"></i>
                                            </span>
                                            <span class="action-btn-label">Hapus</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-2">Belum ada ujian</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade delete-modal" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="delete-icon">
                        <i class="bi bi-trash3"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Hapus Ujian?</h5>
                    <p class="text-muted mb-0">Apakah Anda yakin ingin menghapus ujian "<strong id="deleteUjianTitle"></strong>"?</p>
                    <p class="text-danger small mb-0 mt-2"><i class="bi bi-exclamation-triangle me-1"></i>Tindakan ini tidak dapat dibatalkan. Semua soal juga akan dihapus.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Batal
                    </button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">
                        <i class="bi bi-trash3 me-1"></i>Hapus
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast-notification" id="toastNotification">
        <div class="toast toast-success p-3" role="alert">
            <div class="d-flex align-items-center">
                <div class="toast-icon me-3">
                    <i class="bi bi-check-circle-fill" style="font-size: 1.5rem;"></i>
                </div>
                <div class="flex-grow-1">
                    <strong class="d-block">Berhasil!</strong>
                    <small class="text-muted" id="toastMessage">Ujian berhasil dihapus.</small>
                </div>
            </div>
        </div>
    </div>

    <script src="../vendor/bootstrap/bootstrap.bundle.min.js" defer></script>
    <script>
        var deleteModal;
        
        document.addEventListener('DOMContentLoaded', function() {
            deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            
            // Check for delete success message in URL
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('deleted') === '1') {
                showToast('Ujian berhasil dihapus!');
                // Clean URL
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        });

        function showDeleteModal(id, title) {
            document.getElementById('deleteUjianTitle').textContent = title;
            document.getElementById('confirmDeleteBtn').href = '?hapus=' + id;
            deleteModal.show();
        }

        function showToast(message) {
            var toast = document.getElementById('toastNotification');
            document.getElementById('toastMessage').textContent = message;
            toast.classList.add('show');
            setTimeout(function() {
                toast.classList.remove('show');
            }, 3000);
        }
        
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
        
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('show');
            document.querySelector('.overlay').classList.toggle('show');
        }
        
        function copyLink(id) {
            var copyText = document.getElementById("link" + id);
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(window.location.origin + '/' + copyText.value).then(function() {
                alert("Link ujian copied!");
            }).catch(function() {
                copyText.select();
                document.execCommand('copy');
                alert("Link ujian copied!");
            });
        }
    </script>
</body>
</html>
