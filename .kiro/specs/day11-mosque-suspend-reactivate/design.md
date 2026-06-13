# Design Document

## Overview

Dokumen ini merinci desain teknis fitur **Suspend dan Reactivate Masjid** pada Owner Panel platform eMasjid (Day 11). Fitur ini melengkapi alur manajemen masjid dari Day 8 (approve/reject) dengan kemampuan menangguhkan masjid aktif dan mengaktifkan kembali masjid yang ditangguhkan, beserta notifikasi push (FCM) ke admin masjid yang terdampak. Day 11 menutup Fase 2 (Day 7–11: Owner Panel).

Desain mengikuti pola yang sudah ditetapkan di Day 8: Form Request → Controller → Service → Repository → Event → Listener → Job. Perbedaan utama dari Day 8 adalah penggunaan **FCM push notification** (bukan email) untuk notifikasi ke admin masjid.

---

## Architecture

### Diagram Komponen

```
┌──────────────────────────────────────────────────────────────────────┐
│                          Frontend Layer                              │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────────┐ │
│  │ show.blade.php  │  │  SweetAlert2    │  │  Flash Messages     │ │
│  │ (tombol per     │  │  Konfirmasi     │  │  (success/error)    │ │
│  │  status)        │  │  Suspend/React. │  │                     │ │
│  └─────────────────┘  └─────────────────┘  └─────────────────────┘ │
└──────────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌──────────────────────────────────────────────────────────────────────┐
│                        Middleware Layer                              │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │  EnsureMosqueActive (diperbarui)                             │   │
│  │  - Cek status masjid, redirect ke halaman info jika suspended│   │
│  └──────────────────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌──────────────────────────────────────────────────────────────────────┐
│                        Controller Layer                              │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │  Owner\MosqueController (diperbarui)                         │   │
│  │  - suspend(SuspendMosqueRequest, int $id): RedirectResponse  │   │
│  │  - reactivate(ReactivateMosqueRequest, int $id): Redirect    │   │
│  └──────────────────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌──────────────────────────────────────────────────────────────────────┐
│                      Form Request Layer                              │
│  ┌───────────────────────────┐  ┌───────────────────────────────┐  │
│  │  SuspendMosqueRequest     │  │  ReactivateMosqueRequest      │  │
│  │  - mosque_id exists where │  │  - mosque_id exists where     │  │
│  │    status = active        │  │    status = suspended         │  │
│  └───────────────────────────┘  └───────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌──────────────────────────────────────────────────────────────────────┐
│                        Service Layer                                 │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │  MosqueService (diperbarui)                                  │   │
│  │  - suspend(int $mosqueId, int $userId): bool                 │   │
│  │  - reactivate(int $mosqueId, int $userId): bool              │   │
│  └──────────────────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌──────────────────────────────────────────────────────────────────────┐
│                      Repository Layer                                │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │  MosqueRepositoryInterface (tidak ada perubahan interface)   │   │
│  │  - update(int $id, array $data): bool  (sudah ada)           │   │
│  └──────────────────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌──────────────────────────────────────────────────────────────────────┐
│                         Event Layer                                  │
│  ┌────────────────────────┐  ┌──────────────────────────────────┐  │
│  │  MosqueSuspended       │  │  MosqueReactivated               │  │
│  │  (mosque, actor, time) │  │  (mosque, actor, time)           │  │
│  └────────────────────────┘  └──────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌──────────────────────────────────────────────────────────────────────┐
│                       Listener Layer                                 │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │  SendMosqueSuspendedNotification (ShouldQueue)               │   │
│  │  - Load fcm_tokens milik admin_user_id                       │   │
│  │  - Dispatch SendFcmNotificationJob per token                 │   │
│  └──────────────────────────────────────────────────────────────┘   │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │  SendMosqueReactivatedNotification (ShouldQueue)             │   │
│  │  - Load fcm_tokens milik admin_user_id                       │   │
│  │  - Dispatch SendFcmNotificationJob per token                 │   │
│  └──────────────────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌──────────────────────────────────────────────────────────────────────┐
│                          Job Layer                                   │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │  SendFcmNotificationJob (ShouldQueue)                        │   │
│  │  - Kirim HTTP POST ke FCM v1 API                             │   │
│  │  - Log error jika gagal, tidak throw ke aksi utama           │   │
│  └──────────────────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────────────────┘
```

### Diagram Alur (Sequence) — Suspend

```
Owner       Controller     FormRequest      Service        Repository     Event        Listener       Job
  │              │               │              │               │             │             │            │
  │ Klik Suspend │               │              │               │             │             │            │
  ├─────────────►│               │              │               │             │             │            │
  │              │ Validate      │              │               │             │             │            │
  │              ├──────────────►│              │               │             │             │            │
  │              │ ◄─────────────┤              │               │             │             │            │
  │              │               │              │               │             │             │            │
  │              │ suspend()     │              │               │             │             │            │
  │              ├───────────────┼─────────────►│               │             │             │            │
  │              │               │              │ find()        │             │             │            │
  │              │               │              ├──────────────►│             │             │            │
  │              │               │              │ ◄─────────────┤             │             │            │
  │              │               │              │               │             │             │            │
  │              │               │              │ update()      │             │             │            │
  │              │               │              ├──────────────►│             │             │            │
  │              │               │              │ ◄─────────────┤             │             │            │
  │              │               │              │               │             │             │            │
  │              │               │              │ event(Suspended)            │             │            │
  │              │               │              ├───────────────┼────────────►│             │            │
  │              │               │              │               │             │ handle()    │            │
  │              │               │              │               │             ├────────────►│            │
  │              │               │              │               │             │             │ dispatch() │
  │              │               │              │               │             │             ├───────────►│
  │              │               │              │               │             │             │  FCM POST  │
  │              │               │              │               │             │             │            │
  │              │ ◄─────────────┼──────────────┤               │             │             │            │
  │◄─────────────┤               │              │               │             │             │            │
  │  Redirect+Flash               │              │               │             │             │            │
```

### Diagram Alur (Sequence) — Reactivate

Identik dengan alur Suspend di atas, tetapi menggunakan `reactivate()`, event `MosqueReactivated`, dan listener `SendMosqueReactivatedNotification`.

---

## Components and Interfaces

### Form Requests (Baru)

#### `SuspendMosqueRequest`

```php
namespace App\Http\Requests\Owner;

use App\Enums\MosqueStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SuspendMosqueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Otorisasi ditangani oleh middleware 'owner'
    }

    public function rules(): array
    {
        return [
            'mosque_id' => [
                'required',
                'integer',
                Rule::exists('mosques', 'id')->where('status', MosqueStatus::Active->value),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'mosque_id.required' => 'Mosque ID wajib diisi.',
            'mosque_id.integer'  => 'Mosque ID harus berupa angka.',
            'mosque_id.exists'   => 'Masjid tidak dapat ditangguhkan karena statusnya bukan aktif.',
        ];
    }
}
```

#### `ReactivateMosqueRequest`

```php
namespace App\Http\Requests\Owner;

use App\Enums\MosqueStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReactivateMosqueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mosque_id' => [
                'required',
                'integer',
                Rule::exists('mosques', 'id')->where('status', MosqueStatus::Suspended->value),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'mosque_id.required' => 'Mosque ID wajib diisi.',
            'mosque_id.integer'  => 'Mosque ID harus berupa angka.',
            'mosque_id.exists'   => 'Masjid tidak dapat diaktifkan kembali karena statusnya bukan ditangguhkan.',
        ];
    }
}
```

### Controller — Metode Tambahan

```php
// app/Http/Controllers/Owner/MosqueController.php

public function suspend(SuspendMosqueRequest $request, int $id): RedirectResponse
{
    $userId = auth()->id();

    try {
        $this->mosqueService->suspend($id, $userId);

        return redirect()
            ->route('owner.mosques.show', $id)
            ->with('success', 'Masjid berhasil ditangguhkan.');
    } catch (\Exception $e) {
        return redirect()
            ->back()
            ->with('error', 'Gagal menangguhkan masjid: ' . $e->getMessage());
    }
}

public function reactivate(ReactivateMosqueRequest $request, int $id): RedirectResponse
{
    $userId = auth()->id();

    try {
        $this->mosqueService->reactivate($id, $userId);

        return redirect()
            ->route('owner.mosques.show', $id)
            ->with('success', 'Masjid berhasil diaktifkan kembali.');
    } catch (\Exception $e) {
        return redirect()
            ->back()
            ->with('error', 'Gagal mengaktifkan kembali masjid: ' . $e->getMessage());
    }
}
```

### Service — Metode Tambahan

```php
// app/Services/MosqueService.php

public function suspend(int $mosqueId, int $userId): bool
{
    DB::beginTransaction();

    try {
        $mosque = $this->mosqueRepository->find($mosqueId);

        if (! $mosque || $mosque->status !== MosqueStatus::Active) {
            throw new \Exception('Invalid mosque or status is not active');
        }

        $updated = $this->mosqueRepository->update($mosqueId, [
            'status' => MosqueStatus::Suspended,
        ]);

        if (! $updated) {
            throw new \Exception('Failed to update mosque');
        }

        $mosque->refresh();

        event(new MosqueSuspended($mosque, $userId, now()));

        DB::commit();

        Log::info("Mosque suspended: mosque_id={$mosque->id}, mosque_name={$mosque->name}, by_user_id={$userId}");

        Cache::forget('owner.dashboard.statistics');

        return true;
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}

public function reactivate(int $mosqueId, int $userId): bool
{
    DB::beginTransaction();

    try {
        $mosque = $this->mosqueRepository->find($mosqueId);

        if (! $mosque || $mosque->status !== MosqueStatus::Suspended) {
            throw new \Exception('Invalid mosque or status is not suspended');
        }

        $updated = $this->mosqueRepository->update($mosqueId, [
            'status' => MosqueStatus::Active,
        ]);

        if (! $updated) {
            throw new \Exception('Failed to update mosque');
        }

        $mosque->refresh();

        event(new MosqueReactivated($mosque, $userId, now()));

        DB::commit();

        Log::info("Mosque reactivated: mosque_id={$mosque->id}, mosque_name={$mosque->name}, by_user_id={$userId}");

        Cache::forget('owner.dashboard.statistics');

        return true;
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}
```

### Events (Baru)

Kedua event mengikuti signature constructor yang sama dengan `MosqueApproved`:

```php
// app/Events/MosqueSuspended.php
class MosqueSuspended
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Mosque $mosque,
        public int    $actorUserId,
        public Carbon $actedAt,
    ) {}
}

// app/Events/MosqueReactivated.php
class MosqueReactivated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Mosque $mosque,
        public int    $actorUserId,
        public Carbon $actedAt,
    ) {}
}
```

### Listeners (Baru)

Kedua listener bersifat `ShouldQueue`, menggunakan FCM (bukan email):

```php
// app/Listeners/SendMosqueSuspendedNotification.php
class SendMosqueSuspendedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public function handle(MosqueSuspended $event): void
    {
        $mosque = $event->mosque;
        $adminUserId = $mosque->admin_user_id;

        if (! $adminUserId) {
            Log::warning("SendMosqueSuspendedNotification: admin_user_id null", [
                'mosque_id' => $mosque->id,
            ]);
            return;
        }

        $tokens = FcmToken::where('user_id', $adminUserId)->get();

        if ($tokens->isEmpty()) {
            Log::warning("No FCM token found for mosque admin user_id={$adminUserId}");
            return;
        }

        foreach ($tokens as $fcmToken) {
            SendFcmNotificationJob::dispatch(
                token: $fcmToken->token,
                title: 'Masjid Anda Ditangguhkan',
                body:  "Masjid {$mosque->name} telah ditangguhkan. Silakan hubungi admin platform untuk informasi lebih lanjut.",
            );
        }
    }
}
```

```php
// app/Listeners/SendMosqueReactivatedNotification.php
// Identik dengan di atas, menggunakan event MosqueReactivated
// title: 'Masjid Anda Diaktifkan Kembali'
// body:  "Masjid {$mosque->name} telah diaktifkan kembali. Masjid Anda kini dapat dikelola kembali."
```

### Job FCM (Baru)

```php
// app/Jobs/SendFcmNotificationJob.php
class SendFcmNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $token,
        public string $title,
        public string $body,
        public array  $data = [],
    ) {}

    public function handle(): void
    {
        try {
            $accessToken = $this->getFcmAccessToken();

            $projectId = config('services.fcm.project_id');

            Http::withToken($accessToken)
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                    'message' => [
                        'token'        => $this->token,
                        'notification' => [
                            'title' => $this->title,
                            'body'  => $this->body,
                        ],
                        'data' => $this->data,
                    ],
                ])
                ->throw();
        } catch (\Throwable $e) {
            Log::error('SendFcmNotificationJob: Gagal mengirim FCM', [
                'token' => substr($this->token, 0, 20) . '...',
                'title' => $this->title,
                'error' => $e->getMessage(),
            ]);
            // Tidak re-throw — FCM failure tidak boleh merusak alur utama
        }
    }

    private function getFcmAccessToken(): string
    {
        // Menggunakan Google Auth Library atau service account credentials
        // yang dikonfigurasi di config/services.php (fcm.credentials_path)
        $credentials = new ServiceAccountCredentials(
            'https://www.googleapis.com/auth/firebase.messaging',
            config('services.fcm.credentials_path'),
        );
        $token = $credentials->fetchAuthToken();
        return $token['access_token'];
    }
}
```

### Middleware — `EnsureMosqueActive` (Diperbarui)

Perubahan: ganti `abort(403, ...)` untuk status `Suspended` dengan redirect ke named route `mosque.suspended`:

```php
if ($mosque->status !== MosqueStatus::Active) {
    $message = match ($mosque->status) {
        MosqueStatus::Pending    => 'Masjid masih menunggu persetujuan.',
        MosqueStatus::Suspended  => 'Masjid sedang ditangguhkan. Hubungi admin platform.',
        MosqueStatus::Rejected   => 'Pendaftaran masjid ditolak.',
        default                  => 'Masjid tidak aktif.',
    };

    if ($request->expectsJson()) {
        return response()->json(['success' => false, 'message' => $message], 403);
    }

    if ($mosque->status === MosqueStatus::Suspended) {
        return redirect()->route('mosque.suspended')
            ->with('mosque_name', $mosque->name);
    }

    abort(403, $message);
}
```

> **Catatan desain**: Route `mosque.suspended` adalah halaman informasi statis sederhana yang menampilkan pesan "Masjid Anda sedang ditangguhkan". Route ini harus berada di luar grup middleware `mosque.active` untuk menghindari redirect loop.

### Routes (Ditambahkan)

```php
// routes/web.php — di dalam group prefix('owner')
Route::post('/mosques/{id}/suspend',    [MosqueController::class, 'suspend'])    ->name('mosques.suspend');
Route::post('/mosques/{id}/reactivate', [MosqueController::class, 'reactivate'])->name('mosques.reactivate');
```

### AppServiceProvider (Diperbarui)

```php
Event::listen(MosqueSuspended::class,    SendMosqueSuspendedNotification::class);
Event::listen(MosqueReactivated::class,  SendMosqueReactivatedNotification::class);
```

---

## Data Models

### Tabel `mosques` — Tidak ada kolom baru

Aksi suspend dan reactivate hanya memperbarui kolom `status` (dan `updated_at` otomatis oleh Eloquent). Tidak diperlukan kolom `suspended_at` atau `suspended_by` untuk MVP Day 11 — audit trail ditangani sepenuhnya oleh Laravel Log.

### Tabel `fcm_tokens` — Sudah ada, tidak ada perubahan

| Kolom         | Tipe             | Keterangan                           |
|---------------|------------------|--------------------------------------|
| `id`          | bigint unsigned  | Primary key                          |
| `user_id`     | bigint unsigned  | FK ke `users.id`                     |
| `token`       | string           | FCM registration token               |
| `device_type` | string nullable  | `android`, `ios`, dsb.               |
| `created_at`  | timestamp        |                                      |
| `updated_at`  | timestamp        |                                      |

### MosqueStatus Enum — Sudah ada, tidak ada perubahan

```
pending    → 'Menunggu Persetujuan'
active     → 'Aktif'
suspended  → 'Ditangguhkan'
rejected   → 'Ditolak'
```

### Badge Color Mapping (View)

| Status      | Bootstrap Color Class |
|-------------|----------------------|
| `active`    | `success`            |
| `pending`   | `warning`            |
| `suspended` | `warning`            |
| `rejected`  | `danger`             |

> **Catatan**: Requirements 7.2 menetapkan `suspended` → warna oranye (warning), bukan `danger` seperti di view saat ini.

---

## Desain UI (Blade / Bootstrap 5)

### Tombol Aksi Kontekstual — `show.blade.php`

Blok `@if($mosque->status === Pending)` yang ada saat ini digantikan dengan blok yang mencakup semua status:

```blade
{{-- Action Card: ditampilkan untuk semua status kecuali rejected --}}
@if($mosque->status !== \App\Enums\MosqueStatus::Rejected)
<div class="card mb-4 border-{{ $badgeColor }} shadow-sm">
    <div class="card-body">
        <h5 class="card-title text-{{ $badgeColor }}">
            <i class="bi bi-shield-check me-2"></i>Aksi Manajemen Masjid
        </h5>

        @if($mosque->status === \App\Enums\MosqueStatus::Pending)
            <p class="card-text text-muted">
                Masjid ini menunggu persetujuan Anda. Tinjau detail di bawah sebelum mengambil tindakan.
            </p>
            <div class="d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-success" id="btn-approve"
                        onclick="approveMosque({{ $mosque->id }})">
                    <i class="bi bi-check-circle me-1"></i>Setujui Pendaftaran
                </button>
                <button type="button" class="btn btn-danger" id="btn-reject"
                        onclick="rejectMosque({{ $mosque->id }})">
                    <i class="bi bi-x-circle me-1"></i>Tolak Pendaftaran
                </button>
            </div>

        @elseif($mosque->status === \App\Enums\MosqueStatus::Active)
            <p class="card-text text-muted">
                Masjid ini sedang aktif. Anda dapat menangguhkannya jika diperlukan.
            </p>
            <button type="button" class="btn btn-warning" id="btn-suspend"
                    onclick="suspendMosque({{ $mosque->id }})">
                <i class="bi bi-pause-circle me-1"></i>Tangguhkan
            </button>

        @elseif($mosque->status === \App\Enums\MosqueStatus::Suspended)
            <p class="card-text text-muted">
                Masjid ini sedang ditangguhkan. Aktifkan kembali setelah permasalahan diselesaikan.
            </p>
            <button type="button" class="btn btn-success" id="btn-reactivate"
                    onclick="reactivateMosque({{ $mosque->id }})">
                <i class="bi bi-play-circle me-1"></i>Aktifkan Kembali
            </button>
        @endif
    </div>
</div>
@endif
```

### JavaScript — Fungsi SweetAlert Baru

```javascript
function suspendMosque(mosqueId) {
    Swal.fire({
        title: 'Tangguhkan Masjid?',
        text: 'Tangguhkan masjid ini? Masjid tidak akan bisa diakses sampai diaktifkan kembali.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#fd7e14',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Tangguhkan',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            const btn = document.getElementById('btn-suspend');
            btn.textContent = 'Memproses...';
            btn.disabled = true;

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/owner/mosques/${mosqueId}/suspend`;

            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';

            const mosqueInput = document.createElement('input');
            mosqueInput.type = 'hidden';
            mosqueInput.name = 'mosque_id';
            mosqueInput.value = mosqueId;

            form.appendChild(csrf);
            form.appendChild(mosqueInput);
            document.body.appendChild(form);
            form.submit();
        }
    });
}

function reactivateMosque(mosqueId) {
    Swal.fire({
        title: 'Aktifkan Kembali Masjid?',
        text: 'Aktifkan kembali masjid ini? Masjid akan langsung bisa diakses kembali.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Aktifkan',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            const btn = document.getElementById('btn-reactivate');
            btn.textContent = 'Memproses...';
            btn.disabled = true;

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/owner/mosques/${mosqueId}/reactivate`;

            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';

            const mosqueInput = document.createElement('input');
            mosqueInput.type = 'hidden';
            mosqueInput.name = 'mosque_id';
            mosqueInput.value = mosqueId;

            form.appendChild(csrf);
            form.appendChild(mosqueInput);
            document.body.appendChild(form);
            form.submit();
        }
    });
}
```

---

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system — essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Suspend mengubah status menjadi suspended

*Untuk sembarang* masjid dengan status `active`, memanggil `MosqueService::suspend()` harus menghasilkan status masjid berubah menjadi `suspended` dan `updated_at` diperbarui.

**Validates: Requirements 1.3, 1.4**

---

### Property 2: Reactivate mengubah status menjadi active

*Untuk sembarang* masjid dengan status `suspended`, memanggil `MosqueService::reactivate()` harus menghasilkan status masjid berubah menjadi `active` dan `updated_at` diperbarui.

**Validates: Requirements 2.3**

---

### Property 3: Guard status — suspend hanya valid dari active

*Untuk sembarang* `mosque_id` yang status masjid-nya bukan `active` (bisa `pending`, `suspended`, atau `rejected`), `SuspendMosqueRequest` harus gagal validasi dengan pesan yang tepat, dan `MosqueService::suspend()` harus melempar exception tanpa mengubah status masjid.

**Validates: Requirements 1.7**

---

### Property 4: Guard status — reactivate hanya valid dari suspended

*Untuk sembarang* `mosque_id` yang status masjid-nya bukan `suspended` (bisa `pending`, `active`, atau `rejected`), `ReactivateMosqueRequest` harus gagal validasi dengan pesan yang tepat, dan `MosqueService::reactivate()` harus melempar exception tanpa mengubah status masjid.

**Validates: Requirements 2.7**

---

### Property 5: Atomisitas transaksi — tidak ada perubahan parsial

*Untuk sembarang* operasi suspend atau reactivate yang mengalami kegagalan database (repository melempar exception setelah `DB::beginTransaction()`), status masjid harus tetap tidak berubah (rollback sempurna) dan respons yang dikembalikan ke Owner Panel harus mengandung pesan error.

**Validates: Requirements 1.9, 2.9, 8.3, 8.4**

---

### Property 6: Audit log tercatat pada setiap suspend berhasil

*Untuk sembarang* operasi `MosqueService::suspend()` yang berhasil, `Log::info` harus dipanggil dengan string yang mengandung `mosque_id`, nama masjid, dan `by_user_id` sesuai format yang ditetapkan.

**Validates: Requirements 8.1**

---

### Property 7: Audit log tercatat pada setiap reactivate berhasil

*Untuk sembarang* operasi `MosqueService::reactivate()` yang berhasil, `Log::info` harus dipanggil dengan string yang mengandung `mosque_id`, nama masjid, dan `by_user_id` sesuai format yang ditetapkan.

**Validates: Requirements 8.2**

---

### Property 8: Notifikasi FCM dikirim ke semua token admin saat suspend

*Untuk sembarang* masjid dengan admin yang memiliki N FCM token (N ≥ 1), setelah `MosqueSuspended` event di-dispatch, listener harus men-dispatch tepat N `SendFcmNotificationJob`, masing-masing dengan token yang berbeda.

**Validates: Requirements 3.1, 3.3**

---

### Property 9: Notifikasi FCM dikirim ke semua token admin saat reactivate

*Untuk sembarang* masjid dengan admin yang memiliki N FCM token (N ≥ 1), setelah `MosqueReactivated` event di-dispatch, listener harus men-dispatch tepat N `SendFcmNotificationJob`, masing-masing dengan token yang berbeda.

**Validates: Requirements 4.1, 4.3**

---

### Property 10: FCM failure tidak merusak alur utama

*Untuk sembarang* `SendFcmNotificationJob` yang gagal (HTTP client melempar exception), job harus mencatat `Log::error` dan tidak meneruskan exception, sehingga antrian job lain tidak terpengaruh dan status masjid tidak berubah kembali.

**Validates: Requirements 3.7, 4.6**

---

### Property 11: EnsureMosqueActive memblokir akses masjid suspended

*Untuk sembarang* request HTTP ke route yang dilindungi `EnsureMosqueActive` dengan `current_mosque` berstatus `suspended`, middleware harus mengembalikan redirect (bukan HTTP 200, bukan abort 403) ke halaman info penangguhan, tanpa menyebabkan redirect loop.

**Validates: Requirements 5.2, 5.4**

---

### Property 12: Rendering tombol aksi sesuai status

*Untuk setiap* nilai `MosqueStatus` yang mungkin (`pending`, `active`, `suspended`, `rejected`), view `show.blade.php` harus menampilkan tepat set tombol aksi yang ditetapkan oleh spesifikasi — tidak lebih, tidak kurang.

**Validates: Requirements 6.1, 6.2, 6.3, 6.4**

---

## Error Handling

### Validasi Form Request (HTTP 422)

- `SuspendMosqueRequest`: gagal jika `mosque_id` tidak ada atau status bukan `active` → pesan "Masjid tidak dapat ditangguhkan karena statusnya bukan aktif."
- `ReactivateMosqueRequest`: gagal jika `mosque_id` tidak ada atau status bukan `suspended` → pesan "Masjid tidak dapat diaktifkan kembali karena statusnya bukan ditangguhkan."
- Kedua request gagal validasi dikembalikan sebagai redirect back dengan error bag (behavior default Laravel Form Request).

### Kegagalan Service Layer

- Exception dari `MosqueService::suspend()` atau `reactivate()` ditangkap di controller.
- Controller me-redirect back dengan flash `error` yang berisi pesan exception.
- `DB::rollBack()` dipanggil di blok `catch` — tidak ada perubahan status yang tersimpan.
- `Log::error` dicatat oleh service jika rollback dipicu.

### Kegagalan FCM

- `SendFcmNotificationJob` mencatat `Log::error` dan **tidak** melempar exception.
- Listener tidak menggunakan `$this->fail()` atau re-throw untuk FCM error — ini disengaja agar kegagalan notifikasi tidak memblokir atau membatalkan perubahan status masjid yang sudah di-commit.
- Jika admin tidak ditemukan atau tidak punya token: listener mencatat `Log::warning` dan return lebih awal.

### Middleware — Tidak Ada Redirect Loop

Halaman `mosque.suspended` (route `mosque.suspended`) harus berada di luar grup middleware `mosque.active`. Implementasinya adalah route terpisah tanpa middleware `EnsureMosqueActive`, sehingga redirect dari middleware tidak menyebabkan loop.

---

## Testing Strategy

### Pendekatan Ganda: Unit Test + Property-Based Test

Fitur ini melibatkan logika bisnis murni (perubahan status, guard validasi, audit log, notifikasi token) yang sangat cocok untuk property-based testing menggunakan **[PHPUnit](https://phpunit.de/) + [Pest](https://pestphp.com/)** dengan paket **[etiennes/pest-plugin-laravel-faker](https://github.com/pestphp/pest-plugin-faker)** atau generator kustom sederhana.

Library PBT yang direkomendasikan: **Pest PHP** (sudah umum di ekosistem Laravel) dengan helper `faker()` untuk generate data acak.

Setiap property test dikonfigurasi minimum **100 iterasi** per properti melalui loop generator.

Tag komentar format: `// Feature: day11-mosque-suspend-reactivate, Property {N}: {teks}`

---

### Unit Tests — Contoh Spesifik

**Form Requests:**
- `SuspendMosqueRequest` menolak `mosque_id` dengan status `pending`, `suspended`, `rejected`.
- `ReactivateMosqueRequest` menolak `mosque_id` dengan status `pending`, `active`, `rejected`.
- Kedua request menerima `mosque_id` dengan status yang benar.

**Controller:**
- `suspend()` memanggil `MosqueService::suspend()` dan redirect ke `owner.mosques.show` dengan flash success.
- `reactivate()` memanggil `MosqueService::reactivate()` dan redirect ke `owner.mosques.show` dengan flash success.
- Error dari service dikembalikan sebagai flash error + redirect back.

**Middleware:**
- `EnsureMosqueActive` me-redirect ke `mosque.suspended` (bukan abort) saat status `suspended`.
- `EnsureMosqueActive` mengizinkan request lanjut saat status `active`.
- `EnsureMosqueActive` me-abort 403 saat status `pending` atau `rejected`.

**Listener:**
- `SendMosqueSuspendedNotification` tidak dispatch job jika `admin_user_id` null.
- `SendMosqueReactivatedNotification` tidak dispatch job jika admin tidak punya token.

---

### Property-Based Tests

```php
// Feature: day11-mosque-suspend-reactivate, Property 1: Suspend mengubah status menjadi suspended
test('suspend mengubah status active menjadi suspended', function () {
    // Generate 100 masjid aktif acak
    for ($i = 0; $i < 100; $i++) {
        $mosque = Mosque::factory()->active()->create();
        $userId = User::factory()->superAdmin()->create()->id;

        app(MosqueService::class)->suspend($mosque->id, $userId);

        expect($mosque->fresh()->status)->toBe(MosqueStatus::Suspended);
        expect($mosque->fresh()->updated_at)->not->toEqual($mosque->created_at);
    }
});

// Feature: day11-mosque-suspend-reactivate, Property 3: Guard status suspend
test('suspend gagal untuk status selain active', function () {
    $nonActiveStatuses = [MosqueStatus::Pending, MosqueStatus::Suspended, MosqueStatus::Rejected];
    for ($i = 0; $i < 100; $i++) {
        $status = $nonActiveStatuses[array_rand($nonActiveStatuses)];
        $mosque = Mosque::factory()->withStatus($status)->create();

        expect(fn() => app(MosqueService::class)->suspend($mosque->id, 1))
            ->toThrow(\Exception::class);

        expect($mosque->fresh()->status)->toBe($status); // Status tidak berubah
    }
});

// Feature: day11-mosque-suspend-reactivate, Property 8: Notifikasi FCM per token admin
test('listener dispatch job FCM untuk setiap token admin saat suspended', function () {
    Queue::fake();
    for ($i = 0; $i < 100; $i++) {
        $tokenCount = rand(1, 5);
        $admin = User::factory()->create();
        FcmToken::factory()->count($tokenCount)->for($admin)->create();
        $mosque = Mosque::factory()->create(['admin_user_id' => $admin->id]);

        event(new MosqueSuspended($mosque, 1, now()));

        Queue::assertPushed(SendFcmNotificationJob::class, $tokenCount);
        Queue::clearResolvedInstances();
    }
});

// Feature: day11-mosque-suspend-reactivate, Property 10: FCM failure tidak merusak alur
test('SendFcmNotificationJob tidak throw saat FCM gagal', function () {
    Log::spy();
    for ($i = 0; $i < 100; $i++) {
        Http::fake(['*' => Http::response([], 500)]);

        $job = new SendFcmNotificationJob(
            token: fake()->uuid(),
            title: fake()->sentence(),
            body:  fake()->paragraph(),
        );

        expect(fn() => $job->handle())->not->toThrow(\Throwable::class);
        Log::shouldHaveReceived('error')->once();
    }
});
```

### Integration Tests

- Alur end-to-end suspend: POST ke `/owner/mosques/{id}/suspend` → status berubah di DB → event terdaftar → listener dispatch job FCM ke queue.
- Alur end-to-end reactivate: POST ke `/owner/mosques/{id}/reactivate` → status berubah → job FCM di queue.
- Middleware blocking: request dari admin masjid yang suspended ke `/admin/dashboard` → redirect ke `mosque.suspended`.

---

## Pertimbangan Keamanan

1. **Autentikasi**: Semua route owner dilindungi middleware `auth:web`.
2. **Otorisasi**: Middleware `owner` memastikan hanya super-admin yang dapat suspend/reactivate. HTTP 403 dikembalikan sebelum validasi status.
3. **CSRF Protection**: Semua form POST menyertakan `_token`.
4. **Input Validation**: Form Request memvalidasi `mosque_id` + guard status sebelum service dipanggil.
5. **Database Transaction**: Mencegah perubahan status parsial akibat kegagalan concurrent atau network.
6. **FCM Token Privacy**: Di log error, token FCM dipotong (20 karakter pertama saja) untuk mencegah token penuh muncul di log.
7. **No Redirect Loop**: Route `mosque.suspended` berada di luar grup middleware `EnsureMosqueActive`.

---

## Pertimbangan Performa

1. **Antrian (Queue)**: Pengiriman FCM sepenuhnya asinkron — aksi suspend/reactivate tidak menunggu FCM selesai.
2. **Cache Invalidation**: `Cache::forget('owner.dashboard.statistics')` dipanggil setelah setiap perubahan status untuk memastikan statistik dashboard selalu segar.
3. **Eager Loading**: Detail masjid di `show.blade.php` memuat relasi `admin` sekali, tidak ada N+1 query.
4. **Indeks DB**: Kolom `status` pada tabel `mosques` diasumsikan sudah diindeks (dari migrasi Day 8).
5. **FCM Batch**: Untuk MVP, job dikirim per token. Jika satu admin memiliki banyak token, jumlah job yang di-dispatch proporsional — dapat dioptimalkan ke batch FCM API di iterasi berikutnya.

---

## Checklist File yang Dimodifikasi / Dibuat

### File Baru
| File | Keterangan |
|------|------------|
| `app/Events/MosqueSuspended.php` | Event dengan constructor `(Mosque, int, Carbon)` |
| `app/Events/MosqueReactivated.php` | Event dengan constructor `(Mosque, int, Carbon)` |
| `app/Listeners/SendMosqueSuspendedNotification.php` | Queued, FCM, no re-throw |
| `app/Listeners/SendMosqueReactivatedNotification.php` | Queued, FCM, no re-throw |
| `app/Jobs/SendFcmNotificationJob.php` | Reusable FCM dispatch via HTTP |
| `app/Http/Requests/Owner/SuspendMosqueRequest.php` | Guard status `active` |
| `app/Http/Requests/Owner/ReactivateMosqueRequest.php` | Guard status `suspended` |

### File yang Dimodifikasi
| File | Perubahan |
|------|-----------|
| `app/Services/MosqueService.php` | Tambah `suspend()` dan `reactivate()` |
| `app/Http/Controllers/Owner/MosqueController.php` | Tambah `suspend()` dan `reactivate()` |
| `app/Providers/AppServiceProvider.php` | Daftarkan 2 binding event baru |
| `routes/web.php` | Tambah 2 POST route |
| `resources/views/owner/mosques/show.blade.php` | Tombol aksi kontekstual per status + badge color fix |
| `app/Http/Middleware/EnsureMosqueActive.php` | Redirect ke named route, bukan abort(403) |
