# Tutorial Aplikasi Ujian Online (Exam6)

Aplikasi ujian online adalah sistem manajemen ujian berbasis web yang memungkinkan guru membuat dan mengelola ujian secara online. Siswa dapat mengerjakan ujian melalui antarmuka yang menarik dan responsif.

## Daftar Isi

1. [Persiapan & Instalasi](#persiapan--instalasi)
2. [Login Admin](#login-admin)
3. [Manajemen Ujian](#manajemen-ujian)
4. [Bank Soal](#bank-soal)
5. [Rekap Nilai](#rekap-nilai)
6. [Profil Sekolah](#profil-sekolah)
7. [Panduan Siswa](#panduan-siswa)
8. [Fitur Utama](#fitur-utama)

---

## Persiapan & Instalasi

### Persyaratan Sistem

- PHP versi 7.4 atau lebih tinggi
- MySQL/MariaDB
- Web server (Apache/Nginx/XAMPP)
- Ekstensi PHP: `mysqli`, `mbstring`, `json`, `fileinfo`

### Struktur Folder

```
my-php-app/
├── src/
│   └── exam6/
│       ├── admin/              # Panel admin
│       ├── api/                # API untuk processing
│       ├── config/             # Konfigurasi database
│       ├── uploads/            # File upload (gambar)
│       ├── vendor/             # Bootstrap & icon
│       ├── backup_db/          # Backup database
│       ├── index.php           # Halaman utama (list ujian)
│       ├── ujian.php           # Halaman ujian siswa
│       ├── review.php          # Halaman review jawaban
│       └── riwayat.php         # Riwayat nilai siswa
```

### Setup Database

1. Buat database baru, misalnya `ujian_online`
2. Import file database yang disediakan (biasanya `ujian_online.sql`)
3. Konfigurasi koneksi database di `config/database.php`

### Setup Awal Admin

1. Akses halaman login admin: `exam6/admin/login.php`
2. Username default: `admin`
3. Password default: `admin123` (sebaiknya segera ganti setelah login pertama)

---

## Login Admin

### Cara Login

1. Buka browser dan akses URL: `exam6/admin/login.php`
2. Masukkan username dan password
3. Klik tombol "Masuk"

### Menu Admin Panel

Setelah login, Anda akan melihat menu sidebar:
- **Manajemen Ujian** - Kelola ujian (tambah/edit/hapus)
- **Bank soal** - Kelola soal untuk setiap ujian
- **Rekap Nilai** - Lihat hasil ujian siswa
- **Profil Sekolah** - Pengaturan sekolah & tampilan
- **Logout** - Keluar dari sistem

---

## Manajemen Ujian

### Membuat Ujian Baru

1. Di panel admin, klik **Manajemen Ujian**
2. Isi formulir tambah ujian:
   - **Judul Ujian**: Nama ujian (contoh: "Ujian Matematika Semester 1")
   - **Status**: Pilih "Aktif" agar siswa dapat mengakses
   - **Waktu (menit)**: Batas waktu ujian (isi 0 untuk tanpa batas)
   - **Acak Urutan Soal**: "Ya" jika ingin mengacak urutan soal
   - **Acak Opsi Jawaban**: "Ya" jika ingin mengacak opsi A/B/C/D/E
   - **Tampilkan Review**: "Ya" jika siswa boleh lihat pembahasan setelah submit
   - **Tampilkan Skor**: "Ya" jika skor ditampilkan setelah submit
   - **Deskripsi**: Penjelasan tentang ujian (opsional)
3. Klik **Simpan**

### Mengedit Ujian

1. Di tabel daftar ujian, klik tombol **Edit** pada ujian yang ingin diubah
2. Ubah data yang diperlukan
3. Klik **Perbarui**

### Mengaktifkan/Menonaktifkan Ujian

Klik tombol toggle pada kolom "Aksi":
- Tombol hijau = Aktif
- Tombol abu-abu = Nonaktif

### Menghapus Ujian

1. Klik tombol **Hapus** pada ujian yang ingin dihapus
2. Konfirmasi penghapusan pada modal yang muncul
3. **Peringatan**: Menghapus ujian akan menghapus semua soal dan hasil ujian terkait

### Link Ujian

Setiap ujian memiliki link unik yang dapat disalin:
- Klik tombol **copy** pada kolom "Link" untuk menyalin
- Atau klik tombol **buka** untuk membuka halaman ujian langsung

---

## Bank Soal

### Menambahkan Soal Baru

1. Di panel admin, klik **Bank Soal**
2. Pilih ujian yang ingin ditambahkan soalnya dari dropdown
3. Isi formulir soal:
   - **Pertanyaan**: Isi pertanyaan soal (wajib)
   - **Gambar Pertanyaan** (opsional): Upload gambar untuk soal
   - **Opsi A-E**: Isi 5 opsi jawaban (wajib)
   - **Gambar Opsi** (opsional): Upload gambar untuk setiap opsi
   - **Kunci Jawaban**: Pilih jawaban yang benar (A/B/C/D/E)
   - **Poin**: Nilai untuk soal ini (default: 10)
4. Klik **Simpan Soal**

### Format Gambar

- Format yang diizinkan: JPG, PNG, GIF, WebP
- Maksimal ukuran: 2MB
- Gambar akan otomatis diubah nama untuk keamanan

### Mengedit/DeleteSoal

- **Edit**: Klik tombol edit pada soal yang ingin diubah
- **Hapus**: Klik tombol hapus pada soal yang ingin dihapus

### Import soal dari DOCX

1. Download template terlebih dahulu: klik **Download Template**
2. Isi template sesuai format yang disediakan
3. Klik **Import DOCX** dan upload file template yang sudah diisi

---

## Rekap Nilai

### Melihat Hasil Ujian

1. Klik **Rekap Nilai** di sidebar
2. Pilih ujian dari dropdown
3. Anda akan melihat:
   - **Total Peserta**: Jumlah siswa yang mengerjakan
   - **Rata-rata Skor**: Nilai rata-rata
   - **Skor Tertinggi**: Nilai tertinggi
   - **Skor Terendah**: Nilai terendah

### Tabel Hasil Ujian

Tabel menampilkan:
- NIS siswa
- Nama lengkap
- Kelas
- Skor yang diperoleh
- Waktu submit

### Ekspor ke Excel

Klik tombol **Ekspor Excel** untuk mengunduh data dalam format spreadsheet.

### Menghapus Hasil Ujian

Klik tombol hapus pada baris yang ingin dihapus untuk menghapus hasil ujian siswa tertentu.

---

## Profil Sekolah

### Mengatur Profil

1. Klik **Profil Sekolah** di sidebar
2. Isi informasi sekolah:
   - **Nama Sekolah**: Nama sekolah Anda
   - **Logo**: Upload logo sekolah (opsional)
   - **Warna Primer**: Warna utama tema (hex code)
   - **Warna Sekunder**: warna sekunder tema
   - **Tampilkan Riwayat**: Apakah riwayat nilai ditampilkan di halaman utama
3. Klik **Simpan**

### Kustomisasi Tampilan

Warna yang dipilih akan otomatis diterapkan pada:
- Halaman login admin
- Halaman ujian siswa
- Halaman utama

---

## Panduan Siswa

### Mengakses Ujian

1. Buka halaman utama ujian (biasanya `exam6/`)
2. Pilih kartu ujian yang tersedia
3. Klik tombol **Mulai Ujian**

### Memasukkan Identitas

Sebelum mulai mengerjakan, siswa wajib mengisi:
- **NIS**: Nomor Induk Siswa
- **Nama Lengkap**: Nama siswa
- **Kelas**: Kelas siswa (contoh: X IPA 1)

### Mengerjakan Ujian

1. Baca pertanyaan dengan teliti
2. Pilih jawaban dengan mengklik pada opsi yang dipilih
3. Progress indikator di bawah menunjukkan jumlah soal yang sudah dijawab
4. Sistem **auto-save** secara otomatis menyimpan jawaban

### Fitur Timer

Jika ujian memiliki batasan waktu:
- Timer ditampilkan di pojok kanan atas
- Warna timer berubah menjadi kuning (5 menit terakhir) dan merah (1 menit terakhir)
- Saat waktu habis, jawaban akan otomatis disubmit

### Submit Jawaban

1. Klik tombol **Kirim Jawaban** di akhir soal
2. Konfirmasi submit pada modal yang muncul
3. Setelah submit, siswa akan melihat:
   - Pesan berhasil
   - Skor yang diperoleh (jika ditampilkan)
   - Opsi lihat pembahasan (jika diaktifkan)

### Melihat Riwayat Nilai

1. Di halaman utama, masukkan NIS pada form "Cek Riwayat Nilai"
2. Klik **Cari** untuk melihat semua nilai ujian siswa tersebut

---

## Fitur Utama

### Keamanan

- Proteksi CSRF pada form
- Sanitasi input untuk mencegah XSS
- Prepared statements untuk mencegah SQL injection
- Validasi file upload

### Fitur Auto-Save

Sistem secara otomatis menyimpan jawaban siswa secara berkala untuk mengantisipasi kehilangan data jika terjadi disconnect atau browser crash.

### Fitur Randomization

- **Acak Urutan Soal**: Setiap siswa mendapat soal dengan urutan berbeda
- **Acak Opsi**: Urutan opsi jawaban (A/B/C/D/E) diacak

### Responsif

Aplikasi dapat diakses melalui:
- Komputer desktop
- Tablet
- Smartphone

### Tampilan Modern

- UI modern dengan Bootstrap 5
- Animasi halus
- Indikator progress real-time
- Notifikasi toast

---

## Troubleshooting

### Masalah Umum

| Masalah | Solusi |
|---------|--------|
| Tidak bisa login | Cek username & password, pastikan database terhubung |
| Gambar tidak muncul | Cek folder `uploads/` ada dan permission benar |
| Ujian tidak muncul di halaman utama | Pastikan status ujian "Aktif" |
| Timer tidak berjalan | Pastikan JavaScript tidak diblokir |

### Reset Password Admin

Jika lupa password admin:
1. Akses database langsung
2. Update password menggunakan: `UPDATE admin_users SET password = '$2y$10$...' WHERE username = 'admin'`
3. Password default hash: `$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi` (untuk "admin123")

---

## Catatan

- Selalu backup database secara berkala
- Jangan share link admin kepada siswa
- Regularly update aplikasi untuk keamanan
- Pastikan server memiliki SSL/HTTPS

---

*Tutorial ini dibuat untuk Exam6 - Sistem Ujian Online*
