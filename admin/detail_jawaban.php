<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';
require_once '../config/init_sekolah.php';

$sekolah = getKonfigurasiSekolah($conn);

if (!isset($_GET['id_hasil']) || empty($_GET['id_hasil'])) {
    die("Parameter tidak valid");
}

$id_hasil = (int)$_GET['id_hasil'];

$stmt = $conn->prepare("SELECT * FROM hasil_ujian WHERE id = ?");
$stmt->bind_param("i", $id_hasil);
$stmt->execute();
$hasil = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$hasil) {
    die("Data tidak ditemukan");
}

$detail_jawaban = json_decode($hasil['detail_jawaban'], true);
if (!$detail_jawaban) {
    $detail_jawaban = [];
}

$total_benar = 0;
foreach ($detail_jawaban as $jw) {
    if (isset($jw['is_correct']) && $jw['is_correct']) {
        $total_benar++;
    }
}
$total_soal = count($detail_jawaban);
$persentase = $total_soal > 0 ? round(($total_benar / $total_soal) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Jawaban - <?= htmlspecialchars($hasil['nama']) ?> - <?= htmlspecialchars($sekolah['nama_sekolah']) ?></title>
    <link href="../vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../vendor/bootstrap-icons/bootstrap-icons.min.css">
    <link href="../vendor/fonts/poppins.css" rel="stylesheet">
    <style>
        * { font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        body { background: #f8f9fa; }
        
        .review-header {
            background: linear-gradient(135deg, <?= $sekolah['warna_primer'] ?> 0%, <?= $sekolah['warna_sekunder'] ?> 100%);
            padding: 30px 0;
            margin-bottom: 30px;
        }
        
        .review-card { 
            background: white; 
            border-radius: 16px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.08); 
            margin-bottom: 20px;
            padding: 25px;
            border-left: 5px solid #e9ecef;
        }
        
        .review-card.benar {
            border-left-color: #10b981;
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        }
        
        .review-card.salah {
            border-left-color: #ef4444;
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
        }
        
        .soal-number {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
            margin-right: 15px;
        }
        
        .badge-benar {
            background: #10b981;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        
        .badge-salah {
            background: #ef4444;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        
        .jawaban-box {
            background: rgba(255,255,255,0.7);
            padding: 12px 15px;
            border-radius: 10px;
            margin-top: 10px;
            font-size: 0.9rem;
        }
        
        .jawaban-benar {
            border: 2px solid #10b981;
            background: rgba(16, 185, 129, 0.1);
        }
        
        .jawaban-salah {
            border: 2px solid #ef4444;
            background: rgba(239, 68, 68, 0.1);
        }
        
        .skor-summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 16px;
            padding: 25px;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .skor-number {
            font-size: 3rem;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="review-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <a href="rekap_nilai.php?ujian=<?= $hasil['id_ujian'] ?>" class="text-white text-decoration-none mb-2 d-inline-block">
                        <i class="bi bi-arrow-left"></i> Kembali ke Rekap Nilai
                    </a>
                    <h2 class="text-white fw-bold mb-1">Detail Jawaban</h2>
                    <p class="text-white-50 mb-0"><?= htmlspecialchars($hasil['nama']) ?> (NIS: <?= htmlspecialchars($hasil['nis']) ?>)</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="rekap_nilai.php" class="btn btn-light">
                        <i class="bi bi-clock-history me-2"></i>Lihat Semua Rekap
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container pb-5">
        <div class="skor-summary">
            <div class="row">
                <div class="col-4">
                    <div class="skor-number"><?= $hasil['total_skor'] ?></div>
                    <div class="opacity-75">Total Skor</div>
                </div>
                <div class="col-4">
                    <div class="skor-number"><?= $total_benar ?>/<?= $total_soal ?></div>
                    <div class="opacity-75">Benar</div>
                </div>
                <div class="col-4">
                    <div class="skor-number"><?= $persentase ?>%</div>
                    <div class="opacity-75">Nilai</div>
                </div>
            </div>
        </div>

        <h5 class="fw-bold mb-4">
            <i class="bi bi-card-checklist me-2"></i>Pembahasan Jawaban
        </h5>

        <div class="mb-3">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-secondary active" onclick="filterAnswers('all')">Semua</button>
                <button type="button" class="btn btn-outline-success" onclick="filterAnswers('benar')">Benar Saja</button>
                <button type="button" class="btn btn-outline-danger" onclick="filterAnswers('salah')">Salah Saja</button>
            </div>
        </div>

        <?php if (empty($detail_jawaban)): ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle me-2"></i>Detail jawaban tidak tersedia untuk hasil ujian ini.
            </div>
        <?php else: ?>
            <?php $no = 1; foreach ($detail_jawaban as $jw): ?>
            <div class="review-card <?= isset($jw['is_correct']) && $jw['is_correct'] ? 'benar' : 'salah' ?>">
                <div class="d-flex align-items-start mb-3">
                    <span class="soal-number"><?= $no ?></span>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start">
                            <p class="mb-2 fw-medium fs-5"><?= nl2br(htmlspecialchars($jw['pertanyaan'] ?? '')) ?></p>
                            <span class="<?= isset($jw['is_correct']) && $jw['is_correct'] ? 'badge-benar' : 'badge-salah' ?>">
                                <i class="bi bi-<?= isset($jw['is_correct']) && $jw['is_correct'] ? 'check-circle' : 'x-circle' ?> me-1"></i>
                                <?= isset($jw['is_correct']) && $jw['is_correct'] ? 'BENAR' : 'SALAH' ?>
                            </span>
                        </div>
                        <small class="text-muted">
                            <i class="bi bi-star me-1"></i>Poin: <?= $jw['poin_diperoleh'] ?? 0 ?>/<?= $jw['poin'] ?? 0 ?>
                        </small>
                    </div>
                </div>
                
                <div class="ms-5">
                    <?php 
                    $options = [
                        'a' => $jw['opsi_a'] ?? '',
                        'b' => $jw['opsi_b'] ?? '',
                        'c' => $jw['opsi_c'] ?? '',
                        'd' => $jw['opsi_d'] ?? '',
                        'e' => $jw['opsi_e'] ?? ''
                    ];
                    
                    foreach ($options as $key => $opt): 
                        if (empty($opt)) continue;
                        
                        $is_jawaban_siswa = (isset($jw['jawaban_siswa']) && $jw['jawaban_siswa'] === $key);
                        $is_kunci = (isset($jw['kunci_jawaban']) && $jw['kunci_jawaban'] === $key);
                    ?>
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge bg-secondary me-2" style="width: 30px;"><?= strtoupper($key) ?></span>
                        <span><?= htmlspecialchars($opt) ?></span>
                        <?php if ($is_kunci): ?>
                            <span class="badge bg-success ms-2"><i class="bi bi-check"></i> Jawaban Benar</span>
                        <?php elseif ($is_jawaban_siswa): ?>
                            <span class="badge bg-danger ms-2"><i class="bi bi-x"></i> Jawaban Anda</span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php $no++; endforeach; ?>
        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="rekap_nilai.php?ujian=<?= $hasil['id_ujian'] ?>" class="btn btn-primary btn-lg">
                <i class="bi bi-house-door me-2"></i>Kembali ke Rekap Nilai
            </a>
        </div>
        
        <footer class="text-center text-muted py-4 mt-5">
            <small>&copy; <?= date('Y') ?> Sistem Ujian Online - by natedekaka</small>
        </footer>
    </div>

    <script src="../vendor/bootstrap/bootstrap.bundle.min.js" defer></script>
    <script>
        function filterAnswers(type) {
            const cards = document.querySelectorAll('.review-card');
            cards.forEach(card => {
                if (type === 'all') {
                    card.style.display = '';
                } else if (type === 'benar') {
                    card.style.display = card.classList.contains('benar') ? '' : 'none';
                } else if (type === 'salah') {
                    card.style.display = card.classList.contains('salah') ? '' : 'none';
                }
            });
            
            // Update button states
            document.querySelectorAll('.btn-group .btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
