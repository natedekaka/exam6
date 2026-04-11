<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

function generateTemplateDocx() {
    $templateContent = "PERTANYAAN: Apa ibukota Indonesia?
OPSI_A: Jakarta
OPSI_B: Bandung
OPSI_C: Surabaya
OPSI_D: Yogyakarta
OPSI_E: Medan
KUNCI: A
POIN: 10

PERTANYAAN: [Tulis pertanyaan Anda di sini]
OPSI_A: [Tulis opsi A]
OPSI_B: [Tulis opsi B]
OPSI_C: [Tulis opsi C]
OPSI_D: [Tulis opsi D]
OPSI_E: [Tulis opsi E]
KUNCI: [A/B/C/D/E]
POIN: [Angka, contoh: 10]
GAMBAR_PERTANYAAN: [nama_file.jpg] (opsional)

===============================================
FORMAT IMPORT SOAL DOCX
===============================================

Cara Penggunaan:
1. Setiap soal terdiri dari 8-9 baris dengan format:
   - PERTANYAAN: [isi pertanyaan]
   - OPSI_A: [isi opsi A]
   - OPSI_B: [isi opsi B]
   - OPSI_C: [isi opsi C]
   - OPSI_D: [isi opsi D]
   - OPSI_E: [isi opsi E]
   - KUNCI: [A/B/C/D/E] (huruf besar)
   - POIN: [angka]
   - GAMBAR_PERTANYAAN: [nama_file.jpg] (opsional)
   - GAMBAR_A: [nama_file.jpg] (opsional)
   - GAMBAR_B: [nama_file.jpg] (opsional)
   - GAMBAR_C: [nama_file.jpg] (opsional)
   - GAMBAR_D: [nama_file.jpg] (opsional)
   - GAMBAR_E: [nama_file.jpg] (opsional)

2. Setiap soal dipisahkan oleh 1 baris kosong

3. Untuk gambar:
   - Simpan file DOCX dan folder 'images' bersamaan
   - Dalam DOCX, tulis nama file gambarnya saja
   - Contoh: GAMBAR_PERTANYAAN: soal1.jpg

4. Contoh soal lengkap dengan gambar:
   PERTANYAAN: Perhatikan gambar berikut!
   OPSI_A: 3
   OPSI_B: 4
   OPSI_C: 5
   OPSI_D: 6
   OPSI_E: 7
   KUNCI: B
   POIN: 10
   GAMBAR_PERTANYAAN: grafik_fungsi.jpg

5. Format gambar yang didukung: jpg, jpeg, png, gif, webp
   Ukuran maksimal: 2MB per gambar";

    $zip = new ZipArchive();
    $tempFile = tempnam(sys_get_temp_dir(), 'template');
    
    if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
</Types>';
        
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>';
        
        $escapedContent = htmlspecialchars($templateContent, ENT_XML1, 'UTF-8');
        
        $documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p>
            <w:pPr>
                <w:jc w:val="center"/>
                <w:spacing w:after="200" w:line="360" w:lineRule="auto"/>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:b w:val="true"/>
                    <w:sz w:val="28"/>
                </w:rPr>
                <w:t>TEMPLATE IMPORT SOAL</w:t>
            </w:r>
        </w:p>
        <w:p><w:r><w:rPr><w:sz w:val="20"/></w:rPr><w:t></w:t></w:r></w:p>
        <w:p>
            <w:pPr>
                <w:spacing w:after="120"/>
            </w:pPr>
            <w:r>
                <w:rPr>
                    <w:sz w:val="20"/>
                </w:rPr>
                <w:t>' . $escapedContent . '</w:t>
            </w:r>
        </w:p>
    </w:body>
</w:document>';
        
        $docRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
</Relationships>';
        
        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('word/document.xml', $documentXml);
        $zip->addFromString('word/_rels/document.xml.rels', $docRels);
        $zip->close();
        
        return $tempFile;
    }
    
    return false;
}

if (class_exists('ZipArchive')) {
    $templateFile = generateTemplateDocx();
    
    if ($templateFile) {
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="template_import_soal.docx"');
        header('Content-Length: ' . filesize($templateFile));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        readfile($templateFile);
        unlink($templateFile);
        exit;
    }
} else {
    $templateContent = "TEMPLATE IMPORT SOAL
========================

PERTANYAAN: Apa ibukota Indonesia?
OPSI_A: Jakarta
OPSI_B: Bandung
OPSI_C: Surabaya
OPSI_D: Yogyakarta
OPSI_E: Medan
KUNCI: A
POIN: 10

PERTANYAAN: [Tulis pertanyaan Anda di sini]
OPSI_A: [Tulis opsi A]
OPSI_B: [Tulis opsi B]
OPSI_C: [Tulis opsi C]
OPSI_D: [Tulis opsi D]
OPSI_E: [Tulis opsi E]
KUNCI: [A/B/C/D/E]
POIN: [Angka, contoh: 10]
GAMBAR_PERTANYAAN: [nama_file.jpg] (opsional)

===============================================
FORMAT IMPORT SOAL
===============================================

Cara Penggunaan:
1. Setiap soal terdiri dari 8-9 baris dengan format:
   - PERTANYAAN: [isi pertanyaan]
   - OPSI_A: [isi opsi A]
   - OPSI_B: [isi opsi B]
   - OPSI_C: [isi opsi C]
   - OPSI_D: [isi opsi D]
   - OPSI_E: [isi opsi E]
   - KUNCI: [A/B/C/D/E] (huruf besar)
   - POIN: [angka]
   - GAMBAR_PERTANYAAN: [nama_file.jpg] (opsional)
   - GAMBAR_A: [nama_file.jpg] (opsional)
   - GAMBAR_B: [nama_file.jpg] (opsional)
   - GAMBAR_C: [nama_file.jpg] (opsional)
   - GAMBAR_D: [nama_file.jpg] (opsional)
   - GAMBAR_E: [nama_file.jpg] (opsional)

2. Setiap soal dipisahkan oleh 1 baris kosong

3. Setelah填写, simpan file ini sebagai .docx (bisa dibuka dengan Microsoft Word)
   Atau hubungi admin untuk install PHP Zip extension

4. Contoh soal lengkap dengan gambar:
   PERTANYAAN: Perhatikan gambar berikut!
   OPSI_A: 3
   OPSI_B: 4
   OPSI_C: 5
   OPSI_D: 6
   OPSI_E: 7
   KUNCI: B
   POIN: 10
   GAMBAR_PERTANYAAN: grafik_fungsi.jpg

5. Format gambar yang didukung: jpg, jpeg, png, gif, webp
   Ukuran maksimal: 2MB per gambar";

    header('Content-Type: application/vnd.ms-word');
    header('Content-Disposition: attachment; filename="template_import_soal.doc"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo $templateContent;
    exit;
}
