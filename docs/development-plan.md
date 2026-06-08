# Development Plan - EMasjid

> Rencana pengembangan day-by-day untuk MVP EMasjid.
> Estimasi: 1 developer full-stack, 31 hari kerja.
> Asumsi: Environment development (PHP, Composer, Node, Flutter SDK) sudah ter-install di mesin lokal.

---

## Overview Timeline

| Fase | Hari | Fokus |
|------|------|-------|
| Fase 0 | Day 1 | Instalasi & Setup Environment |
| Fase 1 | Day 2-6 | Foundation (Backend scaffolding, auth, multi-tenant) |
| Fase 2 | Day 7-11 | Owner Panel (Super Admin) |
| Fase 3 | Day 12-19 | Admin Panel (Admin Masjid & Pengurus) |
| Fase 4 | Day 20-26 | API + Flutter (Jamaah) |
| Fase 5 | Day 27-29 | Integrasi Payment & Notification |
| Fase 6 | Day 30-31 | Testing, Bug Fix, Deploy |

---

## Fase 0: Instalasi & Setup Environment (Day 1)

### Day 1 — Instalasi Laravel, Flutter & Dependencies

**Backend (Laravel):**
- [ ] `composer create-project laravel/laravel . --prefer-dist`
- [ ] Install packages:
  ```bash
  composer require spatie/laravel-permission laravel/sanctum barryvdh/laravel-dompdf maatwebsite/excel yajra/laravel-datatables-oracle guzzlehttp/guzzle
  ```
- [ ] Install dev packages:
  ```bash
  composer require --dev laravel/pint
  ```
- [ ] Install frontend dependencies:
  ```bash
  npm install bootstrap@5 jquery sweetalert2 chart.js datatables.net datatables.net-bs5
  ```
- [ ] Publish vendor configs:
  ```bash
  php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
  php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
  php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
  ```
- [ ] Setup `.env` (database, queue=database, session, cache)
- [ ] Edit `config/permission.php` → `teams = true`, `team_foreign_key = mosque_id`
- [ ] Setup Vite config (`vite.config.js`, `resources/js/app.js`, `resources/css/app.css`)
- [ ] `php artisan queue:table`
- [ ] `php artisan key:generate`
- [ ] `php artisan storage:link`
- [ ] `npm run build`
- [ ] Buat database MySQL `emasjid`
- [ ] `php artisan migrate` (migration default Laravel)
- [ ] Verifikasi: `php artisan serve` → halaman Laravel default muncul

**Mobile (Flutter):**
- [ ] `flutter create --org com.emasjid emasjid_app`
- [ ] Edit `pubspec.yaml` → tambahkan semua dependencies (dio, flutter_bloc, firebase_messaging, go_router, webview_flutter, dll)
- [ ] `flutter pub get`
- [ ] Setup Firebase project di Firebase Console
- [ ] Download `google-services.json` → `android/app/`
- [ ] Edit `android/build.gradle` dan `android/app/build.gradle` untuk Firebase
- [ ] Edit `android/app/src/main/AndroidManifest.xml` (permissions: internet, camera, location)
- [ ] Buat folder structure (`lib/core`, `lib/features`, `lib/shared`, `lib/config`, `lib/routing`)
- [ ] `flutter run` → verifikasi app jalan di emulator/device

**Deliverable:** Kedua project (Laravel + Flutter) ter-install, bisa dijalankan, semua dependencies terpasang.

---

## Fase 1: Foundation (Day 2-6)

### Day 2 — Project Setup & Database Structure

**Backend:**
- [ ] Setup folder structure sesuai `AGENTS.md` (Controllers, Services, Repositories, DTOs, Enums, Traits, Contracts)
- [ ] Buat semua migration files:
  - `users` (tambah kolom `active_mosque_id`)
  - `mosques`
  - `platform_settings`
  - `schedules`
  - `activities`
  - `cash_transactions`
  - `donations`
  - `announcements`
  - `staffs`
  - `mosque_user` (pivot)
  - `fcm_tokens`
- [ ] Buat semua Enum classes (`MosqueStatus`, `DonationStatus`, `DonationCategory`, `TransactionType`, `AnnouncementStatus`, `ActivityStatus`)
- [ ] Jalankan `php artisan migrate`

**Deliverable:** Database siap, enum lengkap, struktur folder rapi.

---

### Day 3 — Models & Trait Multi-Tenant

**Backend:**
- [ ] Buat trait `BelongsToMosque` (global scope + auto-fill `mosque_id`)
- [ ] Buat helper `mosque_id()`
- [ ] Buat semua Model classes dengan relasi, cast, dan trait:
  - `User`
  - `Mosque`
  - `PlatformSetting`
  - `Schedule`
  - `Activity`
  - `CashTransaction`
  - `Donation`
  - `Announcement`
  - `Staff`
  - `MosqueUser`
  - `FcmToken`
- [ ] Buat Model Factory untuk semua model (untuk testing nanti)

**Deliverable:** Semua model dengan relasi dan tenant scope berfungsi.

---

### Day 4 — Auth System & Middleware

**Backend:**
- [ ] Setup auth guards (web + api/sanctum)
- [ ] Buat middleware `IdentifyMosque` (resolve mosque dari session/route)
- [ ] Buat middleware `EnsureOwnerAccess` (cek role super-admin)
- [ ] Buat middleware `EnsureMosqueActive` (cek status masjid aktif)
- [ ] Setup Spatie permission (teams mode, `mosque_id`)
- [ ] Buat `PermissionSeeder` (semua permission + role)
- [ ] Buat `PlatformSettingsSeeder` (default fee settings)
- [ ] Buat `SuperAdminSeeder` (akun owner pertama)
- [ ] Jalankan seeder

**Deliverable:** Sistem auth + permission + middleware berjalan.

---

### Day 5 — Base Layout & Shared Components (Web)

**Frontend Web:**
- [ ] Buat base layout owner panel (`resources/views/layouts/owner.blade.php`)
- [ ] Buat base layout admin panel (`resources/views/layouts/admin.blade.php`)
- [ ] Buat layout auth (`resources/views/layouts/auth.blade.php`)
- [ ] Setup Bootstrap 5 + jQuery + SweetAlert2 + DataTables di layout
- [ ] Buat shared blade components:
  - `alert` (flash message)
  - `confirm-form` (SweetAlert handler global)
  - `sidebar` (owner & admin)
  - `navbar`
  - `breadcrumb`
  - `stat-card` (dashboard widget)
  - `empty-state`
- [ ] Buat halaman login (shared untuk owner & admin)
- [ ] Buat login controller + request + logic

**Deliverable:** Layout siap pakai, login berfungsi untuk owner & admin.

---

### Day 6 — Repository Service Provider & Base Classes

**Backend:**
- [ ] Buat `RepositoryServiceProvider` dengan semua binding
- [ ] Buat semua Repository Interfaces:
  - `MosqueRepositoryInterface`
  - `ScheduleRepositoryInterface`
  - `CashTransactionRepositoryInterface`
  - `DonationRepositoryInterface`
  - `AnnouncementRepositoryInterface`
  - `CongregationRepositoryInterface`
  - `StaffRepositoryInterface`
  - `PlatformSettingRepositoryInterface`
- [ ] Buat semua Repository Implementations (minimal CRUD methods)
- [ ] Register provider di `config/app.php`
- [ ] Buat base API response trait/helper (success, error, pagination format)

**Deliverable:** Repository layer lengkap, siap dipakai oleh service.

---

## Fase 2: Owner Panel (Day 7-11)

### Day 7 — Owner Dashboard & Mosque Management (List)

**Backend:**
- [ ] Buat `MosqueService` (list, approve, reject, suspend)
- [ ] Buat `Owner\DashboardController` (statistik platform)
- [ ] Buat `Owner\MosqueController` (index, pending, show)
- [ ] Buat DTOs: `ApproveMosqueDTO`, `RejectMosqueDTO`

**Frontend:**
- [ ] Halaman dashboard owner (total masjid, jamaah, pendapatan fee)
- [ ] Halaman list masjid (DataTable: semua masjid, filter by status)
- [ ] Halaman list masjid pending (menunggu verifikasi)

**Deliverable:** Owner bisa login, lihat dashboard, lihat daftar masjid.

---

### Day 8 — Owner: Approve/Reject & Mosque Detail

**Backend:**
- [ ] Buat `Owner\MosqueController@approve` dan `@reject`
- [ ] Buat Form Request: `ApproveMosqueRequest`, `RejectMosqueRequest`
- [ ] Buat Event `MosqueApproved`, `MosqueRejected`
- [ ] Buat Listener: kirim email notifikasi ke admin masjid

**Frontend:**
- [ ] Halaman detail masjid (owner view: semua info + statistik)
- [ ] Modal/form approve dengan konfirmasi SweetAlert
- [ ] Modal/form reject dengan input alasan
- [ ] Flash message sukses/error

**Deliverable:** Owner bisa approve/reject pendaftaran masjid.

---

### Day 9 — Owner: Platform Settings & Fee Configuration

**Backend:**
- [x] ✅ Buat `PlatformSettingService` (get, update settings)
- [x] ✅ Buat `Owner\SettingController` (fee configuration)
- [x] ✅ Buat Form Request: `UpdateFeeSettingRequest`

**Frontend:**
- [x] ✅ Halaman settings (form: fee percentage, fee mechanism, fee active/inactive)
- [x] ✅ Penjelasan/helper text di form
- [x] ✅ Preview kalkulasi fee (contoh: "Jika donasi Rp 100.000, fee = Rp 2.500")

**Deliverable:** ✅ Owner bisa mengatur fee platform.

**Status:** COMPLETED (8 Juni 2026) | Files: 11 created, 1 modified | Tests: 8 passing  
**Documentation:** See docs/DAY-9-COMPLETE.md for full details.

---

### Day 10 — Owner: Manage Owner Accounts & Reports

**Backend:**
- [ ] Buat `Owner\UserController` (CRUD akun super-admin lain)
- [ ] Buat `Owner\ReportController` (laporan pendapatan fee)

**Frontend:**
- [ ] Halaman list akun owner (DataTable)
- [ ] Form tambah/edit akun owner
- [ ] Halaman laporan fee platform (ringkasan bulanan, chart)

**Deliverable:** Owner bisa kelola akun owner lain dan lihat laporan fee.

---

### Day 11 — Owner: Mosque Suspend/Reactivate & Polish

**Backend:**
- [ ] Buat aksi suspend dan reactivate masjid
- [ ] Buat notifikasi ke admin masjid saat di-suspend

**Frontend:**
- [ ] Tombol suspend/reactivate di halaman detail masjid
- [ ] Polish UI owner panel (responsive, konsistensi)
- [ ] Test manual semua flow owner

**Deliverable:** Owner panel lengkap dan fungsional.

---

## Fase 3: Admin Panel (Day 12-19)

### Day 12 — Mosque Registration & Admin Login

**Backend:**
- [ ] Buat halaman registrasi masjid (form publik)
- [ ] Buat `MosqueRegistrationController` (store, create akun admin)
- [ ] Buat Form Request: `RegisterMosqueRequest`
- [ ] Generate `invitation_code` saat masjid approved

**Frontend:**
- [ ] Halaman form daftar masjid (publik, tanpa login)
- [ ] Halaman sukses "Menunggu Verifikasi"
- [ ] Login admin masjid → redirect ke `/admin/dashboard`
- [ ] Dashboard admin masjid (statistik: jamaah, keuangan, donasi)

**Deliverable:** Masjid bisa mendaftar, admin bisa login ke panel.

---

### Day 13 — Admin: Profil Masjid & Jadwal Sholat

**Backend:**
- [ ] Buat `Admin\MosqueProfileController` (edit profil)
- [ ] Buat `ScheduleService` + `Admin\ScheduleController` (CRUD jadwal)
- [ ] Buat DTOs: `UpdateMosqueProfileDTO`, `UpdateScheduleDTO`
- [ ] Buat Form Requests

**Frontend:**
- [ ] Halaman edit profil masjid (nama, alamat, foto, rekening, lokasi GPS)
- [ ] Halaman jadwal sholat (form input 5 waktu + Jumat)
- [ ] Upload foto masjid

**Deliverable:** Admin bisa edit profil dan kelola jadwal sholat.

---

### Day 14 — Admin: Kegiatan (Activities)

**Backend:**
- [ ] Buat `ActivityService` + `Admin\ActivityController` (CRUD)
- [ ] Buat DTOs: `CreateActivityDTO`, `UpdateActivityDTO`
- [ ] Buat Form Requests

**Frontend:**
- [ ] Halaman list kegiatan (DataTable)
- [ ] Form tambah/edit kegiatan
- [ ] Status management (upcoming, ongoing, completed, cancelled)

**Deliverable:** Admin bisa kelola kegiatan masjid.

---

### Day 15 — Admin: Keuangan - Pemasukan & Pengeluaran

**Backend:**
- [ ] Buat `FinanceService` + `Admin\CashIncomeController` + `Admin\CashExpenseController`
- [ ] Buat DTOs: `CreateCashTransactionDTO`
- [ ] Buat Form Requests: `StoreCashIncomeRequest`, `StoreCashExpenseRequest`

**Frontend:**
- [ ] Halaman list pemasukan (DataTable)
- [ ] Halaman list pengeluaran (DataTable)
- [ ] Form tambah pemasukan
- [ ] Form tambah pengeluaran
- [ ] Hapus transaksi (dengan konfirmasi)

**Deliverable:** Admin bisa input pemasukan dan pengeluaran.

---

### Day 16 — Admin: Keuangan - Laporan & Export

**Backend:**
- [ ] Buat `Admin\FinanceReportController` (monthly report)
- [ ] Buat Export class (CSV via Maatwebsite)
- [ ] Buat PDF export (DomPDF)

**Frontend:**
- [ ] Halaman laporan keuangan bulanan (ringkasan + chart)
- [ ] Filter by bulan/tahun
- [ ] Tombol export CSV
- [ ] Tombol export PDF
- [ ] Preview print-friendly

**Deliverable:** Laporan keuangan lengkap dengan export.

---

### Day 17 — Admin: Pengumuman

**Backend:**
- [ ] Buat `AnnouncementService` + `Admin\AnnouncementController` (CRUD + publish)
- [ ] Buat DTOs: `CreateAnnouncementDTO`, `PublishAnnouncementDTO`
- [ ] Buat Form Requests
- [ ] Buat Event `AnnouncementPublished`

**Frontend:**
- [ ] Halaman list pengumuman (DataTable, filter by status)
- [ ] Form tambah/edit pengumuman (judul, isi, gambar)
- [ ] Tombol publish (dari draft → published)
- [ ] Tombol arsipkan

**Deliverable:** Admin bisa kelola dan publikasi pengumuman.

---

### Day 18 — Admin: Manajemen Jamaah & Pengurus

**Backend:**
- [ ] Buat `CongregationService` + `Admin\CongregationController` (list, statistik)
- [ ] Buat `StaffService` + `Admin\StaffController` (CRUD, assign permission)
- [ ] Buat Form Requests

**Frontend:**
- [ ] Halaman list jamaah (DataTable: nama, tanggal join)
- [ ] Statistik jamaah (total, baru bulan ini)
- [ ] Halaman list pengurus (DataTable)
- [ ] Form tambah pengurus (pilih user, jabatan)
- [ ] Halaman atur izin akses per pengurus (checkbox permission)

**Deliverable:** Admin bisa lihat jamaah dan kelola pengurus + permission.

---

### Day 19 — Admin: Donasi Masuk & Polish

**Backend:**
- [ ] Buat `Admin\DonationController` (list donasi masuk, detail, confirm manual)
- [ ] Buat Form Request: `ConfirmDonationRequest`

**Frontend:**
- [ ] Halaman list donasi masuk (DataTable: donatur, nominal, status, tanggal)
- [ ] Detail donasi (breakdown fee, status)
- [ ] Tombol konfirmasi manual (untuk transfer manual)
- [ ] Polish seluruh admin panel (responsive, konsistensi)
- [ ] Test manual semua flow admin

**Deliverable:** Admin panel lengkap dan fungsional.

---

## Fase 4: API + Flutter (Day 20-26)

### Day 20 — API: Auth & Mosque Endpoints

**Backend API:**
- [ ] Buat `Api\AuthController` (register, login, logout, refresh)
- [ ] Buat `Api\MosqueController` (list/search, detail, join, leave, my mosques, switch, join-by-code)
- [ ] Buat API Resources: `UserResource`, `MosqueResource`
- [ ] Setup routes `api.php` dengan Sanctum middleware
- [ ] Test via Postman/Insomnia

**Deliverable:** API auth dan mosque endpoints ready.

---

### Day 21 — API: Schedule, Activity, Announcement, Finance

**Backend API:**
- [ ] Buat `Api\ScheduleController` (prayer schedule, activities)
- [ ] Buat `Api\AnnouncementController` (list, detail)
- [ ] Buat `Api\FinanceController` (monthly report, transaction list read-only)
- [ ] Buat API Resources: `ScheduleResource`, `ActivityResource`, `AnnouncementResource`, `CashTransactionResource`

**Deliverable:** API content endpoints ready.

---

### Day 22 — API: Donation & Notification + Duitku Integration

**Backend API:**
- [ ] Buat `Api\DonationController` (categories, calculate, create, detail, my history)
- [ ] Buat `Api\NotificationController` (list, read, read-all, unread count)
- [ ] Buat `Api\ProfileController` (get, update, avatar, change password, delete account)
- [ ] Buat `Api\FcmTokenController` (register token)
- [ ] Buat API Resources: `DonationResource`, `NotificationResource`

**Deliverable:** Semua API endpoint lengkap.

---

### Day 23 — Flutter: Setup, Auth, & Mosque Feature

**Flutter:**
- [ ] Setup project structure (core, features, shared, routing)
- [ ] Buat `ApiClient` (Dio + auth interceptor + error handling)
- [ ] Buat `SecureStorage` (simpan token)
- [ ] Buat feature Auth: model, repository, BLoC, pages (login, register)
- [ ] Buat feature Mosque: model, repository, BLoC, pages (search, detail, join, my mosques, switch)

**Deliverable:** Flutter app bisa login dan join masjid.

---

### Day 24 — Flutter: Schedule, Announcement, Finance

**Flutter:**
- [ ] Buat feature Schedule: model, repository, BLoC, pages (jadwal sholat, list kegiatan)
- [ ] Buat feature Announcement: model, repository, BLoC, pages (list, detail)
- [ ] Buat feature Finance: model, repository, BLoC, pages (laporan bulanan, list transaksi)
- [ ] Buat bottom navigation & routing

**Deliverable:** Flutter app bisa lihat jadwal, pengumuman, dan laporan keuangan.

---

### Day 25 — Flutter: Donation & Profile

**Flutter:**
- [ ] Buat feature Donation: model, repository, BLoC, pages (pilih kategori, input nominal, calculate preview, create, WebView payment, history)
- [ ] Buat feature Profile: model, repository, BLoC, pages (view, edit, avatar, change password)
- [ ] Buat QR scanner page (join masjid via QR)

**Deliverable:** Flutter app bisa donasi dan kelola profil.

---

### Day 26 — Flutter: Notification & Polish

**Flutter:**
- [ ] Setup Firebase Messaging (foreground, background, terminated)
- [ ] Buat feature Notification: model, repository, BLoC, pages (list, mark read)
- [ ] Handle deep link dari notification (tap → buka halaman terkait)
- [ ] Polish UI (loading states, error states, empty states, pull-to-refresh)
- [ ] Test semua flow di device/emulator

**Deliverable:** Flutter app lengkap semua fitur MVP.

---

## Fase 5: Integrasi Payment & Notification (Day 27-29)

### Day 27 — Duitku Integration (End-to-End)

**Backend:**
- [ ] Buat `DuitkuService` (create transaction, verify callback)
- [ ] Buat `WebhookController` untuk Duitku callback
- [ ] Buat `ProcessDuitkuCallbackJob`
- [ ] Flow: donasi dibuat → redirect ke payment URL → callback → update status → catat ke kas
- [ ] Handle edge cases: expired, failed, duplicate callback
- [ ] Test dengan Duitku sandbox

**Flutter:**
- [ ] WebView membuka `payment_url` dari API
- [ ] Polling status donasi setelah kembali dari WebView
- [ ] Tampilkan status sukses/gagal

**Deliverable:** Flow donasi end-to-end berjalan (sandbox).

---

### Day 28 — FCM Push Notification (End-to-End)

**Backend:**
- [ ] Buat FCM notification channel (custom)
- [ ] Integrasikan FCM send via HTTP v1 API (Guzzle)
- [ ] Trigger notification saat:
  - Pengumuman di-publish → kirim ke semua jamaah masjid
  - Donasi dikonfirmasi → kirim ke donatur
  - Masjid approved → kirim ke admin masjid
- [ ] Queue semua notification (job)

**Flutter:**
- [ ] Handle notification saat app foreground (local notification)
- [ ] Handle notification saat app background/terminated
- [ ] Tap notification → navigate ke halaman terkait
- [ ] Register/update FCM token saat login dan app start

**Deliverable:** Push notification berjalan end-to-end.

---

### Day 29 — Event System & Email Notification

**Backend:**
- [ ] Finalisasi semua Event classes
- [ ] Buat semua Listener yang diperlukan
- [ ] Buat email templates:
  - Masjid approved (ke admin masjid)
  - Masjid rejected (ke admin masjid)
  - Donation receipt (ke donatur, optional)
  - Welcome email (ke admin masjid baru)
- [ ] Test event → listener → notification flow

**Deliverable:** Event system dan email notification lengkap.

---

## Fase 6: Testing, Bug Fix, Deploy (Day 30-31)

### Day 30 — Testing & Bug Fix

- [ ] Tulis feature test untuk flow kritikal:
  - Owner approve/reject masjid
  - Admin input keuangan
  - API auth flow
  - API donasi flow
  - Duitku callback handling
  - Multi-tenant isolation (masjid A tidak lihat data masjid B)
- [ ] Run semua test: `php artisan test`
- [ ] Fix bugs yang ditemukan
- [ ] Test manual end-to-end:
  - Daftar masjid → approve → login admin → setup → jamaah join → donasi
- [ ] Test Flutter di real device (Android)

**Deliverable:** Semua flow kritikal tested dan bug-free.

---

### Day 31 — Deploy & Go Live

- [ ] Setup shared hosting (upload code, setup database)
- [ ] Configure `.env` production
- [ ] Run migration & seeder di production
- [ ] Setup cron job (scheduler + queue worker)
- [ ] Setup SSL certificate
- [ ] Test production environment
- [ ] Buat APK release Flutter
- [ ] Upload APK ke Play Store (atau distribusi manual untuk testing)
- [ ] Onboard 1-3 masjid pertama
- [ ] Monitor error log hari pertama

**Deliverable:** Platform live, masjid pertama terdaftar.

---

## Catatan Penting

### Prioritas Jika Waktu Tidak Cukup

Jika development melebihi estimasi, potong dari:

1. ~~Email notification~~ → bisa ditambah nanti
2. ~~Export PDF~~ → CSV cukup dulu
3. ~~QR Code join~~ → link undangan cukup dulu
4. ~~Chart di dashboard~~ → angka statistik cukup dulu
5. ~~GPS nearby mosque~~ → search by nama cukup dulu

Yang TIDAK BOLEH dipotong:
- Auth & multi-tenant isolation
- CRUD keuangan + laporan
- Donasi online (Duitku)
- Push notification (pengumuman)
- Jadwal sholat

### Asumsi

- 1 developer, fokus full-time
- Familiar dengan Laravel & Flutter
- Tidak ada blocker dari hosting/payment gateway approval
- Design UI menggunakan template Bootstrap (tidak custom design dari scratch)

---

## Penutup

Plan ini adalah guide, bukan kontrak. Jika suatu hari selesai lebih cepat, lanjut ke hari berikutnya. Jika ada blocker, geser task ke hari setelahnya. Yang penting urutan dependency tetap dijaga (Foundation → Owner → Admin → API → Integration → Deploy).
