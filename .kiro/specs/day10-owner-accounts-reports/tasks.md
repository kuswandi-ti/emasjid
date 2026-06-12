# Implementation Plan: Day 10 — Kelola Akun Owner & Laporan Fee

## Overview

Implementasi ini mencakup dua area fungsional di Owner Panel EMasjid:

1. **Kelola Akun Owner** — CRUD penuh untuk akun `super-admin` lain menggunakan arsitektur Controller → Service → Repository → Model, DataTable server-side (Yajra), Form Request untuk validasi, dan SweetAlert untuk konfirmasi penghapusan.
2. **Laporan Fee Platform** — Halaman laporan fee bulanan dengan ringkasan statistik, bar chart 12 bulan (Chart.js), dan ekspor CSV menggunakan `StreamedResponse`.

Semua kode berada di bawah namespace `App\Http\Controllers\Owner\` dengan middleware `auth:web` + role `super-admin`.

---

## Tasks

- [x] 1. Siapkan contracts, model scope, dan registrasi service provider
  - [x] 1.1 Buat interface `UserRepositoryInterface`
    - Buat file `app/Contracts/Repositories/UserRepositoryInterface.php`
    - Deklarasikan method: `ownersQueryBuilder()`, `find()`, `existsByEmail()`, `create()`, `update()`, `delete()`
    - _Requirements: 1.1, 1.2, 2.2, 3.2, 4.3_

  - [x] 1.2 Buat interface `FeeReportRepositoryInterface`
    - Buat file `app/Contracts/Repositories/FeeReportRepositoryInterface.php`
    - Deklarasikan method: `getSummaryByPeriod()`, `getMonthlyTrend()`, `getConfirmedByPeriod()`
    - _Requirements: 5.1, 5.3, 6.1, 7.2_

  - [x] 1.3 Tambahkan scope `scopeConfirmed()` dan `scopeInPeriod()` ke model `Donation`
    - Edit file `app/Models/Donation.php`
    - Tambahkan `scopeConfirmed(Builder $query): Builder` — filter `status = 'confirmed'`
    - Tambahkan `scopeInPeriod(Builder $query, int $month, int $year): Builder` — filter `confirmed_at` bulan dan tahun
    - _Requirements: 5.3, 6.1, 7.2_

  - [x] 1.4 Daftarkan binding interface → implementasi di `AppServiceProvider`
    - Edit `app/Providers/AppServiceProvider.php`
    - Bind `UserRepositoryInterface` → `UserRepository`
    - Bind `FeeReportRepositoryInterface` → `FeeReportRepository`
    - _Requirements: 1.1, 5.1_

- [x] 2. Implementasi layer Repository
  - [x] 2.1 Buat `UserRepository`
    - Buat file `app/Repositories/UserRepository.php` yang mengimplementasi `UserRepositoryInterface`
    - Implementasikan `ownersQueryBuilder()` — query `User` dengan `whereHas('roles')` scope ke `super-admin`, select kolom `id, name, email, created_at`
    - Implementasikan `find()`, `existsByEmail()`, `create()`, `update()`, `delete()` (soft delete)
    - _Requirements: 1.2, 1.4, 2.2, 3.2, 4.3_

  - [x] 2.2 Buat `FeeReportRepository`
    - Buat file `app/Repositories/FeeReportRepository.php` yang mengimplementasi `FeeReportRepositoryInterface`
    - Implementasikan `getSummaryByPeriod()` — `COALESCE(SUM(fee_amount), 0)` dan `COUNT(*)` via scope `confirmed()` + `inPeriod()`
    - Implementasikan `getMonthlyTrend()` — bangun grid 12 slot penuh di PHP, query satu kali, gabungkan (bulan tanpa data = 0)
    - Implementasikan `getConfirmedByPeriod()` — eager-load relasi `mosque:id,name`, order by `confirmed_at`
    - _Requirements: 5.1, 5.3, 6.1, 6.3, 7.2_

- [x] 3. Implementasi layer Service
  - [x] 3.1 Buat `UserService`
    - Buat file `app/Services/UserService.php`
    - Implementasikan `getOwnerQueryBuilder()` — delegasi ke `userRepo->ownersQueryBuilder()`
    - Implementasikan `createOwner()` — cek duplikasi email, hash password, `create()`, `assignRole('super-admin')`; lempar `\InvalidArgumentException` jika email sudah ada
    - Implementasikan `updateOwner()` — cek self-edit (lempar `\DomainException`), cek email duplikat, update password hanya jika field tidak kosong
    - Implementasikan `deleteOwner()` — cek self-delete (lempar `\DomainException`), `removeRole()`, kemudian `delete()`
    - _Requirements: 2.2, 2.3, 2.4, 3.2, 3.3, 3.4, 3.7, 4.3, 4.4_

  - [x] 3.2 Tulis property test untuk `UserService` — Property 2, 4, 5
    - Buat file `tests/Unit/Services/UserServiceTest.php`
    - **Property 2: Validasi Email Duplikat (Create)** — loop 100 iterasi, email yang sudah `existsByEmail = true` harus selalu throw `\InvalidArgumentException` dengan pesan "Email sudah terdaftar."
    - **Validates: Requirements 2.3**
    - **Property 4: Password Dipertahankan saat Field Kosong** — loop 100 iterasi, `update` dengan `password = ''` tidak boleh menyertakan key `password` dalam data yang dikirim ke repository
    - **Validates: Requirements 3.4**
    - **Property 5 (self-edit): Cegah Self-Edit** — loop 100 iterasi, `userId === currentUserId` harus selalu throw `\DomainException`
    - **Property 5 (self-delete): Cegah Self-Delete** — loop 100 iterasi, sama dengan di atas untuk `deleteOwner()`
    - **Validates: Requirements 3.7, 4.4**

  - [x] 3.3 Buat `FeeReportService`
    - Buat file `app/Services/FeeReportService.php`
    - Implementasikan `getSummary()` — delegasi ke `reportRepo->getSummaryByPeriod()`
    - Implementasikan `getChartData()` — ambil 12 elemen dari `getMonthlyTrend()`, petakan ke `labels` dan `values`
    - Implementasikan `exportCsv()` — buat `StreamedResponse` dengan BOM UTF-8, tulis header CSV, tulis baris data via `fputcsv()`; nama file `laporan-fee-{YYYY}-{MM}.csv`
    - _Requirements: 5.1, 5.3, 6.1, 7.2, 7.3_

  - [x] 3.4 Tulis property test untuk `FeeReportService` — Property 6, 8
    - Buat file `tests/Unit/Services/FeeReportServiceTest.php`
    - **Property 6: Kalkulasi Ringkasan Laporan** — loop 100 iterasi dengan `count` dan `total_fee` acak, verifikasi `average = total_fee / count` (atau 0 jika count = 0)
    - **Validates: Requirements 5.1, 5.3**
    - **Property 8: Chart Selalu 12 Data Point** — loop 100 iterasi dengan trend acak, `getChartData()` harus selalu kembalikan `labels` dan `values` dengan tepat 12 elemen
    - **Validates: Requirements 6.1, 6.3**

- [x] 4. Checkpoint — Pastikan semua unit test lulus
  - Jalankan `php artisan test --filter=UserServiceTest` dan `php artisan test --filter=FeeReportServiceTest`
  - Pastikan semua test lulus, tanyakan ke user jika ada pertanyaan.

- [x] 5. Implementasi Form Requests, Controller, dan Routes
  - [x] 5.1 Buat `StoreOwnerRequest`
    - Buat file `app/Http/Requests/StoreOwnerRequest.php`
    - `authorize()` — return `$this->user()?->hasRole('super-admin')`
    - `rules()` — `name`: required string max:255; `email`: required email max:255; `password`: required string min:8
    - `messages()` — pesan validasi berbahasa Indonesia sesuai desain
    - _Requirements: 2.1, 2.3, 2.4, 2.6_

  - [x] 5.2 Buat `UpdateOwnerRequest`
    - Buat file `app/Http/Requests/UpdateOwnerRequest.php`
    - `authorize()` — return `$this->user()?->hasRole('super-admin')`
    - `rules()` — `name`: required string max:255; `email`: required email unique ignore `$userId`; `password`: nullable string min:8
    - `messages()` — pesan validasi berbahasa Indonesia sesuai desain
    - _Requirements: 3.1, 3.5, 3.6_

  - [x] 5.3 Buat `UserController`
    - Buat file `app/Http/Controllers/Owner/UserController.php`
    - `index()` — jika AJAX, kembalikan JSON Yajra DataTables dari `getOwnerQueryBuilder()`; jika bukan, kembalikan view `owner.users.index`
    - `create()` — kembalikan view `owner.users.create`
    - `store()` — panggil `userService->createOwner()`, redirect ke `owner.users.index` dengan flash `success`; tangkap `\InvalidArgumentException` → redirect back dengan flash `error`
    - `edit()` — kembalikan view `owner.users.edit` dengan `$user`
    - `update()` — panggil `userService->updateOwner()` dengan `Auth::id()`; redirect ke `owner.users.index` dengan flash `success`; tangkap exception → redirect back
    - `destroy()` — panggil `userService->deleteOwner()` dengan `Auth::id()`; redirect ke `owner.users.index` dengan flash `success`; tangkap `\DomainException` → redirect back dengan flash `error`
    - _Requirements: 1.1, 1.4, 2.2, 2.5, 3.2, 3.6, 3.7, 4.1, 4.4, 4.5_

  - [x] 5.4 Buat `ReportController`
    - Buat file `app/Http/Controllers/Owner/ReportController.php`
    - `feeIndex()` — baca `month` dan `year` dari query string (default `now()->month` / `now()->year`), panggil `getSummary()` dan `getChartData()`, kembalikan view `owner.reports.fee`
    - `feeExport()` — baca `month` dan `year`, kembalikan `StreamedResponse` dari `exportCsv()`
    - _Requirements: 5.1, 5.2, 5.5, 6.1, 7.1, 7.5_

  - [x] 5.5 Daftarkan routes di `routes/web.php`
    - Tambahkan grup route dengan middleware `auth:web` dan role `super-admin` (middleware `owner` atau `role:super-admin`)
    - Resource route `owner/users` → `Owner\UserController` (index, create, store, edit, update, destroy), named prefix `owner.users`
    - Route GET `owner/reports/fee` → `Owner\ReportController@feeIndex`, name `owner.reports.fee.index`
    - Route GET `owner/reports/fee/export` → `Owner\ReportController@feeExport`, name `owner.reports.fee.export`
    - _Requirements: 1.4, 2.6, 3.6, 4.1, 5.5, 7.5_

- [x] 6. Buat Blade views untuk Kelola Akun Owner
  - [x] 6.1 Buat view `owner/users/index.blade.php`
    - Extends layout owner panel yang ada
    - Sertakan tabel DataTables dengan kolom: Nama, Email, Tanggal Dibuat, Aksi
    - Inisialisasi DataTables dengan server-side processing ke route `owner.users.index` (AJAX)
    - Tampilkan flash message `success` dan `error` menggunakan alert Bootstrap 5
    - Tombol "Tambah Akun" menuju `owner.users.create`
    - _Requirements: 1.1, 1.2, 1.3_

  - [x] 6.2 Buat partial `owner/users/_action.blade.php`
    - Tampilkan tombol Edit (link ke `owner.users.edit`) dan tombol Hapus
    - Tombol Hapus memicu dialog konfirmasi SweetAlert sebelum submit form `DELETE`
    - _Requirements: 4.1, 4.2_

  - [x] 6.3 Buat view `owner/users/create.blade.php`
    - Form dengan field: Nama, Email, Password
    - Tampilkan `@error` per field untuk pesan validasi
    - Tombol simpan submit ke `owner.users.store` (POST)
    - _Requirements: 2.1, 2.3, 2.4_

  - [x] 6.4 Buat view `owner/users/edit.blade.php`
    - Form dengan field yang sudah ter-populate: Nama (`$user->name`), Email (`$user->email`), Password (kosong/opsional)
    - Tampilkan `@error` per field
    - Tombol simpan submit ke `owner.users.update` (PUT)
    - _Requirements: 3.1, 3.3, 3.4, 3.5_

- [x] 7. Buat Blade view untuk Laporan Fee
  - [x] 7.1 Buat view `owner/reports/fee.blade.php`
    - Extends layout owner panel yang ada
    - Filter form dengan dropdown bulan, input tahun, dan tombol "Filter" (GET ke `owner.reports.fee.index`)
    - Ringkasan statistik: Total Fee, Jumlah Donasi, Rata-rata Fee (format Rupiah menggunakan `number_format`)
    - Tampilkan pesan "Belum ada data fee pada periode ini." jika `$summary['count'] === 0`
    - Tombol "Export CSV" sebagai link ke `owner.reports.fee.export` dengan query string `month` dan `year` terpilih
    - Kanvas `<canvas id="feeChart">` untuk Chart.js
    - Inisialisasi Chart.js bar chart menggunakan `@json($chartData['labels'])` dan `@json($chartData['values'])`; label sumbu Y diformat sebagai Rupiah
    - _Requirements: 5.1, 5.2, 5.4, 6.1, 6.2, 6.3, 6.4, 7.1_

- [x] 8. Checkpoint — Pastikan semua unit test dan feature test lulus
  - Jalankan `php artisan test --filter=UserFeatureTest` dan `php artisan test --filter=FeeReportFeatureTest`
  - Pastikan semua test lulus, tanyakan ke user jika ada pertanyaan.

- [x] 9. Tulis feature tests
  - [x] 9.1 Tulis property feature test untuk `UserFeatureTest` — Property 1, 3, 4, 5
    - Buat file `tests/Feature/Owner/UserFeatureTest.php`
    - **Property 1: DataTable Hanya Menampilkan Owner** — 30 iterasi, buat N super-admin + M non-super-admin acak, verifikasi `recordsTotal === N + 1`
    - **Validates: Requirements 1.2**
    - **Property 3: Validasi Password Pendek** — uji setiap panjang 1–7 (× 10 variasi), pastikan `assertSessionHasErrors('password')`
    - **Validates: Requirements 2.4**
    - **Property 4: Password Dipertahankan (feature)** — 50 iterasi, update tanpa password baru, `Hash::check()` password lama harus tetap berhasil
    - **Validates: Requirements 3.4**
    - **Property 5: Cegah Self-Edit dan Self-Delete** — 50 iterasi masing-masing, `assertSessionHas('error')` dan user tidak terhapus
    - **Validates: Requirements 3.7, 4.4**
    - Tambahkan contoh test: redirect setelah create, form edit ter-populate dengan nilai tersimpan
    - **Validates: Requirements 2.5, 3.1**

  - [x] 9.2 Tulis property feature test untuk `FeeReportFeatureTest` — Property 6, 7, 9, 10
    - Buat file `tests/Feature/Owner/FeeReportFeatureTest.php`
    - **Property 6: Kalkulasi Ringkasan Laporan** — 50 iterasi, buat N donasi acak di periode M/Y, verifikasi `total_fee` dan `count` di view
    - **Validates: Requirements 5.1, 5.3**
    - **Property 7: Filter Periode Laporan** — 30 iterasi, donasi di bulan lain tidak boleh ikut terhitung (fee besar diabaikan)
    - **Validates: Requirements 5.2, 5.3**
    - **Property 9 & 10: CSV content dan filename** — 30 iterasi, verifikasi jumlah baris (`N + 1`) dan nama file (`laporan-fee-{YYYY}-{MM}.csv`)
    - **Validates: Requirements 7.2, 7.3**
    - Tambahkan edge case: periode kosong tampilkan ringkasan nol, CSV kosong hanya header, non-super-admin mendapat 403
    - **Validates: Requirements 5.4, 7.4, 5.5**

- [x] 10. Final Checkpoint — Pastikan semua test lulus
  - Jalankan `php artisan test` untuk seluruh test suite
  - Pastikan tidak ada test yang gagal, tanyakan ke user jika ada pertanyaan.

---

## Notes

- Task bertanda `*` bersifat opsional dan dapat dilewati untuk pengembangan MVP yang lebih cepat.
- Setiap task mereferensikan requirement spesifik untuk kemudahan penelusuran.
- Checkpoint memastikan validasi inkremental di setiap tahap penting.
- Property tests memvalidasi kebenaran universal; unit tests memvalidasi contoh spesifik dan edge case.
- Semua property test PHP menggunakan pola loop (minimal 100 iterasi untuk unit, 30–50 iterasi untuk feature) karena PHP tidak memiliki library PBT sekelas Hypothesis.

---

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2", "1.3"] },
    { "id": 1, "tasks": ["1.4", "2.1", "2.2"] },
    { "id": 2, "tasks": ["3.1", "3.3"] },
    { "id": 3, "tasks": ["3.2", "3.4", "5.1", "5.2"] },
    { "id": 4, "tasks": ["5.3", "5.4"] },
    { "id": 5, "tasks": ["5.5"] },
    { "id": 6, "tasks": ["6.1", "6.2", "6.3", "6.4", "7.1"] },
    { "id": 7, "tasks": ["9.1", "9.2"] }
  ]
}
```
