# Design Document

## Feature: Platform Settings — Fee Configuration

---

## Overview

Fitur ini memungkinkan Owner (super-admin) platform EMasjid untuk mengkonfigurasi fee platform melalui web panel. Implementasi mengikuti arsitektur berlapis Laravel standar: **Controller → Service → Repository → Model**, dengan Redis/file cache di layer repository untuk mengurangi query database.

Konfigurasi fee disimpan dalam tabel `platform_settings` sebagai key-value store global (tidak scoped per masjid). Tiga key yang relevan:

| Key                        | Tipe stored   | Contoh nilai        |
|----------------------------|---------------|---------------------|
| `platform_fee_percentage`  | string (int)  | `"250"`             |
| `platform_fee_mechanism`   | string        | `"added_to_donor"`  |
| `platform_fee_active`      | string        | `"1"` atau `"0"`    |

Seluruh implementasi sudah ada. Dokumen desain ini mendokumentasikan arsitektur, logika kalkulasi, strategi cache, struktur view, dan properti kebenaran yang harus diuji.

> **⚠️ Bug Note — floor() vs round():** Requirement 5.1 menetapkan formula `floor(Donation_Amount * Fee_Percentage / 10000)`, namun implementasi saat ini di `PlatformSettingService::calculateFee()` menggunakan `round()`. Untuk input bersih seperti `100000 * 250 / 10000 = 2500.0` perbedaannya tidak tampak, tetapi untuk kasus seperti `100001 * 333 / 10000 = 3333.033`, `round()` → `3333` sementara `floor()` → `3333`. Perbedaan akan tampak jelas pada kasus seperti `100001 * 334 / 10000 = 3333.7`, di mana `round()` → `3334` tapi `floor()` → `3333`. Koreksi yang diperlukan: ganti `round()` dengan `(int)` (yang secara efektif adalah `floor` untuk integer positif):
>
> ```php
> // Bug: menggunakan round()
> $feeAmount = (int) round(($amount * $feePercentage) / 10000);
>
> // Fix: gunakan floor semantics
> $feeAmount = (int) floor(($amount * $feePercentage) / 10000);
> ```

---

## Architecture

```
HTTP Request
     │
     ▼
┌──────────────────────────────┐
│  routes/web.php              │
│  GET  /owner/settings        │  middleware: auth:web, owner
│  PUT  /owner/settings        │
└──────────────┬───────────────┘
               │
               ▼
┌──────────────────────────────┐
│  UpdateFeeSettingRequest     │  Validation + Authorization
│  (Form Request)              │  authorize(): hasRole('super-admin')
└──────────────┬───────────────┘
               │
               ▼
┌──────────────────────────────┐
│  SettingController           │  Thin controller
│  (Owner namespace)           │  index() / update()
└──────────────┬───────────────┘
               │
               ▼
┌──────────────────────────────┐
│  PlatformSettingService      │  Business logic + fee calculation
│                              │  getFeeSettings() / updateFeeSettings()
│                              │  calculateFee() / getFeePreview()
└──────────────┬───────────────┘
               │
               ▼
┌──────────────────────────────┐
│  PlatformSettingRepository   │  Data access + Redis/file cache
│  (implements Interface)      │  1-hour TTL, cache invalidation on write
└──────────────┬───────────────┘
               │
               ▼
┌──────────────────────────────┐
│  PlatformSetting (Model)     │  Eloquent, table: platform_settings
│                              │  fillable: key, value, description
└──────────────────────────────┘
```

**Alur GET /owner/settings:**
1. `SettingController::index()` memanggil `getFeeSettings()` dan `getFeePreview()`
2. Service memanggil `repository->getValue()` per key (cache hit jika tersedia)
3. View di-render dengan `$settings` dan `$preview`

**Alur PUT /owner/settings:**
1. `UpdateFeeSettingRequest` memvalidasi input dan memeriksa otorisasi
2. `SettingController::update()` memanggil `settingService->updateFeeSettings($request->validated())`
3. Service memanggil `repository->setMany($settings)` yang iterasi `set()` per key
4. `set()` memanggil `PlatformSetting::updateOrCreate()` (upsert) lalu `clearCache()`
5. Redirect ke index dengan flash `success` atau `back()` dengan flash `error`

---

## Components and Interfaces

### 1. `PlatformSetting` Model

**File:** `app/Models/PlatformSetting.php`

```php
class PlatformSetting extends Model
{
    protected $fillable = ['key', 'value', 'description'];

    public static function getValue(string $key, mixed $default = null): mixed;
}
```

- Tabel: `platform_settings`
- Kolom: `id`, `key` (unique, varchar 100), `value` (text), `description` (nullable varchar 255), `created_at`, `updated_at`
- `getValue()` adalah static convenience method; penggunaan utama melalui repository

### 2. `PlatformSettingRepositoryInterface`

**File:** `app/Contracts/Repositories/PlatformSettingRepositoryInterface.php`

```php
interface PlatformSettingRepositoryInterface
{
    public function all(): Collection;
    public function get(string $key): ?PlatformSetting;
    public function getValue(string $key, mixed $default = null): mixed;
    public function set(string $key, mixed $value, ?string $description = null): PlatformSetting;
    public function update(string $key, mixed $value): bool;
    public function delete(string $key): bool;
    public function exists(string $key): bool;
    public function getMany(array $keys): Collection;
    public function setMany(array $settings): bool;
    public function getAllAsKeyValue(): array;
}
```

### 3. `PlatformSettingRepository`

**File:** `app/Repositories/PlatformSettingRepository.php`

Metode utama yang relevan untuk fitur ini:

| Metode | Deskripsi |
|--------|-----------|
| `getValue($key, $default)` | Ambil single value via cache, fallback ke default |
| `set($key, $value, $desc)` | Upsert satu key, invalidate cache |
| `setMany($settings)` | Iterasi `set()` untuk semua key, atomisitas per key |

**Cache keys:**
- `platform_settings_all` — hasil `all()`
- `platform_settings_{key}` — hasil `get($key)` per key
- `platform_settings_key_value` — hasil `getAllAsKeyValue()`

**TTL:** 3600 detik (1 jam)

**Cache invalidation:** Dipanggil pada setiap `set()`, `update()`, dan `delete()`:
- Hapus `platform_settings_{key}` (spesifik per key)
- Hapus `platform_settings_all` (aggregate)
- Hapus `platform_settings_key_value` (key-value map)

> **Catatan:** `setMany()` tidak menggunakan database transaction. Jika satu key gagal disimpan di tengah iterasi, key sebelumnya sudah tersimpan. Requirement 10.7 (atomik) belum sepenuhnya dipenuhi — ini adalah area perbaikan yang direkomendasikan.

### 4. `PlatformSettingService`

**File:** `app/Services/PlatformSettingService.php`

```php
class PlatformSettingService
{
    public function __construct(private PlatformSettingRepositoryInterface $repository) {}

    public function getAll(): Collection;
    public function getFeeSettings(): array;
    public function updateFeeSettings(array $data): bool;
    public function calculateFee(int $amount): array;
    public function getFeePreview(int $sampleAmount = 100000): array;
    private function getMechanismLabel(string $mechanism): string;
}
```

**`getFeeSettings()` → array:**
```php
[
    'fee_percentage' => int,   // 0–10000
    'fee_mechanism'  => string, // 'added_to_donor' | 'deducted_from_donation'
    'fee_active'     => bool,
]
```
Defaults: `fee_percentage=250`, `fee_mechanism='added_to_donor'`, `fee_active=true`

> **Catatan:** Default `fee_active` di service adalah `true`, sementara Requirement 1.4 menetapkan default `Fee_Active = 0` (nonaktif). Ada inkonsistensi antara requirement dan implementasi yang perlu disesuaikan.

**`updateFeeSettings(array $data)` → bool:**
Memetakan key input ke key database:
- `fee_percentage` → `platform_fee_percentage` (cast ke string)
- `fee_mechanism` → `platform_fee_mechanism`
- `fee_active` → `platform_fee_active` (`true`→`'1'`, `false`→`'0'`)

**`calculateFee(int $amount)` → array:**
```php
[
    'fee_amount'     => int,
    'payment_amount' => int,
    'mosque_receives'=> int,
]
```

**`getFeePreview(int $sampleAmount = 100000)` → array:**
Menggabungkan `getFeeSettings()` + `calculateFee()` + number_format (format Rupiah dengan pemisah titik).

### 5. `UpdateFeeSettingRequest`

**File:** `app/Http/Requests/UpdateFeeSettingRequest.php`

```php
public function authorize(): bool
{
    return $this->user()?->hasRole('super-admin');
}

public function rules(): array
{
    return [
        'fee_percentage' => ['required', 'integer', 'min:0', 'max:10000'],
        'fee_mechanism'  => ['required', 'string', Rule::in(['added_to_donor', 'deducted_from_donation'])],
        'fee_active'     => ['required', 'boolean'],
    ];
}
```

Pesan validasi custom dalam Bahasa Indonesia untuk tiap rule.

### 6. `SettingController`

**File:** `app/Http/Controllers/Owner/SettingController.php`

```php
public function index(): View
{
    $settings = $this->settingService->getFeeSettings();
    $preview  = $this->settingService->getFeePreview();
    return view('owner.settings.index', compact('settings', 'preview'));
}

public function update(UpdateFeeSettingRequest $request): RedirectResponse
{
    try {
        $this->settingService->updateFeeSettings($request->validated());
        return redirect()->route('owner.settings.index')->with('success', '...');
    } catch (\Exception $e) {
        return redirect()->back()->withInput()->with('error', '...');
    }
}
```

---

## Data Models

### Tabel `platform_settings`

```sql
CREATE TABLE platform_settings (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    key         VARCHAR(100) NOT NULL UNIQUE,
    value       TEXT NOT NULL,
    description VARCHAR(255) NULL,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL
);
```

### Setting Keys untuk Fee Config

```
platform_fee_percentage   → "0" – "10000"   (integer sebagai string)
platform_fee_mechanism    → "added_to_donor" | "deducted_from_donation"
platform_fee_active       → "1" | "0"
```

### Tipe Data Kalkulasi

```
Donation_Amount   : int ≥ 1 (Rupiah, unsignedBigInteger semantics)
Fee_Percentage    : int ∈ [0, 10000] (basis poin)
Fee_Amount        : int ≥ 0 = floor(Donation_Amount × Fee_Percentage / 10000)
Payment_Amount    : int ≥ 1
Mosque_Receives   : int ≥ 0 (unsignedBigInteger — tidak boleh negatif)
```

---

## Fee Calculation Algorithm

### Formula Utama

```
Fee_Amount = floor(Donation_Amount × Fee_Percentage / 10000)
```

Basis poin ke persentase: `Fee_Percentage / 100 = persentase (%)`.
Contoh: 250 basis poin = 2.5%.

### Kasus: Fee Nonaktif (`fee_active = false`)

```
Fee_Amount     = 0
Payment_Amount = Donation_Amount
Mosque_Receives = Donation_Amount
```

### Kasus: `added_to_donor` (Fee Aktif)

Donatur membayar lebih dari nominal donasi. Masjid menerima penuh.

```
Fee_Amount      = floor(Donation_Amount × Fee_Percentage / 10000)
Payment_Amount  = Donation_Amount + Fee_Amount
Mosque_Receives = Donation_Amount
```

**Contoh:** Donasi Rp 100.000, fee 2.5% (250 basis poin):
- `Fee_Amount = floor(100000 × 250 / 10000) = floor(2500) = 2500`
- `Payment_Amount = 100000 + 2500 = 102500`
- `Mosque_Receives = 100000`

### Kasus: `deducted_from_donation` (Fee Aktif)

Donatur membayar tepat nominal. Masjid menerima setelah dipotong fee.

```
Fee_Amount      = floor(Donation_Amount × Fee_Percentage / 10000)
Payment_Amount  = Donation_Amount
Mosque_Receives = max(0, Donation_Amount - Fee_Amount)
```

**Contoh:** Donasi Rp 100.000, fee 2.5%:
- `Fee_Amount = 2500`
- `Payment_Amount = 100000`
- `Mosque_Receives = 100000 - 2500 = 97500`

**Kasus ekstrem (fee 100%, `Fee_Percentage = 10000`):**
- `Fee_Amount = floor(100000 × 10000 / 10000) = 100000`
- `Mosque_Receives = max(0, 100000 - 100000) = 0`

### Edge Cases

| Kondisi | Hasil |
|---------|-------|
| `fee_percentage = 0` | `fee_amount = 0`, `mosque_receives = donation_amount` |
| `fee_percentage = 10000` (100%) + deducted | `mosque_receives = 0` |
| `fee_active = false` | Semua hasil nol fee, tidak ada kalkulasi |
| Pecahan basis poin (misal: `fee_percentage = 333`, `amount = 100001`) | `floor(100001 × 333 / 10000) = floor(3333.033) = 3333` |

---

## Cache Invalidation Strategy

Setiap kali `set()` dipanggil (termasuk via `setMany()`), tiga cache key dihapus:

```php
private function clearCache(?string $key = null): void
{
    if ($key) {
        Cache::forget("platform_settings_{$key}");   // per-key cache
    }
    Cache::forget("platform_settings_all");           // aggregate cache
    Cache::forget("platform_settings_key_value");     // key-value map cache
}
```

**Flow saat update fee settings:**
1. Owner submit form → `updateFeeSettings()` dipanggil
2. `setMany(['platform_fee_percentage' => ..., ...])` iterasi tiga `set()` calls
3. Setiap `set()` memanggil `updateOrCreate()` lalu `clearCache()`
4. Setelah semua key tersimpan, semua cache yang relevan sudah diinvalidasi
5. Request berikutnya ke `getFeeSettings()` akan hit database dan populate cache baru

**Implikasi:** Cache diinvalidasi tiga kali (sekali per key). Ini aman namun sedikit tidak efisien — alternatifnya adalah satu flush di akhir `setMany()`, tetapi pola saat ini sudah cukup untuk volume yang diharapkan.

---

## Blade View Structure

**File:** `resources/views/owner/settings/index.blade.php`

Extends: `layouts.owner`

### Layout Halaman

```
┌─────────────────────────────────────────────────┐
│  Page Header: "Pengaturan Fee Platform"          │
│  Breadcrumb: Dashboard > Pengaturan Fee          │
└─────────────────────────────────────────────────┘

┌──────────────────────┐  ┌───────────────────────┐
│  col-lg-8            │  │  col-lg-4              │
│  Card: Form          │  │  Card: Preview         │
│  ─────────────────── │  │  ─────────────────────│
│  • fee_percentage    │  │  Contoh: Rp 100.000    │
│    [input number]    │  │  Fee: [previewFee]     │
│    helper text       │  │  Total bayar: [...]    │
│    % display (JS)    │  │  Masjid terima: [...]  │
│  • fee_mechanism     │  │  Mekanisme info        │
│    [radio x2]        │  ├───────────────────────┤
│    descriptions      │  │  Card: Penjelasan      │
│  • fee_active        │  │  Basis poin explained  │
│    [toggle/switch]   │  │  Mekanisme explained   │
│  ─────────────────── │  └───────────────────────┘
│  [Simpan] [Batal]    │
└──────────────────────┘
```

### Form Fields

| Field | Tipe HTML | Name | Validasi Browser |
|-------|-----------|------|-----------------|
| Fee Percentage | `<input type="number">` | `fee_percentage` | min=0, max=10000, step=1 |
| Fee Mechanism | `<input type="radio">` | `fee_mechanism` | required |
| Fee Active | `<input type="checkbox" role="switch">` | `fee_active` | value="1" |

**Pre-population:** Menggunakan `old('field', $settings['field'])` sehingga nilai lama dipertahankan saat validasi gagal.

**Error display:** `@error('field_name')` menampilkan `<div class="invalid-feedback d-block">` per field.

**Flash message:** Layout `owner` menampilkan `session('success')` dan `session('error')`.

### JavaScript Preview Logic

Dibungkus dalam `DOMContentLoaded` listener. **Tidak menggunakan Alpine.js atau framework JS.**

**Event listeners:**
- `feePercentageInput` → `input` → `updatePercentageDisplay()` + `updatePreview()`
- `feeActiveCheckbox` → `change` → `updateFeeStatusLabel()` + `updatePreview()`
- `mechanismRadios` → `change` → `updatePreview()`

**`updatePreview()` — Kalkulasi JS (client-side):**

```javascript
const feeAmount = Math.round((sampleAmount * basisPoints) / 10000);
// Note: menggunakan Math.round() di JS, konsisten dengan bug PHP
```

> **Catatan:** JS preview juga menggunakan `Math.round()` bukan `Math.floor()`. Jika PHP diperbaiki ke `floor()`, JS preview juga harus diubah ke `Math.floor()` agar konsisten.

**Timing:** Update terjadi synchronously pada setiap event — tidak ada debounce timer. Karena operasi pure JS (tidak ada HTTP call), respons jauh di bawah 300ms threshold dari Requirement 9.

**Form confirmation dialog:** `window.confirm()` saat submit menampilkan ringkasan konfigurasi yang akan disimpan.

---

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system — essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Fee Identity

*For any* valid `Donation_Amount` (≥ 1) and `Fee_Active = true`, jika `Fee_Percentage = 0`, maka `Fee_Amount` SHALL sama dengan `0`.

**Validates: Requirements 5.3**

### Property 2: Fee Inactive

*For any* valid `Donation_Amount` (≥ 1) dan `Fee_Percentage` dalam [0, 10000], jika `Fee_Active = false`, maka `Fee_Amount` SHALL sama dengan `0`, `Payment_Amount` SHALL sama dengan `Donation_Amount`, dan `Mosque_Receives` SHALL sama dengan `Donation_Amount`.

**Validates: Requirements 4.4, 5.2**

### Property 3: Floor Semantics

*For any* valid `Donation_Amount` (≥ 1) dan `Fee_Percentage` dalam [0, 10000] dengan `Fee_Active = true`, maka `Fee_Amount` SHALL sama persis dengan `⌊Donation_Amount × Fee_Percentage / 10000⌋` (floor division, bukan round).

**Validates: Requirements 4.3, 5.1**

### Property 4: `added_to_donor` Invariant

*For any* valid `Donation_Amount` (≥ 1) dan `Fee_Percentage` dalam [0, 10000] dengan `Fee_Active = true` dan `Fee_Mechanism = added_to_donor`, maka:
- `Payment_Amount = Donation_Amount + Fee_Amount`
- `Mosque_Receives = Donation_Amount` (tepat sama, tanpa potongan)

**Validates: Requirements 6.1, 6.2**

### Property 5: `deducted_from_donation` Invariant

*For any* valid `Donation_Amount` (≥ 1) dan `Fee_Percentage` dalam [0, 10000] dengan `Fee_Active = true` dan `Fee_Mechanism = deducted_from_donation`, maka:
- `Payment_Amount = Donation_Amount`
- `Mosque_Receives = max(0, Donation_Amount - Fee_Amount)`
- `Mosque_Receives ≥ 0` selalu (tidak pernah negatif)

**Validates: Requirements 7.1, 7.2, 7.3**

### Property 6: Conservation and Non-Negativity

*For any* valid input (mekanisme apapun, `Fee_Active` apapun), semua output SHALL memenuhi:
- `fee_amount ≥ 0`
- `payment_amount ≥ 1`
- `mosque_receives ≥ 0`
- `mosque_receives + fee_amount ≤ payment_amount`

**Validates: Requirements 5.1, 6.1, 7.2, 7.3**

### Property 7: Upsert Idempotence

*For any* valid fee settings array `{fee_percentage, fee_mechanism, fee_active}`, memanggil `updateFeeSettings()` dua kali berturut-turut dengan data yang sama SHALL menghasilkan:
- `getFeeSettings()` mengembalikan nilai yang sama setelah panggilan pertama maupun kedua
- Jumlah record di tabel `platform_settings` untuk setiap key tetap `1` (tidak ada duplikat)

**Validates: Requirements 10.1, 10.3**

### Property 8: Valid Percentage Persistence Round-Trip

*For any* integer `p` dalam [0, 10000], memanggil `updateFeeSettings(['fee_percentage' => p, ...])` lalu `getFeeSettings()` SHALL mengembalikan `fee_percentage` yang sama persis dengan `p`.

**Validates: Requirements 2.1, 10.1, 10.4**

---

## Error Handling

### HTTP 403 — Unauthorized Access

- Terjadi ketika user bukan `super-admin` mengakses `GET` atau `PUT /owner/settings`
- `UpdateFeeSettingRequest::authorize()` mengembalikan `false` → Laravel throw `AuthorizationException` → HTTP 403

### Validation Error (HTTP 422)

- Terjadi ketika input tidak lolos rule di `UpdateFeeSettingRequest`
- Controller tidak perlu menangani — Laravel secara otomatis redirect `back()` dengan `$errors` dan `$input`
- View menampilkan error per field via `@error` directive

### Service Exception

- Terjadi ketika `settingService->updateFeeSettings()` melempar exception (contoh: database error)
- `SettingController::update()` menangkap dengan `try/catch`, redirect `back()` dengan flash `error`
- Input dipertahankan via `withInput()`

### Cache Miss

- Tidak menyebabkan error — `Cache::remember()` otomatis fallback ke database query
- TTL 1 jam, setelah expired cache di-repopulate secara otomatis

---

## Testing Strategy

### Unit Tests (PHPUnit + Mockery)

File: `tests/Unit/Services/PlatformSettingServiceTest.php`

Unit tests mem-mock `PlatformSettingRepositoryInterface` sehingga tidak memerlukan database.

**Tests yang sudah ada** mencakup:
- `getFeeSettings()` mengembalikan array dengan required keys
- `calculateFee()` untuk `added_to_donor`, `deducted_from_donation`, dan fee nonaktif
- `calculateFee()` dengan berbagai persentase
- `getFeePreview()` mengembalikan nilai yang diformat dengan benar
- `updateFeeSettings()` mempersist nilai

**Tests yang perlu ditambahkan** (PBT-style dengan data provider atau loop):

```php
// P1: Fee Identity — fee_percentage=0 → fee_amount=0
public function test_fee_identity_zero_percentage_always_yields_zero_fee(): void
{
    foreach ($this->randomAmounts(50) as $amount) {
        $this->service->updateFeeSettings([
            'fee_percentage' => 0,
            'fee_mechanism' => 'added_to_donor',
            'fee_active' => true,
        ]);
        $result = $this->service->calculateFee($amount);
        $this->assertEquals(0, $result['fee_amount'],
            "fee_identity failed for amount={$amount}");
    }
}

// P2: Fee Inactive — fee_active=false → all outputs use donation_amount
public function test_fee_inactive_returns_zero_fee_for_any_input(): void
{
    foreach ($this->randomFeeConfigs(50) as [$amount, $pct]) {
        $this->service->updateFeeSettings([
            'fee_percentage' => $pct,
            'fee_mechanism' => 'added_to_donor',
            'fee_active' => false,
        ]);
        $result = $this->service->calculateFee($amount);
        $this->assertEquals(0, $result['fee_amount']);
        $this->assertEquals($amount, $result['payment_amount']);
        $this->assertEquals($amount, $result['mosque_receives']);
    }
}

// P3: Floor Semantics — fee_amount = floor(amount * pct / 10000)
public function test_floor_semantics_for_fee_calculation(): void
{
    foreach ($this->randomFeeConfigs(100) as [$amount, $pct]) {
        $this->service->updateFeeSettings([
            'fee_percentage' => $pct,
            'fee_mechanism' => 'added_to_donor',
            'fee_active' => true,
        ]);
        $result = $this->service->calculateFee($amount);
        $expected = (int) floor($amount * $pct / 10000);
        $this->assertEquals($expected, $result['fee_amount'],
            "floor_semantics failed for amount={$amount}, pct={$pct}");
    }
}

// P4: added_to_donor invariant
public function test_added_to_donor_payment_equals_amount_plus_fee(): void
{
    foreach ($this->randomFeeConfigs(100) as [$amount, $pct]) {
        $this->service->updateFeeSettings([
            'fee_percentage' => $pct,
            'fee_mechanism' => 'added_to_donor',
            'fee_active' => true,
        ]);
        $result = $this->service->calculateFee($amount);
        $this->assertEquals($amount + $result['fee_amount'], $result['payment_amount']);
        $this->assertEquals($amount, $result['mosque_receives']);
    }
}

// P5: deducted_from_donation invariant (termasuk edge case mosque_receives >= 0)
public function test_deducted_from_donation_invariant(): void
{
    foreach ($this->randomFeeConfigs(100) as [$amount, $pct]) {
        $this->service->updateFeeSettings([
            'fee_percentage' => $pct,
            'fee_mechanism' => 'deducted_from_donation',
            'fee_active' => true,
        ]);
        $result = $this->service->calculateFee($amount);
        $this->assertEquals($amount, $result['payment_amount']);
        $this->assertGreaterThanOrEqual(0, $result['mosque_receives'],
            "mosque_receives went negative for amount={$amount}, pct={$pct}");
        $expectedMosque = max(0, $amount - $result['fee_amount']);
        $this->assertEquals($expectedMosque, $result['mosque_receives']);
    }
}

// P6: Conservation — mosque_receives + fee_amount <= payment_amount, all >= 0
public function test_conservation_and_non_negativity(): void
{
    $mechanisms = ['added_to_donor', 'deducted_from_donation'];
    foreach ($this->randomFeeConfigs(50) as [$amount, $pct]) {
        foreach ($mechanisms as $mechanism) {
            $this->service->updateFeeSettings([
                'fee_percentage' => $pct,
                'fee_mechanism' => $mechanism,
                'fee_active' => true,
            ]);
            $result = $this->service->calculateFee($amount);
            $this->assertGreaterThanOrEqual(0, $result['fee_amount']);
            $this->assertGreaterThanOrEqual(0, $result['mosque_receives']);
            $this->assertGreaterThanOrEqual(1, $result['payment_amount']);
            $this->assertLessThanOrEqual(
                $result['payment_amount'],
                $result['mosque_receives'] + $result['fee_amount']
            );
        }
    }
}

// P7: Upsert Idempotence
public function test_upsert_idempotence(): void
{
    foreach ($this->randomSettingsArrays(30) as $settings) {
        $this->service->updateFeeSettings($settings);
        $after_first = $this->service->getFeeSettings();
        $this->service->updateFeeSettings($settings);
        $after_second = $this->service->getFeeSettings();
        $this->assertEquals($after_first, $after_second);
    }
}

// P8: Valid percentage persistence round-trip
public function test_valid_percentage_persists_correctly(): void
{
    foreach (range(0, 10000, 100) as $pct) {  // Sample across range
        $this->service->updateFeeSettings([
            'fee_percentage' => $pct,
            'fee_mechanism' => 'added_to_donor',
            'fee_active' => true,
        ]);
        $stored = $this->service->getFeeSettings();
        $this->assertEquals($pct, $stored['fee_percentage'],
            "Percentage {$pct} was not persisted correctly");
    }
}

// Edge case: deducted_from_donation when fee_amount >= donation_amount
public function test_deducted_mechanism_mosque_receives_never_negative(): void
{
    $this->service->updateFeeSettings([
        'fee_percentage' => 10000, // 100%
        'fee_mechanism' => 'deducted_from_donation',
        'fee_active' => true,
    ]);
    $result = $this->service->calculateFee(100000);
    $this->assertEquals(0, $result['mosque_receives']);
    $this->assertEquals(100000, $result['payment_amount']);
}

// Helper methods
private function randomAmounts(int $count): array { ... }       // Random int [1, 10_000_000]
private function randomFeeConfigs(int $count): array { ... }    // Random [amount, pct] pairs
private function randomSettingsArrays(int $count): array { ... } // Random settings arrays
```

**Note on PBT Library:** PHP tidak memiliki library property-based testing sematang Hypothesis (Python) atau fast-check (JS). Pendekatan yang direkomendasikan adalah menggunakan `data provider` PHPUnit dengan set data yang cukup besar, atau library seperti [Eris](https://github.com/giorgiosironi/eris) untuk generative testing. Untuk proyek ini, loop-based dengan random seeding menggunakan `mt_rand()` sudah mencukupi.

### Feature Tests (PHPUnit dengan real database)

File: `tests/Feature/Owner/SettingFeatureTest.php` (perlu dibuat)

```php
// 1.2 / 2.4: Non-super-admin mendapat 403
public function test_non_owner_cannot_access_settings(): void

// 1.1 / 8.4: Form pre-populated dengan nilai dari DB
public function test_settings_form_prepopulated_with_stored_values(): void

// 1.4: Default values saat belum ada record
public function test_default_values_shown_when_no_settings_exist(): void

// 8.5: Flash message sukses setelah save berhasil
public function test_success_flash_message_shown_after_update(): void

// 8.6: Error per field saat validasi gagal
public function test_validation_errors_displayed_per_field(): void

// 10.2: Insert saat belum ada record
public function test_upsert_creates_record_when_not_exists(): void

// 10.3: Update tanpa duplikat saat record sudah ada
public function test_upsert_does_not_create_duplicate_records(): void
```

### PHPUnit Configuration

Konfigurasi minimum per test property-based (simulasi):
- Minimal **100 iterasi** per property test menggunakan random seeding
- Setiap property test diberi komentar tag referensi:
  ```php
  // Feature: day9-platform-settings-fee-config, Property 3: Floor Semantics
  ```
