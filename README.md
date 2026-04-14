# Exam6 - Sistem Ujian Online

Aplikasi ujian online berbasis PHP dan MySQL untuk sekolah dengan fitur keamanan dan administrasi yang lengkap.

## Kompatibilitas

### Platform yang Didukung
- **Desktop**: Chrome, Firefox, Safari, Edge (versi terbaru)
- **Mobile**: 
  - **Android**: Android 7.0 (Nougat) ke atas
  - **iOS**: iOS 12 ke atas (termasuk iPhone XR, iPhone 11, dll)
- **Browser Mobile**: Chrome, Safari, Firefox

### Fitur Mobile
- Tampilan responsive mobile-first design
- Interface seperti aplikasi native
- Navigasi soal yang mudah di HP
- Load cepat untuk koneksi lambat

## Fitur

### Admin
- Manajemen ujian (CRUD) dengan pengaturan keamanan
- Bank soal (tambah/edit/hapus soal dengan gambar)
- Import soal massal dari Excel/CSV
- Ekspor soal ke PDF
- Template download untuk import soal
- Rekap nilai dengan ekspor Excel
- Profil sekolah (nama, logo, warna tema)
- Pengaturan keamanan ujian:
  - Kode Rahasia (Exam Code)
  - Batasan IP Address
  - Browser Lock (deteksi tab switching)
  - Device Fingerprint (deteksi pergantian device)

### Siswa
- Landing page dengan list ujian tersedia
- Verifikasi kode rahasia sebelum ujian
- Sistem ujian interaktif dengan timer
- **Alur ujian baru**:
  1. Input kode rahasia (jika ada)
  2. Isi identitas siswa (NIS, Nama, Kelas)
  3. Jawab soal 1 per halaman dengan navigasi nomor
  4. Auto-save jawaban secara berkala
  5. Load jawaban tersimpan jika refresh halaman
- Review jawaban setelah submit
- Cek riwayat nilai berdasarkan NIS

### Keamanan
- CSRF Protection untuk semua API
- Pengecekan ganda agar siswa tidak mengerjakan dua kali
- Auto-submit jika pelanggaran browser melebihi batas
- Log pelanggaran tab switching
- Race Condition Protection untuk mencegah double submission saat submit bersamaan
- Transaksi database dengan locking untuk integritas data
- Temporary table untuk auto-save tanpa mempengaruhi data final

### Performa
- Mobile-first design untuk load cepat di HP
- Pagination 1 soal per halaman
- Render JavaScript yang dioptimasi
- Inisialisasi token di background (tidak blocking)
- Database indexes untuk query cepat

## Requirements

- PHP 8.0+
- MySQL 8.0+
- Web Server (Apache/Nginx)

## Struktur Direktori

```
exam6/
├── admin/                  # Panel admin
│   ├── index.php         # Dashboard admin
│   ├── login.php         # Login admin
│   ├── tambah_soal.php   # Tambah/edit soal
│   └── rekap_nilai.php   # Rekap nilai
├── api/                  # API endpoint
│   └── submit_jawaban.php
├── config/
│   ├── database.php       # Konfigurasi database
│   └── init_sekolah.php   # Konfigurasi sekolah
├── vendor/               # Library (Bootstrap, Bootstrap Icons)
├── uploads/             # File upload (logo, gambar soal)
├── migrations/            # Database migrations
├── backup_db/           # Database backup
├── index.php            # Landing page
├── ujian.php            # Halaman ujian siswa
├── review.php           # Review jawaban
├── riwayat.php          # Riwayat nilai
└── docker-compose.yml   # Konfigurasi Docker/Podman
```

## Cara Install

### Cara 1: Podman/Docker (Recommended)

1. Clone repository:
```bash
git clone https://github.com/natedekaka/exam6.git
cd exam6
```

2. Jalankan Podman/Docker:
```bash
podman-compose up -d
# atau docker-compose up -d
```

3. Buka browser:
   - **Aplikasi**: http://localhost:8024
   - **phpMyAdmin**: http://localhost:8025
     - Server: `db`
     - Username: `user`
     - Password: `pass123`
     - Database: `ujian_online`

4. Import database melalui phpMyAdmin:
   - Database: `backup_db/ujian_online.sql`

5. Jalankan migration untuk performa optimal:
   - Database: `migrations/06_performance_indexes.sql`

6. Login admin:
   - Username: `admin`
   - Password: `admin123`

### Cara 2: Manual (XAMPP/LAMPP)

1. Clone/download ke folder web server:
```bash
git clone https://github.com/natedekaka/exam6.git
```

2. Buat database:
```sql
CREATE DATABASE ujian_online CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

3. Import database melalui phpMyAdmin:
   - Database: `backup_db/ujian_online.sql`

4. Konfigurasi database di `config/database.php`:
```php
$host = 'localhost';
$user = 'root';
$pass = ''; // password MySQL Anda
$db   = 'ujian_online';
```

5. Akses: http://localhost/exam6

## Pengaturan Keamanan Ujian

Saat membuat/mengedit ujian di admin, tersedia pengaturan keamanan:

| Fitur | Deskripsi |
|-------|------------|
| **Kode Ujian** | Kode rahasia yang harus dimasukkan siswa untuk mengakses ujian |
| **Batasan IP** | Batasi akses ujian hanya dari IP tertentu (pisahkan dengan koma) |
| **Browser Lock** | Deteksi jika siswa switch tab/copy-paste. Akan melakukan auto-submit setelah X pelanggaran |
| **Device Fingerprint** | Deteksi jika siswa更换设备 (ganti device/browser) |

## Alur Ujian Baru

1. **Halaman Utama**: Siswa melihat list ujian yang tersedia
2. **Input Kode** (jika diperlukan): Masukkan kode rahasia
3. **Identitas**: Isi NIS, Nama, Kelas → klik "Mulai Ujian"
4. **Soal**: Jawab 1 soal per halaman dengan navigasi:
   - Previous/Next button
   - Grid nomor soal untuk lompat ke soal tertentu
   - Indicator warna: abu (belum), hijau (dijawab), biru (aktif)
5. **Submit**: Klik "Kirim Jawaban" jika sudah selesai
6. **Hasil**: Lihat skor langsung

## Akun Default

| Role | Username | Password |
|------|----------|----------|
| Admin | admin | admin123 |

## Cara Penggunaan

### Admin

1. **Login**: Buka `/admin/login.php`
2. **Tambah Ujian**: Dashboard → Tambah Ujian
3. **Atur Keamanan**: Pada form ujian, bagian "Pengaturan Keamanan"
4. **Tambah Soal**: Klik tombol "Soal" pada ujian → Tambah Soal
5. **Edit Profil Sekolah**: Dashboard → Profil Sekolah

### Siswa

1. Buka landing page
2. Jika ujian menggunakan kode rahasia, masukkan kode terlebih dahulu
3. Isi identitas (NIS, Nama, Kelas) → klik "Mulai Ujian"
4. Jawab soal satu per satu dengan navigasi
5. Klik "Kirim Jawaban"

### Cek Nilai

1. Masuk halaman Riwayat Nilai
2. Masukkan NIS
3. Lihat nilai

## Teknologi

- **Frontend**: Bootstrap 5, Bootstrap Icons
- **Backend**: PHP 8 Native (Native, no framework)
- **Database**: MySQL
- **Container**: Docker/Podman

## Lisensi

MIT License