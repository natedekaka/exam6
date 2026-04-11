<?php
// admin/rekap_nilai.php - Rekap Nilai

session_start();

header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:;");
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

$ujian_list = $conn->query("SELECT id, judul_ujian FROM ujian ORDER BY judul_ujian");
$selected_ujian = isset($_GET['ujian']) ? (int)$_GET['ujian'] : 0;

$hasil_list = [];
$stats = ['total' => 0, 'rata' => 0, 'tertinggi' => 0, 'terendah' => 0];
if ($selected_ujian > 0) {
    $stmt = $conn->prepare("SELECT h.*, u.judul_ujian FROM hasil_ujian h JOIN ujian u ON h.id_ujian = u.id WHERE h.id_ujian = ? ORDER BY h.total_skor DESC");
    $stmt->bind_param("i", $selected_ujian);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $hasil_list[] = $row;
    }
    $stmt->close();
    
    if (count($hasil_list) > 0) {
        $scores = array_column($hasil_list, 'total_skor');
        $stats['total'] = count($hasil_list);
        $stats['rata'] = round(array_sum($scores) / count($scores), 1);
        $stats['tertinggi'] = max($scores);
        $stats['terendah'] = min($scores);
    }
}

if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $id_ujian = isset($_GET['ujian']) ? (int)$_GET['ujian'] : 0;
    $stmt = $conn->prepare("DELETE FROM hasil_ujian WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header('Location: ?ujian=' . $id_ujian . '&deleted=1');
        exit;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Rekap Nilai - Admin</title>
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
        }
        
        .page-header h3 { margin: 0; font-weight: 600; color: var(--dark); }
        
        .card { border: none; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 1.5rem; }
        .card-header { background: #fff; border-bottom: 1px solid var(--border); padding: 1.25rem 1.5rem; font-weight: 600; color: var(--dark); }
        .card-body { padding: 1.5rem; }
        
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
        .btn-success { background: var(--success); border-color: var(--success); }
        
        .stat-card {
            background: linear-gradient(135deg, var(--primary) 0%, #6366f1 100%);
            border-radius: 12px;
            padding: 1.25rem;
            color: white;
            text-align: center;
        }
        
        .stat-card.success { background: linear-gradient(135deg, var(--success) 0%, #34d399 100%); }
        .stat-card.warning { background: linear-gradient(135deg, var(--warning) 0%, #fbbf24 100%); }
        .stat-card.danger { background: linear-gradient(135deg, var(--danger) 0%, #f87171 100%); }
        
        .stat-value { font-size: 2rem; font-weight: 700; }
        .stat-label { font-size: 0.875rem; opacity: 0.9; }
        
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
        
        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 4rem 1rem 1rem; }
            .mobile-toggle { display: flex; }
            .overlay.show { display: block; }
        }
        
        @media (max-width: 768px) {
            .card-body { padding: 1rem; }
            .stat-card { margin-bottom: 1rem; }
            .table { font-size: 0.875rem; }
            .btn { width: 100%; margin-bottom: 0.5rem; }
        }
        
        .animate-fade-in { animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
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
            <a href="tambah_soal.php"><i class="bi bi-question-circle-fill"></i> Bank Soal</a>
            <a href="rekap_nilai.php" class="active"><i class="bi bi-bar-chart-fill"></i> Rekap Nilai</a>
            <a href="profil_sekolah.php"><i class="bi bi-building"></i> Profil Sekolah</a>
            <a href="logout.php" class="text-warning mt-3"><i class="bi bi-box-arrow-right"></i> Logout (<?= htmlspecialchars($_SESSION['admin_username']) ?>)</a>
        </div>
    </div>

    <div class="main-content">
        <div class="page-header animate-fade-in">
            <h3><i class="bi bi-bar-chart-line me-2"></i>Rekap Nilai</h3>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show animate-fade-in">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="card animate-fade-in">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold"><i class="bi bi-file-earmark-text me-1"></i>Pilih Ujian</label>
                        <select name="ujian" class="form-select" onchange="this.form.submit()">
                            <option value="">-- Pilih Ujian --</option>
                            <?php 
                            $ujian_list->data_seek(0);
                            while ($ujian = $ujian_list->fetch_assoc()): 
                            ?>
                            <option value="<?= $ujian['id'] ?>" <?= $selected_ujian == $ujian['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ujian['judul_ujian']) ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <?php if ($selected_ujian > 0): ?>
                    <div class="col-md-4">
                        <a href="ekspor_excel.php?ujian=<?= $selected_ujian ?>" class="btn btn-success w-100">
                            <i class="bi bi-file-earmark-excel me-1"></i> Ekspor Excel
                        </a>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <?php if ($selected_ujian > 0): ?>
        
        <?php if ($stats['total'] > 0): ?>
        <div class="row animate-fade-in">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['total'] ?></div>
                    <div class="stat-label">Total Peserta</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card success">
                    <div class="stat-value"><?= $stats['rata'] ?></div>
                    <div class="stat-label">Rata-rata Skor</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card warning">
                    <div class="stat-value"><?= $stats['tertinggi'] ?></div>
                    <div class="stat-label">Skor Tertinggi</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card danger">
                    <div class="stat-value"><?= $stats['terendah'] ?></div>
                    <div class="stat-label">Skor Terendah</div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card animate-fade-in">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-people me-2"></i>Hasil Ujian</span>
                <span class="badge bg-primary"><?= $stats['total'] ?> peserta</span>
            </div>
            <div class="card-body scrollable-table">
                <?php if ($stats['total'] > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 50px;">No</th>
                                <th>NIS</th>
                                <th>Nama</th>
                                <th>Kelas</th>
                                <th class="text-center" style="width: 80px;">Skor</th>
                                <th class="text-center" style="width: 140px;">Waktu Submit</th>
                                <th class="text-center" style="width: 70px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            foreach ($hasil_list as $hasil): 
                            ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><?= htmlspecialchars($hasil['nis']) ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($hasil['nama']) ?></td>
                                <td><?= htmlspecialchars($hasil['kelas']) ?></td>
                                <td class="text-center">
                                    <span class="badge bg-<?= $hasil['total_skor'] >= $stats['rata'] ? 'success' : 'warning' ?>">
                                        <?= $hasil['total_skor'] ?>
                                    </span>
                                </td>
                                <td class="text-center text-muted"><?= date('d/m/Y H:i', strtotime($hasil['waktu_submit'])) ?></td>
                                <td class="text-center">
                                    <div class="action-buttons">
                                        <button type="button" 
                                            class="action-btn-group btn-hapus-hasil" 
                                            data-id="<?= $hasil['id'] ?>" 
                                            data-ujian="<?= $selected_ujian ?>"
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
                    <p class="text-muted mt-2">Belum ada peserta yang mengerjakan</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php else: ?>
        <div class="card animate-fade-in">
            <div class="card-body text-center py-5">
                <i class="bi bi-folder2-open text-muted" style="font-size: 4rem;"></i>
                <p class="text-muted mt-3">Silakan pilih ujian untuk melihat rekap nilai</p>
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
        
        function showToast(message, type = 'success') {
            const toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
            toastContainer.style.zIndex = '9999';
            
            const bgColor = type === 'success' ? 'linear-gradient(135deg, #10b981 0%, #34d399 100%)' : 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)';
            
            toastContainer.innerHTML = `
                <div class="toast show" role="alert">
                    <div class="toast-header" style="background: ${bgColor}; border: none;">
                        <i class="bi bi-${type === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill'} me-2 text-white"></i>
                        <strong class="me-auto text-white">${type === 'success' ? 'Berhasil' : 'Gagal'}</strong>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                    </div>
                    <div class="toast-body">
                        ${message}
                    </div>
                </div>
            `;
            
            document.body.appendChild(toastContainer);
            
            const toast = new bootstrap.Toast(toastContainer.querySelector('.toast'), { delay: 3000 });
            toast.show();
            
            toastContainer.querySelector('.toast').addEventListener('hidden.bs.toast', function() {
                toastContainer.remove();
            });
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            })
            
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('deleted')) {
                showToast('Data berhasil dihapus!', 'success');
                window.history.replaceState({}, document.title, window.location.pathname + '?ujian=' + urlParams.get('ujian'));
            }
            
            const deleteModalEl = document.getElementById('deleteModal');
            if (deleteModalEl) {
                const deleteModal = new bootstrap.Modal(deleteModalEl);
                const deleteBtn = document.querySelectorAll('.btn-hapus-hasil');
                
                deleteBtn.forEach(btn => {
                    btn.addEventListener('click', function() {
                        const id = this.getAttribute('data-id');
                        const ujian = this.getAttribute('data-ujian');
                        document.getElementById('deleteLink').href = '?ujian=' + ujian + '&hapus=' + id;
                        deleteModal.show();
                    });
                });
                
                document.getElementById('deleteLink').addEventListener('click', function(e) {
                    e.preventDefault();
                    const href = this.getAttribute('href');
                    deleteModal.hide();
                    setTimeout(function() {
                        window.location.href = href;
                    }, 300);
                });
            }
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
                    <h4 class="fw-bold mb-2" style="color: #1e293b;">Hapus Data?</h4>
                    <p class="text-muted mb-0">Data yang dihapus tidak dapat dikembalikan. Apakah Anda yakin?</p>
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
</body>
</html>
