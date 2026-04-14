<?php
// index.php - Halaman Depan (List Ujian)

require_once 'config/database.php';
require_once 'config/init_sekolah.php';

$sekolah = getKonfigurasiSekolah($conn);
$ujian_list = $conn->query("SELECT * FROM ujian WHERE status = 'aktif' ORDER BY tgl_dibuat DESC");

$ujian_ids = [];
$ujian_array = [];
while ($row = $ujian_list->fetch_assoc()) {
    $ujian_ids[] = $row['id'];
    $ujian_array[$row['id']] = $row;
}

$soal_counts = [];
$waktu_counts = [];
if (!empty($ujian_ids)) {
    $ids_placeholder = implode(',', array_fill(0, count($ujian_ids), '?'));
    
    $stmt = $conn->prepare("SELECT id_ujian, COUNT(*) as total FROM soal WHERE id_ujian IN ($ids_placeholder) GROUP BY id_ujian");
    $stmt->bind_param(str_repeat('i', count($ujian_ids)), ...$ujian_ids);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $soal_counts[$row['id_ujian']] = $row['total'];
    }
    $stmt->close();
    
    $result_cols = $conn->query("SHOW COLUMNS FROM ujian LIKE 'waktu_tersedia'");
    if ($result_cols && $result_cols->num_rows > 0) {
        $stmt = $conn->prepare("SELECT id, waktu_tersedia FROM ujian WHERE id IN ($ids_placeholder)");
        $stmt->bind_param(str_repeat('i', count($ujian_ids)), ...$ujian_ids);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $waktu_counts[$row['id']] = $row['waktu_tersedia'] ?? 0;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistem Ujian Online - SMA Negeri 6 Cimahi">
    <title>Sistem Ujian Online</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="vendor/bootstrap-icons/bootstrap-icons.min.css">
    <style>
        * {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .hero-section {
            background: linear-gradient(135deg, <?= $sekolah['warna_primer'] ?> 0%, <?= $sekolah['warna_sekunder'] ?> 100%);
            padding: 60px 0;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        
        .school-logo {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .school-logo img {
            width: 60px;
            height: 60px;
            object-fit: contain;
        }
        
        .school-name {
            font-size: 1.4rem;
            font-weight: 700;
            color: white;
            text-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 5px;
        }
        
        .hero-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            text-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .hero-subtitle {
            color: rgba(255,255,255,0.9);
            font-size: 1.1rem;
        }
        
        .ujian-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            overflow: hidden;
            background: white;
        }
        
        .ujian-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        }
        
        .ujian-card .card-header {
            background: linear-gradient(135deg, <?= $sekolah['warna_primer'] ?> 0%, <?= $sekolah['warna_sekunder'] ?> 100%);
            color: white;
            border: none;
            padding: 20px;
        }
        
        .ujian-card .card-body {
            padding: 25px;
        }
        
        .status-badge {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .btn-ujian {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-ujian:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .info-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
        }
        
        .footer {
            background: #1a1a2e;
            color: white;
            padding: 30px 0;
            margin-top: 60px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-icon {
            font-size: 5rem;
            color: #dee2e6;
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container position-relative">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <div class="school-logo">
                        <?php if ($sekolah['logo'] && file_exists('uploads/' . $sekolah['logo'])): ?>
                            <img src="uploads/<?= $sekolah['logo'] ?>" alt="Logo" width="60" height="60">
                        <?php else: ?>
                            <i class="bi bi-mortarboard-fill" style="font-size: 2.5rem; color: <?= $sekolah['warna_primer'] ?>;"></i>
                        <?php endif; ?>
                    </div>
                    <p class="school-name mb-1"><?= htmlspecialchars($sekolah['nama_sekolah']) ?></p>
                    <h1 class="hero-title">
                        <i class="bi bi-clipboard-check me-2"></i>Sistem Ujian Online
                    </h1>
                    <p class="hero-subtitle">Selamat datang! Silakan pilih ujian yang tersedia di bawah ini untuk memulai.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Ujian List -->
    <section class="py-5">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <h4 class="fw-bold">
                        <i class="bi bi-collection-fill me-2 text-primary"></i>Ujian Tersedia
                    </h4>
                    <p class="text-muted">Klik pada kartu ujian untuk memulai</p>
                </div>
            </div>
            
            <?php if (!empty($ujian_array)): ?>
            <div class="row g-4">
                <?php foreach ($ujian_array as $ujian): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="ujian-card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 fw-bold"><?= htmlspecialchars($ujian['judul_ujian']) ?></h5>
                                <span class="status-badge bg-white bg-opacity-25">
                                    <i class="bi bi-check-circle-fill me-1"></i>Aktif
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if ($ujian['deskripsi']): ?>
                            <p class="text-muted mb-3"><?= htmlspecialchars($ujian['deskripsi']) ?></p>
                            <?php endif; ?>
                            
                            <div class="d-flex align-items-center mb-3 text-muted small">
                                <div class="info-icon me-2">
                                    <i class="bi bi-calendar3"></i>
                                </div>
                                <span><?= date('d M Y', strtotime($ujian['tgl_dibuat'])) ?></span>
                            </div>
                            
                            <div class="d-flex align-items-center mb-3 text-muted small">
                                <div class="info-icon me-2">
                                    <i class="bi bi-question-circle"></i>
                                </div>
                                <span><?= $soal_counts[$ujian['id']] ?? 0 ?> Soal</span>
                            </div>
                            
                            <?php 
                            $waktu = $waktu_counts[$ujian['id']] ?? 0;
                            ?>
                            <?php if ($waktu > 0): ?>
                            <div class="d-flex align-items-center mb-4 text-muted small">
                                <div class="info-icon me-2" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                                    <i class="bi bi-clock"></i>
                                </div>
                                <span><?= $waktu ?> menit</span>
                            </div>
                            <?php endif; ?>
                            
                            <a href="ujian.php?id=<?= $ujian['id'] ?>" class="btn btn-ujian text-white w-100">
                                <i class="bi bi-pencil-square me-2"></i>Mulai Ujian
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-inbox empty-icon"></i>
                <h4 class="mt-3 text-muted">Belum Ada Ujian Tersedia</h4>
                <p class="text-muted">Silakan hubungi guru atau administrator untuk informasi lebih lanjut.</p>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Riwayat Nilai Section -->
    <?php $tampilkan_riwayat = $sekolah['tampilkan_riwayat'] ?? 'ya'; ?>
    <?php if ($tampilkan_riwayat === 'ya'): ?>
    <section class="py-4 bg-light">
        <div class="container">
            <div class="card">
                <div class="card-body text-center py-4">
                    <h5 class="fw-bold mb-3">
                        <i class="bi bi-clock-history me-2 text-primary"></i>Cek Riwayat Nilai
                    </h5>
                    <p class="text-muted mb-3">Masukkan NIS Anda untuk melihat riwayat nilai ujian</p>
                    <form method="GET" action="riwayat.php" class="row justify-content-center g-3">
                        <div class="col-md-4">
                            <input type="text" name="nis" id="nisInput" class="form-control form-control-lg" required placeholder="Masukkan NIS">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-search me-2"></i>Cari
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
    <script>
        // Auto-fill NIS from localStorage if available
        const savedNis = localStorage.getItem('exam_nis');
        if (savedNis && document.getElementById('nisInput')) {
            document.getElementById('nisInput').value = savedNis;
        }
    </script>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="bi bi-mortarboard-fill me-2"></i>Sistem Ujian Online</h5>
                    <p class="text-white-50 mb-0">Platform ujian online untuk memudahkan proses pembelajaran.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-white-50 mb-0">&copy; <?= date('Y') ?> Sistem Ujian Online - by natedekaka</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="vendor/bootstrap/bootstrap.bundle.min.js" defer></script>
</body>
</html>
