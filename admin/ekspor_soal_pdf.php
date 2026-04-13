<?php
// admin/ekspor_soal_pdf.php - Export PDF via HTML Print

require_once '../config/database.php';
require_once '../config/init_sekolah.php';

session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['ujian']) || empty($_GET['ujian'])) {
    die("Parameter tidak valid");
}

$id_ujian = (int)$_GET['ujian'];

$stmt = $conn->prepare("SELECT judul_ujian FROM ujian WHERE id = ?");
$stmt->bind_param("i", $id_ujian);
$stmt->execute();
$result = $stmt->get_result();
$ujian = $result->fetch_assoc();
$stmt->close();

if (!$ujian) {
    die("Ujian tidak ditemukan");
}

$stmt = $conn->prepare("SELECT * FROM soal WHERE id_ujian = ? ORDER BY id");
$stmt->bind_param("i", $id_ujian);
$stmt->execute();
$result = $stmt->get_result();
$soal_list = [];
while ($row = $result->fetch_assoc()) {
    $soal_list[] = $row;
}
$stmt->close();

$sekolah = getKonfigurasiSekolah($conn);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Soal: <?= htmlspecialchars($ujian['judul_ujian']) ?></title>
    <link href="../vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 40px; 
            line-height: 1.6; 
            background: #fff;
        }
        .header { 
            text-align: center; 
            margin-bottom: 30px; 
            border-bottom: 2px solid #000; 
            padding-bottom: 20px; 
        }
        .header h1 { 
            margin: 0; 
            font-size: 18px; 
        }
        .header h2 { 
            margin: 5px 0 0; 
            font-size: 14px; 
            font-weight: normal; 
        }
        .soal-item { 
            margin-bottom: 25px; 
            page-break-inside: avoid;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 8px;
            background: #f9f9f9;
        }
        .nomor { 
            font-weight: bold; 
            font-size: 16px;
        }
        .pertanyaan { 
            margin: 10px 0 15px; 
            font-size: 14px;
        }
        .opsi { 
            margin: 5px 0 5px 15px; 
            font-size: 13px;
        }
        .jawaban-benar { 
            color: #16a34a; 
            font-weight: bold; 
            margin-top: 10px;
            padding: 8px 12px;
            background: #dcfce7;
            border-radius: 6px;
            display: inline-block;
        }
        .gambar-soal {
            max-width: 200px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        @media print {
            body { margin: 20px; }
            .no-print { display: none !important; }
            .soal-item { 
                page-break-inside: avoid; 
                border: 1px solid #000;
                background: #fff;
            }
            .jawaban-benar {
                background: #e0e0e0;
                color: #000;
            }
        }
        .btn-print {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            background: #4f46e5;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 1000;
        }
        .btn-print:hover {
            background: #4338ca;
        }
    </style>
</head>
<body>
    <button class="btn-print no-print" onclick="window.print()">
        <i class="bi bi-printer"></i> 🖨️ Print / Save PDF
    </button>

    <div class="header">
        <h1><?= htmlspecialchars($sekolah['nama_sekolah']) ?></h1>
        <h2><?= htmlspecialchars($ujian['judul_ujian']) ?></h2>
        <p style="font-size: 12px; color: #666; margin-top: 5px;">Total <?= count($soal_list) ?> soal</p>
    </div>

    <div class="soal-list">
        <?php 
        $no = 1; 
        $current_kategori = '';
        foreach ($soal_list as $soal): 
        ?>
        <?php if (!empty($soal['kategori']) && $soal['kategori'] !== $current_kategori): ?>
            <?php $current_kategori = $soal['kategori']; ?>
            <div style="background: #e0e7ff; padding: 10px 15px; margin: 20px 0 15px; border-radius: 8px; border-left: 4px solid #4f46e5;">
                <strong style="color: #4f46e5;"><i class="bi bi-folder"></i> <?= htmlspecialchars($soal['kategori']) ?></strong>
            </div>
        <?php endif; ?>
        <div class="soal-item">
            <div class="nomor">Soal No. <?= $no ?>
                <?php if (($soal['timer_soal'] ?? 0) > 0): ?>
                    <span style="font-size: 11px; font-weight: normal; color: #666; background: #f0f9ff; padding: 2px 8px; border-radius: 4px; margin-left: 10px;">
                        ⏱️ <?= $soal['timer_soal'] ?> detik
                    </span>
                <?php endif; ?>
            </div>
            <div class="pertanyaan"><?= nl2br(htmlspecialchars($soal['pertanyaan'])) ?></div>
            <?php if ($soal['gambar_pertanyaan'] && file_exists('../uploads/' . $soal['gambar_pertanyaan'])): ?>
                <div class="gambar-soal-container">
                    <img src="../uploads/<?= $soal['gambar_pertanyaan'] ?>" class="gambar-soal" alt="Gambar soal">
                </div>
            <?php endif; ?>
            
            <div class="opsi-list">
                <div class="opsi" style="<?= $soal['kunci_jawaban'] === 'a' ? 'background: #dcfce7; padding: 5px 10px; border-radius: 4px;' : '' ?>">
                    <strong>A.</strong> <?= htmlspecialchars($soal['opsi_a']) ?>
                    <?php if ($soal['gambar_a'] && file_exists('../uploads/' . $soal['gambar_a'])): ?>
                        <img src="../uploads/<?= $soal['gambar_a'] ?>" style="max-width: 80px; vertical-align: middle; margin-left: 5px;">
                    <?php endif; ?>
                    <?= $soal['kunci_jawaban'] === 'a' ? ' ✓' : '' ?>
                </div>
                <div class="opsi" style="<?= $soal['kunci_jawaban'] === 'b' ? 'background: #dcfce7; padding: 5px 10px; border-radius: 4px;' : '' ?>">
                    <strong>B.</strong> <?= htmlspecialchars($soal['opsi_b']) ?>
                    <?php if ($soal['gambar_b'] && file_exists('../uploads/' . $soal['gambar_b'])): ?>
                        <img src="../uploads/<?= $soal['gambar_b'] ?>" style="max-width: 80px; vertical-align: middle; margin-left: 5px;">
                    <?php endif; ?>
                    <?= $soal['kunci_jawaban'] === 'b' ? ' ✓' : '' ?>
                </div>
                <div class="opsi" style="<?= $soal['kunci_jawaban'] === 'c' ? 'background: #dcfce7; padding: 5px 10px; border-radius: 4px;' : '' ?>">
                    <strong>C.</strong> <?= htmlspecialchars($soal['opsi_c']) ?>
                    <?php if ($soal['gambar_c'] && file_exists('../uploads/' . $soal['gambar_c'])): ?>
                        <img src="../uploads/<?= $soal['gambar_c'] ?>" style="max-width: 80px; vertical-align: middle; margin-left: 5px;">
                    <?php endif; ?>
                    <?= $soal['kunci_jawaban'] === 'c' ? ' ✓' : '' ?>
                </div>
                <div class="opsi" style="<?= $soal['kunci_jawaban'] === 'd' ? 'background: #dcfce7; padding: 5px 10px; border-radius: 4px;' : '' ?>">
                    <strong>D.</strong> <?= htmlspecialchars($soal['opsi_d']) ?>
                    <?php if ($soal['gambar_d'] && file_exists('../uploads/' . $soal['gambar_d'])): ?>
                        <img src="../uploads/<?= $soal['gambar_d'] ?>" style="max-width: 80px; vertical-align: middle; margin-left: 5px;">
                    <?php endif; ?>
                    <?= $soal['kunci_jawaban'] === 'd' ? ' ✓' : '' ?>
                </div>
                <div class="opsi" style="<?= $soal['kunci_jawaban'] === 'e' ? 'background: #dcfce7; padding: 5px 10px; border-radius: 4px;' : '' ?>">
                    <strong>E.</strong> <?= htmlspecialchars($soal['opsi_e']) ?>
                    <?php if ($soal['gambar_e'] && file_exists('../uploads/' . $soal['gambar_e'])): ?>
                        <img src="../uploads/<?= $soal['gambar_e'] ?>" style="max-width: 80px; vertical-align: middle; margin-left: 5px;">
                    <?php endif; ?>
                    <?= $soal['kunci_jawaban'] === 'e' ? ' ✓' : '' ?>
                </div>
            </div>
            
            <div class="jawaban-benar">
                ✓ Jawaban Benar: <strong><?= strtoupper($soal['kunci_jawaban']) ?></strong> 
                <span style="margin-left: 15px;">📊 Poin: <?= $soal['poin'] ?></span>
            </div>
        </div>
        <?php $no++; endforeach; ?>
    </div>

    <div class="footer">
        <p>Dicetak pada: <?= date('d F Y H:i:s') ?></p>
        <p><?= htmlspecialchars($sekolah['nama_sekolah']) ?></p>
    </div>
</body>
</html>