# AGENTS.md - EMasjid

> Panduan wajib bagi AI coding agent dan developer yang bekerja pada codebase EMasjid.
> Baca dokumen ini sebelum membuat perubahan kode, migration, test, atau dokumentasi teknis proyek.

---

## Daftar Isi

1. [Konteks Proyek](#1-konteks-proyek)
2. [Tech Stack Aktual](#2-tech-stack-aktual)
3. [Struktur Proyek](#3-struktur-proyek)
4. [Prinsip Arsitektur](#4-prinsip-arsitektur)
5. [Aturan Multi-Tenant](#5-aturan-multi-tenant)
6. [Owner Panel vs Admin Panel vs API](#6-owner-panel-vs-admin-panel-vs-api)
7. [Konvensi Penamaan](#7-konvensi-penamaan)
8. [Alur Pengembangan Fitur Baru](#8-alur-pengembangan-fitur-baru)
9. [Aturan Database dan Migration](#9-aturan-database-dan-migration)
10. [Authorization dan Permissions](#10-authorization-dan-permissions)
11. [Events, Notifications, Queue](#11-events-notifications-queue)
12. [Testing](#12-testing)
13. [Dokumentasi](#13-dokumentasi)
14. [Anti-Pattern yang Dilarang](#14-anti-pattern-yang-dilarang)
15. [Aturan Interaksi Aksi Backend](#15-aturan-interaksi-aksi-backend)
16. [Checklist Sebelum Commit](#16-checklist-sebelum-commit)

---

## 1. Konteks Proyek

EMasjid adalah platform multi-tenant untuk manajemen masjid di Indonesia. Model bisnis berbasis donasi dengan fee platform dari donasi jamaah ke masjid.

Produk ini memiliki tiga area utama:

| Area | Target User | Implementasi |
|------|-------------|--------------|
| Owner Panel | Super Admin (tim platform) | Blade + Bootstrap 5 + jQuery di `/owner` |
| Admin Panel | Admin Masjid & Pengurus | Blade + Bootstrap 5 + jQuery di `/admin` |
| Mobile API | Jamaah | REST API (Laravel Sanctum) untuk Flutter |

Terminologi penting:

- `mosque` = satu masjid yang terdaftar di platform
- `owner` = super admin yang mengelola platform secara keseluruhan
- `admin` = admin masjid yang mengelola satu masjid
- `staff` = pengurus masjid dengan permission terbatas (configurable)
- `congregation` = jamaah yang mengakses via mobile app
- `mosque_id` = kunci isolasi data antar masjid dan wajib diperhatikan di semua query tenant

Dokumen ini bersifat:

- `normatif` untuk aturan kerja yang harus diikuti
- `deskriptif` untuk mencerminkan struktur repo saat ini

Jika ada benturan antara contoh lama, kebiasaan pribadi, atau asumsi umum framework dengan dokumen ini, ikuti dokumen ini.

---

## 2. Tech Stack Aktual

Versi dan stack harus mengikuti kondisi repo saat ini, bukan blueprint lama.

### Backend

```text
PHP            ^8.2
Laravel        ^12.0
MySQL          8.0+
```

### Frontend (Web)

```text
Bootstrap      5.x
jQuery         3.x
Chart.js       4.x
SweetAlert2    11.x
DataTables     (via Yajra Laravel DataTables)
```

### Mobile

```text
Flutter        (latest stable)
Dart           (latest stable)
```

### Composer Packages Penting

```text
spatie/laravel-permission
laravel/sanctum
barryvdh/laravel-dompdf
maatwebsite/excel
guzzlehttp/guzzle
yajra/laravel-datatables
laravel/pint (dev)
```

### Integrasi Eksternal

```text
Duitku         Payment gateway
FCM            Push notification untuk Flutter
```

### Prinsip Umum

- Selalu cek `composer.json`, `package.json`, `config/`, dan struktur `app/` sebelum menyimpulkan arsitektur.
- Jangan mengasumsikan package atau versi yang belum terdaftar di repo.
- Shared hosting constraints: tidak ada Redis, tidak ada websocket, queue via database driver.

---

## 3. Struktur Proyek

Struktur utama yang perlu dipahami:

```text
app/
  Console/Commands/              Command terjadwal
  Contracts/Repositories/        Interface repository
  DataTransferObjects/           DTO per domain
  Enums/                         Enum status, tipe, dan opsi domain
  Events/                        Domain events
  Exports/                       Export Excel/CSV
  Http/
    Controllers/
      Owner/                     Controller owner panel
      Admin/                     Controller admin panel
      Api/                       Controller API untuk Flutter
    Middleware/                   Mosque resolver, permission gate, owner gate
    Requests/
      Owner/                     Form request owner
      Admin/                     Form request admin
      Api/                       Form request API
    Resources/                   API Resource (JSON transformation)
  Jobs/                          Background jobs
  Listeners/                     Event listeners
  Mail/                          Mailables
  Models/                        Eloquent models
  Notifications/                 Notification classes
  Policies/                      Authorization policy
  Providers/                     Service provider dan binding
  Repositories/                  Implementasi repository
  Services/                      Business logic per domain
  Traits/                        Trait reusable seperti mosque scope

config/
  duitku.php
  permission.php
  queue.php

resources/views/
  owner/                         View owner panel
  admin/                         View admin panel
  components/
    owner/                       Komponen blade owner
    admin/                       Komponen blade admin

routes/
  web.php
  api.php
  console.php

tests/
  Feature/Owner/
  Feature/Admin/
  Feature/Api/
  Feature/Console/
  Unit/Services/
  Unit/Jobs/

docs/
```

### Domain Utama

- Mosque (profil, onboarding, approval)
- Schedule (jadwal sholat, kegiatan)
- Finance (kas masjid: pemasukan, pengeluaran, laporan)
- Donation (donasi online via Duitku, donasi manual, fee platform)
- Announcement (pengumuman + push notification)
- Congregation (manajemen jamaah)
- Staff (manajemen pengurus, assign permission)
- Platform settings (fee, mekanisme fee, global config)

---

## 4. Prinsip Arsitektur

Arsitektur utama proyek ini:

```text
Controller
  -> Service
    -> Repository
      -> Model / Query Builder
```

### Aturan Per Lapisan

| Lapisan | Boleh | Tidak Boleh |
|---------|-------|-------------|
| Controller | menerima request, authorize, panggil service, return response/view | business logic berat, query langsung ke DB/model |
| Form Request | validasi dan authorization HTTP | business logic domain |
| Service | business logic, orchestration, transaction, event dispatch, job dispatch | menerima `Request` mentah, return view/redirect, query acak lintas model tanpa repository |
| Repository | query Eloquent, filter, pagination, aggregate | business rule, event dispatch, notifikasi |
| Model | relasi, cast, scope, accessor sederhana | business flow lintas entitas |

### Dependency Injection

Selalu inject interface repository, bukan implementasi konkret.

```php
class DonationService
{
    public function __construct(
        private readonly DonationRepositoryInterface $donations,
        private readonly FinanceRepositoryInterface $finances,
    ) {}
}
```

Jangan lakukan ini:

```php
class DonationService
{
    public function __construct(
        private readonly DonationRepository $donations,
    ) {}
}
```

### DTO Wajib untuk Input Eksternal ke Service

Service yang menerima data dari controller, request, callback, atau action harus memakai DTO.

Benar:

```php
public function createDonation(CreateDonationDTO $dto): Donation
{
    // ...
}
```

Salah:

```php
public function createDonation(array $data): Donation {}
public function createDonation(Request $request): Donation {}
```

### Transaksi Database

Gunakan `DB::transaction()` untuk operasi multi-tabel atau state transition penting, seperti:

- konfirmasi donasi + catat ke kas masjid
- approval pendaftaran masjid
- perubahan admin masjid
- proses callback payment gateway

---

## 5. Aturan Multi-Tenant

Ini bagian paling sensitif di codebase.

### Tenant Aktif (Mosque)

Mosque aktif di-resolve oleh middleware `IdentifyMosque` dan disimpan di container aplikasi.

Pola akses:

```php
$mosque = app('current_mosque');
$mosqueId = mosque_id();
```

### Trait `BelongsToMosque`

Model mosque-scoped harus menggunakan trait ini agar:

- query otomatis ter-filter mosque aktif
- `mosque_id` otomatis terisi saat create jika context mosque tersedia

Model mosque-scoped wajib mengikuti pola ini:

- `Schedule`
- `Activity`
- `CashTransaction`
- `Donation`
- `Announcement`
- `MosqueUser` (jamaah yang join masjid)
- `Staff`

### Aturan Query Repository

Benar:

```php
public function findOrFail(int $id): Donation
{
    return Donation::findOrFail($id);
}
```

Benar untuk bypass yang disengaja:

```php
public function findByIdForMosque(int $id, int $mosqueId): ?Donation
{
    return Donation::withoutGlobalScopes()
        ->where('id', $id)
        ->where('mosque_id', $mosqueId)
        ->first();
}
```

Salah:

```php
public function find(int $id): ?object
{
    return DB::table('donations')->find($id);
}
```

### Owner Panel dan Cross-Mosque Access

Owner panel memang boleh melihat data lintas masjid, tetapi harus eksplisit dan sadar.

Gunakan:

- `withoutGlobalScopes()` jika memang perlu
- query owner-only di service/repository yang jelas konteksnya
- middleware `EnsureOwnerAccess`

Jangan melakukan bypass scope mosque di admin panel tanpa alasan yang sangat jelas.

---

## 6. Owner Panel vs Admin Panel vs API

### Owner Panel (`/owner`)

Area internal platform untuk Super Admin:

- approve/reject pendaftaran masjid
- kelola semua masjid (nonaktifkan, statistik)
- setting global (fee donasi, mekanisme fee)
- dashboard platform (total masjid, jamaah, pendapatan fee)
- kelola akun owner lain

Aturan:

- akses lewat middleware `EnsureOwnerAccess`
- permission owner terpisah dari permission mosque
- UI: Blade + Bootstrap 5 + jQuery + DataTables

### Admin Panel (`/admin`)

Area untuk Admin Masjid dan Pengurus:

- profil masjid
- jadwal sholat & kegiatan
- keuangan (kas)
- donasi online
- pengumuman
- manajemen jamaah
- manajemen pengurus (hanya admin)
- setting masjid (rekening, kategori donasi)

Aturan:

- middleware mosque: `mosque`, `mosque.ownership`, `mosque.active`
- gunakan Form Request untuk validasi dan authorize
- admin punya akses penuh, pengurus punya permission configurable
- UI: Blade + Bootstrap 5 + jQuery + DataTables

### API (untuk Flutter)

Endpoint REST untuk jamaah via mobile app:

- auth (register, login, logout via Sanctum)
- list & join masjid (search, QR, link, GPS)
- switch masjid aktif
- lihat jadwal, pengumuman, laporan keuangan (read only)
- donasi online (Duitku)
- riwayat donasi
- profil jamaah
- push notification (FCM token registration)

Aturan:

- auth via Laravel Sanctum (token-based)
- response konsisten via API Resource
- error response format standar
- pagination format standar
- API controller tetap tipis, panggil service yang sama dengan web

---

## 7. Konvensi Penamaan

### Class dan File

| Tipe | Konvensi | Contoh |
|------|----------|--------|
| Model | PascalCase | `Mosque`, `Donation`, `CashTransaction` |
| Controller | PascalCase + Controller | `DonationController`, `ScheduleController` |
| DTO | PascalCase + DTO | `CreateDonationDTO`, `ApproveMosqueDTO` |
| Repository Interface | PascalCase + RepositoryInterface | `DonationRepositoryInterface` |
| Repository | PascalCase + Repository | `DonationRepository` |
| Service | PascalCase + Service | `DonationService`, `FinanceService` |
| Request | VerbNounRequest | `StoreDonationRequest`, `UpdateScheduleRequest` |
| Job | PascalCase + Job | `ProcessDuitkuCallbackJob` |
| Enum | PascalCase | `DonationStatus`, `TransactionType` |
| Notification | PascalCase + Notification | `DonationReceivedNotification` |
| API Resource | PascalCase + Resource | `DonationResource`, `MosqueResource` |

### Namespace Domain

Ikuti namespace aktual repo. Contoh:

- `App\Services\Finance\...`
- `App\Services\Donation\...`
- `App\DataTransferObjects\Donation\...`
- `App\Http\Controllers\Owner\...`
- `App\Http\Controllers\Admin\...`
- `App\Http\Controllers\Api\...`

Semua nama domain dalam bahasa Inggris. Jangan gunakan nama domain bahasa Indonesia di namespace.

### Method Naming

Pilih nama method yang deskriptif dan berorientasi use case:

```php
approveMosque()
rejectMosque()
confirmDonation()
createCashIncome()
createCashExpense()
publishAnnouncement()
assignPermissions()
```

Hindari nama yang terlalu umum:

```php
process()
handleData()
doAction()
saveThing()
```

### Route Naming

Format route berbasis area:

- `owner.*` untuk owner panel
- `admin.*` untuk admin panel
- `api.*` untuk REST API

Contoh:

```text
owner.mosques.index
owner.mosques.approve
owner.settings.fee
admin.finance.income.store
admin.donations.index
admin.announcements.publish
api.mosques.join
api.donations.create
api.schedules.index
```

---

## 8. Alur Pengembangan Fitur Baru

Ikuti urutan ini sebisa mungkin:

1. Tentukan domain dan apakah fitur milik owner, admin, atau API.
2. Tentukan mosque-safety dan permission yang dibutuhkan.
3. Buat atau ubah migration.
4. Buat enum jika ada state/tipe yang jelas.
5. Buat atau ubah model beserta relasi, cast, trait.
6. Buat DTO untuk input service.
7. Tambahkan interface repository bila menyentuh akses data baru.
8. Implementasikan repository.
9. Implementasikan service.
10. Tambahkan Form Request.
11. Tambahkan controller (owner/admin/API sesuai kebutuhan).
12. Tambahkan API Resource jika fitur di-expose ke Flutter.
13. Daftarkan route.
14. Tambahkan event, listener, notification, atau job bila diperlukan.
15. Tambahkan atau perbarui test.
16. Update dokumentasi bila perilaku sistem berubah.
17. Pastikan binding repository terdaftar di `RepositoryServiceProvider`.

### Wajib Ditentukan di Awal

Sebelum coding, jawab minimal ini:

- fitur ini mosque-scoped atau platform-scoped (owner)?
- apakah butuh `mosque_id`?
- permission apa yang dibutuhkan?
- apakah ada state transition yang butuh event atau notification?
- apakah ada proses lambat yang harus di-queue?
- apakah fitur ini perlu di-expose ke API (Flutter)?

---

## 9. Aturan Database dan Migration

### Tabel Mosque-Scoped

Jika tabel menyimpan data masjid, wajib punya kolom:

```php
$table->foreignId('mosque_id')->constrained()->cascadeOnDelete();
```

Tambahkan index yang relevan terhadap pola query.

### Nilai Uang

Gunakan integer atau unsigned big integer untuk nominal Rupiah, bukan decimal.

Benar:

```php
$table->unsignedBigInteger('amount')->default(0);
```

Salah:

```php
$table->decimal('amount', 15, 2);
```

### Enum dan Status

Jika status domain terbatas dan dimodelkan di PHP enum, pastikan database dan cast model konsisten.

Enum yang diharapkan:

- `MosqueStatus` (pending, active, suspended, rejected)
- `DonationStatus` (pending, confirmed, failed, expired)
- `DonationCategory` (infaq, zakat, sadaqah, waqf)
- `TransactionType` (income, expense)
- `AnnouncementStatus` (draft, published, archived)
- `ActivityStatus` (upcoming, ongoing, completed, cancelled)

### Fee Mechanism

Fee platform disimpan sebagai setting global:

- `platform_fee_percentage` — persentase fee (integer, basis poin: 250 = 2.5%)
- `platform_fee_mechanism` — enum: `added_to_donor` atau `deducted_from_donation`

### Migration Rules

- method `down()` harus valid
- jangan lupa foreign key dan index yang relevan
- jika menambah tabel permission/team scope, perhatikan config Spatie
- jika migration memengaruhi query besar, pertimbangkan dampak performa

---

## 10. Authorization dan Permissions

### Prinsip Dasar

- Jangan hardcode role name sebagai syarat akses di controller.
- Gunakan permission check atau policy.
- Role adalah wadah permission, bukan sumber logika akses langsung.
- Gunakan Spatie laravel-permission dengan team scope (`mosque_id`).

### Format Permission

Pola permission memakai format:

```text
{resource}.{action}
{resource}.{subresource}.{action}
```

Contoh:

```text
mosque.edit
schedule.view
schedule.create
schedule.edit
schedule.delete
finance.view
finance.create_income
finance.create_expense
finance.export
donation.view
donation.confirm
announcement.view
announcement.create
announcement.publish
announcement.delete
congregation.view
staff.view
staff.manage
```

### Role Definitions

| Role | Scope | Permission |
|------|-------|------------|
| `super-admin` | Platform (global) | Semua akses owner panel |
| `mosque-admin` | Per masjid | Semua permission dalam satu masjid |
| `staff` | Per masjid | Permission configurable per individu |
| `congregation` | Per masjid (via API) | Read-only + donasi |

### Spatie Teams

Project ini memakai Spatie Permission dengan team/mosque scope.

Artinya:

- permission staff dievaluasi dalam konteks mosque aktif
- jamaah yang join banyak masjid punya konteks terpisah per masjid
- seeder permission dan role harus mempertimbangkan `mosque_id` sebagai team foreign key

### Tempat Authorization

Gunakan salah satu atau kombinasi:

- `FormRequest::authorize()`
- `$this->authorize(...)` di controller
- route middleware `can:*`
- middleware `EnsureOwnerAccess` untuk owner panel

Jangan meletakkan logika permission di Blade atau JavaScript sebagai satu-satunya pertahanan.

---

## 11. Events, Notifications, Queue

### Kapan Wajib Fire Event

State transition penting sebaiknya memicu event:

- mosque approved atau rejected
- donation confirmed atau failed
- cash transaction created
- announcement published
- jamaah joined mosque
- payment callback processed

### Notification Rules

Gunakan channel yang sesuai:

- in-app via database notification
- push notification via FCM (untuk jamaah di Flutter)
- email via mail + queue (untuk admin)

Pola yang diutamakan: `service -> event -> listener/job -> notification`

Jangan kirim notifikasi langsung dari controller kecuali sangat sederhana.

### Jobs yang Wajar Di-Queue

Gunakan queue (database driver) untuk:

- proses callback Duitku
- kirim push notification FCM
- generate PDF laporan keuangan
- export CSV/Excel
- kirim email

### Scheduler

Scheduler aktif di `routes/console.php` untuk proses seperti:

- expire donation yang pending terlalu lama
- kirim reminder ke admin masjid (laporan bulanan)

Jika menambah automation baru:

- daftarkan schedule dengan waktu yang jelas
- gunakan `withoutOverlapping()` bila relevan

---

## 12. Testing

### Struktur Test

```text
tests/
  Feature/Owner/
  Feature/Admin/
  Feature/Api/
  Feature/Console/
  Unit/Services/
  Unit/Jobs/
  Traits/
    CreatesTestMosque.php
```

### Aturan Test

1. Feature test admin harus membuat fixture mosque dan set mosque aktif.
2. Feature test API harus menggunakan Sanctum `actingAs` dengan token.
3. Test akses harus memverifikasi mosque ownership dan permission.
4. Unit test service harus memfokuskan business rules utama.
5. Payment callback wajib punya test khusus.

### Minimal Test yang Diharapkan

Untuk fitur baru, usahakan ada:

- minimal satu feature test happy path
- minimal satu test authorization atau access denial
- minimal satu test edge case atau invalid state

---

## 13. Dokumentasi

Dokumentasi proyek tersebar di:

- `README.md` sebagai entry point
- `docs/AGENTS.md` ini sebagai aturan kerja
- `docs/SKILLS.md` sebagai katalog pattern implementasi
- `docs/copywriting.md` sebagai kamus istilah UI
- `docs/api-contract.md` sebagai kontrak API untuk Flutter
- `docs/database-design.md` sebagai ERD dan penjelasan tabel
- `docs/deployment.md` sebagai panduan deploy ke shared hosting

Aturan dokumentasi:

- jika perubahan memengaruhi arsitektur atau workflow penting, update dokumentasi terkait
- jangan tulis dokumentasi yang bertentangan dengan implementasi aktual repo
- bedakan jelas antara "sudah ada" dan "rencana"
- gunakan namespace dan istilah domain bahasa Inggris yang dipakai repo

### Aturan Copywriting

- Owner panel: istilah teknis boleh, label UI dalam bahasa Indonesia
- Admin panel: istilah ramah untuk pengurus masjid, bahasa Indonesia
- API/Mobile: istilah ramah untuk jamaah, bahasa Indonesia

Detail lengkap di `docs/copywriting.md`.

---

## 14. Anti-Pattern yang Dilarang

### Fat Controller

Jangan menaruh query, approval flow, fee calculation, notification, dan audit sekaligus di controller.

### Query di Blade

Jangan lakukan query model langsung di view.

### `DB::` di Controller atau Service Tanpa Alasan Kuat

Query data sebaiknya lewat repository. `DB::transaction()` di service boleh. `DB::table()` sembarangan tidak boleh.

### Request Langsung ke Service

Jangan inject `Request` ke service sebagai payload domain.

### Hardcode Mosque atau Role

Jangan hardcode `mosque_id = 1` atau cek `hasRole('Admin')` sebagai basis akses.

### Bypass Mosque Scope Diam-Diam

`withoutGlobalScopes()` hanya boleh dipakai dengan sadar dan untuk konteks yang jelas, terutama owner panel.

### Notifikasi Sync di HTTP Flow Jika Bisa Di-Queue

Email, generate file, callback processing, dan push notification jangan membebani request utama.

### Inconsistent API Response

Semua API endpoint harus menggunakan format response yang sama (API Resource + standar error format). Jangan return array mentah atau mixed format.

---

## 15. Aturan Interaksi Aksi Backend

Aturan ini berlaku untuk tombol, link bergaya tombol, form submit, dan aksi UI lain yang memicu proses backend di owner panel maupun admin panel.

### Wajib untuk Aksi yang Memicu Backend

Jika suatu aksi melakukan request backend mutatif (`POST`, `PUT`, `PATCH`, `DELETE`), implementasi wajib memenuhi:

1. tampilkan konfirmasi SweetAlert sebelum request dikirim
2. setelah user menekan konfirmasi, tombol berubah menjadi `Memproses...`
3. tombol harus `disabled` sampai request selesai

### Contoh Aksi yang Wajib Mengikuti Aturan Ini

- simpan form create atau edit
- approve/reject pendaftaran masjid
- publish/unpublish pengumuman
- konfirmasi donasi manual
- hapus data
- assign/revoke permission pengurus

### Pengecualian

Tidak wajib untuk:

- navigasi `GET` biasa
- filter/pencarian
- export/download read-only

### Implementasi

- Utamakan pola `form[data-confirm="true"]` dengan handler global di layout
- Gunakan atribut `data-confirm-title`, `data-confirm-text`, `data-confirm-button`, `data-processing-text`
- Jangan tambah script konfirmasi lokal jika pola global bisa dipakai

---

## 16. Checklist Sebelum Commit

### Arsitektur

- [ ] Tidak ada query bisnis berat di controller atau Blade
- [ ] Service tidak menerima `Request` mentah
- [ ] Input eksternal ke service sudah dibungkus DTO
- [ ] Repository interface di-inject, bukan implementasi
- [ ] API endpoint menggunakan API Resource untuk response

### Multi-Tenant Safety

- [ ] Model mosque-scoped memakai `BelongsToMosque` trait
- [ ] Migration mosque-scoped punya `mosque_id`
- [ ] Tidak ada query yang berisiko bocor lintas masjid
- [ ] Bypass global scope hanya dilakukan secara eksplisit dan sah

### Authorization

- [ ] Controller, request, atau route sudah memproteksi aksi baru
- [ ] Tidak ada hardcode cek role sebagai sumber otorisasi utama
- [ ] Permission name konsisten dengan pola `{resource}.{action}`
- [ ] API endpoint terproteksi Sanctum + permission check

### Data dan Domain

- [ ] Nilai uang disimpan sebagai integer
- [ ] Status domain konsisten antara enum, database, dan cast model
- [ ] Operasi multi-tabel kritikal dibungkus transaction
- [ ] Fee platform terhitung dengan benar di flow donasi

### Event, Notification, Queue

- [ ] State transition penting sudah memicu event
- [ ] Proses lambat atau I/O berat sudah di-queue
- [ ] Push notification (FCM) dikirim via queue, bukan sync
- [ ] Notifikasi tidak dikirim langsung dari controller

### Testing

- [ ] Ada test untuk happy path
- [ ] Ada test untuk authorization atau denial path
- [ ] Ada test untuk edge case penting
- [ ] Test lama yang relevan tetap lulus

### Dokumentasi dan Kebersihan Kode

- [ ] Dokumentasi diperbarui jika perlu
- [ ] Tidak ada `dd()`, `dump()`, `var_dump()` tertinggal
- [ ] Tidak ada secret atau credential di repo
- [ ] Naming, namespace, dan struktur file konsisten
- [ ] Semua aksi UI mutatif punya konfirmasi SweetAlert
- [ ] Tombol aksi berubah ke state proses setelah dikonfirmasi
- [ ] Tombol di-disable selama request berjalan

---

## Penutup

Dokumen ini harus dijaga tetap sinkron dengan implementasi proyek. Jika struktur repo, domain, atau aturan inti berubah, update `AGENTS.md` agar agent dan developer berikutnya tidak bekerja dengan asumsi lama.
