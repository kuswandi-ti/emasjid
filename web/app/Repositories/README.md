# Repository Layer Documentation

This directory contains the repository implementations for the EMasjid application. The repository pattern provides an abstraction layer between the data access logic and the business logic.

## Directory Structure

```
app/
├── Contracts/
│   └── Repositories/          # Repository interfaces
│       ├── MosqueRepositoryInterface.php
│       ├── ScheduleRepositoryInterface.php
│       ├── CashTransactionRepositoryInterface.php
│       ├── DonationRepositoryInterface.php
│       ├── AnnouncementRepositoryInterface.php
│       ├── CongregationRepositoryInterface.php
│       ├── StaffRepositoryInterface.php
│       └── PlatformSettingRepositoryInterface.php
│
└── Repositories/              # Repository implementations
    ├── MosqueRepository.php
    ├── ScheduleRepository.php
    ├── CashTransactionRepository.php
    ├── DonationRepository.php
    ├── AnnouncementRepository.php
    ├── CongregationRepository.php
    ├── StaffRepository.php
    └── PlatformSettingRepository.php
```

## Available Repositories

### 1. MosqueRepository
Handles all mosque-related data operations.

**Key Methods:**
- `all()` - Get all mosques with filters and pagination
- `find()` - Get mosque by ID
- `findBySlug()` - Get mosque by slug
- `findByInvitationCode()` - Get mosque by invitation code
- `getNearby()` - Get nearby mosques using Haversine formula
- `search()` - Search mosques by name or location
- `getByStatus()` - Filter mosques by status
- `getByCity()` - Get mosques in a specific city

### 2. ScheduleRepository
Manages prayer schedules for mosques.

**Key Methods:**
- `all()` - Get all schedules for a mosque
- `findByDate()` - Get schedule for a specific date
- `getToday()` - Get today's schedule
- `getUpcoming()` - Get upcoming schedules (next N days)
- `upsertMany()` - Bulk create or update schedules
- `getByDateRange()` - Get schedules within a date range

### 3. CashTransactionRepository
Handles mosque cash transactions (income/expense).

**Key Methods:**
- `all()` - Get all transactions with filters
- `getByType()` - Filter by income or expense
- `getByCategory()` - Filter by category
- `getBalance()` - Calculate total balance
- `getSummary()` - Get income/expense summary
- `getByDateRange()` - Get transactions within date range

### 4. DonationRepository
Manages online donations to mosques.

**Key Methods:**
- `all()` - Get all donations with filters
- `findByMerchantOrderId()` - Find by payment gateway order ID
- `getByStatus()` - Filter by status (pending/confirmed/failed/expired)
- `getByCategory()` - Filter by category (infaq/zakat/sadaqah/waqf)
- `getTotalDonations()` - Calculate total donation amount
- `getSummaryByCategory()` - Get summary grouped by category
- `markAsConfirmed()` - Mark donation as confirmed
- `markAsFailed()` - Mark donation as failed
- `markAsExpired()` - Mark donation as expired

### 5. AnnouncementRepository
Handles mosque announcements.

**Key Methods:**
- `all()` - Get all announcements with filters
- `getPublished()` - Get published announcements only
- `publish()` - Publish an announcement
- `archive()` - Archive an announcement
- `search()` - Search by title or content
- `getRecent()` - Get recent announcements

### 6. CongregationRepository
Manages mosque congregation members (jamaah).

**Key Methods:**
- `all()` - Get all members for a mosque
- `attach()` - Add a user as congregation member
- `detach()` - Remove a user from congregation
- `isMember()` - Check if user is a member
- `getUserMosques()` - Get all mosques a user has joined
- `search()` - Search members by name/email
- `getRecent()` - Get recently joined members

### 7. StaffRepository
Manages mosque staff members (pengurus).

**Key Methods:**
- `all()` - Get all staff members
- `findByUser()` - Find staff by user ID
- `getActive()` - Get active staff members only
- `getByPosition()` - Filter by position
- `activate()/deactivate()` - Change staff status
- `isStaff()` - Check if user is a staff member
- `getUserStaffMosques()` - Get mosques where user is staff

### 8. PlatformSettingRepository
Handles platform-level settings (key-value store).

**Key Methods:**
- `get()` - Get setting by key
- `getValue()` - Get value with default fallback
- `set()` - Create or update setting
- `getMany()` - Get multiple settings
- `setMany()` - Update multiple settings at once
- `getAllAsKeyValue()` - Get all as associative array

**Note:** This repository uses caching for performance.

## Usage in Services

Repositories should be injected via constructor in your service classes:

```php
use App\Contracts\Repositories\MosqueRepositoryInterface;

class MosqueService
{
    public function __construct(
        protected MosqueRepositoryInterface $mosqueRepository
    ) {}

    public function getAllActiveMosques()
    {
        return $this->mosqueRepository->getByStatus('active');
    }
}
```

## Usage in Controllers

You can also inject repositories directly in controllers:

```php
use App\Contracts\Repositories\DonationRepositoryInterface;

class DonationController extends Controller
{
    public function __construct(
        protected DonationRepositoryInterface $donationRepository
    ) {}

    public function index()
    {
        $donations = $this->donationRepository->all(
            mosqueId: mosque_id(),
            filters: request()->all(),
            perPage: 15
        );

        return api_paginated($donations);
    }
}
```

## API Response Helpers

The application provides both a trait and helper functions for consistent API responses:

### Using the Trait

```php
use App\Traits\ApiResponse;

class MyController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $data = $this->repository->all();
        return $this->successWithPagination($data);
    }

    public function store()
    {
        $item = $this->repository->create($data);
        return $this->created($item, 'Item created successfully');
    }
}
```

### Using Helper Functions

```php
public function index()
{
    $data = $this->repository->all();
    return api_paginated($data);
}

public function store()
{
    $item = $this->repository->create($data);
    return api_created($item, 'Item created successfully');
}

public function update()
{
    $item = $this->repository->update($id, $data);
    return api_updated($item);
}

public function destroy()
{
    $this->repository->delete($id);
    return api_deleted();
}
```

### Available Helper Functions

- `api_success($data, $message, $statusCode)` - General success response
- `api_error($message, $statusCode, $errors)` - General error response
- `api_paginated($paginator, $message)` - Paginated response
- `api_created($data, $message)` - 201 Created response
- `api_updated($data, $message)` - Update success response
- `api_deleted($message)` - Delete success response
- `api_not_found($message)` - 404 response
- `api_unauthorized($message)` - 401 response
- `api_forbidden($message)` - 403 response
- `api_validation_error($errors, $message)` - 422 validation error
- `api_server_error($message, $errors)` - 500 server error

## Response Format

All API responses follow this standard format:

### Success Response
```json
{
  "success": true,
  "message": "Success message",
  "data": { ... }
}
```

### Paginated Response
```json
{
  "success": true,
  "message": "Success message",
  "data": [ ... ],
  "pagination": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7,
    "from": 1,
    "to": 15,
    "path": "http://..."
  }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error message",
  "errors": { ... }  // Optional
}
```

## Service Provider Registration

The `RepositoryServiceProvider` is automatically registered in `bootstrap/providers.php` and binds all repository interfaces to their implementations using Laravel's container.

## Testing

When testing, you can easily mock repositories:

```php
use App\Contracts\Repositories\MosqueRepositoryInterface;

public function test_example()
{
    $mock = Mockery::mock(MosqueRepositoryInterface::class);
    $mock->shouldReceive('find')->once()->andReturn(new Mosque());
    
    $this->app->instance(MosqueRepositoryInterface::class, $mock);
    
    // Your test code...
}
```

## Best Practices

1. **Always use interfaces** when injecting repositories
2. **Keep repositories focused** on data operations only
3. **Business logic** should be in service classes, not repositories
4. **Use filters array** for flexible query building
5. **Return appropriate types** - use type hints consistently
6. **Cache when appropriate** - especially for settings and rarely-changing data
7. **Use transactions** for multi-step operations in services, not repositories

## Next Steps

After implementing the repository layer, you should:

1. Create service classes that use these repositories
2. Build controllers that use services
3. Add proper validation using Form Requests
4. Implement authorization using policies
5. Add API resources for response transformation
6. Write unit tests for repositories
