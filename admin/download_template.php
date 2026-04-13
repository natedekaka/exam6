<?php
// admin/download_template.php - Download CSV Template

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$headers = ['pertanyaan', 'opsi_a', 'opsi_b', 'opsi_c', 'opsi_d', 'opsi_e', 'kunci_jawaban', 'poin', 'kategori', 'timer_soal'];
$sample_data = [
    ['Apa itu jaringan komputer?', 'Sekumpulan komputer yang saling terhubung', 'Sekumpulan kabel', 'Sekumpulan website', 'Sekumpulan server', 'Sekumpulan orang', 'a', '10', 'Jaringan Dasar', '60'],
    ['Apa itu internet?', 'Jaringan global', 'Jaringan lokal', 'Jaringan sekolah', 'Jaringan rumah', 'Jaringan kantor', 'c', '10', 'Internet', '45'],
    ['Apa fungsi router?', 'Menghubungkan jaringan', 'Menyimpan data', 'Mengedit dokumen', 'Mencetak dokumen', 'Menghitung angka', 'a', '15', 'Jaringan Dasar', '30'],
];

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="template_import_soal.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, $headers, ',', '"');

foreach ($sample_data as $row) {
    fputcsv($output, $row, ',', '"');
}

fclose($output);
exit;