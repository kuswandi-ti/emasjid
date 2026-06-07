# SKILLS.md - EMasjid

> Katalog pattern implementasi yang disetujui untuk proyek EMasjid.
> Gunakan dokumen ini sebagai referensi saat membuat fitur baru. Copy-paste dan adaptasi sesuai domain.

---

## Daftar Isi

1. [Full Flow Fitur Baru](#1-full-flow-fitur-baru)
2. [Repository + Interface](#2-repository--interface)
3. [Service dengan DTO](#3-service-dengan-dto)
4. [Controller Web (Admin Panel)](#4-controller-web-admin-panel)
5. [Controller Web (Owner Panel)](#5-controller-web-owner-panel)
6. [Controller API (Flutter)](#6-controller-api-flutter)
7. [API Resource](#7-api-resource)
8. [Form Request](#8-form-request)
9. [Multi-Tenant Query (BelongsToMosque)](#9-multi-tenant-query-belongstomosque)
10. [Enum dan Status](#10-enum-dan-status)
11. [Event dan Listener](#11-event-dan-listener)
12. [Job (Queue)](#12-job-queue)
13. [Notification (FCM + Database)](#13-notification-fcm--database)
14. [Integrasi Duitku (Payment Gateway)](#14-integrasi-duitku-payment-gateway)
15. [Export CSV dan PDF](#15-export-csv-dan-pdf)
16. [DataTables (Yajra)](#16-datatables-yajra)
17. [SweetAlert Konfirmasi](#17-sweetalert-konfirmasi)
18. [Migration Pattern](#18-migration-pattern)
19. [Testing Pattern](#19-testing-pattern)
20. [Permission Seeder](#20-permission-seeder)

---

## 1. Full Flow Fitur Baru

Contoh: menambahkan fitur **Cash Income** (pemasukan kas masjid).

Urutan file yang dibuat:

```text
1. database/migrations/xxxx_create_cash_transactions_table.php
2. app/Enums/TransactionType.php
3. app/Models/CashTransaction.php
4. app/DataTransferObjects/Finance/CreateCashTransactionDTO.php
5. app/Contracts/Repositories/CashTransactionRepositoryInterface.php
6. app/Repositories/CashTransactionRepository.php
7. app/Services/Finance/FinanceService.php
8. app/Http/Requests/Admin/Finance/StoreCashIncomeRequest.php
9. app/Http/Controllers/Admin/Finance/CashIncomeController.php
10. routes/web.php (admin group)
11. resources/views/admin/finance/income/create.blade.php
12. app/Providers/RepositoryServiceProvider.php (binding)
```

Jika fitur juga diakses dari API:

```text
13. app/Http/Controllers/Api/Finance/CashTransactionController.php
14. app/Http/Resources/CashTransactionResource.php
15. routes/api.php
```

---

## 2. Repository + Interface

### Interface

```php
// app/Contracts/Repositories/CashTransactionRepositoryInterface.php

namespace App\Contracts\Repositories;

use App\Models\CashTransaction;
use Illuminate\Pagination\LengthAwarePaginator;

interface CashTransactionRepositoryInterface
{
    public function findOrFail(int $id): CashTransaction;

    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    public function create(array $data): CashTransaction;

    public function update(CashTransaction $transaction, array $data): CashTransaction;

    public function delete(CashTransaction $transaction): bool;

    public function sumByType(string $type, ?string $startDate = null, ?string $endDate = null): int;
}
```

### Implementasi

```php
// app/Repositories/CashTransactionRepository.php

namespace App\Repositories;

use App\Contracts\Repositories\CashTransactionRepositoryInterface;
use App\Models\CashTransaction;
use Illuminate\Pagination\LengthAwarePaginator;

class CashTransactionRepository implements CashTransactionRepositoryInterface
{
    public function findOrFail(int $id): CashTransaction
    {
        return CashTransaction::findOrFail($id);
    }

    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = CashTransaction::query();

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['start_date'])) {
            $query->whereDate('transaction_date', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->whereDate('transaction_date', '<=', $filters['end_date']);
        }

        return $query->latest('transaction_date')->paginate($perPage);
    }

    public function create(array $data): CashTransaction
    {
        return CashTransaction::create($data);
    }

    public function update(CashTransaction $transaction, array $data): CashTransaction
    {
        $transaction->update($data);
        return $transaction->fresh();
    }

    public function delete(CashTransaction $transaction): bool
    {
        return $transaction->delete();
    }

    public function sumByType(string $type, ?string $startDate = null, ?string $endDate = null): int
    {
        $query = CashTransaction::where('type', $type);

        if ($startDate) {
            $query->whereDate('transaction_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('transaction_date', '<=', $endDate);
        }

        return (int) $query->sum('amount');
    }
}
```

### Binding di ServiceProvider

```php
// app/Providers/RepositoryServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\Repositories\CashTransactionRepositoryInterface;
use App\Repositories\CashTransactionRepository;
use App\Contracts\Repositories\DonationRepositoryInterface;
use App\Repositories\DonationRepository;
use App\Contracts\Repositories\MosqueRepositoryInterface;
use App\Repositories\MosqueRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    public array $bindings = [
        CashTransactionRepositoryInterface::class => CashTransactionRepository::class,
        DonationRepositoryInterface::class => DonationRepository::class,
        MosqueRepositoryInterface::class => MosqueRepository::class,
    ];
}
```

---

## 3. Service dengan DTO

### DTO

```php
// app/DataTransferObjects/Finance/CreateCashTransactionDTO.php

namespace App\DataTransferObjects\Finance;

use App\Enums\TransactionType;

class CreateCashTransactionDTO
{
    public function __construct(
        public readonly TransactionType $type,
        public readonly int $amount,
        public readonly string $description,
        public readonly string $transactionDate,
        public readonly ?string $category = null,
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(array $validated): self
    {
        return new self(
            type: TransactionType::from($validated['type']),
            amount: (int) $validated['amount'],
            description: $validated['description'],
            transactionDate: $validated['transaction_date'],
            category: $validated['category'] ?? null,
            notes: $validated['notes'] ?? null,
        );
    }
}
```

### Service

```php
// app/Services/Finance/FinanceService.php

namespace App\Services\Finance;

use App\Contracts\Repositories\CashTransactionRepositoryInterface;
use App\DataTransferObjects\Finance\CreateCashTransactionDTO;
use App\Events\Finance\CashTransactionCreated;
use App\Models\CashTransaction;
use Illuminate\Support\Facades\DB;

class FinanceService
{
    public function __construct(
        private readonly CashTransactionRepositoryInterface $transactions,
    ) {}

    public function createCashTransaction(CreateCashTransactionDTO $dto): CashTransaction
    {
        $transaction = DB::transaction(function () use ($dto) {
            return $this->transactions->create([
                'mosque_id' => mosque_id(),
                'type' => $dto->type,
                'amount' => $dto->amount,
                'description' => $dto->description,
                'transaction_date' => $dto->transactionDate,
                'category' => $dto->category,
                'notes' => $dto->notes,
            ]);
        });

        event(new CashTransactionCreated($transaction));

        return $transaction;
    }

    public function getMonthlyReport(int $year, int $month): array
    {
        $startDate = "{$year}-{$month}-01";
        $endDate = date('Y-m-t', strtotime($startDate));

        $totalIncome = $this->transactions->sumByType('income', $startDate, $endDate);
        $totalExpense = $this->transactions->sumByType('expense', $startDate, $endDate);

        return [
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'balance' => $totalIncome - $totalExpense,
            'period' => "{$year}-{$month}",
        ];
    }
}
```

---

## 4. Controller Web (Admin Panel)

```php
// app/Http/Controllers/Admin/Finance/CashIncomeController.php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Finance\StoreCashIncomeRequest;
use App\DataTransferObjects\Finance\CreateCashTransactionDTO;
use App\Services\Finance\FinanceService;
use App\Enums\TransactionType;

class CashIncomeController extends Controller
{
    public function __construct(
        private readonly FinanceService $financeService,
    ) {}

    public function index()
    {
        return view('admin.finance.income.index');
    }

    public function create()
    {
        return view('admin.finance.income.create');
    }

    public function store(StoreCashIncomeRequest $request)
    {
        $dto = CreateCashTransactionDTO::fromRequest(
            array_merge($request->validated(), ['type' => TransactionType::INCOME->value])
        );

        $this->financeService->createCashTransaction($dto);

        return redirect()
            ->route('admin.finance.income.index')
            ->with('success', 'Pemasukan berhasil dicatat.');
    }
}
```

---

## 5. Controller Web (Owner Panel)

```php
// app/Http/Controllers/Owner/MosqueController.php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\Mosque\MosqueService;
use App\Http\Requests\Owner\ApproveMosqueRequest;

class MosqueController extends Controller
{
    public function __construct(
        private readonly MosqueService $mosqueService,
    ) {}

    public function index()
    {
        return view('owner.mosques.index');
    }

    public function pending()
    {
        return view('owner.mosques.pending');
    }

    public function approve(ApproveMosqueRequest $request, int $id)
    {
        $this->mosqueService->approve($id);

        return redirect()
            ->route('owner.mosques.pending')
            ->with('success', 'Masjid berhasil disetujui.');
    }

    public function reject(ApproveMosqueRequest $request, int $id)
    {
        $this->mosqueService->reject($id, $request->validated('reason'));

        return redirect()
            ->route('owner.mosques.pending')
            ->with('success', 'Pendaftaran masjid ditolak.');
    }
}
```

---

## 6. Controller API (Flutter)

```php
// app/Http/Controllers/Api/Finance/CashTransactionController.php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Http\Resources\CashTransactionResource;
use App\Contracts\Repositories\CashTransactionRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CashTransactionController extends Controller
{
    public function __construct(
        private readonly CashTransactionRepositoryInterface $transactions,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $transactions = $this->transactions->paginate(
            perPage: $request->integer('per_page', 15),
            filters: $request->only(['type', 'start_date', 'end_date']),
        );

        return CashTransactionResource::collection($transactions);
    }

    public function show(int $id): CashTransactionResource
    {
        $transaction = $this->transactions->findOrFail($id);

        return new CashTransactionResource($transaction);
    }
}
```

### Standar Error Response API

```php
// Gunakan di base controller atau exception handler

// Success response
return response()->json([
    'success' => true,
    'data' => $data,
    'message' => 'Operation successful',
], 200);

// Error response
return response()->json([
    'success' => false,
    'message' => 'Validation failed',
    'errors' => [
        'amount' => ['The amount field is required.'],
    ],
], 422);

// Not found
return response()->json([
    'success' => false,
    'message' => 'Resource not found',
], 404);
```

---

## 7. API Resource

```php
// app/Http/Resources/CashTransactionResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CashTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'amount' => $this->amount,
            'amount_formatted' => 'Rp ' . number_format($this->amount, 0, ',', '.'),
            'description' => $this->description,
            'category' => $this->category,
            'transaction_date' => $this->transaction_date->format('Y-m-d'),
            'transaction_date_formatted' => $this->transaction_date->format('d M Y'),
            'notes' => $this->notes,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
```

### Collection Resource dengan Pagination

```php
// Otomatis via ResourceCollection
// Response format:
{
    "data": [...],
    "links": {
        "first": "...",
        "last": "...",
        "prev": null,
        "next": "..."
    },
    "meta": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 15,
        "total": 73
    }
}
```

---

## 8. Form Request

```php
// app/Http/Requests/Admin/Finance/StoreCashIncomeRequest.php

namespace App\Http\Requests\Admin\Finance;

use Illuminate\Foundation\Http\FormRequest;

class StoreCashIncomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('finance.create_income');
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:1'],
            'description' => ['required', 'string', 'max:255'],
            'transaction_date' => ['required', 'date', 'before_or_equal:today'],
            'category' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Nominal wajib diisi.',
            'amount.min' => 'Nominal minimal Rp 1.',
            'description.required' => 'Keterangan wajib diisi.',
            'transaction_date.required' => 'Tanggal transaksi wajib diisi.',
            'transaction_date.before_or_equal' => 'Tanggal tidak boleh melebihi hari ini.',
        ];
    }
}
```

---

## 9. Multi-Tenant Query (BelongsToMosque)

### Trait

```php
// app/Traits/BelongsToMosque.php

namespace App\Traits;

use App\Models\Mosque;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToMosque
{
    public static function bootBelongsToMosque(): void
    {
        static::addGlobalScope('mosque', function (Builder $query) {
            if ($mosqueId = mosque_id()) {
                $query->where($query->getModel()->getTable() . '.mosque_id', $mosqueId);
            }
        });

        static::creating(function ($model) {
            if (! $model->mosque_id && $mosqueId = mosque_id()) {
                $model->mosque_id = $mosqueId;
            }
        });
    }

    public function mosque(): BelongsTo
    {
        return $this->belongsTo(Mosque::class);
    }
}
```

### Helper Function

```php
// app/Support/helpers.php

if (! function_exists('mosque_id')) {
    function mosque_id(): ?int
    {
        $mosque = app('current_mosque');
        return $mosque?->id;
    }
}
```

### Penggunaan di Model

```php
// app/Models/CashTransaction.php

namespace App\Models;

use App\Enums\TransactionType;
use App\Traits\BelongsToMosque;
use Illuminate\Database\Eloquent\Model;

class CashTransaction extends Model
{
    use BelongsToMosque;

    protected $fillable = [
        'mosque_id',
        'type',
        'amount',
        'description',
        'category',
        'transaction_date',
        'notes',
    ];

    protected $casts = [
        'type' => TransactionType::class,
        'amount' => 'integer',
        'transaction_date' => 'date',
    ];
}
```

---

## 10. Enum dan Status

```php
// app/Enums/TransactionType.php

namespace App\Enums;

enum TransactionType: string
{
    case INCOME = 'income';
    case EXPENSE = 'expense';

    public function label(): string
    {
        return match ($this) {
            self::INCOME => 'Pemasukan',
            self::EXPENSE => 'Pengeluaran',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::INCOME => 'success',
            self::EXPENSE => 'danger',
        };
    }
}
```

```php
// app/Enums/DonationStatus.php

namespace App\Enums;

enum DonationStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case FAILED = 'failed';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu Pembayaran',
            self::CONFIRMED => 'Terkonfirmasi',
            self::FAILED => 'Gagal',
            self::EXPIRED => 'Kedaluwarsa',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::CONFIRMED => 'success',
            self::FAILED => 'danger',
            self::EXPIRED => 'secondary',
        };
    }
}
```

```php
// app/Enums/MosqueStatus.php

namespace App\Enums;

enum MosqueStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu Verifikasi',
            self::ACTIVE => 'Aktif',
            self::SUSPENDED => 'Ditangguhkan',
            self::REJECTED => 'Ditolak',
        };
    }
}
```

---

## 11. Event dan Listener

### Event

```php
// app/Events/Finance/CashTransactionCreated.php

namespace App\Events\Finance;

use App\Models\CashTransaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CashTransactionCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly CashTransaction $transaction,
    ) {}
}
```

### Listener

```php
// app/Listeners/Finance/NotifyAdminOnLargeTransaction.php

namespace App\Listeners\Finance;

use App\Events\Finance\CashTransactionCreated;
use App\Notifications\Finance\LargeTransactionNotification;

class NotifyAdminOnLargeTransaction
{
    public function handle(CashTransactionCreated $event): void
    {
        $threshold = 5000000; // 5 juta

        if ($event->transaction->amount >= $threshold) {
            $admin = $event->transaction->mosque->admin;
            $admin->notify(new LargeTransactionNotification($event->transaction));
        }
    }
}
```

### Registrasi di EventServiceProvider

```php
protected $listen = [
    \App\Events\Finance\CashTransactionCreated::class => [
        \App\Listeners\Finance\NotifyAdminOnLargeTransaction::class,
    ],
    \App\Events\Donation\DonationConfirmed::class => [
        \App\Listeners\Donation\RecordDonationIncome::class,
        \App\Listeners\Donation\NotifyMosqueAdmin::class,
        \App\Listeners\Donation\SendDonorReceipt::class,
    ],
    \App\Events\Mosque\MosqueApproved::class => [
        \App\Listeners\Mosque\SendApprovalEmail::class,
        \App\Listeners\Mosque\CreateDefaultPermissions::class,
    ],
];
```

---

## 12. Job (Queue)

```php
// app/Jobs/ProcessDuitkuCallbackJob.php

namespace App\Jobs;

use App\Services\Donation\DonationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessDuitkuCallbackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        private readonly array $callbackData,
    ) {}

    public function handle(DonationService $donationService): void
    {
        $donationService->processPaymentCallback($this->callbackData);
    }

    public function failed(\Throwable $exception): void
    {
        // Log failure untuk investigasi
        logger()->error('Duitku callback failed', [
            'data' => $this->callbackData,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

### Dispatch dari Controller

```php
// Di callback controller
ProcessDuitkuCallbackJob::dispatch($request->all());

return response()->json(['success' => true]);
```

---

## 13. Notification (FCM + Database)

### Notification Class

```php
// app/Notifications/Announcement/NewAnnouncementNotification.php

namespace App\Notifications\Announcement;

use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewAnnouncementNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Announcement $announcement,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'fcm'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Pengumuman Baru',
            'body' => $this->announcement->title,
            'announcement_id' => $this->announcement->id,
            'mosque_id' => $this->announcement->mosque_id,
        ];
    }

    public function toFcm(object $notifiable): array
    {
        return [
            'title' => 'Pengumuman Baru',
            'body' => $this->announcement->title,
            'data' => [
                'type' => 'announcement',
                'id' => (string) $this->announcement->id,
                'mosque_id' => (string) $this->announcement->mosque_id,
            ],
        ];
    }
}
```

### Kirim ke Semua Jamaah Masjid

```php
// Di service
use Illuminate\Support\Facades\Notification;

$congregation = $mosque->congregationUsers;
Notification::send($congregation, new NewAnnouncementNotification($announcement));
```

---

## 14. Integrasi Duitku (Payment Gateway)

### Config

```php
// config/duitku.php

return [
    'merchant_code' => env('DUITKU_MERCHANT_CODE'),
    'api_key' => env('DUITKU_API_KEY'),
    'base_url' => env('DUITKU_BASE_URL', 'https://sandbox.duitku.com/webapi/api/merchant'),
    'callback_url' => env('DUITKU_CALLBACK_URL'),
    'return_url' => env('DUITKU_RETURN_URL'),
    'expiry_period' => env('DUITKU_EXPIRY_PERIOD', 1440), // menit
];
```

### Service Pattern untuk Duitku

```php
// app/Services/Payment/DuitkuService.php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DuitkuService
{
    private string $merchantCode;
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->merchantCode = config('duitku.merchant_code');
        $this->apiKey = config('duitku.api_key');
        $this->baseUrl = config('duitku.base_url');
    }

    public function createTransaction(
        string $merchantOrderId,
        int $amount,
        string $productDetails,
        string $customerEmail,
        string $paymentMethod,
    ): array {
        $timestamp = now()->timestamp;
        $signature = md5($this->merchantCode . $merchantOrderId . $amount . $this->apiKey);

        $payload = [
            'merchantCode' => $this->merchantCode,
            'paymentAmount' => $amount,
            'merchantOrderId' => $merchantOrderId,
            'productDetails' => $productDetails,
            'email' => $customerEmail,
            'paymentMethod' => $paymentMethod,
            'callbackUrl' => config('duitku.callback_url'),
            'returnUrl' => config('duitku.return_url'),
            'signature' => $signature,
            'expiryPeriod' => config('duitku.expiry_period'),
        ];

        $response = Http::post("{$this->baseUrl}/v2/inquiry", $payload);

        if ($response->failed()) {
            Log::error('Duitku create transaction failed', [
                'order_id' => $merchantOrderId,
                'response' => $response->body(),
            ]);

            throw new \RuntimeException('Payment gateway request failed');
        }

        return $response->json();
    }

    public function verifyCallback(array $data): bool
    {
        $merchantCode = $data['merchantCode'] ?? '';
        $amount = $data['amount'] ?? '';
        $merchantOrderId = $data['merchantOrderId'] ?? '';
        $signature = $data['signature'] ?? '';

        $expectedSignature = md5($this->merchantCode . $amount . $merchantOrderId . $this->apiKey);

        return hash_equals($expectedSignature, $signature);
    }
}
```

### Flow Donasi

```php
// app/Services/Donation/DonationService.php (partial)

public function createOnlineDonation(CreateDonationDTO $dto): Donation
{
    return DB::transaction(function () use ($dto) {
        // Hitung fee
        $feePercentage = $this->platformSettings->getFeePercentage();
        $feeMechanism = $this->platformSettings->getFeeMechanism();

        $feeAmount = (int) ceil($dto->amount * $feePercentage / 10000);
        $paymentAmount = $feeMechanism === 'added_to_donor'
            ? $dto->amount + $feeAmount
            : $dto->amount;
        $mosqueReceives = $feeMechanism === 'deducted_from_donation'
            ? $dto->amount - $feeAmount
            : $dto->amount;

        // Simpan donasi
        $donation = $this->donations->create([
            'mosque_id' => $dto->mosqueId,
            'user_id' => $dto->userId,
            'category' => $dto->category,
            'amount' => $dto->amount,
            'fee_amount' => $feeAmount,
            'payment_amount' => $paymentAmount,
            'mosque_receives' => $mosqueReceives,
            'is_anonymous' => $dto->isAnonymous,
            'status' => DonationStatus::PENDING,
            'merchant_order_id' => $this->generateOrderId(),
        ]);

        // Request ke Duitku
        $paymentResult = $this->duitkuService->createTransaction(
            merchantOrderId: $donation->merchant_order_id,
            amount: $paymentAmount,
            productDetails: "Donasi {$dto->category->label()} - {$donation->mosque->name}",
            customerEmail: $dto->email,
            paymentMethod: $dto->paymentMethod,
        );

        // Update dengan payment reference
        $this->donations->update($donation, [
            'payment_url' => $paymentResult['paymentUrl'] ?? null,
            'reference' => $paymentResult['reference'] ?? null,
        ]);

        return $donation->fresh();
    });
}
```

---

## 15. Export CSV dan PDF

### Export CSV (Maatwebsite Excel)

```php
// app/Exports/Finance/CashTransactionExport.php

namespace App\Exports\Finance;

use App\Models\CashTransaction;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CashTransactionExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(
        private readonly ?string $startDate = null,
        private readonly ?string $endDate = null,
    ) {}

    public function query()
    {
        $query = CashTransaction::query()->orderBy('transaction_date', 'desc');

        if ($this->startDate) {
            $query->whereDate('transaction_date', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('transaction_date', '<=', $this->endDate);
        }

        return $query;
    }

    public function headings(): array
    {
        return ['Tanggal', 'Tipe', 'Kategori', 'Keterangan', 'Nominal', 'Catatan'];
    }

    public function map($row): array
    {
        return [
            $row->transaction_date->format('d/m/Y'),
            $row->type->label(),
            $row->category ?? '-',
            $row->description,
            $row->amount,
            $row->notes ?? '-',
        ];
    }
}
```

### Export PDF (DomPDF)

```php
// Di controller
use Barryvdh\DomPDF\Facade\Pdf;

public function exportPdf(Request $request)
{
    $transactions = $this->transactions->paginate(
        perPage: 999999,
        filters: $request->only(['start_date', 'end_date']),
    );

    $report = $this->financeService->getMonthlyReport(
        $request->integer('year', now()->year),
        $request->integer('month', now()->month),
    );

    $pdf = Pdf::loadView('admin.finance.report-pdf', [
        'transactions' => $transactions,
        'report' => $report,
        'mosque' => app('current_mosque'),
    ]);

    return $pdf->download("laporan-keuangan-{$report['period']}.pdf");
}
```

---

## 16. DataTables (Yajra)

### Controller Method

```php
// Di controller yang melayani DataTable AJAX

public function datatable(Request $request)
{
    $query = CashTransaction::query()
        ->select(['id', 'type', 'amount', 'description', 'category', 'transaction_date']);

    return datatables()
        ->eloquent($query)
        ->addColumn('type_label', fn ($row) => $row->type->label())
        ->addColumn('amount_formatted', fn ($row) => 'Rp ' . number_format($row->amount, 0, ',', '.'))
        ->addColumn('date_formatted', fn ($row) => $row->transaction_date->format('d M Y'))
        ->addColumn('actions', fn ($row) => view('admin.finance.income._actions', compact('row'))->render())
        ->rawColumns(['actions'])
        ->toJson();
}
```

### Blade View

```html
<table id="income-table" class="table table-striped">
    <thead>
        <tr>
            <th>Tanggal</th>
            <th>Kategori</th>
            <th>Keterangan</th>
            <th>Nominal</th>
            <th>Aksi</th>
        </tr>
    </thead>
</table>

@push('scripts')
<script>
$(function() {
    $('#income-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("admin.finance.income.datatable") }}',
        columns: [
            { data: 'date_formatted', name: 'transaction_date' },
            { data: 'category', name: 'category' },
            { data: 'description', name: 'description' },
            { data: 'amount_formatted', name: 'amount' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false },
        ],
        order: [[0, 'desc']],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json',
        },
    });
});
</script>
@endpush
```

---

## 17. SweetAlert Konfirmasi

### Handler Global (di layout)

```html
<!-- resources/views/components/admin/layout.blade.php -->

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('form[data-confirm="true"]').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const title = this.dataset.confirmTitle || 'Konfirmasi';
            const text = this.dataset.confirmText || 'Apakah Anda yakin?';
            const confirmButton = this.dataset.confirmButton || 'Ya, Lanjutkan';
            const processingText = this.dataset.processingText || 'Memproses...';
            const submitBtn = this.querySelector('[type="submit"]');
            const originalText = submitBtn.innerHTML;

            Swal.fire({
                title: title,
                text: text,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: confirmButton,
                cancelButtonText: 'Batal',
                reverseButtons: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = processingText;
                    this.submit();
                }
            });
        });
    });
});
</script>
@endpush
```

### Penggunaan di Form

```html
<form action="{{ route('admin.finance.income.store') }}"
      method="POST"
      data-confirm="true"
      data-confirm-title="Simpan Pemasukan"
      data-confirm-text="Pastikan data yang diisi sudah benar."
      data-confirm-button="Ya, Simpan"
      data-processing-text="Menyimpan...">
    @csrf
    <!-- form fields -->
    <button type="submit" class="btn btn-primary">Simpan</button>
</form>
```

### Untuk Tombol Delete (Non-Form)

```html
<button class="btn btn-danger btn-sm"
        data-delete-url="{{ route('admin.finance.income.destroy', $id) }}"
        data-delete-title="Hapus Pemasukan"
        data-delete-text="Data yang dihapus tidak dapat dikembalikan.">
    Hapus
</button>

@push('scripts')
<script>
document.querySelectorAll('[data-delete-url]').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const url = this.dataset.deleteUrl;
        const title = this.dataset.deleteTitle || 'Hapus Data';
        const text = this.dataset.deleteText || 'Apakah Anda yakin?';

        Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#dc3545',
            reverseButtons: true,
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = url;
                form.innerHTML = `@csrf @method('DELETE')`;
                document.body.appendChild(form);
                form.submit();
            }
        });
    });
});
</script>
@endpush
```

---

## 18. Migration Pattern

### Tabel Mosque-Scoped

```php
// database/migrations/xxxx_create_cash_transactions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mosque_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // enum: income, expense
            $table->unsignedBigInteger('amount')->default(0);
            $table->string('description');
            $table->string('category')->nullable();
            $table->date('transaction_date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['mosque_id', 'type', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');
    }
};
```

### Tabel Donasi (dengan fee)

```php
Schema::create('donations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('mosque_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('category'); // enum: infaq, zakat, sadaqah, waqf
    $table->unsignedBigInteger('amount')->default(0);
    $table->unsignedBigInteger('fee_amount')->default(0);
    $table->unsignedBigInteger('payment_amount')->default(0);
    $table->unsignedBigInteger('mosque_receives')->default(0);
    $table->string('status'); // enum: pending, confirmed, failed, expired
    $table->boolean('is_anonymous')->default(false);
    $table->string('merchant_order_id')->unique();
    $table->string('payment_url')->nullable();
    $table->string('reference')->nullable();
    $table->string('payment_method')->nullable();
    $table->timestamp('confirmed_at')->nullable();
    $table->timestamps();

    $table->index(['mosque_id', 'status']);
    $table->index(['user_id', 'status']);
    $table->index('merchant_order_id');
});
```

---

## 19. Testing Pattern

### Feature Test (Admin Panel)

```php
// tests/Feature/Admin/Finance/CashIncomeTest.php

namespace Tests\Feature\Admin\Finance;

use App\Models\User;
use App\Models\Mosque;
use Tests\TestCase;
use Tests\Traits\CreatesTestMosque;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CashIncomeTest extends TestCase
{
    use RefreshDatabase, CreatesTestMosque;

    private User $admin;
    private Mosque $mosque;

    protected function setUp(): void
    {
        parent::setUp();
        [$this->mosque, $this->admin] = $this->createTestMosque();
    }

    public function test_admin_can_create_cash_income(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.finance.income.store'), [
                'amount' => 500000,
                'description' => 'Infaq Jumat',
                'transaction_date' => '2026-06-06',
                'category' => 'infaq_jumat',
            ])
            ->assertRedirect(route('admin.finance.income.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('cash_transactions', [
            'mosque_id' => $this->mosque->id,
            'amount' => 500000,
            'type' => 'income',
        ]);
    }

    public function test_staff_without_permission_cannot_create(): void
    {
        $staff = $this->createStaffWithoutPermission($this->mosque);

        $this->actingAs($staff)
            ->post(route('admin.finance.income.store'), [
                'amount' => 500000,
                'description' => 'Test',
                'transaction_date' => '2026-06-06',
            ])
            ->assertForbidden();
    }

    public function test_cannot_access_other_mosque_data(): void
    {
        [$otherMosque, $otherAdmin] = $this->createTestMosque();

        $this->actingAs($otherAdmin)
            ->get(route('admin.finance.income.index'))
            ->assertOk();

        // Pastikan tidak ada data mosque lain
        // (karena BelongsToMosque scope otomatis filter)
    }
}
```

### Feature Test (API)

```php
// tests/Feature/Api/DonationTest.php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Mosque;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\CreatesTestMosque;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DonationTest extends TestCase
{
    use RefreshDatabase, CreatesTestMosque;

    public function test_congregation_can_create_donation(): void
    {
        [$mosque, $admin] = $this->createTestMosque();
        $jamaah = $this->createCongregation($mosque);

        Sanctum::actingAs($jamaah);

        $this->postJson(route('api.donations.create'), [
            'mosque_id' => $mosque->id,
            'category' => 'infaq',
            'amount' => 100000,
            'payment_method' => 'QRIS',
            'is_anonymous' => false,
        ])
        ->assertCreated()
        ->assertJsonStructure([
            'success',
            'data' => ['id', 'amount', 'payment_url', 'status'],
        ]);
    }

    public function test_unauthenticated_cannot_donate(): void
    {
        $this->postJson(route('api.donations.create'), [
            'amount' => 100000,
        ])
        ->assertUnauthorized();
    }
}
```

### Test Trait

```php
// tests/Traits/CreatesTestMosque.php

namespace Tests\Traits;

use App\Models\Mosque;
use App\Models\User;
use App\Enums\MosqueStatus;
use Spatie\Permission\Models\Role;

trait CreatesTestMosque
{
    protected function createTestMosque(): array
    {
        $mosque = Mosque::factory()->create([
            'status' => MosqueStatus::ACTIVE,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('mosque-admin');

        // Set mosque context
        app()->instance('current_mosque', $mosque);

        return [$mosque, $admin];
    }

    protected function createStaffWithoutPermission(Mosque $mosque): User
    {
        $staff = User::factory()->create();
        $staff->assignRole('staff');

        return $staff;
    }

    protected function createCongregation(Mosque $mosque): User
    {
        $user = User::factory()->create();
        $user->assignRole('congregation');
        $mosque->users()->attach($user->id);

        return $user;
    }
}
```

---

## 20. Permission Seeder

```php
// database/seeders/PermissionSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define permissions
        $permissions = [
            // Mosque
            'mosque.edit',

            // Schedule
            'schedule.view',
            'schedule.create',
            'schedule.edit',
            'schedule.delete',

            // Finance
            'finance.view',
            'finance.create_income',
            'finance.create_expense',
            'finance.export',

            // Donation
            'donation.view',
            'donation.confirm',

            // Announcement
            'announcement.view',
            'announcement.create',
            'announcement.publish',
            'announcement.delete',

            // Congregation
            'congregation.view',

            // Staff
            'staff.view',
            'staff.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles
        $mosqueAdmin = Role::firstOrCreate(['name' => 'mosque-admin']);
        $mosqueAdmin->givePermissionTo($permissions);

        $staff = Role::firstOrCreate(['name' => 'staff']);
        // Staff permissions assigned individually per user

        $congregation = Role::firstOrCreate(['name' => 'congregation']);
        // Congregation has no admin permissions

        // Owner role (platform-level, no team scope)
        Role::firstOrCreate(['name' => 'super-admin']);
    }
}
```

---

## Penutup

Dokumen ini berisi contoh implementasi yang disetujui. Saat membuat fitur baru:

1. Cari pattern yang sesuai di dokumen ini
2. Copy-paste dan adaptasi sesuai domain baru
3. Jangan membuat pattern baru yang bertentangan tanpa diskusi
4. Jika menemukan pattern yang lebih baik, update dokumen ini

Konsistensi lebih penting daripada kreativitas arsitektur.
