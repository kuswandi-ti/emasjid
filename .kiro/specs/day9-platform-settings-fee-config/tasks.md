# Implementation Plan: Platform Settings — Fee Configuration

## Overview

Fitur ini sudah diimplementasikan sebagian. Task-task di bawah mencakup perbaikan bug kritis, pengisian gap implementasi, dan penulisan test (unit + feature) untuk memverifikasi kebenaran logika fee sesuai requirements. Semua task mengacu pada file-file yang sudah didefinisikan di design document.

---

## Tasks

- [x] 1. Perbaiki bug `calculateFee()`: ganti `round()` dengan `floor()`
  - [x] 1.1 Buka `app/Services/PlatformSettingService.php`, cari baris yang menggunakan `round()` pada kalkulasi fee, dan ganti dengan `(int) floor(($amount * $feePercentage) / 10000)`
    - Sesuai dengan Formula Utama di design: `Fee_Amount = floor(Donation_Amount × Fee_Percentage / 10000)`
    - _Requirements: 4.3, 5.1_
  - [x] 1.2 Buka `resources/views/owner/settings/index.blade.php`, cari blok JavaScript `updatePreview()`, dan ganti `Math.round(...)` dengan `Math.floor(...)` agar preview client-side konsisten dengan kalkulasi PHP
    - _Requirements: 9.2, 9.3_

- [x] 2. Perbaiki default `fee_active` di `getFeeSettings()`
  - [x] 2.1 Buka `app/Services/PlatformSettingService.php`, cari `getFeeSettings()`, dan ubah default value `fee_active` dari `true` menjadi `false` (atau ekuivalen: nilai fallback string `'0'` saat key belum ada di database)
    - Requirement 1.4 menetapkan default `Fee_Active = 0` (nonaktif) saat record belum ada
    - _Requirements: 1.4_

- [x] 3. Bungkus `setMany()` dalam database transaction
  - [x] 3.1 Buka `app/Repositories/PlatformSettingRepository.php`, cari method `setMany()`, dan bungkus seluruh iterasi `set()` di dalam `DB::transaction(function () use (...) { ... })` sehingga jika satu key gagal, semua perubahan di-rollback
    - Ini memenuhi Requirement 10.7: atomisitas operasi upsert
    - _Requirements: 10.7_

- [x] 4. Checkpoint — Verifikasi bug fix sebelum menulis tests
  - Pastikan `php artisan test --filter=PlatformSettingServiceTest` lulus (jika test sudah ada), dan tidak ada error sintaks. Tanyakan ke user jika ada kendala.

- [x] 5. Tulis unit tests PBT-style untuk correctness properties
  - [x] 5.1 Buka `tests/Unit/Services/PlatformSettingServiceTest.php`, tambahkan helper methods `randomAmounts(int $count): array`, `randomFeeConfigs(int $count): array`, dan `randomSettingsArrays(int $count): array` menggunakan `mt_rand()` untuk menghasilkan data test acak
    - `randomAmounts`: int acak dalam range [1, 10_000_000]
    - `randomFeeConfigs`: pasangan [amount, pct] dengan pct dalam [0, 10000]
    - `randomSettingsArrays`: array settings lengkap dengan fee_percentage, fee_mechanism, fee_active
    - _Requirements: 5.1, 5.3_
  - [x] 5.2 Tulis property test **Property 1: Fee Identity** — untuk semua amount acak (≥ 50 iterasi), jika `fee_percentage = 0` dan `fee_active = true`, maka `fee_amount` harus `0`
    - Tag komentar: `// Feature: day9-platform-settings-fee-config, Property 1: Fee Identity`
    - **Validates: Requirements 5.3**
  - [x] 5.3 Tulis property test **Property 2: Fee Inactive** — untuk semua kombinasi amount dan pct acak (≥ 50 iterasi), jika `fee_active = false`, maka `fee_amount = 0`, `payment_amount = donation_amount`, `mosque_receives = donation_amount`
    - Tag komentar: `// Feature: day9-platform-settings-fee-config, Property 2: Fee Inactive`
    - **Validates: Requirements 4.4, 5.2**
  - [x] 5.4 Tulis property test **Property 3: Floor Semantics** — untuk ≥ 100 pasangan acak [amount, pct], verifikasi `fee_amount === (int) floor($amount * $pct / 10000)` (bukan round)
    - Sertakan kasus tepi: `amount=100001`, `pct=334` → `floor(3333.7334) = 3333` bukan `3334`
    - Tag komentar: `// Feature: day9-platform-settings-fee-config, Property 3: Floor Semantics`
    - **Validates: Requirements 4.3, 5.1**
  - [x] 5.5 Tulis property test **Property 4: `added_to_donor` Invariant** — untuk ≥ 100 pasangan acak [amount, pct] dengan `fee_active = true` dan `fee_mechanism = added_to_donor`, verifikasi `payment_amount = donation_amount + fee_amount` dan `mosque_receives = donation_amount`
    - Tag komentar: `// Feature: day9-platform-settings-fee-config, Property 4: added_to_donor Invariant`
    - **Validates: Requirements 6.1, 6.2**
  - [x] 5.6 Tulis property test **Property 5: `deducted_from_donation` Invariant** — untuk ≥ 100 pasangan acak [amount, pct] dengan `fee_active = true` dan `fee_mechanism = deducted_from_donation`, verifikasi `payment_amount = donation_amount`, `mosque_receives = max(0, donation_amount - fee_amount)`, dan `mosque_receives >= 0` selalu
    - Tag komentar: `// Feature: day9-platform-settings-fee-config, Property 5: deducted_from_donation Invariant`
    - **Validates: Requirements 7.1, 7.2, 7.3**
  - [x] 5.7 Tulis property test **Property 6: Conservation and Non-Negativity** — untuk ≥ 50 pasangan acak [amount, pct] dengan kedua mekanisme, verifikasi `fee_amount >= 0`, `payment_amount >= 1`, `mosque_receives >= 0`, dan `mosque_receives + fee_amount <= payment_amount`
    - Tag komentar: `// Feature: day9-platform-settings-fee-config, Property 6: Conservation and Non-Negativity`
    - **Validates: Requirements 5.1, 6.1, 7.2, 7.3**
  - [x] 5.8 Tulis property test **Property 7: Upsert Idempotence** — untuk ≥ 30 settings arrays acak, panggil `updateFeeSettings()` dua kali dengan data yang sama, lalu verifikasi `getFeeSettings()` mengembalikan nilai identik setelah panggilan pertama dan kedua
    - Tag komentar: `// Feature: day9-platform-settings-fee-config, Property 7: Upsert Idempotence`
    - **Validates: Requirements 10.1, 10.3**
  - [x] 5.9 Tulis property test **Property 8: Valid Percentage Persistence Round-Trip** — untuk sampling `range(0, 10000, 100)`, panggil `updateFeeSettings(['fee_percentage' => $p, ...])` lalu `getFeeSettings()`, verifikasi `fee_percentage === $p`
    - Tag komentar: `// Feature: day9-platform-settings-fee-config, Property 8: Valid Percentage Persistence Round-Trip`
    - **Validates: Requirements 2.1, 10.1, 10.4**

- [x] 6. Tulis feature tests untuk HTTP layer
  - [x] 6.1 Buat file `tests/Feature/Owner/SettingFeatureTest.php` dengan boilerplate: `use RefreshDatabase`, setup owner user dengan role `super-admin`, dan setup non-owner user
    - _Requirements: 1.2, 2.4_
  - [x] 6.2 Tulis feature test `test_non_owner_cannot_access_settings_page`: GET `/owner/settings` dengan user non-super-admin harus mengembalikan HTTP 403
    - _Requirements: 1.2_
  - [x] 6.3 Tulis feature test `test_non_owner_cannot_update_settings`: PUT `/owner/settings` dengan user non-super-admin harus mengembalikan HTTP 403 tanpa mengubah database
    - _Requirements: 2.4_
  - [x] 6.4 Tulis feature test `test_settings_form_prepopulated_with_stored_values`: seed tiga record `platform_settings`, GET `/owner/settings`, verifikasi response mengandung nilai yang di-seed pada masing-masing field form
    - _Requirements: 1.1, 8.4_
  - [x] 6.5 Tulis feature test `test_default_values_shown_when_no_settings_exist`: pastikan tabel kosong, GET `/owner/settings`, verifikasi response menampilkan `fee_percentage = 0`, `fee_mechanism = added_to_donor`, dan `fee_active = 0`
    - _Requirements: 1.4_
  - [x] 6.6 Tulis feature test `test_success_flash_message_shown_after_update`: PUT `/owner/settings` dengan data valid sebagai owner, ikuti redirect, verifikasi session flash `success` ada
    - _Requirements: 8.5_
  - [x] 6.7 Tulis feature test `test_validation_error_for_out_of_range_fee_percentage`: PUT `/owner/settings` dengan `fee_percentage = 10001`, verifikasi response redirect dengan error bag yang mengandung key `fee_percentage`
    - _Requirements: 2.2_
  - [x] 6.8 Tulis feature test `test_validation_error_for_invalid_fee_mechanism`: PUT `/owner/settings` dengan `fee_mechanism = 'invalid_value'`, verifikasi error bag mengandung key `fee_mechanism`
    - _Requirements: 3.3_
  - [x] 6.9 Tulis feature test `test_upsert_creates_record_when_not_exists`: pastikan tabel kosong, PUT valid settings sebagai owner, verifikasi tiga record baru terbuat di `platform_settings`
    - _Requirements: 10.2_
  - [x] 6.10 Tulis feature test `test_upsert_does_not_create_duplicate_records`: PUT valid settings dua kali berturut-turut, verifikasi jumlah record untuk masing-masing key tetap `1` (tidak ada duplikat)
    - _Requirements: 10.3_

- [x] 7. Checkpoint Final — Jalankan seluruh test suite
  - Pastikan semua tests pass dengan `php artisan test --filter=PlatformSettingServiceTest` dan `php artisan test --filter=SettingFeatureTest`. Tanyakan ke user jika ada kegagalan yang tidak terduga.

---

## Notes

- Task yang ditandai `*` adalah opsional dan dapat di-skip untuk iterasi lebih cepat, namun direkomendasikan untuk memverifikasi kebenaran properti formal
- Task 1.1 dan 1.2 (bug fix `floor`) harus dikerjakan sebelum menulis property tests agar tests tidak menguji perilaku yang salah
- Task 2.1 (default `fee_active`) harus dikerjakan sebelum feature test 6.5 agar test default values lulus
- Task 3.1 (DB transaction di `setMany()`) adalah gap atomisitas — penting untuk integritas data produksi
- Semua property tests (5.2–5.9) menggunakan loop/random seeding untuk simulasi property-based testing tanpa library tambahan
- Feature tests (6.1–6.10) membutuhkan `RefreshDatabase` dan setup user dengan Spatie Permission atau role system yang digunakan proyek

---

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2", "2.1", "3.1"] },
    { "id": 1, "tasks": ["5.1", "6.1"] },
    { "id": 2, "tasks": ["5.2", "5.3", "5.4", "5.5", "5.6", "5.7", "5.8", "5.9"] },
    { "id": 3, "tasks": ["6.2", "6.3", "6.4", "6.5", "6.6", "6.7", "6.8", "6.9", "6.10"] }
  ]
}
```
