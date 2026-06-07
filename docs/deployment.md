# Deployment Guide - EMasjid

> Panduan deploy dan konfigurasi EMasjid di shared hosting.
> Ikuti langkah-langkah ini untuk setup environment production.

---

## Daftar Isi

1. [Persyaratan Hosting](#1-persyaratan-hosting)
2. [Struktur Directory di Hosting](#2-struktur-directory-di-hosting)
3. [Setup Awal](#3-setup-awal)
4. [Environment Variables](#4-environment-variables)
5. [Deploy Kode](#5-deploy-kode)
6. [Database Setup](#6-database-setup)
7. [Storage & Permission](#7-storage--permission)
8. [Cron Job (Scheduler)](#8-cron-job-scheduler)
9. [Queue Worker](#9-queue-worker)
10. [SSL & Domain](#10-ssl--domain)
11. [Maintenance & Update](#11-maintenance--update)
12. [Troubleshooting](#12-troubleshooting)
13. [Monitoring](#13-monitoring)

---

## 1. Persyaratan Hosting

### Minimum Requirements

| Komponen | Requirement |
|----------|-------------|
| PHP | >= 8.2 |
| MySQL | >= 8.0 |
| Disk Space | >= 2 GB |
| PHP Extensions | OpenSSL, PDO, Mbstring, Tokenizer, XML, Ctype, JSON, BCMath, Fileinfo, GD/Imagick |
| PHP Memory Limit | >= 256 MB |
| Max Execution Time | >= 120 seconds |
| cPanel/Panel | Diperlukan untuk cron job dan file manager |

### PHP Extensions yang Wajib Aktif

```text
ext-curl          (untuk HTTP client / Duitku / FCM)
ext-gd            (untuk image processing)
ext-mbstring      (untuk string handling)
ext-openssl       (untuk encryption)
ext-pdo_mysql     (untuk database)
ext-xml           (untuk DomPDF)
ext-zip           (untuk export Excel)
ext-fileinfo      (untuk file upload validation)
```

### Yang TIDAK Tersedia di Shared Hosting (dan alternatifnya)

| Fitur | Alternatif |
|-------|------------|
| Redis | Database cache driver |
| Supervisor | Cron-based queue worker |
| Websocket | FCM push notification + polling |
| Custom port | Gunakan port 80/443 standar |
| SSH (kadang) | FTP/SFTP atau Git deployment |

---

## 2. Struktur Directory di Hosting

```text
/home/username/
├── public_html/              ← Document root (symlink atau isi dari /public)
│   ├── index.php
│   ├── .htaccess
│   ├── css/
│   ├── js/
│   ├── images/
│   └── storage -> ../emasjid/storage/app/public
│
├── emasjid/                  ← Source code Laravel (di luar public_html)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   ├── .env
│   ├── artisan
│   └── composer.json
│
└── logs/                     ← Custom log directory (optional)
```

### Kenapa Laravel di Luar public_html?

Security. Hanya folder `public` yang bisa diakses browser. Source code, `.env`, dan vendor tidak terekspos.

---

## 3. Setup Awal

### Langkah 1: Upload Source Code

```bash
# Via Git (jika hosting support SSH)
cd /home/username
git clone https://github.com/yourrepo/emasjid.git

# Via FTP/SFTP
# Upload semua file ke /home/username/emasjid/
```

### Langkah 2: Install Dependencies

```bash
cd /home/username/emasjid
composer install --no-dev --optimize-autoloader
```

Jika tidak ada SSH, upload folder `vendor/` dari local (setelah `composer install --no-dev`).

### Langkah 3: Setup public_html

**Opsi A: Symlink (recommended jika ada SSH)**

```bash
rm -rf /home/username/public_html
ln -s /home/username/emasjid/public /home/username/public_html
```

**Opsi B: Copy + modifikasi index.php (tanpa SSH)**

Copy isi `/emasjid/public/` ke `/public_html/`, lalu edit `index.php`:

```php
// public_html/index.php

// Ubah path bootstrap
require __DIR__.'/../emasjid/vendor/autoload.php';
$app = require_once __DIR__.'/../emasjid/bootstrap/app.php';
```

### Langkah 4: Generate Key & Optimize

```bash
cd /home/username/emasjid
php artisan key:generate
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 4. Environment Variables

### File `.env` Production

```env
APP_NAME=EMasjid
APP_ENV=production
APP_KEY=base64:xxxxxxxxxxxxx
APP_DEBUG=false
APP_URL=https://emasjid.id

# Database
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=emasjid_db
DB_USERNAME=emasjid_user
DB_PASSWORD=strong_password_here

# Cache & Session
CACHE_DRIVER=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=noreply@emasjid.id
MAIL_PASSWORD=app_password_here
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@emasjid.id
MAIL_FROM_NAME="EMasjid"

# Duitku
DUITKU_MERCHANT_CODE=your_merchant_code
DUITKU_API_KEY=your_api_key
DUITKU_BASE_URL=https://passport.duitku.com/webapi/api/merchant
DUITKU_CALLBACK_URL=https://emasjid.id/webhooks/duitku/callback
DUITKU_RETURN_URL=https://emasjid.id/donation/return
DUITKU_EXPIRY_PERIOD=1440

# FCM
FCM_SERVER_KEY=your_fcm_server_key
FCM_PROJECT_ID=your_project_id

# Filesystem
FILESYSTEM_DISK=public

# Logging
LOG_CHANNEL=daily
LOG_LEVEL=error
```

### Hal yang TIDAK Boleh

- Jangan commit `.env` ke repository
- Jangan pakai `APP_DEBUG=true` di production
- Jangan pakai password lemah untuk database
- Jangan expose `DUITKU_API_KEY` di frontend

---

## 5. Deploy Kode

### Metode 1: Git Pull (Recommended)

```bash
cd /home/username/emasjid
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Metode 2: FTP Upload

1. Upload file yang berubah via FTP/SFTP
2. Jangan upload `.env`, `storage/`, atau `vendor/` (kecuali ada dependency baru)
3. Jika ada dependency baru, upload ulang folder `vendor/`
4. Clear cache via URL (buat route khusus) atau via cPanel Terminal

### Script Deploy (untuk SSH)

Buat file `deploy.sh`:

```bash
#!/bin/bash
cd /home/username/emasjid

echo "Pulling latest code..."
git pull origin main

echo "Installing dependencies..."
composer install --no-dev --optimize-autoloader

echo "Running migrations..."
php artisan migrate --force

echo "Caching config..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Clearing old cache..."
php artisan cache:clear

echo "Deploy complete!"
```

---

## 6. Database Setup

### Create Database via cPanel

1. Login cPanel → MySQL Databases
2. Create database: `emasjid_db`
3. Create user: `emasjid_user`
4. Assign user ke database dengan ALL PRIVILEGES

### Run Migration

```bash
php artisan migrate --force
```

### Run Seeder (pertama kali)

```bash
php artisan db:seed --class=PermissionSeeder
php artisan db:seed --class=PlatformSettingsSeeder
php artisan db:seed --class=SuperAdminSeeder
```

### Backup Database

Setup backup rutin via cPanel atau script:

```bash
# Backup harian (tambahkan ke cron)
mysqldump -u emasjid_user -p emasjid_db > /home/username/backups/db_$(date +%Y%m%d).sql
```

---

## 7. Storage & Permission

### Create Storage Link

```bash
php artisan storage:link
```

Jika tidak bisa via artisan (karena public_html terpisah), buat symlink manual:

```bash
ln -s /home/username/emasjid/storage/app/public /home/username/public_html/storage
```

### Folder Permissions

```bash
chmod -R 775 /home/username/emasjid/storage
chmod -R 775 /home/username/emasjid/bootstrap/cache
```

### Storage Structure

```text
storage/
├── app/
│   └── public/
│       ├── mosques/        ← Foto masjid
│       ├── announcements/  ← Gambar pengumuman
│       ├── avatars/        ← Foto profil user
│       └── exports/        ← Temporary export files
├── framework/
│   ├── cache/
│   ├── sessions/
│   └── views/
└── logs/
    └── laravel.log
```

---

## 8. Cron Job (Scheduler)

Laravel scheduler perlu satu cron entry. Setup via cPanel → Cron Jobs.

### Cron Entry

```text
* * * * * cd /home/username/emasjid && php artisan schedule:run >> /dev/null 2>&1
```

### Scheduled Tasks yang Aktif

| Task | Jadwal | Keterangan |
|------|--------|------------|
| Expire pending donations | Setiap jam | Donation pending > 24 jam → expired |
| Process queue | Setiap menit | Jalankan job di queue |

---

## 9. Queue Worker

Di shared hosting tidak ada Supervisor. Alternatif:

### Opsi 1: Queue via Scheduler (Recommended untuk Shared Hosting)

Tambahkan di `routes/console.php` atau `app/Console/Kernel.php`:

```php
Schedule::command('queue:work --stop-when-empty --max-time=50')
    ->everyMinute()
    ->withoutOverlapping();
```

Ini menjalankan queue worker setiap menit, memproses job yang ada, lalu berhenti. Tidak ideal tapi works.

### Opsi 2: Cron Job Terpisah

```text
* * * * * cd /home/username/emasjid && php artisan queue:work --stop-when-empty --max-time=50 >> /dev/null 2>&1
```

### Catatan Penting

- `--stop-when-empty`: Worker berhenti jika tidak ada job lagi
- `--max-time=50`: Maksimal 50 detik (agar tidak overlap dengan cron berikutnya)
- Job yang gagal akan di-retry di menit berikutnya
- Untuk job kritikal (callback Duitku), pastikan `tries` dan `backoff` di-set di job class

---

## 10. SSL & Domain

### SSL Certificate

Kebanyakan shared hosting menyediakan free SSL via:
- Let's Encrypt (AutoSSL di cPanel)
- Cloudflare (jika pakai CF)

Pastikan:
- Force HTTPS via `.htaccess`
- `APP_URL` menggunakan `https://`

### .htaccess untuk Force HTTPS

```apache
# public_html/.htaccess

<IfModule mod_rewrite.c>
    RewriteEngine On

    # Force HTTPS
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

    # Remove www (optional)
    RewriteCond %{HTTP_HOST} ^www\.(.+)$ [NC]
    RewriteRule ^(.*)$ https://%1/$1 [R=301,L]

    # Laravel routing
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

### Domain Setup

| Domain | Fungsi |
|--------|--------|
| `emasjid.id` | Main domain (landing page / admin / owner) |
| `api.emasjid.id` | API subdomain (optional, bisa juga `/api/v1`) |

Untuk fase awal, cukup satu domain dengan routing:
- `emasjid.id/owner` → Super Admin
- `emasjid.id/admin` → Admin Masjid
- `emasjid.id/api/v1` → REST API

---

## 11. Maintenance & Update

### Enable Maintenance Mode

```bash
php artisan down --secret="rahasia123"
```

Akses dengan `https://emasjid.id/rahasia123` untuk bypass.

### Disable Maintenance Mode

```bash
php artisan up
```

### Update Checklist

```text
1. [ ] Backup database
2. [ ] Enable maintenance mode
3. [ ] Pull latest code / upload files
4. [ ] composer install --no-dev (jika ada dependency baru)
5. [ ] php artisan migrate --force
6. [ ] php artisan config:cache
7. [ ] php artisan route:cache
8. [ ] php artisan view:cache
9. [ ] php artisan cache:clear
10. [ ] Test critical flows (login, donasi)
11. [ ] Disable maintenance mode
```

### Log Rotation

Laravel daily log sudah otomatis membuat file baru per hari. Untuk membersihkan log lama:

```bash
# Hapus log lebih dari 14 hari (tambahkan ke cron weekly)
find /home/username/emasjid/storage/logs -name "*.log" -mtime +14 -delete
```

---

## 12. Troubleshooting

### Masalah Umum

| Masalah | Solusi |
|---------|--------|
| 500 Internal Server Error | Cek `storage/logs/laravel.log`. Pastikan permission folder `storage` dan `bootstrap/cache` = 775 |
| Class not found | Jalankan `composer dump-autoload` |
| View not found | Jalankan `php artisan view:clear` |
| Config lama masih terpakai | Jalankan `php artisan config:clear` lalu `config:cache` |
| Storage link broken | Hapus dan buat ulang symlink |
| Queue job tidak jalan | Pastikan cron aktif dan `QUEUE_CONNECTION=database` |
| Upload gagal | Cek PHP `upload_max_filesize` dan `post_max_size` di php.ini |
| Session hilang terus | Pastikan `SESSION_DRIVER=database` dan tabel `sessions` ada |
| Duitku callback gagal | Pastikan URL callback bisa diakses publik (tidak di-block firewall) |

### Cara Cek Error

```bash
# Lihat log terbaru
tail -50 /home/username/emasjid/storage/logs/laravel.log

# Atau via cPanel File Manager, buka:
# /home/username/emasjid/storage/logs/laravel-2026-06-07.log
```

### PHP Version Mismatch

Jika hosting punya multiple PHP version, pastikan CLI dan web pakai versi sama:

```bash
# Cek PHP CLI version
php -v

# Jika berbeda, gunakan path lengkap:
/opt/cpanel/ea-php82/root/usr/bin/php artisan migrate
```

Update cron job juga:

```text
* * * * * cd /home/username/emasjid && /opt/cpanel/ea-php82/root/usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

---

## 13. Monitoring

### Yang Perlu Dipantau

| Item | Cara | Frekuensi |
|------|------|-----------|
| Disk usage | cPanel → Disk Usage | Mingguan |
| Database size | phpMyAdmin → size info | Mingguan |
| Error log | `storage/logs/` | Harian |
| Queue failed jobs | `php artisan queue:failed` | Harian |
| Duitku callback | Cek tabel donations yang stuck pending | Harian |
| SSL expiry | cPanel → SSL/TLS Status | Bulanan |
| Backup | Pastikan backup jalan | Mingguan |

### Uptime Monitoring (External)

Gunakan service gratis untuk cek apakah site online:

- UptimeRobot (free, 50 monitors)
- Freshping (free)
- Better Uptime (free tier)

Setup ping ke `https://emasjid.id/api/v1/health` (buat endpoint health check sederhana).

### Health Check Endpoint

```php
// routes/api.php
Route::get('health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
    ]);
});
```

---

## Penutup

Shared hosting memiliki keterbatasan, tetapi untuk fase awal (1-3 masjid) sudah lebih dari cukup. Jika traffic meningkat signifikan, pertimbangkan migrasi ke VPS (DigitalOcean, Hetzner) di masa depan.

Prioritas saat ini:
1. Pastikan deploy berjalan smooth
2. Backup database rutin
3. Monitor error log
4. Queue worker via cron berjalan stabil
