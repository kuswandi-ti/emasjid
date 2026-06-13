# Implementation Plan: Mosque Suspend & Reactivate (Day 11)

## Overview

Implementasi fitur suspend dan reactivate masjid pada Owner Panel eMasjid. Alur kerja mengikuti pola Day 8: Form Request → Controller → Service → Event → Listener → Job (FCM). Dua metode baru ditambahkan ke `MosqueService` dan `MosqueController`, dua event dan dua listener queued dibuat, satu job FCM reusable dibuat, serta tampilan `show.blade.php` diperbarui dengan tombol aksi kontekstual per status dan konfirmasi SweetAlert2.

---

## Tasks

- [x] 1. Buat Form Request untuk validasi suspend dan reactivate
  - [x] 1.1 Buat `SuspendMosqueRequest` dengan guard status `active`
    - Buat file `app/Http/Requests/Owner/SuspendMosqueRequest.php`
    - Implementasi `authorize(): bool` (return true; otorisasi via middleware `owner`)
    - Implementasi `rules()`: `mosque_id` required, integer, `Rule::exists('mosques','id')->where('status', MosqueStatus::Active->value)`
    - Implementasi `messages()` dengan pesan bahasa Indonesia sesuai desain
    - _Requirements: 1.7, 1.8_

  - [x] 1.2 Buat `ReactivateMosqueRequest` dengan guard status `suspended`
    - Buat file `app/Http/Requests/Owner/ReactivateMosqueRequest.php`
    - Implementasi `authorize(): bool` dan `rules()` dengan guard `MosqueStatus::Suspended`
    - Implementasi `messages()` dengan pesan bahasa Indonesia sesuai desain
    - _Requirements: 2.7, 2.8_

  - [x] 1.3 Tulis unit test untuk kedua Form Request
    - Test `SuspendMosqueRequest` menolak status `pending`, `suspended`, `rejected`
    - Test `ReactivateMosqueRequest` menolak status `pending`, `active`, `rejected`
    - Test kedua request menerima `mosque_id` dengan status yang benar
    - _Requirements: 1.7, 2.7_

- [x] 2. Tambah metode `suspend()` dan `reactivate()` di `MosqueService`
  - [x] 2.1 Implementasi `MosqueService::suspend(int $mosqueId, int $userId): bool`
    - Edit `app/Services/MosqueService.php`
    - Bungkus seluruh operasi dalam `DB::beginTransaction()` / `DB::commit()` / `DB::rollBack()`
    - Panggil `$this->mosqueRepository->find()`, validasi status `Active`, lalu `update()` ke `Suspended`
    - Panggil `$mosque->refresh()`, lalu `event(new MosqueSuspended(...))`
    - Catat `Log::info("Mosque suspended: mosque_id=..., mosque_name=..., by_user_id=...")` setelah commit
    - Panggil `Cache::forget('owner.dashboard.statistics')` setelah commit
    - _Requirements: 1.3, 1.4, 8.1, 8.3_

  - [x] 2.2 Tulis property test — Property 1: Suspend mengubah status menjadi `suspended`
    - **Property 1: Suspend mengubah status menjadi suspended**
    - **Validates: Requirements 1.3, 1.4**
    - Jalankan 100 iterasi: buat masjid `active` acak, panggil `suspend()`, assert status menjadi `Suspended` dan `updated_at` diperbarui
    - Tag komentar: `// Feature: day11-mosque-suspend-reactivate, Property 1`

  - [x] 2.3 Tulis property test — Property 3: Guard status suspend
    - **Property 3: Guard status — suspend hanya valid dari active**
    - **Validates: Requirements 1.7**
    - Jalankan 100 iterasi: pilih status non-active secara acak, assert `suspend()` melempar exception dan status masjid tidak berubah
    - Tag komentar: `// Feature: day11-mosque-suspend-reactivate, Property 3`

  - [x] 2.4 Implementasi `MosqueService::reactivate(int $mosqueId, int $userId): bool`
    - Edit `app/Services/MosqueService.php`
    - Struktur identik dengan `suspend()` namun memvalidasi status `Suspended` → ubah ke `Active`
    - Dispatch `event(new MosqueReactivated(...))`
    - Catat `Log::info("Mosque reactivated: ...")`
    - Panggil `Cache::forget('owner.dashboard.statistics')`
    - _Requirements: 2.3, 8.2, 8.3_

  - [x] 2.5 Tulis property test — Property 2: Reactivate mengubah status menjadi `active`
    - **Property 2: Reactivate mengubah status menjadi active**
    - **Validates: Requirements 2.3**
    - Jalankan 100 iterasi: buat masjid `suspended` acak, panggil `reactivate()`, assert status menjadi `Active`
    - Tag komentar: `// Feature: day11-mosque-suspend-reactivate, Property 2`

  - [x] 2.6 Tulis property test — Property 4: Guard status reactivate
    - **Property 4: Guard status — reactivate hanya valid dari suspended**
    - **Validates: Requirements 2.7**
    - Jalankan 100 iterasi: pilih status non-suspended secara acak, assert `reactivate()` melempar exception dan status tidak berubah
    - Tag komentar: `// Feature: day11-mosque-suspend-reactivate, Property 4`

  - [x] 2.7 Tulis property test — Property 5: Atomisitas transaksi
    - **Property 5: Atomisitas transaksi — tidak ada perubahan parsial**
    - **Validates: Requirements 1.9, 2.9, 8.3, 8.4**
    - Mock repository agar melempar exception setelah `beginTransaction`, assert rollback sempurna (status tetap tidak berubah)
    - Tag komentar: `// Feature: day11-mosque-suspend-reactivate, Property 5`

  - [x] 2.8 Tulis property test — Property 6 & 7: Audit log suspend dan reactivate
    - **Property 6: Audit log tercatat pada setiap suspend berhasil**
    - **Property 7: Audit log tercatat pada setiap reactivate berhasil**
    - **Validates: Requirements 8.1, 8.2**
    - Gunakan `Log::spy()`, jalankan 100 iterasi suspend dan 100 iterasi reactivate, assert `Log::info` dipanggil dengan format yang benar
    - Tag komentar: `// Feature: day11-mosque-suspend-reactivate, Property 6` dan `Property 7`

- [x] 3. Buat Events dan Listeners

  - [x] 3.1 Buat event `MosqueSuspended`
    - Buat file `app/Events/MosqueSuspended.php`
    - Gunakan trait `Dispatchable`, `SerializesModels`
    - Constructor: `(public Mosque $mosque, public int $actorUserId, public Carbon $actedAt)`
    - _Requirements: 3.1_

  - [x] 3.2 Buat event `MosqueReactivated`
    - Buat file `app/Events/MosqueReactivated.php`
    - Struktur identik dengan `MosqueSuspended`
    - Constructor: `(public Mosque $mosque, public int $actorUserId, public Carbon $actedAt)`
    - _Requirements: 4.1_

  - [x] 3.3 Buat listener `SendMosqueSuspendedNotification` (ShouldQueue)
    - Buat file `app/Listeners/SendMosqueSuspendedNotification.php`
    - Implementasi `ShouldQueue`, gunakan `InteractsWithQueue`, set `public int $tries = 3`
    - Method `handle(MosqueSuspended $event)`: load `FcmToken::where('user_id', $adminUserId)->get()`
    - Guard: return awal (catat `Log::warning`) jika `admin_user_id` null atau tidak punya token
    - Dispatch `SendFcmNotificationJob` per token dengan title "Masjid Anda Ditangguhkan" dan body sesuai desain
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6_

  - [x] 3.4 Buat listener `SendMosqueReactivatedNotification` (ShouldQueue)
    - Buat file `app/Listeners/SendMosqueReactivatedNotification.php`
    - Struktur identik dengan `SendMosqueSuspendedNotification`, handle `MosqueReactivated`
    - Title: "Masjid Anda Diaktifkan Kembali", body sesuai desain
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

  - [x] 3.5 Tulis property test — Property 8 & 9: FCM job dispatch per token
    - **Property 8: Notifikasi FCM dikirim ke semua token admin saat suspend**
    - **Property 9: Notifikasi FCM dikirim ke semua token admin saat reactivate**
    - **Validates: Requirements 3.1, 3.3, 4.1, 4.3**
    - Gunakan `Queue::fake()`, jalankan 100 iterasi dengan `rand(1, 5)` token per admin, assert `SendFcmNotificationJob` di-dispatch tepat N kali
    - Tag komentar: `// Feature: day11-mosque-suspend-reactivate, Property 8` dan `Property 9`

  - [x] 3.6 Tulis unit test untuk listener (edge cases)
    - Test listener tidak dispatch job jika `admin_user_id` null
    - Test listener tidak dispatch job jika admin tidak punya FCM token
    - Test listener mencatat `Log::warning` pada kedua kasus di atas
    - _Requirements: 3.5, 3.6, 4.5_

- [x] 4. Buat Job `SendFcmNotificationJob`

  - [x] 4.1 Buat `SendFcmNotificationJob` dengan integrasi FCM v1 API
    - Buat file `app/Jobs/SendFcmNotificationJob.php`
    - Implementasi `ShouldQueue`, gunakan trait `Dispatchable`, `InteractsWithQueue`, `Queueable`, `SerializesModels`
    - Constructor: `(public string $token, public string $title, public string $body, public array $data = [])`
    - Method `handle()`: ambil access token dari `getFcmAccessToken()`, kirim POST ke `https://fcm.googleapis.com/v1/projects/{projectId}/messages:send`
    - Method `getFcmAccessToken()`: inisialisasi `ServiceAccountCredentials` dari `config('services.fcm.credentials_path')`
    - Tangkap `\Throwable` di `handle()`: catat `Log::error` dengan token terpotong (20 karakter), **jangan re-throw**
    - _Requirements: 3.3, 3.7, 4.3, 4.6_

  - [x] 4.2 Tulis property test — Property 10: FCM failure tidak merusak alur
    - **Property 10: FCM failure tidak merusak alur utama**
    - **Validates: Requirements 3.7, 4.6**
    - Gunakan `Http::fake(['*' => Http::response([], 500)])` dan `Log::spy()`
    - Jalankan 100 iterasi, assert `handle()` tidak melempar exception dan `Log::error` dipanggil sekali
    - Tag komentar: `// Feature: day11-mosque-suspend-reactivate, Property 10`

- [x] 5. Checkpoint — Pastikan semua test unit dan property test lulus
  - Jalankan `php artisan test --filter=day11` atau filter test terkait
  - Pastikan semua test hijau sebelum melanjutkan ke layer berikutnya
  - Tanyakan kepada user jika ada pertanyaan.

- [x] 6. Tambah routes dan daftarkan events di AppServiceProvider

  - [x] 6.1 Tambah dua POST route di `routes/web.php`
    - Tambahkan di dalam group `prefix('owner')` dengan middleware yang sudah ada
    - `Route::post('/mosques/{id}/suspend', [MosqueController::class, 'suspend'])->name('mosques.suspend');`
    - `Route::post('/mosques/{id}/reactivate', [MosqueController::class, 'reactivate'])->name('mosques.reactivate');`
    - _Requirements: 1.3, 2.3_

  - [x] 6.2 Daftarkan event binding baru di `AppServiceProvider`
    - Edit `app/Providers/AppServiceProvider.php`
    - Tambah: `Event::listen(MosqueSuspended::class, SendMosqueSuspendedNotification::class);`
    - Tambah: `Event::listen(MosqueReactivated::class, SendMosqueReactivatedNotification::class);`
    - Tambahkan import yang diperlukan
    - _Requirements: 3.1, 4.1_

- [x] 7. Tambah metode `suspend()` dan `reactivate()` di `MosqueController`

  - [x] 7.1 Implementasi `MosqueController::suspend()`
    - Edit `app/Http/Controllers/Owner/MosqueController.php`
    - Tambah method `public function suspend(SuspendMosqueRequest $request, int $id): RedirectResponse`
    - Panggil `$this->mosqueService->suspend($id, auth()->id())`
    - Pada sukses: redirect ke `owner.mosques.show` dengan flash `success`
    - Pada exception: redirect back dengan flash `error` berisi pesan exception
    - Tambahkan import `SuspendMosqueRequest`
    - _Requirements: 1.3, 1.5, 1.9_

  - [x] 7.2 Implementasi `MosqueController::reactivate()`
    - Edit `app/Http/Controllers/Owner/MosqueController.php`
    - Tambah method `public function reactivate(ReactivateMosqueRequest $request, int $id): RedirectResponse`
    - Struktur identik dengan `suspend()` namun memanggil `reactivate()` dan flash "Masjid berhasil diaktifkan kembali."
    - Tambahkan import `ReactivateMosqueRequest`
    - _Requirements: 2.3, 2.4, 2.9_

  - [x] 7.3 Tulis feature test untuk suspend (HTTP layer)
    - Test POST `/owner/mosques/{id}/suspend` → 422 jika status bukan `active`
    - Test POST → 403 jika user bukan super-admin
    - Test POST → redirect dengan flash `success` dan status berubah di DB jika valid
    - Test rollback: mock service agar throw, assert status tidak berubah dan flash `error` ada
    - _Requirements: 1.7, 1.8, 1.9_

  - [x] 7.4 Tulis feature test untuk reactivate (HTTP layer)
    - Test POST `/owner/mosques/{id}/reactivate` → 422 jika status bukan `suspended`
    - Test POST → 403 jika user bukan super-admin
    - Test POST → redirect dengan flash `success` dan status berubah di DB jika valid
    - _Requirements: 2.7, 2.8, 2.9_

- [x] 8. Perbarui middleware `EnsureMosqueActive`

  - [x] 8.1 Edit `EnsureMosqueActive` agar redirect ke named route untuk status `suspended`
    - Edit `app/Http/Middleware/EnsureMosqueActive.php`
    - Ganti `abort(403, ...)` untuk `Suspended` dengan `return redirect()->route('mosque.suspended')->with('mosque_name', $mosque->name);`
    - Pertahankan `abort(403, ...)` untuk status lain (`pending`, `rejected`)
    - Pastikan response JSON (API) tetap mengembalikan `403` untuk semua status non-active
    - _Requirements: 5.2, 5.4_

  - [x] 8.2 Tambah route `mosque.suspended` di luar grup middleware `mosque.active`
    - Edit `routes/web.php`
    - Tambah route `GET /mosque/suspended` di luar grup yang menggunakan middleware `EnsureMosqueActive`
    - Route ini render view sederhana "Masjid Anda sedang ditangguhkan" dengan data `mosque_name` dari session
    - _Requirements: 5.2_

  - [x] 8.3 Tulis property test — Property 11: EnsureMosqueActive memblokir akses masjid suspended
    - **Property 11: EnsureMosqueActive memblokir akses masjid suspended**
    - **Validates: Requirements 5.2, 5.4**
    - Jalankan 100 iterasi: buat masjid `suspended` acak, kirim request ke route yang dilindungi, assert redirect (bukan 200 bukan 403) ke `mosque.suspended`
    - Assert tidak ada redirect loop (response bukan redirect ke dirinya sendiri)
    - Tag komentar: `// Feature: day11-mosque-suspend-reactivate, Property 11`

  - [x] 8.4 Tulis unit test middleware (edge cases)
    - Test middleware mengizinkan request lanjut saat status `active` (HTTP 200)
    - Test middleware abort 403 saat status `pending` atau `rejected`
    - Test middleware redirect ke `mosque.suspended` saat status `suspended`
    - _Requirements: 5.2, 5.3_

- [x] 9. Perbarui view `show.blade.php` — tombol aksi kontekstual dan badge color

  - [x] 9.1 Ganti blok tombol aksi dengan kondisi per-status lengkap
    - Edit `resources/views/owner/mosques/show.blade.php`
    - Ganti blok `@if($mosque->status === Pending)` yang ada dengan blok `@if($mosque->status !== Rejected)` yang mencakup semua status
    - Status `pending`: tampilkan tombol "Setujui Pendaftaran" (success) dan "Tolak Pendaftaran" (danger)
    - Status `active`: tampilkan tombol "Tangguhkan" (warning) saja
    - Status `suspended`: tampilkan tombol "Aktifkan Kembali" (success) saja
    - Status `rejected`: tidak tampilkan card aksi sama sekali
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

  - [x] 9.2 Perbaiki badge color untuk status `suspended`
    - Edit mapping `$badgeColor` atau helper yang menghasilkan class badge
    - Ubah `suspended` → `warning` (oranye), sesuai Requirement 7.2
    - Pastikan mapping: `active` → `success`, `pending` → `warning`, `suspended` → `warning`, `rejected` → `danger`
    - Terapkan konsistensi di halaman daftar masjid (index) dan halaman detail (show)
    - _Requirements: 5.1, 7.2_

  - [x] 9.3 Tambah fungsi JavaScript SweetAlert2 `suspendMosque()` dan `reactivateMosque()`
    - Tambah di section `@push('scripts')` atau `@section('scripts')` pada `show.blade.php`
    - Fungsi `suspendMosque(mosqueId)`: konfirmasi SweetAlert dengan icon `warning`, confirmButtonColor oranye `#fd7e14`
    - Fungsi `reactivateMosque(mosqueId)`: konfirmasi SweetAlert dengan icon `question`, confirmButtonColor hijau `#28a745`
    - Setelah konfirmasi: ubah label tombol menjadi "Memproses..." dan disabled, lalu submit form POST dengan `_token` dan `mosque_id`
    - Form action menggunakan path dari route: suspend → `/owner/mosques/{id}/suspend`, reactivate → `/owner/mosques/{id}/reactivate`
    - _Requirements: 1.2, 2.2, 7.5_

  - [x] 9.4 Tulis property test — Property 12: Rendering tombol aksi sesuai status
    - **Property 12: Rendering tombol aksi sesuai status**
    - **Validates: Requirements 6.1, 6.2, 6.3, 6.4**
    - Untuk setiap nilai `MosqueStatus`, render view dan assert tepat set tombol yang muncul (dan yang tidak muncul)
    - Tag komentar: `// Feature: day11-mosque-suspend-reactivate, Property 12`

- [x] 10. Checkpoint akhir — Jalankan seluruh test suite
  - Jalankan `php artisan test` untuk seluruh test suite
  - Pastikan tidak ada regression pada test Day 8 (approve/reject)
  - Verifikasi event binding terdaftar dengan benar: `php artisan event:list`
  - Tanyakan kepada user jika ada pertanyaan sebelum dianggap selesai.

---

## Notes

- Task bertanda `*` bersifat opsional dan dapat dilewati untuk MVP yang lebih cepat
- Setiap task mereferensikan requirements spesifik untuk keterlacakan
- Property test menggunakan loop 100 iterasi via Pest PHP dengan `fake()` helper
- Tag komentar format: `// Feature: day11-mosque-suspend-reactivate, Property {N}: {teks}`
- Route `mosque.suspended` harus berada di luar grup middleware `EnsureMosqueActive` untuk menghindari redirect loop
- `SendFcmNotificationJob` adalah job reusable — tidak perlu dibuat ulang jika sudah ada dari Day sebelumnya
- Badge color `suspended` → `warning` (oranye), bukan `danger` — sesuaikan di semua tempat yang menampilkan badge status

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2", "3.1", "3.2"] },
    { "id": 1, "tasks": ["1.3", "2.1", "2.4", "4.1"] },
    { "id": 2, "tasks": ["2.2", "2.3", "2.5", "2.6", "2.7", "2.8", "3.3", "3.4", "4.2"] },
    { "id": 3, "tasks": ["3.5", "3.6", "6.1", "6.2", "7.1", "7.2", "8.1", "8.2"] },
    { "id": 4, "tasks": ["7.3", "7.4", "8.3", "8.4", "9.1", "9.2", "9.3"] },
    { "id": 5, "tasks": ["9.4"] }
  ]
}
```
