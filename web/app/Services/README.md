# Services Layer Documentation

> Service layer untuk business logic EMasjid platform.

---

## PlatformSettingService

Service untuk mengelola pengaturan platform, khususnya fee configuration.

### Usage

```php
use App\Services\PlatformSettingService;

$settingService = app(PlatformSettingService::class);
```

### Methods

#### `getAll(): Collection`
Get all platform settings.

```php
$allSettings = $settingService->getAll();
```

#### `getFeeSettings(): array`
Get fee settings (percentage, mechanism, active).

```php
$settings = $settingService->getFeeSettings();
// [
//     'fee_percentage' => 250,
//     'fee_mechanism' => 'added_to_donor',
//     'fee_active' => true,
// ]
```

#### `updateFeeSettings(array $data): bool`
Update fee settings.

```php
$settingService->updateFeeSettings([
    'fee_percentage' => 500,
    'fee_mechanism' => 'deducted_from_donation',
    'fee_active' => true,
]);
```

#### `calculateFee(int $amount): array`
Calculate fee amount based on donation amount and current settings.

```php
$calculation = $settingService->calculateFee(100000);
// [
//     'fee_amount' => 2500,
//     'payment_amount' => 102500,  // if added_to_donor
//     'mosque_receives' => 100000,
// ]
```

#### `getFeePreview(int $sampleAmount = 100000): array`
Get fee preview example with formatted values for UI display.

```php
$preview = $settingService->getFeePreview(100000);
// [
//     'settings' => [...],
//     'sample_amount' => 100000,
//     'sample_amount_formatted' => 'Rp 100.000',
//     'fee_amount' => 2500,
//     'fee_amount_formatted' => 'Rp 2.500',
//     'payment_amount' => 102500,
//     'payment_amount_formatted' => 'Rp 102.500',
//     'mosque_receives' => 100000,
//     'mosque_receives_formatted' => 'Rp 100.000',
//     'mechanism_label' => 'Biaya ditambahkan ke donatur',
// ]
```

---

## Integration Example: Donation Flow

### API Controller - Create Donation

```php
use App\Services\PlatformSettingService;

class DonationController extends Controller
{
    public function __construct(
        private PlatformSettingService $settingService
    ) {}

    public function store(CreateDonationRequest $request)
    {
        $validated = $request->validated();
        $amount = $validated['amount'];

        // Get fee calculation
        $feeSettings = $this->settingService->getFeeSettings();
        $calculation = $this->settingService->calculateFee($amount);

        // Create donation with fee snapshot
        $donation = Donation::create([
            'mosque_id' => $validated['mosque_id'],
            'user_id' => auth()->id(),
            'category' => $validated['category'],
            'amount' => $amount,
            'fee_amount' => $calculation['fee_amount'],
            'payment_amount' => $calculation['payment_amount'],
            'mosque_receives' => $calculation['mosque_receives'],
            'fee_mechanism' => $feeSettings['fee_mechanism'],
            'status' => 'pending',
            // ... other fields
        ]);

        return response()->json([
            'success' => true,
            'data' => new DonationResource($donation),
        ], 201);
    }
}
```

### Admin Controller - Donation Confirmation

```php
public function confirm(ConfirmDonationRequest $request, Donation $donation)
{
    $donation->update([
        'status' => 'confirmed',
        'confirmed_at' => now(),
    ]);

    // Record to cash transactions
    CashTransaction::create([
        'mosque_id' => $donation->mosque_id,
        'type' => 'income',
        'amount' => $donation->mosque_receives,  // Use snapshot
        'description' => "Donasi {$donation->category} dari {$donation->user->name}",
        'category' => $donation->category,
        'transaction_date' => now()->toDateString(),
        'recorded_by' => auth()->id(),
    ]);

    return redirect()->back()->with('success', 'Donasi dikonfirmasi.');
}
```

---

## Fee Calculation Logic

### Added to Donor

```
amount = 100000
fee_percentage = 250 (2.5%)
fee_amount = round((100000 * 250) / 10000) = 2500

payment_amount = amount + fee_amount = 102500
mosque_receives = amount = 100000
```

### Deducted from Donation

```
amount = 100000
fee_percentage = 250 (2.5%)
fee_amount = round((100000 * 250) / 10000) = 2500

payment_amount = amount = 100000
mosque_receives = amount - fee_amount = 97500
```

### Fee Inactive

```
fee_amount = 0
payment_amount = amount = 100000
mosque_receives = amount = 100000
```

---

## Best Practices

### 1. Always Use Fee Snapshot

When creating a donation, always store the fee calculation result at that moment:

```php
// ✅ Good - Store snapshot
$calculation = $settingService->calculateFee($amount);
$donation->fee_amount = $calculation['fee_amount'];
$donation->fee_mechanism = $feeSettings['fee_mechanism'];

// ❌ Bad - Don't recalculate later
// $donation->fee_amount might be different if settings changed
```

### 2. Cache Awareness

Settings are cached. If you update settings programmatically:

```php
// Cache is automatically cleared in repository
$settingService->updateFeeSettings($data);

// Fresh data will be fetched on next call
$settings = $settingService->getFeeSettings();
```

### 3. Basis Points Consistency

Always work with basis points internally, convert to percentage only for display:

```php
// Store in DB
'platform_fee_percentage' => '250'  // basis points

// Display
$percentage = $basisPoints / 100;  // 2.5%
```

---

## Testing

### Unit Test Example

```php
use App\Services\PlatformSettingService;
use Tests\TestCase;

class PlatformSettingServiceTest extends TestCase
{
    private PlatformSettingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PlatformSettingService::class);
    }

    public function test_calculate_fee_added_to_donor()
    {
        $this->service->updateFeeSettings([
            'fee_percentage' => 250,
            'fee_mechanism' => 'added_to_donor',
            'fee_active' => true,
        ]);

        $result = $this->service->calculateFee(100000);

        $this->assertEquals(2500, $result['fee_amount']);
        $this->assertEquals(102500, $result['payment_amount']);
        $this->assertEquals(100000, $result['mosque_receives']);
    }

    public function test_calculate_fee_deducted_from_donation()
    {
        $this->service->updateFeeSettings([
            'fee_percentage' => 250,
            'fee_mechanism' => 'deducted_from_donation',
            'fee_active' => true,
        ]);

        $result = $this->service->calculateFee(100000);

        $this->assertEquals(2500, $result['fee_amount']);
        $this->assertEquals(100000, $result['payment_amount']);
        $this->assertEquals(97500, $result['mosque_receives']);
    }

    public function test_calculate_fee_inactive()
    {
        $this->service->updateFeeSettings([
            'fee_percentage' => 250,
            'fee_mechanism' => 'added_to_donor',
            'fee_active' => false,
        ]);

        $result = $this->service->calculateFee(100000);

        $this->assertEquals(0, $result['fee_amount']);
        $this->assertEquals(100000, $result['payment_amount']);
        $this->assertEquals(100000, $result['mosque_receives']);
    }
}
```

---

## Changelog

### Version 1.0.0 (Day 9)
- Initial implementation
- Fee percentage, mechanism, and active status
- Calculate fee based on current settings
- Preview with formatted values

---

**Last Updated:** 8 Juni 2026
