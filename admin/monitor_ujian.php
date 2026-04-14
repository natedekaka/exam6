<?php
// admin/monitor_ujian.php - Monitor Progress Ujian Aktif

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';
require_once '../config/init_sekolah.php';

$sekolah = getKonfigurasiSekolah($conn);

$id_ujian_terpilih = isset($_GET['id_ujian']) ? (int)$_GET['id_ujian'] : 0;

$ujian_list = $conn->query("SELECT id, judul_ujian, status, waktu_tersedia FROM ujian ORDER BY id DESC LIMIT 20");
$ujian_arr = [];
while ($row = $ujian_list->fetch_assoc()) {
    $ujian_arr[] = $row;
}

$progress_data = [];
$total_soal = 0;
if ($id_ujian_terpilih > 0) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total_soal FROM soal WHERE id_ujian = ?");
    $stmt->bind_param("i", $id_ujian_terpilih);
    $stmt->execute();
    $result = $stmt->get_result();
    $total_soal = $result->fetch_assoc()['total_soal'] ?? 0;
    $stmt->close();
    
    // Ambil data dari jawaban_sEMENTARA (sedang ujian)
    $stmt = $conn->prepare("SELECT nis, nama, kelas, answers, updated_at FROM jawaban_sEMENTARA WHERE id_ujian = ?");
    $stmt->bind_param("i", $id_ujian_terpilih);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $answers = json_decode($row['answers'] ?? '{}', true);
        $answered_count = is_array($answers) ? count($answers) : 0;
        $progress_data[$row['nis']] = [
            'nis' => $row['nis'],
            'nama' => $row['nama'],
            'kelas' => $row['kelas'],
            'answered' => $answered_count,
            'last_active' => $row['updated_at'],
            'status' => 'belum_submit'
        ];
    }
    $stmt->close();
    
    // Ambil data dari hasil_ujian (sudah submit)
    $stmt = $conn->prepare("SELECT nis, nama, kelas, total_skor, waktu_submit FROM hasil_ujian WHERE id_ujian = ?");
    $stmt->bind_param("i", $id_ujian_terpilih);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $nis = $row['nis'];
        if (isset($progress_data[$nis])) {
            $progress_data[$nis]['status'] = 'sudah_submit';
            $progress_data[$nis]['skor'] = $row['total_skor'];
        } else {
            $progress_data[$nis] = [
                'nis' => $nis,
                'nama' => $row['nama'],
                'kelas' => $row['kelas'],
                'answered' => $total_soal,
                'last_active' => $row['waktu_submit'],
                'status' => 'sudah_submit',
                'skor' => $row['total_skor']
            ];
        }
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor Ujian - <?= htmlspecialchars($sekolah['nama_sekolah']) ?></title>
    <link href="../vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../vendor/bootstrap-icons/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Poppins', system-ui, -apple-system, sans-serif; }
        body { background: #f0f2f5; margin: 0; }
        .sidebar {
            width: 260px;
            min-height: 100vh;
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            padding: 0;
            flex-shrink: 0;
        }
        .sidebar-menu { padding: 20px 0; }
        .sidebar-menu a {
            display: block;
            padding: 0.75rem 1.5rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        .sidebar-menu a i { margin-right: 10px; width: 20px; }
        .main-content { flex: 1; padding: 25px; }
        .card { 
            border: none; 
            border-radius: 12px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.06); 
            margin-bottom: 20px;
        }
        .card-body { padding: 20px; }
        .status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 5px; }
        .status-active { background: #10b981; animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        .progress-bar-mini { height: 6px; border-radius: 3px; background: #e5e7eb; overflow: hidden; }
        .progress-bar-fill { height: 100%; background: linear-gradient(90deg, #667eea, #764ba2); }
        .badge { padding: 0.5em 0.75em; font-weight: 500; }
        .btn { padding: 0.5rem 1rem; border-radius: 8px; font-weight: 500; }
    </style>
</head>
<body>
    <div class="container-fluid" style="display:flex;">
        <div class="sidebar">
            <div class="text-center py-4" style="background: rgba(0,0,0,0.1);">
                <div style="width: 80px; height: 80px; background: white; border-radius: 50%; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center;">
                    <?php if ($sekolah['logo'] && file_exists('../uploads/' . $sekolah['logo'])): ?>
                    <img src="../uploads/<?= $sekolah['logo'] ?>" alt="Logo" style="width: 60px; height: 60px; object-fit: contain;">
                    <?php else: ?>
                    <i class="bi bi-mortarboard-fill" style="font-size: 2.5rem; color: #667eea;"></i>
                    <?php endif; ?>
                </div>
                <div class="text-white fw-bold" style="font-size: 0.85rem;"><?= htmlspecialchars($sekolah['nama_sekolah']) ?></div>
                <h5 class="mt-2"><i class="bi bi-gear me-1"></i>Admin Panel</h5>
            </div>
            <div class="sidebar-menu">
                <a href="index.php"><i class="bi bi-grid-1x2-fill"></i> Manajemen Ujian</a>
                <a href="tambah_soal.php"><i class="bi bi-question-circle-fill"></i> Bank Soal</a>
                <a href="rekap_nilai.php"><i class="bi bi-bar-chart-fill"></i> Rekap Nilai</a>
                <a href="profil_sekolah.php"><i class="bi bi-building"></i> Profil Sekolah</a>
                <a href="monitor_ujian.php" class="active"><i class="bi bi-display"></i> Monitor Ujian</a>
                <?php if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'super_admin'): ?>
                <a href="manage_users.php"><i class="bi bi-people-fill"></i> Kelola Admin</a>
                <?php endif; ?>
                <a href="logout.php" class="text-warning mt-3"><i class="bi bi-box-arrow-right"></i> Logout (<?= htmlspecialchars($_SESSION['admin_username']) ?>)</a>
            </div>
        </div>
        
        <div class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold">Monitor Progress Ujian</h4>
                <button onclick="location.reload()" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Pilih Ujian</label>
                            <select name="id_ujian" class="form-select" onchange="this.form.submit()">
                                <option value="">-- Pilih Ujian --</option>
                                <?php foreach ($ujian_arr as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= $id_ujian_terpilih == $u['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u['judul_ujian']) ?> (<?= $u['status'] ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <?php if ($id_ujian_terpilih > 0): ?>
                            <div class="badge bg-primary fs-6">
                                <i class="bi bi-question-circle"></i> Total <?= $total_soal ?> soal
                            </div>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if ($id_ujian_terpilih > 0 && !empty($progress_data)): ?>
            <div class="card">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0"><i class="bi bi-people"></i> Progress Semua Siswa</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>NIS</th>
                                <th>Nama</th>
                                <th>Kelas</th>
                                <th>Jawaban</th>
                                <th>Progress</th>
                                <th>Status</th>
                                <th>Skor</th>
                                <th>Last Active</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($progress_data as $p): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($p['nis']) ?></td>
                                <td><?= htmlspecialchars($p['nama']) ?></td>
                                <td><?= htmlspecialchars($p['kelas']) ?></td>
                                <td><?= $p['answered'] ?>/<?= $total_soal ?></td>
                                <td style="width: 150px;">
                                    <div class="progress-bar-mini">
                                        <div class="progress-bar-fill" style="width: <?= ($p['answered'] / max(1, $total_soal)) * 100 ?>%"></div>
                                    </div>
                                    <small class="text-muted"><?= round(($p['answered'] / max(1, $total_soal)) * 100) ?>%</small>
                                </td>
                                <td>
                                    <?php if ($p['status'] === 'sudah_submit'): ?>
                                    <span class="badge bg-success"><i class="bi bi-check-circle"></i> Submit</span>
                                    <?php else: ?>
                                    <span class="badge bg-warning"><span class="status-dot status-active"></span>Lagi Ujian</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= isset($p['skor']) ? $p['skor'] : '-' ?></td>
                                <td><small><?= date('H:i', strtotime($p['last_active'] ?? date('Y-m-d H:i:s'))) ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php elseif ($id_ujian_terpilih > 0): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                    <p class="mt-3 text-muted">Belum ada siswa yang memulai ujian ini</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="../vendor/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>