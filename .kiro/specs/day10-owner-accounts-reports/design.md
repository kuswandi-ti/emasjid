# Design Document

## Feature: Day 10 — Kelola Akun Owner & Laporan Fee

---

## Overview

Fitur ini menambahkan dua area fungsional baru pada Owner Panel EMasjid:

1. **Kelola Akun Owner** — CRUD penuh untuk akun `super-admin` lain. Owner dapat menambah, mengubah, dan menghapus akun sesama admin platform. Fitur ini menggunakan DataTable server-side processing untuk daftar, Form Request untuk validasi, dan SweetAlert untuk konfirmasi penghapusan.

2. **Laporan Fee Platform** — Halaman laporan yang menyajikan ringkasan pendapatan fee (total, jumlah donasi, rata-rata) untuk periode bulan tertentu, dilengkapi bar chart 12 bulan menggunakan Chart.js, serta tombol Export CSV untuk keperluan analitik lebih lanjut.

Implementasi mengikuti arsitektur berlapis standar proyek ini: **Controller → Service → Repository → Model**, di bawah namespace `App\Http\Controllers\Owner\`, dengan middleware `auth:web` + role `super-admin`.

---

## Architecture

### Alur UserController (CRUD Akun Owner)

```
HTTP Request
     │
     ▼
┌──────────────────────────────────────┐
│  routes/web.php                      │
│  GET    /owner/users                 │  middleware: auth:web, owner (super-admin)
│  GET    /owner/users/create          │
│  POST   /owner/users                 │
│  GET    /owner/users/{user}/edit     │
│  PUT    /owner/users/{user}          │
│  DELETE /owner/users/{user}          │
└──────────────────┬───────────────────┘
                   │
                   ▼
┌──────────────────────────────────────┐
│  StoreOwnerRequest /                 │  Validation + Authorization
│  UpdateOwnerRequest                  │  authorize(): hasRole('super-admin')
│  (Form Requests)                     │
└──────────────────┬───────────────────┘
                   │
                   ▼
┌──────────────────────────────────────┐
│  UserController                      │  Thin controller
│  (Owner namespace)                   │  index / create / store / edit /
│                                      │  update / destroy
└──────────────────┬───────────────────┘
                   │
                   ▼
┌──────────────────────────────────────┐
│  UserService                         │  Business logic
│                                      │  getOwnerUsers() — query DataTable
│                                      │  createOwner() — create + assign role
│                                      │  updateOwner() — update + cegah self-edit
│                                      │  deleteOwner() — delete + cegah self-delete
└──────────────────┬───────────────────┘
                   │
                   ▼
┌──────────────────────────────────────┐
│  UserRepository                      │  Data access
│  (implements UserRepositoryInterface)│  findOwners() — scope role super-admin
│                                      │  create() / update() / delete()
│                                      │  existsByEmail() — cek duplikasi
└──────────────────┬───────────────────┘
                   │
                   ▼
┌──────────────────────────────────────┐
│  User (Model)                        │  Eloquent + SoftDeletes + HasRoles
│  + Spatie HasRoles trait             │  assignRole() / removeRole()
└──────────────────────────────────────┘
```

**Alur GET /owner/users (DataTable server-side):**
1. Browser memuat halaman → `UserController::index()` mengembalikan view kosong
2. DataTable mengirim AJAX request ke endpoint yang sama dengan parameter `draw`, `start`, `length`, `search`
3. `UserController::index()` mendeteksi request AJAX → memanggil `UserService::getOwnerUsers($request)`
4. Service memanggil `UserRepository::findOwners()` yang menghasilkan query builder scope ke `super-admin`
5. Yajra DataTable memproses query → mengembalikan JSON ke browser

**Alur POST /owner/users (Simpan akun baru):**
1. `StoreOwnerRequest` memvalidasi input dan otorisasi
2. `UserController::store()` memanggil `UserService::createOwner($data)`
3. Service: `UserRepository::create($data)` → `user->assignRole('super-admin')`
4. Redirect ke `owner.users.index` dengan flash `success`

**Alur DELETE /owner/users/{user} (Hapus akun):**
1. `UserController::destroy()` memeriksa `$user->id === Auth::id()` → tolak jika sama
2. Memanggil `UserService::deleteOwner($user)`
3. Service: `user->removeRole('super-admin')` → `UserRepository::delete($user->id)` (soft delete)
4. Kembali redirect dengan flash `success` atau `error`

---

### Alur ReportController (Laporan Fee)

```
HTTP Request
     │
     ▼
┌──────────────────────────────────────┐
│  routes/web.php                      │
│  GET /owner/reports/fee              │  middleware: auth:web, owner
│  GET /owner/reports/fee/export       │
└──────────────────┬───────────────────┘
                   │
                   ▼
┌──────────────────────────────────────┐
│  ReportController                    │  Thin controller
│  (Owner namespace)                   │  feeIndex() / feeExport()
└──────────────────┬───────────────────┘
                   │
                   ▼
┌──────────────────────────────────────┐
│  FeeReportService                    │  Business logic
│                                      │  getSummary($month, $year) → array
│                                      │  getChartData() → array[12]
│                                      │  exportCsv($month, $year) → StreamedResponse
│                                      │  getDetailRows($month, $year) → Collection
└──────────────────┬───────────────────┘
                   │
                   ▼
┌──────────────────────────────────────┐
│  FeeReportRepository                 │  Data access
│  (implements FeeReportRepositoryInt.)│  getSummaryByPeriod($month, $year)
│                                      │  getMonthlyTrend($months = 12)
│                                      │  getConfirmedByPeriod($month, $year)
└──────────────────┬───────────────────┘
                   │
                   ▼
┌──────────────────────────────────────┐
│  Donation (Model)                    │  Query scope:
│                                      │  scopeConfirmed() — WHERE status='confirmed'
│                                      │  scopeInPeriod($m,$y) — WHERE confirmed_at
└──────────────────────────────────────┘
```

**Alur GET /owner/reports/fee (Halaman laporan):**
1. `ReportController::feeIndex()` membaca `$request->input('month', now()->month)` dan `year`
2. Memanggil `FeeReportService::getSummary($month, $year)` → ringkasan angka
3. Memanggil `FeeReportService::getChartData()` → data 12 bulan terakhir untuk Chart.js
4. View di-render dengan `$summary`, `$chartData`, `$selectedMonth`, `$selectedYear`
5. Chart.js di sisi browser menerima data via `@json($chartData)` dan merender bar chart

**Alur GET /owner/reports/fee/export (Export CSV):**
1. `ReportController::feeExport()` membaca `month` dan `year` dari query string
2. Memanggil `FeeReportService::exportCsv($month, $year)` → menghasilkan `StreamedResponse`
3. `StreamedResponse` dikirim ke browser dengan header `Content-Disposition: attachment; filename="laporan-fee-{tahun}-{bulan}.csv"`
4. Data ditulis secara streaming (tidak disimpan di disk)

---

## Components and Interfaces

### 1. `User` Model

**File:** `app/Models/User.php` *(sudah ada, tidak perlu diubah)*

Fitur ini memanfaatkan:
- `HasRoles` dari Spatie Permission → `hasRole()`, `assignRole()`, `removeRole()`
- `SoftDeletes` → penghapusan akun menggunakan soft delete (`deleted_at`)
- Field: `name`, `email`, `password` (hashed), `created_at`

### 2. `Donation` Model

**File:** `app/Models/Donation.php` *(sudah ada)*

Scope yang ditambahkan:

```php
// Scope untuk donasi dengan status confirmed
public function scopeConfirmed(Builder $query): Builder
{
    return $query->where('status', 'confirmed');
}

// Scope untuk donasi dalam periode bulan tertentu (berdasarkan confirmed_at)
public function scopeInPeriod(Builder $query, int $month, int $year): Builder
{
    return $query->whereMonth('confirmed_at', $month)
                 ->whereYear('confirmed_at', $year);
}
```

### 3. `UserRepositoryInterface`

**File:** `app/Contracts/Repositories/UserRepositoryInterface.php`

```php
interface UserRepositoryInterface
{
    /** Semua users dengan role super-admin (untuk DataTable query builder) */
    public function ownersQueryBuilder(): Builder;

    /** Cari user berdasarkan ID, termasuk soft-deleted jika perlu */
    public function find(int $id): ?User;

    /** Cek apakah email sudah dipakai user lain (exclude $exceptId) */
    public function existsByEmail(string $email, ?int $exceptId = null): bool;

    /** Buat user baru */
    public function create(array $data): User;

    /** Update user berdasarkan ID */
    public function update(int $id, array $data): bool;

    /** Soft delete user berdasarkan ID */
    public function delete(int $id): bool;
}
```

### 4. `UserRepository`

**File:** `app/Repositories/UserRepository.php`

```php
class UserRepository implements UserRepositoryInterface
{
    public function ownersQueryBuilder(): Builder
    {
        // Bergabung dengan model_has_roles untuk filter role super-admin
        return User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'super-admin'))
            ->select(['users.id', 'users.name', 'users.email', 'users.created_at']);
    }

    public function existsByEmail(string $email, ?int $exceptId = null): bool
    {
        return User::where('email', $email)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->exists();
    }

    public function create(array $data): User
    {
        return User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => $data['password'], // sudah di-hash di Service
        ]);
    }

    public function update(int $id, array $data): bool
    {
        return User::where('id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        return User::where('id', $id)->delete(); // soft delete
    }
}
```

### 5. `UserService`

**File:** `app/Services/UserService.php`

```php
class UserService
{
    public function __construct(private UserRepositoryInterface $userRepo) {}

    /**
     * Mengembalikan query builder untuk Yajra DataTable.
     */
    public function getOwnerQueryBuilder(): Builder
    {
        return $this->userRepo->ownersQueryBuilder();
    }

    /**
     * Membuat akun owner baru dan menetapkan role super-admin.
     * @throws \InvalidArgumentException jika email sudah ada
     */
    public function createOwner(array $data): User
    {
        if ($this->userRepo->existsByEmail($data['email'])) {
            throw new \InvalidArgumentException('Email sudah terdaftar.');
        }

        $user = $this->userRepo->create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->assignRole('super-admin');

        return $user;
    }

    /**
     * Memperbarui akun owner.
     * Jika password tidak diisi (null/empty), password lama dipertahankan.
     * @throws \InvalidArgumentException jika email dipakai user lain
     * @throws \DomainException jika mencoba edit akun sendiri
     */
    public function updateOwner(User $user, array $data, int $currentUserId): bool
    {
        if ($user->id === $currentUserId) {
            throw new \DomainException('Anda tidak dapat mengedit akun Anda sendiri.');
        }

        if ($this->userRepo->existsByEmail($data['email'], $user->id)) {
            throw new \InvalidArgumentException('Email sudah terdaftar.');
        }

        $updateData = [
            'name'  => $data['name'],
            'email' => $data['email'],
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        return $this->userRepo->update($user->id, $updateData);
    }

    /**
     * Menghapus akun owner beserta role-nya.
     * @throws \DomainException jika mencoba hapus akun sendiri
     */
    public function deleteOwner(User $user, int $currentUserId): bool
    {
        if ($user->id === $currentUserId) {
            throw new \DomainException('Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $user->removeRole('super-admin');

        return $this->userRepo->delete($user->id);
    }
}
```

### 6. `StoreOwnerRequest`

**File:** `app/Http/Requests/StoreOwnerRequest.php`

```php
public function authorize(): bool
{
    return $this->user()?->hasRole('super-admin');
}

public function rules(): array
{
    return [
        'name'     => ['required', 'string', 'max:255'],
        'email'    => ['required', 'email', 'max:255'],
        'password' => ['required', 'string', 'min:8'],
    ];
}

public function messages(): array
{
    return [
        'name.required'     => 'Nama wajib diisi.',
        'email.required'    => 'Email wajib diisi.',
        'email.email'       => 'Format email tidak valid.',
        'password.required' => 'Password wajib diisi.',
        'password.min'      => 'Password minimal 8 karakter.',
    ];
}
```

### 7. `UpdateOwnerRequest`

**File:** `app/Http/Requests/UpdateOwnerRequest.php`

```php
public function authorize(): bool
{
    return $this->user()?->hasRole('super-admin');
}

public function rules(): array
{
    $userId = $this->route('user')?->id;

    return [
        'name'     => ['required', 'string', 'max:255'],
        'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
        'password' => ['nullable', 'string', 'min:8'],
    ];
}

public function messages(): array
{
    return [
        'name.required'   => 'Nama wajib diisi.',
        'email.required'  => 'Email wajib diisi.',
        'email.email'     => 'Format email tidak valid.',
        'email.unique'    => 'Email sudah terdaftar.',
        'password.min'    => 'Password minimal 8 karakter.',
    ];
}
```

> **Catatan:** Validasi email unique sudah ditangani di `UpdateOwnerRequest` via `Rule::unique()->ignore($userId)`. `UserService::updateOwner()` juga melakukan cek duplikasi manual untuk kasus yang lebih kompleks — keduanya bersifat saling melengkapi.

### 8. `UserController`

**File:** `app/Http/Controllers/Owner/UserController.php`

```php
class UserController extends Controller
{
    public function __construct(private UserService $userService) {}

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $query = $this->userService->getOwnerQueryBuilder();
            return DataTables::of($query)
                ->addColumn('action', fn ($user) => view('owner.users._action', compact('user'))->render())
                ->rawColumns(['action'])
                ->toJson();
        }
        return view('owner.users.index');
    }

    public function create(): View
    {
        return view('owner.users.create');
    }

    public function store(StoreOwnerRequest $request): RedirectResponse
    {
        try {
            $this->userService->createOwner($request->validated());
            return redirect()->route('owner.users.index')
                ->with('success', 'Akun Admin Platform berhasil ditambahkan.');
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit(User $user): View
    {
        return view('owner.users.edit', compact('user'));
    }

    public function update(UpdateOwnerRequest $request, User $user): RedirectResponse
    {
        try {
            $this->userService->updateOwner($user, $request->validated(), Auth::id());
            return redirect()->route('owner.users.index')
                ->with('success', 'Akun Admin Platform berhasil diperbarui.');
        } catch (\DomainException|\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function destroy(User $user): RedirectResponse
    {
        try {
            $this->userService->deleteOwner($user, Auth::id());
            return redirect()->route('owner.users.index')
                ->with('success', 'Akun Admin Platform berhasil dihapus.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
```

### 9. `FeeReportRepositoryInterface`

**File:** `app/Contracts/Repositories/FeeReportRepositoryInterface.php`

```php
interface FeeReportRepositoryInterface
{
    /**
     * Ringkasan fee: total_fee, count, average untuk periode bulan+tahun.
     * Hanya donasi dengan status 'confirmed'.
     * @return array{total_fee: int, count: int, average: float}
     */
    public function getSummaryByPeriod(int $month, int $year): array;

    /**
     * Tren fee 12 bulan terakhir (untuk Chart.js).
     * @return array{label: string, total_fee: int}[]  — 12 elemen, urutan ASC
     */
    public function getMonthlyTrend(int $months = 12): array;

    /**
     * Baris detail donasi confirmed untuk export CSV.
     * @return Collection<Donation> with mosque relationship eager-loaded
     */
    public function getConfirmedByPeriod(int $month, int $year): Collection;
}
```

### 10. `FeeReportRepository`

**File:** `app/Repositories/FeeReportRepository.php`

```php
class FeeReportRepository implements FeeReportRepositoryInterface
{
    public function getSummaryByPeriod(int $month, int $year): array
    {
        $result = Donation::confirmed()
            ->inPeriod($month, $year)
            ->selectRaw('
                COALESCE(SUM(fee_amount), 0) as total_fee,
                COUNT(*) as count
            ')
            ->first();

        $count    = (int) $result->count;
        $totalFee = (int) $result->total_fee;
        $average  = $count > 0 ? $totalFee / $count : 0.0;

        return [
            'total_fee' => $totalFee,
            'count'     => $count,
            'average'   => $average,
        ];
    }

    public function getMonthlyTrend(int $months = 12): array
    {
        // Bangun 12 bulan terakhir sebagai "grid" penuh
        $result = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i)->startOfMonth();
            $result[] = [
                'label'     => $date->format('M Y'),
                'month'     => (int) $date->month,
                'year'      => (int) $date->year,
                'total_fee' => 0,
            ];
        }

        // Query satu kali untuk semua 12 bulan
        $start = now()->subMonths($months - 1)->startOfMonth();
        $rows  = Donation::confirmed()
            ->where('confirmed_at', '>=', $start)
            ->selectRaw("
                MONTH(confirmed_at) as month,
                YEAR(confirmed_at)  as year,
                COALESCE(SUM(fee_amount), 0) as total_fee
            ")
            ->groupBy('year', 'month')
            ->get()
            ->keyBy(fn ($r) => "{$r->year}-{$r->month}");

        // Gabungkan dengan grid penuh (bulan tanpa data → 0)
        foreach ($result as &$slot) {
            $key = "{$slot['year']}-{$slot['month']}";
            if ($rows->has($key)) {
                $slot['total_fee'] = (int) $rows[$key]->total_fee;
            }
        }

        return $result;
    }

    public function getConfirmedByPeriod(int $month, int $year): Collection
    {
        return Donation::confirmed()
            ->inPeriod($month, $year)
            ->with('mosque:id,name')
            ->orderBy('confirmed_at')
            ->get();
    }
}
```

### 11. `FeeReportService`

**File:** `app/Services/FeeReportService.php`

```php
class FeeReportService
{
    public function __construct(private FeeReportRepositoryInterface $reportRepo) {}

    /**
     * Ringkasan statistik fee untuk periode terpilih.
     */
    public function getSummary(int $month, int $year): array
    {
        return $this->reportRepo->getSummaryByPeriod($month, $year);
    }

    /**
     * Data chart 12 bulan terakhir (labels + values untuk Chart.js).
     */
    public function getChartData(): array
    {
        $trend = $this->reportRepo->getMonthlyTrend(12);

        return [
            'labels' => array_column($trend, 'label'),
            'values' => array_column($trend, 'total_fee'),
        ];
    }

    /**
     * Generate CSV sebagai StreamedResponse (tidak disimpan ke disk).
     */
    public function exportCsv(int $month, int $year): StreamedResponse
    {
        $filename    = sprintf('laporan-fee-%04d-%02d.csv', $year, $month);
        $donations   = $this->reportRepo->getConfirmedByPeriod($month, $year);

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($donations) {
            $handle = fopen('php://output', 'w');

            // BOM untuk Excel UTF-8 compatibility
            fputs($handle, "\xEF\xBB\xBF");

            // Header baris
            fputcsv($handle, [
                'Tanggal Konfirmasi',
                'ID Donasi',
                'Nama Masjid',
                'Nominal Donasi',
                'Fee',
                'Mekanisme Fee',
            ]);

            // Baris data
            foreach ($donations as $donation) {
                fputcsv($handle, [
                    $donation->confirmed_at?->format('Y-m-d H:i:s') ?? '-',
                    $donation->id,
                    $donation->mosque?->name ?? '-',
                    $donation->amount,
                    $donation->fee_amount,
                    $donation->fee_mechanism,
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
```

### 12. `ReportController`

**File:** `app/Http/Controllers/Owner/ReportController.php`

```php
class ReportController extends Controller
{
    public function __construct(private FeeReportService $reportService) {}

    public function feeIndex(Request $request): View
    {
        $selectedMonth = (int) $request->input('month', now()->month);
        $selectedYear  = (int) $request->input('year', now()->year);

        $summary   = $this->reportService->getSummary($selectedMonth, $selectedYear);
        $chartData = $this->reportService->getChartData();

        return view('owner.reports.fee', compact(
            'summary', 'chartData', 'selectedMonth', 'selectedYear'
        ));
    }

    public function feeExport(Request $request): StreamedResponse
    {
        $month = (int) $request->input('month', now()->month);
        $year  = (int) $request->input('year', now()->year);

        return $this->reportService->exportCsv($month, $year);
    }
}
```

---

## Data Models

### Query Utama: Ringkasan Fee Bulanan

```sql
-- Dieksekusi oleh FeeReportRepository::getSummaryByPeriod($month, $year)
SELECT
    COALESCE(SUM(fee_amount), 0) AS total_fee,
    COUNT(*)                     AS count
FROM donations
WHERE status       = 'confirmed'
  AND MONTH(confirmed_at) = :month
  AND YEAR(confirmed_at)  = :year;
```

Rata-rata dihitung di PHP: `average = count > 0 ? total_fee / count : 0`.

### Query Utama: Tren 12 Bulan (Chart.js)

```sql
-- Dieksekusi oleh FeeReportRepository::getMonthlyTrend(12)
SELECT
    MONTH(confirmed_at) AS month,
    YEAR(confirmed_at)  AS year,
    COALESCE(SUM(fee_amount), 0) AS total_fee
FROM donations
WHERE status       = 'confirmed'
  AND confirmed_at >= :start_of_12_months_ago
GROUP BY YEAR(confirmed_at), MONTH(confirmed_at)
ORDER BY year ASC, month ASC;
```

Grid 12 slot selalu diisi penuh di PHP — bulan tanpa data mendapat `total_fee = 0`.

### Query Utama: Detail CSV Export

```sql
-- Dieksekusi oleh FeeReportRepository::getConfirmedByPeriod($month, $year)
SELECT
    d.confirmed_at,
    d.id,
    m.name AS mosque_name,
    d.amount,
    d.fee_amount,
    d.fee_mechanism
FROM donations d
LEFT JOIN mosques m ON m.id = d.mosque_id
WHERE d.status       = 'confirmed'
  AND MONTH(d.confirmed_at) = :month
  AND YEAR(d.confirmed_at)  = :year
ORDER BY d.confirmed_at ASC;
```

### Query Utama: Daftar Owner (DataTable Server-Side)

```sql
-- Dieksekusi oleh UserRepository::ownersQueryBuilder() → Yajra DataTables
SELECT
    users.id,
    users.name,
    users.email,
    users.created_at
FROM users
INNER JOIN model_has_roles mhr ON mhr.model_id = users.id
    AND mhr.model_type = 'App\\Models\\User'
INNER JOIN roles r ON r.id = mhr.role_id
    AND r.name = 'super-admin'
WHERE users.deleted_at IS NULL;
```

### Tipe Data Laporan

```
SummaryResult:
  total_fee : int   ≥ 0  (SUM fee_amount, satuan Rupiah)
  count     : int   ≥ 0  (jumlah donasi confirmed)
  average   : float ≥ 0  (total_fee / count; 0 jika count = 0)

ChartData:
  labels : string[12]  (format "MMM YYYY", misal "Jan 2026")
  values : int[12]     (total_fee per bulan, 0 jika tidak ada data)

CsvRow:
  confirmed_at  : string  (format "YYYY-MM-DD HH:MM:SS" atau "-")
  donation_id   : int
  mosque_name   : string  (atau "-" jika null)
  amount        : int     (Rupiah)
  fee_amount    : int     (Rupiah)
  fee_mechanism : string  ("added_to_donor" | "deducted_from_donation")
```

### Indeks Database yang Direkomendasikan

```php
// Untuk query laporan — sudah ada di database-design.md
$table->index(['status', 'confirmed_at']);
// Tambahan jika belum ada:
$table->index(['status', 'confirmed_at', 'fee_amount']); // covering index untuk SUM query
```

---

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system — essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: DataTable Hanya Menampilkan Owner

*For any* jumlah N user dengan role `super-admin` yang ada di database, endpoint DataTable SHALL mengembalikan tepat N baris — tidak lebih, tidak kurang — dan tidak satu pun baris yang berasal dari user tanpa role `super-admin`.

**Validates: Requirements 1.2**

### Property 2: Validasi Email Duplikat (Create)

*For any* email yang sudah terdaftar di tabel `users`, percobaan membuat akun owner baru dengan email tersebut SHALL selalu ditolak dengan pesan "Email sudah terdaftar.", terlepas dari nilai `name` atau `password`.

**Validates: Requirements 2.3**

### Property 3: Validasi Password Pendek

*For any* string dengan panjang antara 1 sampai 7 karakter (inklusif), percobaan membuat atau memperbarui akun owner SHALL selalu ditolak dengan pesan "Password minimal 8 karakter."

**Validates: Requirements 2.4**

### Property 4: Password Dipertahankan saat Field Kosong

*For any* akun owner dengan password apapun yang tersimpan di database, mengirim form update dengan field `password` kosong SHALL menghasilkan password yang tersimpan tetap sama (hash tidak berubah), sementara perubahan `name` dan `email` yang valid tetap diterapkan.

**Validates: Requirements 3.4**

### Property 5: Cegah Self-Edit dan Self-Delete

*For any* akun owner yang sedang terautentikasi, mencoba mengedit atau menghapus akun miliknya sendiri (via `PUT /owner/users/{own_id}` atau `DELETE /owner/users/{own_id}`) SHALL selalu menghasilkan error — tidak ada perubahan data yang terjadi di database.

**Validates: Requirements 3.7, 4.4**

### Property 6: Kalkulasi Ringkasan Laporan

*For any* kumpulan N donasi dengan `status = 'confirmed'` dan `confirmed_at` dalam bulan M tahun Y, nilai `total_fee` yang dikembalikan oleh `getSummary(M, Y)` SHALL sama persis dengan `SUM(fee_amount)` dari N donasi tersebut, nilai `count` SHALL sama persis dengan N, dan nilai `average` SHALL sama persis dengan `total_fee / N` (atau 0 jika N = 0).

**Validates: Requirements 5.1, 5.3**

### Property 7: Filter Periode Laporan

*For any* kumpulan donasi yang tersebar di berbagai bulan, memanggil `getSummary(M, Y)` SHALL hanya menghitung donasi dengan `confirmed_at` dalam bulan M tahun Y — donasi dari bulan lain tidak boleh ikut terhitung, terlepas dari nilai `fee_amount`-nya.

**Validates: Requirements 5.2, 5.3**

### Property 8: Chart Selalu 12 Data Point

*For any* keadaan database (berapapun jumlah donasi yang ada, termasuk nol), `getChartData()` SHALL selalu mengembalikan array `labels` dengan tepat 12 elemen dan array `values` dengan tepat 12 elemen, masing-masing mewakili satu bulan dari 12 bulan terakhir secara berurutan.

**Validates: Requirements 6.1, 6.3**

### Property 9: CSV Berisi Semua Baris yang Tepat

*For any* kumpulan N donasi `confirmed` dalam periode M/Y, file CSV yang di-generate SHALL memiliki tepat N baris data (di luar baris header), dan setiap baris SHALL memuat keenam kolom yang diminta: tanggal konfirmasi, ID donasi, nama masjid, nominal donasi, fee, mekanisme fee — tidak ada baris yang terlewat atau kolom yang kosong untuk donasi yang ada datanya.

**Validates: Requirements 7.2**

### Property 10: Format Nama File CSV

*For any* kombinasi bulan (1–12) dan tahun valid yang dipilih Owner, nama file CSV yang dihasilkan SHALL selalu mengikuti pola `laporan-fee-{YYYY}-{MM}.csv` — dengan tahun 4 digit dan bulan 2 digit (zero-padded).

**Validates: Requirements 7.3**

---

## Error Handling

### HTTP 403 — Unauthorized

- Terjadi ketika user bukan `super-admin` mengakses route owner
- `StoreOwnerRequest::authorize()` / `UpdateOwnerRequest::authorize()` mengembalikan `false` → Laravel throw `AuthorizationException` → HTTP 403
- Controller endpoint `destroy` juga dilindungi middleware route-level

### Validation Error (HTTP 422 / Redirect Back)

| Field | Kondisi | Pesan |
|-------|---------|-------|
| `email` (store) | Email sudah ada di DB | "Email sudah terdaftar." |
| `password` (store) | Panjang < 8 karakter | "Password minimal 8 karakter." |
| `email` (update) | Email dipakai user lain | "Email sudah terdaftar." |
| `password` (update) | Diisi tapi < 8 karakter | "Password minimal 8 karakter." |

Validasi ditangani di Form Request → Laravel redirect `back()` dengan `$errors` → View menampilkan `@error` per field.

### Self-Action Prevention

- `UserService::updateOwner()` melempar `\DomainException` jika `$user->id === $currentUserId`
- `UserService::deleteOwner()` melempar `\DomainException` yang sama
- `UserController` menangkap exception ini dan redirect `back()` dengan flash `error`

### Exception Database

- `UserController::store()` dan `update()` membungkus operasi dalam `try/catch (\Exception $e)`
- Kegagalan database menghasilkan flash `error` dan redirect `back()->withInput()`

### Laporan dengan Data Kosong

- `FeeReportRepository::getSummaryByPeriod()` menggunakan `COALESCE(SUM(...), 0)` → tidak pernah mengembalikan null
- Jika tidak ada donasi: `total_fee = 0`, `count = 0`, `average = 0.0`
- View menampilkan pesan "Belum ada data fee pada periode ini." ketika `count === 0`

### Export CSV Tanpa Data

- `FeeReportService::exportCsv()` tetap menghasilkan file CSV valid dengan hanya baris header (6 kolom)
- File dikirim sebagai `StreamedResponse` — tidak ada file yang tersimpan di disk

### Period Parameter Tidak Valid

- `ReportController::feeIndex()` dan `feeExport()` membaca `month` dan `year` dari query string
- Jika parameter tidak ada, default ke bulan dan tahun saat ini (`now()->month`, `now()->year`)
- Tidak ada validasi format tambahan — nilai integer yang tidak valid (misalnya bulan 13) akan menghasilkan query yang mengembalikan data kosong, bukan error

---

## Testing Strategy

### Pendekatan Dual Testing

- **Unit tests**: Menguji logika service dan repository secara terisolasi dengan mock
- **Feature tests**: Menguji flow HTTP lengkap dengan database in-memory (SQLite atau MySQL via RefreshDatabase)

### Catatan PBT di PHP

PHP tidak memiliki library property-based testing sekelas Hypothesis (Python) atau fast-check (TypeScript). Pendekatan yang digunakan adalah **loop-based dengan data yang dihasilkan secara random** menggunakan `mt_rand()` dan `Faker`. Setiap property test dijalankan minimal **100 iterasi**. Library [Eris](https://github.com/giorgiosironi/eris) dapat digunakan sebagai alternatif jika diintegrasikan ke proyek.

---

### Unit Tests — `UserServiceTest`

**File:** `tests/Unit/Services/UserServiceTest.php`

```php
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    private UserService $service;
    private $userRepo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepo = Mockery::mock(UserRepositoryInterface::class);
        $this->service  = new UserService($this->userRepo);
    }

    // ── Property 2: Email duplikat selalu ditolak ─────────────────────

    /**
     * Feature: day10-owner-accounts-reports, Property 2: Email Duplikat (Create)
     */
    public function test_create_owner_rejects_any_existing_email(): void
    {
        $faker = \Faker\Factory::create('id_ID');

        for ($i = 0; $i < 100; $i++) {
            $email = $faker->unique()->safeEmail();

            $this->userRepo->shouldReceive('existsByEmail')
                ->with($email, null)
                ->once()
                ->andReturn(true);

            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage('Email sudah terdaftar.');

            $this->service->createOwner([
                'name'     => $faker->name(),
                'email'    => $email,
                'password' => $faker->password(8),
            ]);
        }
    }

    // ── Property 4: Password dipertahankan saat field kosong ──────────

    /**
     * Feature: day10-owner-accounts-reports, Property 4: Password Dipertahankan
     */
    public function test_update_owner_preserves_password_when_field_is_empty(): void
    {
        $faker = \Faker\Factory::create('id_ID');

        for ($i = 0; $i < 100; $i++) {
            $user     = Mockery::mock(User::class)->makePartial();
            $user->id = mt_rand(2, 9999);

            $this->userRepo->shouldReceive('existsByEmail')
                ->once()
                ->andReturn(false);

            // Pastikan 'password' tidak masuk ke update data
            $this->userRepo->shouldReceive('update')
                ->once()
                ->withArgs(function ($id, $data) {
                    return !array_key_exists('password', $data);
                })
                ->andReturn(true);

            $this->service->updateOwner($user, [
                'name'     => $faker->name(),
                'email'    => $faker->safeEmail(),
                'password' => '',   // field kosong
            ], currentUserId: 1);   // currentUserId berbeda dari user->id
        }
    }

    // ── Property 5: Self-edit selalu ditolak ──────────────────────────

    /**
     * Feature: day10-owner-accounts-reports, Property 5: Cegah Self-Edit
     */
    public function test_update_owner_always_rejects_self_edit(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $userId = mt_rand(1, 9999);
            $user   = Mockery::mock(User::class)->makePartial();
            $user->id = $userId;

            $this->expectException(\DomainException::class);

            $this->service->updateOwner($user, [
                'name'  => 'Test',
                'email' => 'test@example.com',
            ], currentUserId: $userId); // currentUserId === user->id → harus ditolak
        }
    }

    // ── Property 5: Self-delete selalu ditolak ────────────────────────

    /**
     * Feature: day10-owner-accounts-reports, Property 5: Cegah Self-Delete
     */
    public function test_delete_owner_always_rejects_self_delete(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $userId = mt_rand(1, 9999);
            $user   = Mockery::mock(User::class)->makePartial();
            $user->id = $userId;

            $this->expectException(\DomainException::class);

            $this->service->deleteOwner($user, currentUserId: $userId);
        }
    }
}
```

---

### Unit Tests — `FeeReportServiceTest`

**File:** `tests/Unit/Services/FeeReportServiceTest.php`

```php
use App\Contracts\Repositories\FeeReportRepositoryInterface;
use App\Services\FeeReportService;
use Mockery;
use Tests\TestCase;

class FeeReportServiceTest extends TestCase
{
    private FeeReportService $service;
    private $reportRepo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reportRepo = Mockery::mock(FeeReportRepositoryInterface::class);
        $this->service    = new FeeReportService($this->reportRepo);
    }

    // ── Property 8: Chart selalu 12 data point ────────────────────────

    /**
     * Feature: day10-owner-accounts-reports, Property 8: Chart Selalu 12 Data Point
     */
    public function test_chart_data_always_has_exactly_12_points(): void
    {
        for ($i = 0; $i < 100; $i++) {
            // Generate random trend data dengan jumlah data bervariasi
            $randomTrend = $this->makeRandomTrend(12);

            $this->reportRepo->shouldReceive('getMonthlyTrend')
                ->with(12)
                ->once()
                ->andReturn($randomTrend);

            $chartData = $this->service->getChartData();

            $this->assertCount(12, $chartData['labels'],
                'Chart labels harus selalu 12 elemen');
            $this->assertCount(12, $chartData['values'],
                'Chart values harus selalu 12 elemen');
        }
    }

    // ── Property 6: Kalkulasi ringkasan akurat ────────────────────────

    /**
     * Feature: day10-owner-accounts-reports, Property 6: Kalkulasi Ringkasan
     */
    public function test_summary_values_match_expected_aggregation(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $count    = mt_rand(0, 200);
            $totalFee = $count === 0 ? 0 : mt_rand(0, 5_000_000);
            $avg      = $count > 0 ? $totalFee / $count : 0.0;
            $month    = mt_rand(1, 12);
            $year     = mt_rand(2020, 2030);

            $this->reportRepo->shouldReceive('getSummaryByPeriod')
                ->with($month, $year)
                ->once()
                ->andReturn([
                    'total_fee' => $totalFee,
                    'count'     => $count,
                    'average'   => $avg,
                ]);

            $summary = $this->service->getSummary($month, $year);

            $this->assertEquals($totalFee, $summary['total_fee']);
            $this->assertEquals($count, $summary['count']);
            $this->assertEquals($avg, $summary['average']);
        }
    }

    private function makeRandomTrend(int $months): array
    {
        $trend = [];
        $faker = \Faker\Factory::create('id_ID');
        for ($m = $months - 1; $m >= 0; $m--) {
            $date    = now()->subMonths($m)->startOfMonth();
            $trend[] = [
                'label'     => $date->format('M Y'),
                'month'     => (int) $date->month,
                'year'      => (int) $date->year,
                'total_fee' => mt_rand(0, 10_000_000),
            ];
        }
        return $trend;
    }
}
```

---

### Feature Tests — `UserFeatureTest`

**File:** `tests/Feature/Owner/UserFeatureTest.php`

```php
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->owner = User::factory()->create();
        $this->owner->assignRole('super-admin');
    }

    // ── Property 1: DataTable hanya menampilkan owner ─────────────────

    /**
     * Feature: day10-owner-accounts-reports, Property 1: DataTable Hanya Menampilkan Owner
     */
    public function test_datatable_returns_only_super_admin_users(): void
    {
        for ($iteration = 0; $iteration < 30; $iteration++) {
            // Buat N super-admin (berbeda tiap iterasi) + M non-super-admin
            $n = mt_rand(1, 10);
            $m = mt_rand(0, 5);

            $superAdmins  = User::factory()->count($n)->create();
            $nonAdmins    = User::factory()->count($m)->create();

            foreach ($superAdmins as $u) {
                $u->assignRole('super-admin');
            }

            $response = $this->actingAs($this->owner)
                ->getJson(route('owner.users.index'), ['X-Requested-With' => 'XMLHttpRequest']);

            $response->assertOk();
            $total = $response->json('recordsTotal');

            // +1 karena $this->owner juga super-admin
            $expectedTotal = $n + 1;
            $this->assertEquals($expectedTotal, $total,
                "Iterasi {$iteration}: DataTable harus menampilkan tepat {$expectedTotal} super-admin");

            // Cleanup untuk iterasi berikutnya
            foreach ($superAdmins as $u) { $u->delete(); }
            foreach ($nonAdmins as $u) { $u->delete(); }
        }
    }

    // ── Property 3: Validasi password pendek ─────────────────────────

    /**
     * Feature: day10-owner-accounts-reports, Property 3: Validasi Password Pendek
     */
    public function test_store_rejects_any_password_shorter_than_8_chars(): void
    {
        $faker = \Faker\Factory::create('id_ID');

        for ($length = 1; $length <= 7; $length++) {
            // Uji setiap panjang 1–7 dengan beberapa karakter berbeda
            for ($j = 0; $j < 10; $j++) {
                $shortPassword = $faker->lexify(str_repeat('?', $length));

                $response = $this->actingAs($this->owner)
                    ->post(route('owner.users.store'), [
                        'name'     => $faker->name(),
                        'email'    => $faker->unique()->safeEmail(),
                        'password' => $shortPassword,
                    ]);

                $response->assertSessionHasErrors('password');
            }
        }
    }

    // ── Property 4: Password dipertahankan ───────────────────────────

    /**
     * Feature: day10-owner-accounts-reports, Property 4: Password Dipertahankan
     */
    public function test_update_preserves_password_when_field_empty(): void
    {
        $faker = \Faker\Factory::create('id_ID');

        for ($i = 0; $i < 50; $i++) {
            $originalPassword = $faker->password(8, 20);
            $target = User::factory()->create(['password' => bcrypt($originalPassword)]);
            $target->assignRole('super-admin');

            $this->actingAs($this->owner)->put(route('owner.users.update', $target), [
                'name'     => $faker->name(),
                'email'    => $faker->unique()->safeEmail(),
                'password' => '',
            ]);

            // Password lama masih bisa digunakan login
            $this->assertTrue(
                \Hash::check($originalPassword, $target->fresh()->password),
                "Iterasi {$i}: password harus tetap sama setelah update tanpa password baru"
            );

            $target->delete();
        }
    }

    // ── Property 5: Self-edit dan self-delete ditolak ─────────────────

    /**
     * Feature: day10-owner-accounts-reports, Property 5: Cegah Self-Edit
     */
    public function test_owner_cannot_edit_own_account(): void
    {
        $faker = \Faker\Factory::create('id_ID');

        for ($i = 0; $i < 50; $i++) {
            $response = $this->actingAs($this->owner)
                ->put(route('owner.users.update', $this->owner), [
                    'name'  => $faker->name(),
                    'email' => $faker->safeEmail(),
                ]);

            $response->assertSessionHas('error');
        }
    }

    /**
     * Feature: day10-owner-accounts-reports, Property 5: Cegah Self-Delete
     */
    public function test_owner_cannot_delete_own_account(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $response = $this->actingAs($this->owner)
                ->delete(route('owner.users.destroy', $this->owner));

            $response->assertSessionHas('error');
            // Pastikan akun tidak terhapus
            $this->assertNotNull(User::find($this->owner->id));
        }
    }

    // ── Contoh: Redirect ke daftar setelah create ─────────────────────
    public function test_store_redirects_to_index_after_success(): void
    {
        $response = $this->actingAs($this->owner)
            ->post(route('owner.users.store'), [
                'name'     => 'Admin Baru',
                'email'    => 'admin.baru@example.com',
                'password' => 'password123',
            ]);

        $response->assertRedirect(route('owner.users.index'));
        $response->assertSessionHas('success');
    }

    // ── Contoh: Form edit pre-populated ───────────────────────────────
    public function test_edit_form_is_prepopulated_with_stored_values(): void
    {
        $faker  = \Faker\Factory::create('id_ID');
        $target = User::factory()->create([
            'name'  => 'Nama Tersimpan',
            'email' => 'email.tersimpan@example.com',
        ]);
        $target->assignRole('super-admin');

        $response = $this->actingAs($this->owner)
            ->get(route('owner.users.edit', $target));

        $response->assertOk();
        $response->assertSee('Nama Tersimpan');
        $response->assertSee('email.tersimpan@example.com');
    }
}
```

---

### Feature Tests — `FeeReportFeatureTest`

**File:** `tests/Feature/Owner/FeeReportFeatureTest.php`

```php
use App\Models\Donation;
use App\Models\Mosque;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FeeReportFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Mosque $mosque;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->owner  = User::factory()->create();
        $this->owner->assignRole('super-admin');
        $this->mosque = Mosque::factory()->create();
    }

    // ── Property 6: Kalkulasi ringkasan ───────────────────────────────

    /**
     * Feature: day10-owner-accounts-reports, Property 6: Kalkulasi Ringkasan Laporan
     */
    public function test_summary_total_fee_equals_sum_of_confirmed_donations(): void
    {
        for ($iteration = 0; $iteration < 50; $iteration++) {
            $month = mt_rand(1, 12);
            $year  = mt_rand(2022, 2025);
            $n     = mt_rand(1, 20);

            $totalFeeExpected = 0;

            // Buat N donasi confirmed dalam periode M/Y
            for ($j = 0; $j < $n; $j++) {
                $fee = mt_rand(100, 50_000);
                $totalFeeExpected += $fee;
                Donation::factory()->create([
                    'mosque_id'    => $this->mosque->id,
                    'user_id'      => $this->owner->id,
                    'status'       => 'confirmed',
                    'fee_amount'   => $fee,
                    'confirmed_at' => \Carbon\Carbon::create($year, $month, mt_rand(1, 28)),
                ]);
            }

            // Buat beberapa donasi di bulan lain (tidak boleh ikut terhitung)
            Donation::factory()->count(mt_rand(1, 5))->create([
                'mosque_id'    => $this->mosque->id,
                'user_id'      => $this->owner->id,
                'status'       => 'confirmed',
                'confirmed_at' => \Carbon\Carbon::create($year, $month === 12 ? 11 : $month + 1, 1),
            ]);

            $response = $this->actingAs($this->owner)
                ->get(route('owner.reports.fee.index', ['month' => $month, 'year' => $year]));

            $response->assertOk();
            $response->assertViewHas('summary', function ($summary) use ($totalFeeExpected, $n) {
                return $summary['total_fee'] === $totalFeeExpected
                    && $summary['count'] === $n;
            });

            // Cleanup
            Donation::truncate();
        }
    }

    // ── Property 7: Filter periode ────────────────────────────────────

    /**
     * Feature: day10-owner-accounts-reports, Property 7: Filter Periode Laporan
     */
    public function test_summary_excludes_donations_outside_selected_period(): void
    {
        for ($iteration = 0; $iteration < 30; $iteration++) {
            $targetMonth = mt_rand(1, 12);
            $targetYear  = 2024;

            // Buat donasi di bulan target
            $inPeriod = mt_rand(1, 10);
            $totalFeeInPeriod = 0;
            for ($j = 0; $j < $inPeriod; $j++) {
                $fee = mt_rand(500, 10_000);
                $totalFeeInPeriod += $fee;
                Donation::factory()->create([
                    'mosque_id'    => $this->mosque->id,
                    'user_id'      => $this->owner->id,
                    'status'       => 'confirmed',
                    'fee_amount'   => $fee,
                    'confirmed_at' => \Carbon\Carbon::create($targetYear, $targetMonth, 15),
                ]);
            }

            // Buat donasi di bulan yang berbeda (harus diabaikan)
            $otherMonth = $targetMonth === 12 ? 11 : $targetMonth + 1;
            Donation::factory()->count(mt_rand(1, 10))->create([
                'mosque_id'    => $this->mosque->id,
                'user_id'      => $this->owner->id,
                'status'       => 'confirmed',
                'fee_amount'   => 999_999, // fee besar, seharusnya tidak terhitung
                'confirmed_at' => \Carbon\Carbon::create($targetYear, $otherMonth, 15),
            ]);

            $response = $this->actingAs($this->owner)
                ->get(route('owner.reports.fee.index', [
                    'month' => $targetMonth,
                    'year'  => $targetYear,
                ]));

            $response->assertViewHas('summary', function ($summary) use ($totalFeeInPeriod, $inPeriod) {
                return $summary['total_fee'] === $totalFeeInPeriod
                    && $summary['count'] === $inPeriod;
            });

            Donation::truncate();
        }
    }

    // ── Property 9 & 10: CSV content dan filename ─────────────────────

    /**
     * Feature: day10-owner-accounts-reports, Property 9: CSV Berisi Semua Baris
     * Feature: day10-owner-accounts-reports, Property 10: Format Nama File CSV
     */
    public function test_csv_export_contains_correct_rows_and_filename(): void
    {
        for ($iteration = 0; $iteration < 30; $iteration++) {
            $month = mt_rand(1, 12);
            $year  = mt_rand(2022, 2025);
            $n     = mt_rand(0, 15);

            for ($j = 0; $j < $n; $j++) {
                Donation::factory()->create([
                    'mosque_id'    => $this->mosque->id,
                    'user_id'      => $this->owner->id,
                    'status'       => 'confirmed',
                    'confirmed_at' => \Carbon\Carbon::create($year, $month, mt_rand(1, 28)),
                ]);
            }

            $response = $this->actingAs($this->owner)
                ->get(route('owner.reports.fee.export', ['month' => $month, 'year' => $year]));

            $response->assertOk();

            // Property 10: Nama file
            $expectedFilename = sprintf('laporan-fee-%04d-%02d.csv', $year, $month);
            $response->assertHeader('Content-Disposition',
                "attachment; filename=\"{$expectedFilename}\"");

            // Property 9: Jumlah baris (parse CSV)
            $content = $response->streamedContent();
            $rows    = array_filter(str_getcsv($content, "\n"));
            // Baris pertama adalah header → $n baris data
            $this->assertCount($n + 1, $rows,
                "Iterasi {$iteration}: CSV harus memiliki " . ($n + 1) . " baris (1 header + {$n} data)");

            Donation::truncate();
        }
    }

    // ── Edge case: Periode tanpa data ─────────────────────────────────
    public function test_empty_period_shows_zero_summary(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('owner.reports.fee.index', ['month' => 1, 'year' => 2000]));

        $response->assertOk();
        $response->assertViewHas('summary', [
            'total_fee' => 0,
            'count'     => 0,
            'average'   => 0.0,
        ]);
    }

    // ── Edge case: CSV header-only saat tidak ada data ────────────────
    public function test_empty_period_csv_contains_only_header(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('owner.reports.fee.export', ['month' => 1, 'year' => 2000]));

        $response->assertOk();
        $content = $response->streamedContent();
        $rows    = array_filter(str_getcsv($content, "\n"));
        $this->assertCount(1, $rows, 'CSV periode kosong harus hanya memiliki baris header');
    }

    // ── Otorisasi: Non-super-admin ditolak ────────────────────────────
    public function test_non_super_admin_cannot_access_report_page(): void
    {
        $regular = User::factory()->create();

        $response = $this->actingAs($regular)
            ->get(route('owner.reports.fee.index'));

        $response->assertForbidden();
    }
}
```

### Konfigurasi Tag Property Test

Setiap property test harus diberi komentar header dengan format:

```php
/**
 * Feature: day10-owner-accounts-reports, Property N: <judul property>
 */
```

Ini memungkinkan penelusuran antara implementasi test dan spesifikasi di dokumen ini.

### Cakupan Test yang Diharapkan

| Area | Unit | Feature | PBT |
|------|------|---------|-----|
| UserService CRUD | ✅ | ✅ | Property 2, 3, 4, 5 |
| DataTable filter role | – | ✅ | Property 1 |
| Kalkulasi fee bulanan | ✅ mock | ✅ real DB | Property 6 |
| Filter periode laporan | ✅ mock | ✅ real DB | Property 7 |
| Chart 12 data point | ✅ mock | – | Property 8 |
| CSV content | – | ✅ | Property 9 |
| CSV filename format | – | ✅ | Property 10 |
| Otorisasi (semua endpoint) | – | ✅ example | – |
| Flash message | – | ✅ example | – |
