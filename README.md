# Exam6 - Sistem Ujian Online

Aplikasi ujian online berbasis PHP dan MySQL untuk sekolah.

## Fitur

- Landing page dengan list ujian tersedia
- Sistem ujian interaktif untuk siswa
- Bank soal (tambah/edit/hapus soal)
- Manajemen ujian (CRUD)
- Rekap nilai siswa
- Riwayat nilai berdasarkan NIS
- Konfigurasi sekolah (nama, logo, warna)
- Tampilan responsif

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
│   ├── tambah_soal.php  # Tambah/edit soal
│   └── rekap_nilai.php   # Rekap nilai
├── api/                  # API endpoint
│   └── submit_jawaban.php
├── config/
│   ├── database.php       # Konfigurasi database
│   └── init_sekolah.php # Konfigurasi sekolah
├── vendor/               # Library (Bootstrap, Bootstrap Icons)
├── uploads/             # File upload (logo)
├── index.php           # Landing page
├── ujian.php           # Halaman ujian siswa
├── review.php         # Review jawaban
├── riwayat.php         # Riwayat nilai
└── docker-compose.yml # Konfigurasi Docker
```

## Cara Install

### Cara 1: Docker (Recommended)

1. Clone repository:
```bash
git clone https://github.com/natedekaka/exam6.git
cd exam6
```

2. Jalankan Docker:
```bash
docker-compose up -d
```

3. Buka browser:
   - **Aplikasi**: http://localhost:8084
   - **phpMyAdmin**: http://localhost:8083
     - Server: `db`
     - Username: `user`
     - Password: `pass123`
     - Database: `ujian_online`

4. Import database `backup_db/ujian_online.sql` melalui phpMyAdmin

5. Login admin:
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

3. Import `backup_db/ujian_online.sql` melalui phpMyAdmin

4. Konfigurasi database di `config/database.php`:
```php
$host = 'localhost';
$user = 'root';
$pass = ''; // password MySQL Anda
$db   = 'ujian_online';
```

5. Akses: http://localhost/exam6

## Akun Default

| Role | Username | Password |
|------|----------|----------|
| Admin | admin | admin123 |

## Cara Penggunaan

### Admin

1. **Login**: Buka `/admin/login.php`
2. **Tambah Ujian**: Dashboard → Tambah Ujian
3. **Tambah Soal**: Klik tombol "Soal" pada ujian → Tambah Soal
4. **Edit Profil Sekolah**: Dashboard → Profil Sekolah

### Siswa

1. Buka landing page
2. Pilih ujian yang tersedia
3. Jawab soal dan submit

### Cek Nilai

1. Masuk halaman Riwayat Nilai
2. Masukkan NIS
3. Lihat nilai

## Teknologi

- **Frontend**: Bootstrap 5, Bootstrap Icons
- **Backend**: PHP 8 Native
- **Database**: MySQL

## Lisensi

MIT License