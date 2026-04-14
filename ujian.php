<?php
// ujian.php - Halaman Ujian Siswa (Tampilan Baru)

require_once 'config/database.php';
require_once 'config/init_sekolah.php';

$sekolah = getKonfigurasiSekolah($conn);

$message = '';
$message_type = '';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID ujian tidak valid");
}

$id_ujian = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT * FROM ujian WHERE id = ?");
$stmt->bind_param("i", $id_ujian);
$stmt->execute();
$result = $stmt->get_result();
$ujian = $result->fetch_assoc();
$stmt->close();

if (!$ujian) {
    die("Ujian tidak ditemukan");
}

if ($ujian['status'] !== 'aktif') {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Ujian Ditutup</title>
        <link href="vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="vendor/bootstrap-icons/bootstrap-icons.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <style>
            * { font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
            body { background: linear-gradient(135deg, #ff6b6b 0%, #ffa500 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
            .card { border: none; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card p-5 text-center">
                        <i class="bi bi-x-circle-fill text-danger" style="font-size: 5rem;"></i>
                        <h2 class="mt-4 fw-bold">Maaf, Ujian Ditutup</h2>
                        <p class="text-muted"><?= htmlspecialchars($ujian['judul_ujian']) ?></p>
                        <p class="text-muted">Silakan hubungi guru untuk informasi lebih lanjut.</p>
                        <a href="index.php" class="btn btn-secondary mt-3">
                            <i class="bi bi-arrow-left me-2"></i>Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$stmt = $conn->prepare("SELECT * FROM soal WHERE id_ujian = ?");
$stmt->bind_param("i", $id_ujian);
$stmt->execute();
$result = $stmt->get_result();
$soal_list = [];
while ($row = $result->fetch_assoc()) {
    $soal_list[] = $row;
}
$stmt->close();

if (isset($ujian['acak_soal']) && $ujian['acak_soal'] === 'ya') {
    shuffle($soal_list);
}

$soal_per_halaman = 1;

if (count($soal_list) === 0) {
    die("Belum ada soal. Hubungi guru.");
}

$soal_json = json_encode($soal_list, JSON_HEX_TAG | JSON_HEX_APOS);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_ujian'])) {
    $nis = trim($_POST['nis']);
    $nama = trim($_POST['nama']);
    $kelas = trim($_POST['kelas']);
    
    if (empty($nis) || empty($nama) || empty($kelas)) {
        $message = "Mohon lengkapi data identitas!";
        $message_type = 'danger';
    } else {
        $total_skor = 0;
        $detail_jawaban = [];
        
        foreach ($soal_list as $soal) {
            $jawaban = isset($_POST['jawaban_' . $soal['id']]) ? $_POST['jawaban_' . $soal['id']] : '';
            $is_correct = ($jawaban === $soal['kunci_jawaban']);
            
            if ($is_correct) {
                $total_skor += $soal['poin'];
            }
            
            $detail_jawaban[] = [
                'soal_id' => $soal['id'],
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
        $submit_success = false;
        
        try {
            $conn->begin_transaction();
            
            $lock_name = "ujian_submit_{$id_ujian}_{$nis}";
            $lock_result = $conn->query("SELECT GET_LOCK('$lock_name', 10) AS lock_result");
            $lock_row = $lock_result->fetch_assoc();
            
            if (!$lock_row || $lock_row['lock_result'] != 1) {
                throw new Exception("Gagal mendapatkan lock. Silakan coba lagi.");
            }
            
            $check = $conn->prepare("SELECT id FROM hasil_ujian WHERE id_ujian = ? AND nis = ? LIMIT 1");
            $check->bind_param("is", $id_ujian, $nis);
            $check->execute();
            $check_result = $check->get_result();
            
            if ($check_result->num_rows > 0) {
                $conn->query("DO RELEASE_LOCK('$lock_name')");
                $conn->rollback();
                $message = "Anda sudah submit ujian ini!";
                $message_type = 'warning';
            } else {
                $check->close();
                
                if ($detail_jawaban_json) {
                    $stmt = $conn->prepare("INSERT INTO hasil_ujian (id_ujian, nis, nama, kelas, total_skor, detail_jawaban, submitted_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->bind_param("isssis", $id_ujian, $nis, $nama, $kelas, $total_skor, $detail_jawaban_json);
                } else {
                    $stmt = $conn->prepare("INSERT INTO hasil_ujian (id_ujian, nis, nama, kelas, total_skor, submitted_at) VALUES (?, ?, ?, ?, ?, NOW())");
                    $stmt->bind_param("isssi", $id_ujian, $nis, $nama, $kelas, $total_skor);
                }
                
                if (!$stmt->execute()) {
                    throw new Exception("Gagal menyimpan hasil ujian.");
                }
                
                $conn->query("DO RELEASE_LOCK('$lock_name')");
                $conn->commit();
                
                $submit_success = true;
            }
        } catch (Exception $e) {
            $conn->query("DO RELEASE_LOCK('$lock_name')");
            $conn->rollback();
            $message = $e->getMessage();
            $message_type = 'danger';
        }
        
        if ($submit_success) {
            ?>
            <!DOCTYPE html>
            <html lang="id">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Ujian Selesai</title>
                <link href="vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
                <link rel="stylesheet" href="vendor/bootstrap-icons/bootstrap-icons.min.css">
                <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
                <style>
                    * { font-family: 'Poppins', sans-serif; }
                    body { 
                        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); 
                        min-height: 100vh; 
                        display: flex; 
                        align-items: center; 
                        justify-content: center;
                        overflow: hidden;
                    }
                    
                    body::before {
                        content: '';
                        position: absolute;
                        width: 200%;
                        height: 200%;
                        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 50%);
                        animation: pulse 4s ease-in-out infinite;
                    }
                    
                    @keyframes pulse {
                        0%, 100% { transform: scale(1); opacity: 0.5; }
                        50% { transform: scale(1.1); opacity: 0.3; }
                    }
                    
                    .card { 
                        border: none; 
                        border-radius: 24px; 
                        box-shadow: 0 25px 80px rgba(0,0,0,0.25);
                        position: relative;
                        overflow: hidden;
                        animation: slideUp 0.6s ease-out;
                    }
                    
                    @keyframes slideUp {
                        from { opacity: 0; transform: translateY(30px); }
                        to { opacity: 1; transform: translateY(0); }
                    }
                    
                    .success-icon {
                        width: 120px;
                        height: 120px;
                        border-radius: 50%;
                        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        margin: 0 auto;
                        animation: scaleIn 0.5s ease-out 0.3s both;
                        box-shadow: 0 10px 40px rgba(17, 153, 142, 0.4);
                    }
                    
                    @keyframes scaleIn {
                        from { transform: scale(0); }
                        to { transform: scale(1); }
                    }
                    
                    .success-icon i {
                        font-size: 4rem;
                        color: white;
                        animation: checkBounce 0.5s ease-out 0.6s both;
                    }
                    
                    @keyframes checkBounce {
                        from { transform: scale(0) rotate(-45deg); }
                        50% { transform: scale(1.2) rotate(0deg); }
                        to { transform: scale(1) rotate(0deg); }
                    }
                    
                    .skor-box { 
                        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); 
                        -webkit-background-clip: text; 
                        -webkit-text-fill-color: transparent; 
                        font-size: 5rem; 
                        font-weight: 700; 
                        animation: countUp 1s ease-out 0.8s both;
                    }
                    
                    @keyframes countUp {
                        from { opacity: 0; transform: translateY(20px); }
                        to { opacity: 1; transform: translateY(0); }
                    }
                    
                    .info-card {
                        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                        border-radius: 16px;
                        padding: 20px;
                        animation: slideUp 0.6s ease-out 0.5s both;
                    }
                    
                    .btn-home {
                        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
                        border: none;
                        padding: 15px 40px;
                        border-radius: 30px;
                        font-weight: 600;
                        font-size: 1.1rem;
                        transition: all 0.3s ease;
                        box-shadow: 0 10px 30px rgba(17, 153, 142, 0.3);
                        animation: slideUp 0.6s ease-out 1s both;
                    }
                    
                    .btn-home:hover {
                        transform: translateY(-3px);
                        box-shadow: 0 15px 40px rgba(17, 153, 142, 0.4);
                    }
                    
                    .confetti {
                        position: absolute;
                        width: 10px;
                        height: 10px;
                        border-radius: 50%;
                        animation: fall 3s ease-in-out infinite;
                    }
                    
                    @keyframes fall {
                        0% { transform: translateY(-100vh) rotate(0deg); opacity: 1; }
                        100% { transform: translateY(100vh) rotate(720deg); opacity: 0; }
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-md-6">
                            <div class="card p-5 text-center">
                                <!-- Confetti -->
                                <div class="confetti" style="left: 10%; background: #ff6b6b; animation-delay: 0s;"></div>
                                <div class="confetti" style="left: 30%; background: #ffd93d; animation-delay: 0.5s;"></div>
                                <div class="confetti" style="left: 50%; background: #6bcb77; animation-delay: 1s;"></div>
                                <div class="confetti" style="left: 70%; background: #4d96ff; animation-delay: 1.5s;"></div>
                                <div class="confetti" style="left: 90%; background: #ff6b6b; animation-delay: 2s;"></div>
                                
                                <div class="success-icon mb-4">
                                    <i class="bi bi-check-lg"></i>
                                </div>
                                
                                <h2 class="fw-bold mb-2" style="animation: slideUp 0.6s ease-out 0.4s both;">Selamat!</h2>
                                <p class="text-muted mb-4" style="animation: slideUp 0.6s ease-out 0.5s both;">Jawaban Anda telah berhasil disubmit</p>
                                
                                <?php if (!isset($ujian['tampilkan_skor']) || $ujian['tampilkan_skor'] === 'ya'): ?>
                                <div class="my-4" style="animation: slideUp 0.6s ease-out 0.6s both;">
                                    <p class="text-muted mb-2 fw-medium">Total Skor Anda</p>
                                    <div class="skor-box"><?= $total_skor ?></div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="info-card mb-4">
                                    <div class="row">
                                        <div class="col-12">
                                            <p class="mb-2"><strong class="fs-5"><?= htmlspecialchars($nama) ?></strong></p>
                                        </div>
                                        <div class="col-6 text-start">
                                            <p class="mb-0 text-muted small">NIS</p>
                                            <p class="mb-0 fw-semibold"><?= htmlspecialchars($nis) ?></p>
                                        </div>
                                        <div class="col-6 text-end">
                                            <p class="mb-0 text-muted small">Kelas</p>
                                            <p class="mb-0 fw-semibold"><?= htmlspecialchars($kelas) ?></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <a href="index.php" class="btn btn-home text-white">
                                    <i class="bi bi-house-door me-2"></i>Kembali ke Halaman Utama
                                </a>
                                <?php if (isset($ujian['tampilkan_review']) && $ujian['tampilkan_review'] === 'ya'): ?>
                                <a href="review.php?nis=<?= urlencode($nis) ?>&id_ujian=<?= $id_ujian ?>" class="btn btn-outline-primary mt-3">
                                    <i class="bi bi-card-checklist me-2"></i>Lihat Pembahasan
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </body>
            </html>
            <?php
            exit;
        } else {
            $message = "Terjadi kesalahan. Coba lagi.";
            $message_type = 'danger';
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang id>
<head>
    <meta charset UTF-8>
    <meta name viewport content width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no>
    <meta name description content Ujian Online>
    <meta name mobile-web-app-capable content yes>
    <meta name apple-mobile-web-app-capable content yes>
    <meta name apple-mobile-web-app-status-bar-style content default>
<title><?= htmlspecialchars($ujian['judul_ujian']) ?> - Ujian Online</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <link href="vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="vendor/bootstrap-icons/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; -webkit-font-smoothing: antialiased; box-sizing: border-box; }
        body { background: #f0f2f5; margin: 0; padding: 0; }
        img { max-width: 100%; height: auto; }
        
/* Header */
.ujian-header {
    background: linear-gradient(135deg, <?= $sekolah['warna_primer'] ?> 0%, <?= $sekolah['warna_sekunder'] ?> 100%);
    padding: 30px 15px 40px;
    position: relative;
    overflow: hidden;
    margin-bottom: 20px;
}
.ujian-header::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}
.ujian-header .school-logo {
    width: 70px;
    height: 70px;
    background: white;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 25px rgba(0,0,0,0.2);
    margin-bottom: 15px;
}
.ujian-header .school-logo img {
    width: 50px;
    height: 50px;
    object-fit: contain;
}
.ujian-header .school-logo i {
    font-size: 2rem;
    color: #667eea;
}
.ujian-header .school-name {
    color: white;
    font-weight: 700;
    font-size: 1.1rem;
    margin-bottom: 15px;
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.ujian-header .header-content .back-link {
    display: inline-block;
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    font-size: 0.9rem;
    margin-bottom: 12px;
}
.ujian-header .header-content .back-link:hover {
    color: white;
}
.ujian-header .exam-title {
    color: white;
    font-weight: 700;
    font-size: 1.4rem;
    margin-bottom: 8px;
}
.ujian-header .exam-desc {
    color: rgba(255,255,255,0.8);
    font-size: 0.9rem;
    margin-bottom: 15px;
}
.ujian-header .header-badges {
    display: flex;
    gap: 10px;
    justify-content: center;
    flex-wrap: wrap;
}
.ujian-header .badge-item {
    background: rgba(255,255,255,0.2);
    color: white;
    padding: 8px 14px;
    border-radius: 20px;
    font-size: 0.85rem;
    backdrop-filter: blur(4px);
}
.ujian-header .badge-item.warning {
    background: #ffc107;
    color: #333;
}
        .ujian-header .back-link {
            display: inline-block;
            color: white;
            text-decoration: none;
            font-size: 0.85rem;
            margin-bottom: 10px;
            opacity: 0.9;
        }
        
        /* Container */
        .container { padding: 0 15px 20px; max-width: 600px; margin-top: -15px; position: relative; }
        
        /* Cards */
        .exam-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            padding: 20px;
            margin-bottom: 15px;
        }
        
        .exam-card h5 {
            color: #1a1a2e;
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        /* Form Inputs */
        .exam-card .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 12px 15px;
            font-size: 0.95rem;
            transition: border-color 0.2s;
        }
        .exam-card .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        
        .exam-card label {
            font-weight: 500;
            font-size: 0.9rem;
            color: #444;
            margin-bottom: 6px;
        }
        
        /* Buttons */
        .btn-start {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 14px 30px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            width: 100%;
            margin-top: 10px;
        }
        .btn-start:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102,126,234,0.3);
        }
        
        .btn-code {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            margin-top: 10px;
        }
        
        /* Question Card */
        .soal-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            padding: 20px;
            margin-bottom: 15px;
        }
        .soal-card .soal-number {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
            margin-right: 10px;
        }
        .soal-card .question {
            font-weight: 500;
            font-size: 1rem;
            color: #1a1a2e;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        /* Options */
        .option-label {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            border: 2px solid #e8e8e8;
            border-radius: 12px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .option-label:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }
        .option-label input:checked + .option-letter {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .option-letter {
            background: #f0f0f0;
            color: #555;
            width: 28px;
            height: 28px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.85rem;
            margin-right: 12px;
            transition: all 0.2s;
        }
        .option-content {
            flex: 1;
            color: #333;
            font-size: 0.95rem;
        }
        
        /* Navigation */
        .soal-navigator {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            padding: 15px;
            margin-bottom: 15px;
        }
        .soal-navigator .nav-info {
            text-align: center;
            margin-bottom: 12px;
            color: #666;
            font-size: 0.9rem;
        }
        .soal-navigator .nav-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 12px;
        }
        .soal-navigator .nav-buttons button {
            flex: 1;
            padding: 10px;
            border-radius: 10px;
            font-weight: 500;
            font-size: 0.9rem;
        }
        .soal-navigator .soal-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: center;
        }
        .soal-navigator .soal-grid button {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        /* Progress */
        .progress-indicator {
            position: fixed;
            bottom: 15px;
            right: 15px;
            background: white;
            padding: 10px 15px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 100;
        }
        .progress-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.8rem;
        }
        
        .progress-text {
            font-size: 0.85rem;
            color: #444;
        }
        
        /* Submit Button */
        .btn-submit {
            background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
            color: white;
            border: none;
            padding: 14px 30px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            width: 100%;
        }
        
/* Responsive Desktop */
        @media (min-width: 768px) {
            .container { max-width: 700px; margin: -20px auto 20px; padding: 0 20px; }
            .ujian-header { padding: 40px 20px 50px; margin-bottom: 30px; }
            .ujian-header .school-logo { width: 80px; height: 80px; }
            .ujian-header .school-logo img { width: 60px; height: 60px; }
            .ujian-header .school-name { font-size: 1.3rem; }
            .ujian-header .exam-title { font-size: 1.8rem; }
            .ujian-header .exam-desc { font-size: 1rem; }
            .soal-card, .exam-card { padding: 25px; border-radius: 20px; }
            .option-label { padding: 15px 20px; }
            .btn-start, .btn-submit { width: auto; display: inline-block; }
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="ujian-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-12 text-center">
                    <div class="school-logo">
                        <?php if ($sekolah['logo'] && file_exists('uploads/' . $sekolah['logo'])): ?>
                            <img src="uploads/<?= $sekolah['logo'] ?>" alt="Logo">
                        <?php else: ?>
                            <i class="bi bi-mortarboard-fill"></i>
                        <?php endif; ?>
                    </div>
                    <div class="school-name"><?= htmlspecialchars($sekolah['nama_sekolah']) ?></div>
                    
                    <div class="header-content">
                        <a href="index.php" class="back-link">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                        <h1 class="exam-title"><?= htmlspecialchars($ujian['judul_ujian']) ?></h1>
                        <?php if ($ujian['deskripsi']): ?>
                        <p class="exam-desc"><?= htmlspecialchars($ujian['deskripsi']) ?></p>
                        <?php endif; ?>
                        
                        <div class="header-badges">
                            <span class="badge-item">
                                <i class="bi bi-question-circle"></i> <?= count($soal_list) ?> Soal
                            </span>
                            <?php if (isset($ujian['waktu_tersedia']) && $ujian['waktu_tersedia'] > 0): ?>
                            <span class="badge-item warning">
                                <i class="bi bi-clock"></i> <?= $ujian['waktu_tersedia'] ?> menit
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container pb-5">
        <!-- Exam Code Form (if required) -->
        <?php if (!empty($ujian['kode_ujian'])): ?>
        <div id="examCodeForm" class="exam-card" style="display: none;">
            <h5><i class="bi bi-shield-lock text-primary"></i> Kode Ujian</h5>
            <div class="mb-3">
                <label class="form-label">Masukkan Kode Ujian <span class="text-danger">*</span></label>
                <input type="text" id="kodeUjianInput" class="form-control" placeholder="Masukkan kode rahasia" autocomplete="off">
            </div>
            <button type="button" class="btn-code" onclick="verifyExamCode()">
                <i class="bi bi-check2-circle me-2"></i>Masuk Ujian
            </button>
        </div>
        <?php endif; ?>
        
        <!-- Main Exam Content -->
        <div id="examContent" style="<?= !empty($ujian['kode_ujian']) ? 'display:none;' : '' ?>">
        
        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" id="formUjian">
            <!-- Identitas - Tampil Pertama -->
            <div id="identitySection">
                <div class="exam-card">
                    <h5><i class="bi bi-person-badge text-primary"></i> Identitas Siswa</h5>
                    <div class="mb-3">
                        <label class="form-label">NIS <span class="text-danger">*</span></label>
                        <input type="text" name="nis" id="nisInput" class="form-control" required placeholder="Masukkan NIS">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="nama" id="namaInput" class="form-control" required placeholder="Masukkan nama">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kelas <span class="text-danger">*</span></label>
                        <input type="text" name="kelas" id="kelasInput" class="form-control" required placeholder="Contoh: X IPA 1">
                    </div>
                    <button type="button" class="btn-start" onclick="startWithIdentity()">
                        <i class="bi bi-play-fill me-2"></i>Mulai Ujian
                    </button>
                </div>
            </div>

            <!-- Daftar Soal - Muncul Setelah Identitas -->
            <div id="questionSection" style="display: none;">
            <div id="soalContainer"></div>
            
            <div id="loadingIndicator" style="text-align: center; padding: 20px; color: #666;">
                <div style="border: 3px solid #f3f3f3; border-top: 3px solid #667eea; border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; margin: 0 auto;"></div>
                <p style="margin-top: 10px; font-size: 0.9rem;">Memuat soal...</p>
            </div>
            
            <div id="loadMoreSection" class="text-center mb-4" style="display: none;">
                <button type="button" class="btn btn-outline-primary" onclick="loadMoreSoal()">
                    <i class="bi bi-chevron-down me-2"></i>Lihat Lebih Banyak
                </button>
            </div>

<!-- Navigasi Superior -->
            <div class="soal-navigator">
                <div class="nav-info">
                    <span style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 6px 14px; border-radius: 8px; font-size: 0.85rem;">
                        <i class="bi bi-file-text"></i> Soal <span id="currentPage">1</span> dari <span id="totalPages">1</span>
                    </span>
                    <span id="progressMobile" style="color: #666; font-size: 0.8rem; margin-left: 8px; display: none;">(0/0)</span>
                </div>
                
                <div class="nav-buttons">
                    <button type="button" class="btn btn-outline-secondary" onclick="prevPage()" id="prevBtn" disabled>
                        <i class="bi bi-chevron-left"></i> Sebelumnya
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="nextPage()" id="nextBtn">
                        Selanjutnya <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
                
                <div class="soal-grid" id="soalNumbersContainer">
                </div>
            </div>

            <!-- Submit -->
            <div style="text-align: center; margin-bottom: 20px;" id="submitSection">
                <button type="button" class="btn-submit" onclick="submitFinal()">
                    <i class="bi bi-send-fill me-2"></i>Kirim Jawaban
                </button>
            </div>
                    <div class="soal-progress-mobile d-md-none">
                        <span class="text-muted small" id="progressMobile">0/0 dijawab</span>
                    </div>
                </div>
                
                <!-- Navigasi Prev/Next dengan Label -->
                <div class="nav-buttons mb-3">
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary flex-fill" onclick="prevPage()" id="prevBtn" disabled>
                            <i class="bi bi-chevron-left me-1"></i>Sebelumnya
                        </button>
                        <button type="button" class="btn btn-outline-secondary flex-fill" onclick="nextPage()" id="nextBtn">
                           Selanjutnya<i class="bi bi-chevron-right ms-1"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Grid Nomor Soal -->
                <div class="soal-grid">
                    <div class="d-flex flex-wrap gap-2 justify-content-center" id="soalNumbersContainer">
                    </div>
                </div>
                
                <!-- Legend -->
                <div class="soal-legend mt-3">
                    <div class="d-flex justify-content-center gap-3 small text-muted">
                        <span><span class="badge bg-secondary bg-opacity-25 text-secondary">○</span> Belum</span>
                        <span><span class="badge bg-primary">●</span> Aktif</span>
                        <span><span class="badge bg-success">✓</span> Dijawab</span>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="text-center mb-5" id="submitSection">
                <button type="button" class="btn btn-primary btn-submit text-white" onclick="submitFinal()">
                    <i class="bi bi-send-fill me-2"></i>Kirim Jawaban
                </button>
            </div>
            </div><!-- End questionSection -->
        </form>
        </div><!-- End examContent -->

        <!-- Progress Indicator -->
        <div class="progress-indicator" id="progressIndicator">
            <div class="progress-circle">
                <span id="answeredCount">0</span>/<span id="totalSoal"><?= count($soal_list) ?></span>
            </div>
            <div class="progress-text">
                <div class="fw-bold">Soal Terjawab</div>
                <small class="text-muted" id="progressPercent">0%</small>
                <small class="d-block" id="autoSaveStatus"></small>
            </div>
        </div>
        
        <footer class="text-center text-muted py-4">
            <small>&copy; <?= date('Y') ?> Sistem Ujian Online - by natedekaka</small>
        </footer>
    </div>

    <script src="vendor/bootstrap/bootstrap.bundle.min.js" defer></script>
    <script>
        const API_URL = 'api/submit_jawaban.php';
        const ID_UJIAN = <?= $id_ujian ?>;
        const HAS_EXAM_CODE = <?= !empty($ujian['kode_ujian']) ? 'true' : 'false' ?>;
        const SOAL_DATA = <?= $soal_json ?>;
        const ACAK_OPSI = <?= isset($ujian['acak_opsi']) && $ujian['acak_opsi'] === 'ya' ? 'true' : 'false' ?>;
        const SOAL_PER_HALAMAN = <?= $soal_per_halaman ?>;
        
        let currentPage = 1;
        let displayedSoal = [];
        let optionsCache = {};
        let answers = {};
        let identitySaved = false;
        let csrfToken = '';
        
        function init() {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
                return;
            }
            
            if (!SOAL_DATA || SOAL_DATA.length === 0) {
                console.error('No questions loaded!');
                return;
            }
            
            const totalSoal = SOAL_DATA.length;
            const totalPages = Math.ceil(totalSoal / SOAL_PER_HALAMAN);
            document.getElementById('totalPages').textContent = totalPages;
            
            document.getElementById('answeredCount').textContent = '0';
            document.getElementById('totalSoal').textContent = totalSoal;
            
            const identitySection = document.getElementById('identitySection');
            const questionSection = document.getElementById('questionSection');
            const examCodeForm = document.getElementById('examCodeForm');
            const examContent = document.getElementById('examContent');
            
            if (HAS_EXAM_CODE && examCodeForm) {
                examCodeForm.style.display = 'block';
                examContent.style.display = 'none';
                identitySection.style.display = 'none';
                questionSection.style.display = 'none';
            } else {
                examContent.style.display = 'block';
                identitySection.style.display = 'block';
                questionSection.style.display = 'none';
            }
        }
        
        function startWithIdentity() {
            const nis = document.getElementById('nisInput').value.trim();
            const nama = document.getElementById('namaInput').value.trim();
            const kelas = document.getElementById('kelasInput').value.trim();
            
            if (!nis || !nama || !kelas) {
                alert('Mohon lengkapi identitas terlebih dahulu!');
                return;
            }
            
            document.getElementById('identitySection').style.display = 'none';
            document.getElementById('questionSection').style.display = 'block';
            document.getElementById('loadingIndicator').style.display = 'none';
            
            loadPage(1);
            
            // Initialize in background without waiting
            setTimeout(initExamFeatures, 500);
        }
        
        function initExamFeatures() {
            fetch(API_URL, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'generate_token'})
            })
            .then(r => r.json())
            .then(data => {
                if (data.csrf_token) csrfToken = data.csrf_token;
            })
            .catch(e => console.log('Token init skipped'));
            
            const savedNis = localStorage.getItem('exam_nis');
            if (savedNis) {
                checkCompletion(savedNis);
            }
}
        
        function renderSoal(soalList) {
            if (!soalList || soalList.length === 0) {
                return '<div class=alert alert-warning>Tidak ada soal untuk ditampilkan</div>';
            }
            
            let html = '';
            let no = (currentPage - 1) * SOAL_PER_HALAMAN + 1;
            
            for (let i = 0; i < soalList.length; i++) {
                const soal = soalList[i];
                let options = {a:{t:soal.opsi_a,i:soal.gambar_a},b:{t:soal.opsi_b,i:soal.gambar_b},c:{t:soal.opsi_c,i:soal.gambar_c},d:{t:soal.opsi_d,i:soal.gambar_d},e:{t:soal.opsi_e,i:soal.gambar_e}};
                
                if (ACAK_OPSI) {
                    if (!optionsCache[soal.id]) {
                        const keys = Object.keys(options);
                        for (let j = keys.length - 1; j > 0; j--) {
                            const k = Math.floor(Math.random() * (j + 1));
                            [keys[j], keys[k]] = [keys[k], keys[j]];
                        }
                        const shuffled = {};
                        keys.forEach(k => shuffled[k] = options[k]);
                        optionsCache[soal.id] = shuffled;
                    }
                    options = optionsCache[soal.id];
                }
                
                const soalId = soal.id;
                const answered = answers[soalId] || '';
                
                html += '<div class=soal-card><div class=d-flex align-items-start mb-3><span class=soal-number>' + no + '</span><div class=flex-grow-1><p class=mb-2 fw-medium>' + soal.pertanyaan.replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>') + '</p>';
                if (soal.gambar_pertanyaan) html += '<img src=uploads/' + soal.gambar_pertanyaan + ' class=soal-img alt=Gambar alt=true>';
                html += '<small class=text-muted d-block mt-2>Poin: ' + soal.poin + '</small></div></div><div class=ms-5>';
                
                for (const key in options) {
                    const opt = options[key];
                    const checked = answered === key ? ' checked' : '';
                    html += '<label class=option-label><input type=radio name=jawaban_' + soalId + ' value=' + key + checked + ' class=d-none onchange=updateProgress()><span class=option-letter>' + key.toUpperCase() + '</span><span class=option-content>';
                    if (opt.i) html += '<img src=uploads/' + opt.i + ' class=opsi-img alt=Opsi ' + key + ' alt=true>';
                    else html += opt.t.replace(/</g,'&lt;').replace(/>/g,'&gt;');
                    html += '</span></label>';
                }
                
                html += '</div></div>';
                no++;
            }
            
return html;
        }
        
        function loadPage(page) {
            if (!SOAL_DATA || SOAL_DATA.length === 0) {
                document.getElementById('soalContainer').innerHTML = '<div class="alert alert-warning">Tidak ada soal</div>';
                return;
            }
            
            const totalPages = Math.ceil(SOAL_DATA.length / SOAL_PER_HALAMAN);
            if (page < 1 || page > totalPages) {
                console.error('Invalid page:', page);
                return;
            }
            
            const start = (page - 1) * SOAL_PER_HALAMAN;
            const end = Math.min(start + SOAL_PER_HALAMAN, SOAL_DATA.length);
            const soalPage = SOAL_DATA.slice(start, end);
            
            const html = renderSoal(soalPage);
            document.getElementById('soalContainer').innerHTML = html;
            document.getElementById('currentPage').textContent = page;
            
            document.getElementById('prevBtn').disabled = page === 1;
            document.getElementById('nextBtn').disabled = page === Math.ceil(SOAL_DATA.length / SOAL_PER_HALAMAN);
            
            updateSoalNumbers();
            updateProgress();
        }
        
        function updateSoalNumbers() {
            const container = document.getElementById('soalNumbersContainer');
            if (!container) return;
            
            let html = '';
            const total = SOAL_DATA.length;
            
            for (let i = 1; i <= total; i++) {
                const soalId = SOAL_DATA[i-1].id;
                const isAnswered = answers[soalId] ? true : false;
                const isCurrent = i === currentPage;
                
                let btnHtml = '';
                if (isCurrent) {
                    btnHtml = `<button type="button" class="btn btn-sm btn-primary fw-bold" onclick="goToPage(${i})" title="Soal ${i} (aktif)">${i}</button>`;
                } else if (isAnswered) {
                    btnHtml = `<button type="button" class="btn btn-sm btn-success" onclick="goToPage(${i})" title="Soal ${i} (dijawab)"><i class="bi bi-check"></i>${i}</button>`;
                } else {
                    btnHtml = `<button type="button" class="btn btn-sm btn-outline-secondary" onclick="goToPage(${i})" title="Soal ${i} (belum dijawab)">${i}</button>`;
                }
                
                html += btnHtml;
            }
            
            container.innerHTML = html;
            
            // Update mobile progress
            const answeredCount = Object.keys(answers).length;
            const progressMobile = document.getElementById('progressMobile');
            if (progressMobile) {
                progressMobile.textContent = `${answeredCount}/${total} dijawab`;
            }
        }
        
        function goToPage(page) {
            currentPage = page;
            loadPage(currentPage);
        }
        
        function nextPage() {
            const totalPages = Math.ceil(SOAL_DATA.length / SOAL_PER_HALAMAN);
            if (currentPage < totalPages) {
                currentPage++;
                loadPage(currentPage);
            }
        }
        
        function prevPage() {
            if (currentPage > 1) {
                currentPage--;
                loadPage(currentPage);
            }
        }
        
        async function startExam() {
            try {
                const tokenRes = await fetch(API_URL, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'generate_token'})
                });
                const tokenData = await tokenRes.json();
                
                if (tokenData.success && tokenData.csrf_token) {
                    csrfToken = tokenData.csrf_token;
                }
                <?php if (!empty($ujian['enable_browser_lock']) && $ujian['enable_browser_lock'] === 'ya'): ?>
                initBrowserLock();
                <?php endif; ?>
                <?php if (!empty($ujian['enable_device_check']) && $ujian['enable_device_check'] === 'ya'): ?>
                checkDeviceFingerprint();
                <?php endif; ?>
                const savedNis = localStorage.getItem('exam_nis');
                if (savedNis) {
                    checkCompletion(savedNis);
                }
            } catch (e) {
                console.error('Failed to initialize:', e);
            }
        }
        
        async function verifyExamCode() {
            const kode = document.getElementById('kodeUjianInput').value.trim();
            if (!kode) {
                alert('Masukkan kode ujian!');
                return;
            }
            
            try {
                console.log('Verifying code:', kode);
                const res = await fetch(API_URL, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'check_exam_code',
                        id_ujian: ID_UJIAN,
                        kode_ujian: kode
                    })
                });
                
                if (!res.ok) {
                    throw new Error('Network error: ' + res.status);
                }
                
                const data = await res.json();
                console.log('Verify response:', data);
                
                if (data.valid === true) {
                    document.getElementById('examCodeForm').style.display = 'none';
                    document.getElementById('examContent').style.display = 'block';
                    document.getElementById('identitySection').style.display = 'block';
                    document.getElementById('questionSection').style.display = 'none';
                } else {
                    alert(data.message || 'Kode ujian salah!');


                }
            } catch (e) {
                console.error('Verify error:', e);
                alert('Gagal memverifikasi kode. Silakan coba lagi. Error: ' + e.message);
            }
        }
        
        function generateDeviceFingerprint() {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            ctx.textBaseline = 'top';
            ctx.font = '14px Arial';
            ctx.fillText('Fingerprint', 2, 2);
            const canvasHash = canvas.toDataURL();
            
            const fingerprint = [
                navigator.userAgent,
                navigator.language,
                screen.width + 'x' + screen.height,
                new Date().getTimezoneOffset(),
                canvasHash.substring(0, 20)
            ].join('|');
            
            let hash = 0;
            for (let i = 0; i < fingerprint.length; i++) {
                const char = fingerprint.charCodeAt(i);
                hash = ((hash << 5) - hash) + char;
                hash = hash & hash;
            }
            return 'fp_' + Math.abs(hash).toString(16);
        }
        
        function checkDeviceFingerprint() {
            const fp = generateDeviceFingerprint();
            const savedFp = localStorage.getItem('exam_fp');
            
            if (savedFp && savedFp !== fp) {
                alert('Peringatan: Anda terdeteksi更换设备. Ini mungkin tercatat.');
            }
            localStorage.setItem('exam_fp', fp);
        }
        
        function initBrowserLock() {
            let violationCount = 0;
            const maxViolations = <?= (int)($ujian['max_violations'] ?? 3) ?>;
            
            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    violationCount++;
                    logViolation('tab_switch', 'Siswa meninggalkan tab ujian');
                    
                    if (violationCount >= maxViolations) {
                        alert('Anda terlalu banyak切换标签. Jawaban akan disubmit otomatis!');
                        submitFinal();
                    } else {
                        const remaining = maxViolations - violationCount;
                        alert(`Peringatan: Anda meninggalkan tab ujian!\nPelanggaran: ${violationCount}/${maxViolations}\nSisa: ${remaining}x sebelum submit otomatis`);
                    }
                }
            });
            
            document.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                logViolation('right_click', 'Siswa mencoba klik kanan');
                return false;
            });
            
            document.addEventListener('copy', function(e) {
                e.preventDefault();
                logViolation('copy_paste', 'Siswa mencoba menyalin teks');
                return false;
            });
            
            document.addEventListener('cut', function(e) {
                e.preventDefault();
                logViolation('cut', 'Siswa mencoba memotong teks');
                return false;
            });
        }
        
        async function logViolation(jenis, detail) {
            try {
                await fetch(API_URL, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'log_violation',
                        id_ujian: ID_UJIAN,
                        nis: document.querySelector('input[name="nis"]')?.value || localStorage.getItem('exam_nis') || '',
                        jenis: jenis,
                        detail: detail,
                        device_fingerprint: localStorage.getItem('exam_fp') || '',
                        ip_address: '',
                        csrf_token: csrfToken,
                        expected_token: csrfToken
                    })
                });
            } catch (e) {
                console.error('Failed to log violation:', e);
            }
        }
        
        async function verifyExamCodeAndShowForm() {
            const kodeInput = document.getElementById('kodeUjianInput');
            if (!kodeInput) return;
            
            const kode = kodeInput.value.trim();
            if (!kode) {
                alert('Masukkan kode ujian!');
                return;
            }
            
            try {
                const res = await fetch(API_URL, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'check_exam_code',
                        id_ujian: ID_UJIAN,
                        kode_ujian: kode,
                        csrf_token: csrfToken,
                        expected_token: csrfToken
                    })
                });
                const data = await res.json();
                
                if (data.valid) {
                    document.getElementById('examCodeForm').style.display = 'none';
                    document.getElementById('examContent').style.display = 'block';
                    document.getElementById('identitySection').style.display = 'block';
                    document.getElementById('questionSection').style.display = 'none';
                } else {
                    alert(data.message || 'Kode ujian salah!');
                }
            } catch (e) {
                alert('Gagal memverifikasi kode. Silakan coba lagi.');
            }
        }
        
        async function getNewToken() {
            const tokenRes = await fetch(API_URL, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'generate_token'})
            });
            const tokenData = await tokenRes.json();
            return tokenData.csrf_token || '';
        }
        
        async function checkCompletion(nis) {
            try {
                const res = await fetch(API_URL, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'check_completion',
                        id_ujian: ID_UJIAN,
                        nis: nis,
                        csrf_token: csrfToken,
                        expected_token: csrfToken
                    })
                });
                const data = await res.json();
                
                if (data.completed) {
                    showAlreadyCompleted(data.result);
                } else if (data.has_saved && data.saved_data) {
                    loadSavedAnswers(data.saved_data);
                }
            } catch (e) {
                console.error('Check completion failed:', e);
            }
        }
        
        function showAlreadyCompleted(result) {
            document.getElementById('formUjian').innerHTML = `
                <div class="alert alert-warning text-center py-5">
                    <i class="bi bi-exclamation-triangle-fill" style="font-size: 3rem;"></i>
                    <h3 class="mt-3">Anda sudah mengerjakan ujian ini</h3>
                    <p class="mb-2">Skor Anda: <strong>${result.skor}</strong></p>
                    <p class="text-muted">Tanggal: ${new Date(result.tanggal).toLocaleString('id-ID')}</p>
                    <a href="index.php" class="btn btn-primary mt-3">Kembali ke Halaman Utama</a>
                </div>
            `;
            document.getElementById('progressIndicator').style.display = 'none';
        }
        
        function loadSavedAnswers(savedData) {
            if (savedData.nama) {
                document.querySelector('input[name="nama"]').value = savedData.nama;
            }
            if (savedData.kelas) {
                document.querySelector('input[name="kelas"]').value = savedData.kelas;
            }
            if (savedData.answers) {
                answers = savedData.answers;
                Object.entries(answers).forEach(([soalId, jawaban]) => {
                    const radio = document.querySelector(`input[name="jawaban_${soalId}"][value="${jawaban}"]`);
                    if (radio) {
                        radio.checked = true;
                    }
                });
                updateProgress();
                document.getElementById('autoSaveStatus').innerHTML = 
                    '<i class="bi bi-cloud-check-fill text-info"></i> Jawaban dimuat dari penyimpanan';
            }
            identitySaved = true;
        }
        
        function saveIdentity() {
            const nis = document.querySelector('input[name="nis"]').value.trim();
            const nama = document.querySelector('input[name="nama"]').value.trim();
            const kelas = document.querySelector('input[name="kelas"]').value.trim();
            
            if (!nis || !nama || !kelas) {
                return false;
            }
            
            if (!identitySaved && csrfToken) {
                fetch(API_URL, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'auto_save',
                        id_ujian: ID_UJIAN,
                        nis: nis,
                        nama: nama,
                        kelas: kelas,
                        answers: {},
                        csrf_token: csrfToken,
                        expected_token: csrfToken
                    })
                }).then(r => r.json()).then(data => {
                    if (data.success) {
                        identitySaved = true;
                        localStorage.setItem('exam_nis', nis);
                        checkCompletion(nis);
                    }
                }).catch(console.error);
            }
            return true;
        }
        
        function autoSaveAnswer(soalId, answer) {
            const nis = document.querySelector('input[name="nis"]').value.trim();
            if (!nis || !identitySaved) return;
            if (!csrfToken) return; // Skip if no CSRF token yet
            
            answers[soalId] = answer;
            
            clearTimeout(window.autoSaveTimer);
            window.autoSaveTimer = setTimeout(() => {
                fetch(API_URL, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'auto_save',
                        id_ujian: ID_UJIAN,
                        nis: nis,
                        answers: answers,
                        csrf_token: csrfToken,
                        expected_token: csrfToken
                    })
                }).then(r => r.json()).then(data => {
                    if (data.success) {
                        document.getElementById('autoSaveStatus').innerHTML = 
                            '<i class="bi bi-check-circle-fill text-success"></i> Tersimpan';
                        setTimeout(() => {
                            document.getElementById('autoSaveStatus').innerHTML = '';
                        }, 2000);
                    } else if (data.message.includes('sudah menyelesaikan')) {
                        alert(data.message);
                        location.reload();
                    }
                }).catch(console.error);
            }, 2000);
        }
        
        function submitFinal() {
            const kodeValidInput = document.getElementById('kodeValid');
            if (kodeValidInput && kodeValidInput.value !== '1') {
                // Auto verify sebelum submit
                verifyExamCodeForSubmit();
                return;
            }
            
            doSubmitFinal();
        }
        
        async function verifyExamCodeForSubmit() {
            const kodeInput = document.getElementById('kodeUjianInput');
            if (!kodeInput) {
                doSubmitFinal();
                return;
            }
            
            const kode = kodeInput.value.trim();
            if (!kode) {
                alert('Masukkan kode ujian!');
                return;
            }
            
            try {
                const res = await fetch(API_URL, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'check_exam_code',
                        id_ujian: ID_UJIAN,
                        kode_ujian: kode,
                        csrf_token: csrfToken,
                        expected_token: csrfToken
                    })
                });
                const data = await res.json();
                
                if (data.valid) {
                    document.getElementById('kodeValid').value = '1';
                    doSubmitFinal();
                } else {
                    alert(data.message || 'Kode ujian salah!');
                }
            } catch (e) {
                console.error('Failed to verify code:', e);
                alert('Gagal memverifikasi kode. Silakan coba lagi.');
            }
        }
        
        function doSubmitFinal() {
            const nis = document.querySelector('input[name="nis"]').value.trim();
            const nama = document.querySelector('input[name="nama"]').value.trim();
            const kelas = document.querySelector('input[name="kelas"]').value.trim();
            
            if (!nis || !nama || !kelas) {
                alert('Mohon lengkapi identitas terlebih dahulu!');
                return;
            }
            
            const totalSoal = SOAL_DATA.length;
            const answeredCount = Object.keys(answers).length;
            
            if (answeredCount < totalSoal) {
                alert('Mohon jawab semua soal terlebih dahulu!\nSoal terjawab: ' + answeredCount + '/' + totalSoal);
                return;
            }
            
            console.log('Submitting answers:', answers);
            console.log('Total answered:', answeredCount);
            
            const btn = document.querySelector('.btn-submit');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Mengirim...';
            btn.disabled = true;
            
            console.log('Submitting with csrfToken:', csrfToken);
            console.log('Answers:', answers);
            
            fetch(API_URL, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: 'submit_final',
                    id_ujian: ID_UJIAN,
                    nis: nis,
                    nama: nama,
                    kelas: kelas,
                    answers: answers,
                    csrf_token: csrfToken,
                    expected_token: csrfToken
                })
            })
            .then(r => {
                console.log('Submit response status:', r.status);
                return r.json();
            })
            .then(data => {
                if (data.success) {
                    localStorage.removeItem('exam_nis');
                    showSuccessPage(data.skor, nis, nama, kelas);
                } else {
                    alert('Error: ' + data.message);
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            })
            .catch(err => {
                console.error(err);
                alert('Gagal mengirim jawaban. Silakan coba lagi.');
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        }
        
        function showSuccessPage(skor, nis, nama, kelas) {
            localStorage.setItem('exam_nis', nis);
            localStorage.setItem('exam_nama', nama);
            localStorage.setItem('exam_kelas', kelas);
            
            document.body.innerHTML = `
                <!DOCTYPE html>
                <html lang="id">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Ujian Selesai</title>
                    <link href="vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
                    <link rel="stylesheet" href="vendor/bootstrap-icons/bootstrap-icons.min.css">
                    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
                    <style>
                        * { font-family: 'Poppins', sans-serif; }
                        body { 
                            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); 
                            min-height: 100vh; 
                            display: flex; 
                            align-items: center; 
                            justify-content: center;
                        }
                        .card { 
                            border: none; 
                            border-radius: 24px; 
                            box-shadow: 0 25px 80px rgba(0,0,0,0.25);
                            animation: slideUp 0.6s ease-out;
                        }
                        @keyframes slideUp {
                            from { opacity: 0; transform: translateY(30px); }
                            to { opacity: 1; transform: translateY(0); }
                        }
                        .success-icon {
                            width: 120px;
                            height: 120px;
                            border-radius: 50%;
                            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            margin: 0 auto;
                            animation: scaleIn 0.5s ease-out 0.3s both;
                            box-shadow: 0 10px 40px rgba(17, 153, 142, 0.4);
                        }
                        @keyframes scaleIn {
                            from { transform: scale(0); }
                            to { transform: scale(1); }
                        }
                        .success-icon i {
                            font-size: 4rem;
                            color: white;
                        }
                        .skor-box { 
                            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); 
                            -webkit-background-clip: text; 
                            -webkit-text-fill-color: transparent; 
                            font-size: 5rem; 
                            font-weight: 700; 
                        }
                        .info-card {
                            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                            border-radius: 16px;
                            padding: 20px;
                        }
                        .btn-home {
                            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
                            border: none;
                            padding: 15px 40px;
                            border-radius: 30px;
                            font-weight: 600;
                            color: white;
                        }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="row justify-content-center">
                            <div class="col-md-6">
                                <div class="card p-5 text-center">
                                    <div class="success-icon mb-4">
                                        <i class="bi bi-check-lg"></i>
                                    </div>
                                    <h2 class="fw-bold mb-2">Selamat!</h2>
                                    <p class="text-muted mb-4">Jawaban Anda telah berhasil disubmit</p>
                                    <div class="my-4">
                                        <p class="text-muted mb-2 fw-medium">Total Skor Anda</p>
                                        <div class="skor-box">${skor}</div>
                                    </div>
                                    <div class="info-card mb-4">
                                        <div class="row">
                                            <div class="col-12">
                                                <p class="mb-2"><strong class="fs-5">${nama}</strong></p>
                                            </div>
                                            <div class="col-6 text-start">
                                                <p class="mb-0 text-muted small">NIS</p>
                                                <p class="mb-0 fw-semibold">${nis}</p>
                                            </div>
                                            <div class="col-6 text-end">
                                                <p class="mb-0 text-muted small">Kelas</p>
                                                <p class="mb-0 fw-semibold">${kelas}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <a href="index.php" class="btn btn-home">
                                        <i class="bi bi-house-door me-2"></i>Kembali ke Halaman Utama
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </body>
                </html>
            `;
        }
        
        // Progress indicator
        const radioButtons = document.querySelectorAll('input[type="radio"]');
        const answeredCount = document.getElementById('answeredCount');
        function updateProgress() {
            const radioButtons = document.querySelectorAll('input[type="radio"]');
            const answered = new Set();
            radioButtons.forEach(radio => {
                if (radio.checked) {
                    answered.add(radio.name);
                    const soalId = radio.name.replace('jawaban_', '');
                    answers[soalId] = radio.value;
                    console.log('Saved answer:', soalId, '=', radio.value);
                    autoSaveAnswer(soalId, radio.value);
                }
            });
            
            const total = SOAL_DATA.length;
            const count = answered.size;
            const percent = Math.round((count / total) * 100);
            
            document.getElementById('answeredCount').textContent = count;
            document.getElementById('progressPercent').textContent = percent + '%';
            
            const circle = document.querySelector('.progress-circle');
            if (percent === 100) {
                circle.style.background = 'linear-gradient(135deg, #10b981 0%, #34d399 100%)';
            } else if (percent >= 50) {
                circle.style.background = 'linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%)';
            } else {
                circle.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
            }
        }
        
        // Save identity on input change
        ['nis', 'nama', 'kelas'].forEach(field => {
            const input = document.querySelector(`input[name="${field}"]`);
            if (input) {
                input.addEventListener('change', saveIdentity);
                input.addEventListener('blur', saveIdentity);
            }
        });
        
        // Hide progress indicator on mobile
        if (window.innerWidth < 768) {
            document.getElementById('progressIndicator').style.display = 'none';
        }
        
        // Timer functionality
        <?php if (isset($ujian['waktu_tersedia']) && $ujian['waktu_tersedia'] > 0): ?>
        let waktuTersedia = <?= (int)$ujian['waktu_tersedia'] ?> * 60;
        const timerDisplay = document.getElementById('timerDisplay');
        const timerBadge = document.getElementById('timerBadge');
        
        function updateTimer() {
            const menit = Math.floor(waktuTersedia / 60);
            const detik = waktuTersedia % 60;
            timerDisplay.textContent = menit + ':' + (detik < 10 ? '0' : '') + detik;
            
            if (waktuTersedia <= 300) {
                timerBadge.className = 'badge bg-danger fs-6 px-3 py-2';
            } else if (waktuTersedia <= 600) {
                timerBadge.className = 'badge bg-warning fs-6 px-3 py-2';
            }
            
            if (waktuTersedia <= 0) {
                alert('Waktu ujian telah habis! Jawaban akan otomatis dikirim.');
                doSubmitFinal();
                return;
            }
            waktuTersedia--;
        }
        
        updateTimer();
        setInterval(updateTimer, 1000);
        <?php endif; ?>
        
        init();
    </script>
</body>
</html>
