# Repository Layer - Quick Reference

## 🚀 Quick Start

### Using in Controllers

```php
use App\Contracts\Repositories\MosqueRepositoryInterface;
use App\Traits\ApiResponse;

class MosqueController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected MosqueRepositoryInterface $mosque
    ) {}

    public function index()
    {
        return $this->successWithPagination(
            $this->mosque->all(request()->all())
        );
    }

    public function show($id)
    {
        $mosque = $this->mosque->find($id);
        return $mosque 
            ? $this->success($mosque)
            : $this->notFound('Mosque not found');
    }

    public function store(Request $request)
    {
        $mosque = $this->mosque->create($request->validated());
        return $this->created($mosque);
    }

    public function update(Request $request, $id)
    {
        $this->mosque->update($id, $request->validated());
        return $this->updated();
    }

    public function destroy($id)
    {
        $this->mosque->delete($id);
        return $this->deleted();
    }
}
```

### Using with Helper Functions

```php
// Success with data
return api_success($data, 'Success message');

// Paginated response
return api_paginated($paginatedData);

// Created (201)
return api_created($data, 'Resource created');

// Updated
return api_updated($data, 'Resource updated');

// Deleted
return api_deleted('Resource deleted');

// Not found (404)
return api_not_found('Resource not found');

// Unauthorized (401)
return api_unauthorized('Please login');

// Forbidden (403)
return api_forbidden('Access denied');

// Validation error (422)
return api_validation_error($errors, 'Invalid input');

// Server error (500)
return api_server_error('Something went wrong');
```

## 📚 Repository Cheat Sheet

### MosqueRepository

```php
// Get all with filters
$mosques = $mosque->all([
    'status' => 'active',
    'city' => 'Jakarta',
    'search' => 'Al-Ikhlas'
], perPage: 20);

// Find by ID
$mosque = $mosque->find(1);

// Find by slug
$mosque = $mosque->findBySlug('masjid-al-ikhlas');

// Find by invitation code
$mosque = $mosque->findByInvitationCode('ABC123');

// Nearby mosques (5km radius)
$nearby = $mosque->getNearby(-6.2088, 106.8456, radiusKm: 5);

// Search
$results = $mosque->search('Al-Ikhlas');

// By status
$active = $mosque->getByStatus('active');

// By city
$jakarta = $mosque->getByCity('Jakarta');

// Check slug exists
$exists = $mosque->slugExists('masjid-al-ikhlas');

// Get by admin
$mosques = $mosque->getByAdmin(userId: 1);
```

### ScheduleRepository

```php
// Get all schedules
$schedules = $schedule->all(mosqueId: 1);

// Find by date
$today = $schedule->findByDate(mosqueId: 1, date: '2024-01-01');

// Get today
$today = $schedule->getToday(mosqueId: 1);

// Get upcoming (next 7 days)
$upcoming = $schedule->getUpcoming(mosqueId: 1, days: 7);

// Date range
$range = $schedule->getByDateRange(1, '2024-01-01', '2024-01-31');

// Bulk upsert
$count = $schedule->upsertMany(mosqueId: 1, schedules: [
    ['date' => '2024-01-01', 'subuh' => '04:30', ...],
    ['date' => '2024-01-02', 'subuh' => '04:31', ...],
]);
```

### CashTransactionRepository

```php
// Get all with filters
$transactions = $cash->all(mosqueId: 1, filters: [
    'type' => 'income',
    'date_from' => '2024-01-01',
    'date_to' => '2024-01-31'
]);

// By type
$income = $cash->getByType(1, 'income');
$expense = $cash->getByType(1, 'expense');

// By category
$donations = $cash->getByCategory(1, 'Donasi');

// Get balance
$balance = $cash->getBalance(mosqueId: 1);

// Get summary
$summary = $cash->getSummary(
    mosqueId: 1,
    startDate: '2024-01-01',
    endDate: '2024-12-31'
);
// Returns: ['income' => 1000000, 'expense' => 500000, 'balance' => 500000]
```

### DonationRepository

```php
// Get all with filters
$donations = $donation->all(mosqueId: 1, filters: [
    'status' => 'confirmed',
    'category' => 'zakat',
    'date_from' => '2024-01-01'
]);

// Find by merchant order ID
$donation = $donation->findByMerchantOrderId('DUITKU-123456');

// By status
$pending = $donation->getByStatus(1, 'pending');

// By category
$zakat = $donation->getByCategory(1, 'zakat');

// By user
$myDonations = $donation->getByUser(userId: 1);

// Total donations
$total = $donation->getTotalDonations(
    mosqueId: 1,
    status: 'confirmed',
    startDate: '2024-01-01',
    endDate: '2024-12-31'
);

// Summary by category
$summary = $donation->getSummaryByCategory(1);
// Returns: ['infaq' => ['total' => 1000000, 'count' => 50], ...]

// Recent donations
$recent = $donation->getRecent(mosqueId: 1, limit: 10);

// Mark as confirmed
$donation->markAsConfirmed(id: 1, data: [
    'reference' => 'REF-123',
    'payment_method' => 'QRIS'
]);

// Mark as failed
$donation->markAsFailed(id: 1, reason: 'Payment timeout');

// Mark as expired
$donation->markAsExpired(id: 1);
```

### AnnouncementRepository

```php
// Get all
$announcements = $announcement->all(mosqueId: 1);

// Published only
$published = $announcement->getPublished(mosqueId: 1);

// Publish
$announcement->publish(id: 1, publishedBy: auth()->id());

// Archive
$announcement->archive(id: 1);

// Recent
$recent = $announcement->getRecent(mosqueId: 1, limit: 5);

// Search
$results = $announcement->search(1, 'kajian');
```

### CongregationRepository

```php
// Get all members
$members = $congregation->all(mosqueId: 1);

// Find member
$member = $congregation->find(mosqueId: 1, userId: 1);

// Add member
$congregation->attach(mosqueId: 1, userId: 1);

// Remove member
$congregation->detach(mosqueId: 1, userId: 1);

// Check membership
$isMember = $congregation->isMember(mosqueId: 1, userId: 1);

// User's mosques
$mosques = $congregation->getUserMosques(userId: 1);

// Search members
$results = $congregation->search(1, 'john@example.com');

// Count members
$count = $congregation->count(mosqueId: 1);

// Recent members
$recent = $congregation->getRecent(mosqueId: 1, limit: 10);
```

### StaffRepository

```php
// Get all staff
$staff = $staff->all(mosqueId: 1);

// Find by user
$staff = $staff->findByUser(mosqueId: 1, userId: 1);

// Get active staff
$active = $staff->getActive(mosqueId: 1);

// By position
$ketua = $staff->getByPosition(1, 'Ketua DKM');

// Activate/Deactivate
$staff->activate(id: 1);
$staff->deactivate(id: 1);

// Check if staff
$isStaff = $staff->isStaff(mosqueId: 1, userId: 1);

// User's staff mosques
$mosques = $staff->getUserStaffMosques(userId: 1);
```

### PlatformSettingRepository

```php
// Get setting
$setting = $setting->get('platform_fee_percentage');

// Get value with default
$fee = $setting->getValue('platform_fee_percentage', 0);

// Set value
$setting->set('platform_fee_percentage', 250, '2.5% fee');

// Update existing
$setting->update('platform_fee_active', 1);

// Get multiple
$settings = $setting->getMany([
    'platform_fee_percentage',
    'platform_fee_mechanism',
    'platform_fee_active'
]);

// Set multiple
$setting->setMany([
    'platform_fee_percentage' => ['value' => 250, 'description' => '2.5%'],
    'platform_fee_active' => ['value' => 1, 'description' => 'Active']
]);

// Get all as array
$all = $setting->getAllAsKeyValue();
// Returns: ['key1' => 'value1', 'key2' => 'value2', ...]
```

## 🎨 Response Examples

### Success Response
```json
{
  "success": true,
  "message": "Data retrieved successfully",
  "data": {
    "id": 1,
    "name": "Masjid Al-Ikhlas",
    "slug": "masjid-al-ikhlas"
  }
}
```

### Paginated Response
```json
{
  "success": true,
  "message": "Data retrieved successfully",
  "data": [
    {"id": 1, "name": "Masjid 1"},
    {"id": 2, "name": "Masjid 2"}
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7,
    "from": 1,
    "to": 15
  }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "name": ["The name field is required."],
    "email": ["The email has already been taken."]
  }
}
```

## 💡 Pro Tips

1. **Always inject interfaces, not implementations:**
   ```php
   // ✅ Good
   public function __construct(MosqueRepositoryInterface $mosque)
   
   // ❌ Bad
   public function __construct(MosqueRepository $mosque)
   ```

2. **Use named arguments for clarity:**
   ```php
   // ✅ Good
   $mosques = $mosque->all(filters: $filters, perPage: 20);
   
   // ❌ Less clear
   $mosques = $mosque->all($filters, 20);
   ```

3. **Check existence before operations:**
   ```php
   // ✅ Good
   $mosque = $mosque->find($id);
   if (!$mosque) {
       return api_not_found('Mosque not found');
   }
   
   // ❌ Bad - will throw exception if not found
   $mosque = $mosque->find($id);
   $mosque->update(...);
   ```

4. **Use filters array for flexible queries:**
   ```php
   // ✅ Good - flexible
   $filters = request()->only(['status', 'city', 'search']);
   $mosques = $mosque->all($filters);
   
   // ❌ Less flexible
   $mosques = $mosque->getByStatus($status);
   ```

5. **Leverage helper functions for concise code:**
   ```php
   // ✅ Good - concise
   return api_paginated($mosques);
   
   // ❌ Verbose
   return $this->successWithPagination($mosques);
   ```

## 🔍 Common Patterns

### CRUD Operations
```php
// Create
$resource = $repository->create($data);
return api_created($resource);

// Read (single)
$resource = $repository->find($id);
return $resource ? api_success($resource) : api_not_found();

// Read (list)
$resources = $repository->all($filters);
return api_paginated($resources);

// Update
$repository->update($id, $data);
return api_updated();

// Delete
$repository->delete($id);
return api_deleted();
```

### Filtering Pattern
```php
$filters = [
    'status' => request('status'),
    'category' => request('category'),
    'date_from' => request('date_from'),
    'date_to' => request('date_to'),
    'search' => request('search'),
];

$results = $repository->all(
    mosqueId: mosque_id(),
    filters: array_filter($filters), // Remove null values
    perPage: request('per_page', 15)
);
```

### Error Handling Pattern
```php
try {
    $resource = $repository->create($data);
    return api_created($resource);
} catch (\Exception $e) {
    return api_server_error($e->getMessage());
}
```
