<?php

session_start();

header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:;");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';
require_once '../config/init_sekolah.php';

$sekolah = getKonfigurasiSekolah($conn);

$selected_ujian = isset($_GET['ujian']) ? (int)$_GET['ujian'] : 0;
$kkm = isset($_GET['kkm']) ? (int)$_GET['kkm'] : 60; // Default KKM = 60

$ujian_list = $conn->query("SELECT id, judul_ujian FROM ujian ORDER BY judul_ujian");

$analytics = [
    'total_peserta' => 0,
    'avg_score' => 0,
    'avg_original' => 0,
    'total_violations' => 0,
    'completion_rate' => 0,
    'grade_distribution' => ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0],
    'score_ranges' => [
        '0-20' => 0,
        '21-40' => 0,
        '41-60' => 0,
        '61-80' => 0,
        '81-100' => 0
    ],
    'violations_by_hour' => [],
    'recent_submissions' => [],
    'top_scorers' => [],
    'needs_remedi' => 0
];

if ($selected_ujian > 0) {
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total,
            AVG(CASE WHEN skor_awal IS NOT NULL THEN skor_awal ELSE total_skor END) as avg_original,
            AVG(total_skor) as avg_score,
            MAX(total_skor) as highest,
            MIN(total_skor) as lowest
        FROM hasil_ujian 
        WHERE id_ujian = ?
    ");
    $stmt->bind_param("i", $selected_ujian);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = $result->fetch_assoc();
    $stmt->close();
    
    $analytics['total_peserta'] = $stats['total'];
    $analytics['avg_score'] = round($stats['avg_score'], 1);
    $analytics['avg_original'] = round($stats['avg_original'], 1);
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total_violations, COUNT(DISTINCT nis) as students_with_violations
        FROM exam_violations 
        WHERE id_ujian = ?
    ");
    $stmt->bind_param("i", $selected_ujian);
    $stmt->execute();
    $result = $stmt->get_result();
    $violation_stats = $result->fetch_assoc();
    $stmt->close();
    
    $analytics['total_violations'] = $violation_stats['total_violations'];
    
    $stmt = $conn->prepare("
        SELECT 
            CASE 
                WHEN total_skor >= 85 THEN 'A'
                WHEN total_skor >= 70 THEN 'B'
                WHEN total_skor >= 55 THEN 'C'
                WHEN total_skor >= 40 THEN 'D'
                ELSE 'E'
            END as grade,
            COUNT(*) as count
        FROM hasil_ujian 
        WHERE id_ujian = ?
        GROUP BY grade
        ORDER BY grade
    ");
    $stmt->bind_param("i", $selected_ujian);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $analytics['grade_distribution'][$row['grade']] = $row['count'];
    }
    $stmt->close();
    
    $stmt = $conn->prepare("
        SELECT 
            CASE 
                WHEN total_skor <= 20 THEN '0-20'
                WHEN total_skor <= 40 THEN '21-40'
                WHEN total_skor <= 60 THEN '41-60'
                WHEN total_skor <= 80 THEN '61-80'
                ELSE '81-100'
            END as score_range,
            COUNT(*) as count
        FROM hasil_ujian 
        WHERE id_ujian = ?
        GROUP BY score_range
        ORDER BY score_range
    ");
    $stmt->bind_param("i", $selected_ujian);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $analytics['score_ranges'][$row['score_range']] = $row['count'];
    }
    $stmt->close();
    
    $stmt = $conn->prepare("
        SELECT HOUR(created_at) as hour, COUNT(*) as count
        FROM exam_violations 
        WHERE id_ujian = ?
        GROUP BY HOUR(created_at)
        ORDER BY hour
    ");
    $stmt->bind_param("i", $selected_ujian);
    $stmt->execute();
    $result = $stmt->get_result();
    $violations_by_hour = [];
    for ($i = 0; $i < 24; $i++) {
        $violations_by_hour[$i] = 0;
    }
    while ($row = $result->fetch_assoc()) {
        $violations_by_hour[(int)$row['hour']] = $row['count'];
    }
    $stmt->close();
    $analytics['violations_by_hour'] = $violations_by_hour;
    
    $stmt = $conn->prepare("
        SELECT nama, nis, total_skor, waktu_submit
        FROM hasil_ujian 
        WHERE id_ujian = ?
        ORDER BY waktu_submit DESC
        LIMIT 10
    ");
    $stmt->bind_param("i", $selected_ujian);
    $stmt->execute();
    $result = $stmt->get_result();
    $analytics['recent_submissions'] = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    $stmt = $conn->prepare("
        SELECT nama, nis, total_skor, kelas
        FROM hasil_ujian 
        WHERE id_ujian = ?
        ORDER BY total_skor DESC
        LIMIT 5
    ");
    $stmt->bind_param("i", $selected_ujian);
    $stmt->execute();
    $result = $stmt->get_result();
    $analytics['top_scorers'] = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    $stmt = $conn->prepare("
        SELECT h.id, h.nama, h.nis, h.kelas, h.total_skor
        FROM hasil_ujian h
        WHERE h.id_ujian = ? AND h.total_skor < ?
        ORDER BY h.total_skor ASC
    ");
    $stmt->bind_param("ii", $selected_ujian, $kkm);
    $stmt->execute();
    $result = $stmt->get_result();
    $analytics['needs_remedi_list'] = $result->fetch_all(MYSQLI_ASSOC);
    $analytics['needs_remedi'] = count($analytics['needs_remedi_list']);
    $stmt->close();
    
    // Get list of students who already have remedi
    $stmt = $conn->prepare("SELECT nis FROM izin_remedi WHERE id_ujian = ?");
    $stmt->bind_param("i", $selected_ujian);
    $stmt->execute();
    $result = $stmt->get_result();
    $remedi_given = [];
    while ($row = $result->fetch_assoc()) {
        $remedi_given[] = $row['nis'];
    }
    $stmt->close();
    $analytics['remedi_given'] = $remedi_given;
    
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT h.nis) as completed, 
               (SELECT COUNT(*) FROM izin_remedi WHERE id_ujian = ?) as remedi_given
        FROM hasil_ujian h
        WHERE h.id_ujian = ?
    ");
    $stmt->bind_param("ii", $selected_ujian, $selected_ujian);
    $stmt->execute();
    $result = $stmt->get_result();
    $completion = $result->fetch_assoc();
    $stmt->close();
    
    $analytics['completion_rate'] = $stats['total'] > 0 ? round(($completion['completed'] / $stats['total']) * 100, 1) : 0;
}

$ujian_judul = '';
if ($selected_ujian > 0) {
    $stmt = $conn->prepare("SELECT judul_ujian FROM ujian WHERE id = ?");
    $stmt->bind_param("i", $selected_ujian);
    $stmt->execute();
    $result = $stmt->get_result();
    $ujian_data = $result->fetch_assoc();
    $ujian_judul = $ujian_data['judul_ujian'];
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - <?= htmlspecialchars($sekolah['nama_sekolah']) ?></title>
    <link href="../vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../vendor/bootstrap-icons/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
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
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f0f2f5;
            color: #2c3e50;
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
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .sidebar a:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .sidebar a.active { background: rgba(79, 70, 229, 0.2); color: #fff; border-left-color: var(--primary); }
        
        .main-content { margin-left: var(--sidebar-width); padding: 2rem; transition: margin-left 0.3s ease; width: calc(100% - var(--sidebar-width)); box-sizing: border-box; min-width: 0; z-index: 1; }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .main-content { margin-left: 0; width: 100%; }
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 { font-size: 1.5rem; font-weight: 600; margin: 0; }
        
        .header-actions a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        
        .header-actions a:hover { background: rgba(255,255,255,0.2); }
        
        .filter-section {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        
        .filter-section form {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-section select {
            padding: 0.6rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            min-width: 300px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        
        .stat-card .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-card.primary .stat-icon { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .stat-card.success .stat-icon { background: linear-gradient(135deg, #10b981 0%, #34d399 100%); color: white; }
        .stat-card.warning .stat-icon { background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%); color: white; }
        .stat-card.danger .stat-icon { background: linear-gradient(135deg, #ef4444 0%, #f87171 100%); color: white; }
        
        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.3rem;
        }
        
        .stat-card .stat-label {
            font-size: 0.85rem;
            color: #64748b;
        }
        
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .chart-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .chart-card h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #2c3e50;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        .table-section {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        
        .table-section h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        th {
            background: #f8fafc;
            font-weight: 600;
            color: #64748b;
            font-size: 0.85rem;
            text-transform: uppercase;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .badge-success { background: #d1fae5; color: #10b981; }
        .badge-danger { background: #fee2e2; color: #ef4444; }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
            color: white;
        }
        
        .btn-success:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #94a3b8;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        th {
            background: #f8fafc;
            font-weight: 600;
            color: #64748b;
            font-size: 0.85rem;
            text-transform: uppercase;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .badge-primary { background: #e0e7ff; color: #667eea; }
        .badge-success { background: #d1fae5; color: #10b981; }
        .badge-warning { background: #fef3c7; color: #f59e0b; }
        .badge-danger { background: #fee2e2; color: #ef4444; }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #94a3b8;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        @media (max-width: 768px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
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
            <h5 class="mt-2"><i class="bi bi-graph-up me-1"></i>Analytics</h5>
        </div>
        <div class="sidebar-menu">
            <a href="index.php"><i class="bi bi-grid-1x2-fill"></i> Manajemen Ujian</a>
            <a href="tambah_soal.php"><i class="bi bi-question-circle-fill"></i> Bank Soal</a>
            <a href="rekap_nilai.php"><i class="bi bi-bar-chart-fill"></i> Rekap Nilai</a>
            <a href="analytics.php" class="active"><i class="bi bi-graph-up"></i> Analytics</a>
            <a href="monitor_ujian.php"><i class="bi bi-display"></i> Monitor Ujian</a>
            <a href="profil_sekolah.php"><i class="bi bi-building"></i> Profil Sekolah</a>
            <?php if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'super_admin'): ?>
            <a href="manage_users.php"><i class="bi bi-people-fill"></i> Kelola Admin</a>
            <?php endif; ?>
            <a href="logout.php" class="text-warning mt-3"><i class="bi bi-box-arrow-right"></i> Logout (<?= htmlspecialchars($_SESSION['admin_username']) ?>)</a>
        </div>
    </div>
    
    <div class="main-content">
        <div class="header">
            <h1><i class="bi bi-bar-chart-line me-2"></i>Analytics Dashboard</h1>
            <div class="header-actions">
                <a href="index.php"><i class="bi bi-house"></i> Home</a>
                <a href="rekap_nilai.php"><i class="bi bi-table"></i> Rekap Nilai</a>
                <a href="monitor_ujian.php"><i class="bi bi-display"></i> Monitor</a>
            </div>
        </div>
        
        <div class="filter-section">
            <form method="GET">
                <label for="ujian" style="font-weight: 600;">Pilih Ujian:</label>
                <select name="ujian" id="ujian" onchange="this.form.submit()">
                    <option value="0">-- Pilih Ujian --</option>
                    <?php while ($ujian = $ujian_list->fetch_assoc()): ?>
                    <option value="<?= $ujian['id'] ?>" <?= $selected_ujian == $ujian['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($ujian['judul_ujian']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
                <label for="kkm" style="font-weight: 600; margin-left: 1rem;">KKM (Kriteria Ketuntasan Minimal):</label>
                <input type="number" name="kkm" id="kkm" value="<?= $kkm ?>" min="0" max="100" style="padding: 0.6rem 1rem; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem; width: 80px;">
                <button type="submit" class="btn btn-primary btn-sm" style="margin-left: 0.5rem;">Terapkan</button>
            </form>
        </div>
        
        <?php if ($selected_ujian > 0): ?>
        
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                <div class="stat-value"><?= $analytics['total_peserta'] ?></div>
                <div class="stat-label">Total Peserta</div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
                <div class="stat-value"><?= $analytics['avg_original'] ?></div>
                <div class="stat-label">Rata-rata Skor Asli</div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-icon"><i class="bi bi-graph-down"></i></div>
                <div class="stat-value"><?= $analytics['avg_score'] ?></div>
                <div class="stat-label">Rata-rata Skor Akhir</div>
            </div>
            
            <div class="stat-card danger">
                <div class="stat-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
                <div class="stat-value"><?= $analytics['needs_remedi'] ?></div>
                <div class="stat-label">Butuh Remedi (KKM <?= $kkm ?>)</div>
            </div>
        </div>
        
        <div class="charts-grid">
            <div class="chart-card">
                <h3><i class="bi bi-pie-chart-fill me-2"></i>Distribusi Grade</h3>
                <div class="chart-container">
                    <canvas id="gradeChart"></canvas>
                </div>
            </div>
            
            <div class="chart-card">
                <h3><i class="bi bi-bar-chart-fill me-2"></i>Distribusi Skor</h3>
                <div class="chart-container">
                    <canvas id="scoreRangeChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="charts-grid">
            <div class="chart-card">
                <h3><i class="bi bi-shield-exclamation me-2"></i>Pelanggaran per Jam</h3>
                <div class="chart-container">
                    <canvas id="violationsChart"></canvas>
                </div>
            </div>
            
            <div class="chart-card">
                <h3><i class="bi bi-trophy-fill me-2"></i>Top 5 Skor Tertinggi</h3>
                <div class="chart-container">
                    <canvas id="topScorersChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="table-section">
            <h3><i class="bi bi-clock-history me-2"></i>Recent Submissions</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>NIS</th>
                            <th>Skor</th>
                            <th>Waktu Submit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($analytics['recent_submissions'] as $sub): ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($sub['nama']) ?></td>
                            <td><?= htmlspecialchars($sub['nis']) ?></td>
                            <td>
                                <span class="badge badge-<?= $sub['total_skor'] >= 70 ? 'success' : ($sub['total_skor'] >= 55 ? 'warning' : 'danger') ?>">
                                    <?= $sub['total_skor'] ?>
                                </span>
                            </td>
                            <td class="text-muted"><?= date('d/m/Y H:i', strtotime($sub['waktu_submit'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="table-section">
            <h3><i class="bi bi-exclamation-circle me-2"></i>Butuh Remedi (Skor < <?= $kkm ?>)</h3>
            <?php if ($analytics['needs_remedi'] > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>NIS</th>
                                <th>Kelas</th>
                                <th>Skor</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($analytics['needs_remedi_list'] as $siswa): ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($siswa['nama']) ?></td>
                                <td><?= htmlspecialchars($siswa['nis']) ?></td>
                                <td><?= htmlspecialchars($siswa['kelas']) ?></td>
                                <td>
                                    <span class="badge badge-danger"><?= $siswa['total_skor'] ?></span>
                                </td>
                                <td>
                                    <?php if (in_array($siswa['nis'], $analytics['remedi_given'])): ?>
                                        <span class="badge badge-success"><i class="bi bi-check-circle"></i> Sudah</span>
                                    <?php else: ?>
                                        <form method="POST" action="rekap_nilai.php?ujian=<?= $selected_ujian ?>" style="display:inline;">
                                            <input type="hidden" name="id_hasil" value="<?= $siswa['id'] ?>">
                                            <input type="hidden" name="id_ujian" value="<?= $selected_ujian ?>">
                                            <button type="submit" name="give_remedi" class="btn btn-sm btn-success">
                                                <i class="bi bi-arrow-repeat"></i> Beri Remedi
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-emoji-smile"></i>
                    <p>Semua siswa sudah mencapai skor minimal!</p>
                </div>
            <?php endif; ?>
        </div>
        
        <?php else: ?>
        <div class="empty-state">
            <i class="bi bi-bar-chart"></i>
            <h3>Pilih Ujian untuk Melihat Analytics</h3>
            <p>Silakan pilih ujian dari dropdown di atas untuk melihat data analytics.</p>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if ($selected_ujian > 0): ?>
    <script>
        const gradeCtx = document.getElementById('gradeChart').getContext('2d');
        new Chart(gradeCtx, {
            type: 'doughnut',
            data: {
                labels: ['A (85-100)', 'B (70-84)', 'C (55-69)', 'D (40-54)', 'E (0-39)'],
                datasets: [{
                    data: [
                        <?= $analytics['grade_distribution']['A'] ?>,
                        <?= $analytics['grade_distribution']['B'] ?>,
                        <?= $analytics['grade_distribution']['C'] ?>,
                        <?= $analytics['grade_distribution']['D'] ?>,
                        <?= $analytics['grade_distribution']['E'] ?>
                    ],
                    backgroundColor: [
                        '#10b981',
                        '#3b82f6',
                        '#f59e0b',
                        '#f97316',
                        '#ef4444'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        
        const scoreRangeCtx = document.getElementById('scoreRangeChart').getContext('2d');
        new Chart(scoreRangeCtx, {
            type: 'bar',
            data: {
                labels: ['0-20', '21-40', '41-60', '61-80', '81-100'],
                datasets: [{
                    label: 'Jumlah Siswa',
                    data: [
                        <?= $analytics['score_ranges']['0-20'] ?>,
                        <?= $analytics['score_ranges']['21-40'] ?>,
                        <?= $analytics['score_ranges']['41-60'] ?>,
                        <?= $analytics['score_ranges']['61-80'] ?>,
                        <?= $analytics['score_ranges']['81-100'] ?>
                    ],
                    backgroundColor: 'rgba(102, 126, 234, 0.8)',
                    borderColor: 'rgba(102, 126, 234, 1)',
                    borderWidth: 1,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
        
        const violationsCtx = document.getElementById('violationsChart').getContext('2d');
        new Chart(violationsCtx, {
            type: 'line',
            data: {
                labels: Array.from({length: 24}, (_, i) => i + ':00'),
                datasets: [{
                    label: 'Jumlah Pelanggaran',
                    data: [<?= implode(', ', $analytics['violations_by_hour']) ?>],
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
        
        const topScorersCtx = document.getElementById('topScorersChart').getContext('2d');
        new Chart(topScorersCtx, {
            type: 'bar',
            data: {
                labels: [<?php foreach($analytics['top_scorers'] as $s) echo "'" . htmlspecialchars($s['nama']) . "',"; ?>],
                datasets: [{
                    label: 'Skor',
                    data: [<?php foreach($analytics['top_scorers'] as $s) echo $s['total_skor'] . ","; ?>],
                    backgroundColor: [
                        '#FFD700',
                        '#C0C0C0',
                        '#CD7F32',
                        '#3b82f6',
                        '#10b981'
                    ],
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
