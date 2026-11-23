# SkripsiTrack API

Backend REST API untuk aplikasi **SkripsiTrack** (Sistem Monitoring Progres Tugas Akhir Berbasis Android), project UAS mata kuliah Pengembangan Aplikasi Perangkat Bergerak.

**Teknologi:** Laravel, Laravel Sanctum (token), MySQL, dijalankan lokal dengan Laragon.

## Prasyarat
- Laragon (PHP 8.2+, MySQL, Composer)
- Git

## Instalasi
1. Clone repo ke `C:\laragon\www`:
```
   git clone https://github.com/USERNAME/skripsitrack-api.git
   cd skripsitrack-api
```
2. Pasang dependensi dan siapkan environment:
```
   composer install
   copy .env.example .env
   php artisan key:generate
```
3. Buat database kosong `skripsitrack` (HeidiSQL/phpMyAdmin), lalu cek kredensial di `.env`
   (`DB_DATABASE=skripsitrack`, `DB_USERNAME=root`, `DB_PASSWORD=` kosong).
   Kode yang harus diisi saat mendaftar sebagai dosen:
   ```
   DOSEN_INVITE_CODE=DOSEN2026
   ```
   Ganti `DOSEN_INVITE_CODE` dengan kode lain jika dipakai di luar keperluan demo. Setelah mengubah `.env`, jalankan `php artisan config:clear`.
5. Migrasi dan data demo:
```
   php artisan migrate:fresh --seed
   php artisan storage:link
```
5. Naikkan batas upload di `php.ini` (cek lokasinya dengan `php --ini`):
```
   upload_max_filesize = 10M
   post_max_size = 12M
```
6. Jalankan server agar bisa diakses HP di jaringan yang sama:
```
   php artisan serve --host=0.0.0.0 --port=8000
```
   Alamat laptop: lihat IPv4 dari `ipconfig`. Izinkan port 8000 di Windows Firewall bila perlu.

## Akun demo (password: `password`)
| Role | Email |
|---|---|
| Dosen | dosen@test.com, dosen2@test.com |
| Mahasiswa | mahasiswa@test.com, mahasiswa2@test.com, mahasiswa3@test.com |

## Endpoint utama
Prefix `/api`. Semua selain `/login` memerlukan header `Authorization: Bearer <token>` dan `Accept: application/json`.

Lihat daftar lengkap di laporan proyek atau `routes/api.php`.

## Struktur database
Migrasi ada di `database/migrations`, data demo di `database/seeders`.
Tabel: `users`, `theses`, `thesis_stages`, `submissions`, `comments`, `activity_logs`, `personal_access_tokens`.
