# Repository Architecture - EMasjid

## Layer Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                         CLIENT (Mobile App)                          │
└─────────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────────┐
│                         API ROUTES (routes/api.php)                  │
│  GET  /api/v1/mosques                                                │
│  POST /api/v1/donations                                              │
│  GET  /api/v1/schedules                                              │
└─────────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    CONTROLLERS (app/Http/Controllers)                │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐  │
│  │ MosqueController │  │DonationController│  │ScheduleController│  │
│  └──────────────────┘  └──────────────────┘  └──────────────────┘  │
│  - Handles HTTP requests                                             │
│  - Validates input (Form Requests)                                   │
│  - Returns API responses (ApiResponse trait/helpers)                 │
└─────────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────────┐
│                      SERVICES (app/Services) - TBD                   │
│  ┌──────────────┐  ┌──────────────────┐  ┌──────────────────┐     │
│  │MosqueService │  │ DonationService  │  │  PaymentService  │     │
│  └──────────────┘  └──────────────────┘  └──────────────────┘     │
│  - Business logic                                                    │
│  - Transaction management                                            │
│  - Cross-repository operations                                       │
└─────────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────────┐
│              REPOSITORY LAYER (app/Repositories) ✅ DONE             │
│  ┌────────────────────────────────────────────────────────────────┐ │
│  │  Repository Interfaces (app/Contracts/Repositories)            │ │
│  │  ┌──────────────────────┐  ┌───────────────────────────────┐  │ │
│  │  │ MosqueRepositoryInt. │  │ DonationRepositoryInterface   │  │ │
│  │  └──────────────────────┘  └───────────────────────────────┘  │ │
│  │  Defines contracts for data operations                          │ │
│  └────────────────────────────────────────────────────────────────┘ │
│                                  │                                   │
│  ┌────────────────────────────────────────────────────────────────┐ │
│  │  Repository Implementations (app/Repositories)                 │ │
│  │  ┌──────────────────┐  ┌────────────────────┐                 │ │
│  │  │ MosqueRepository │  │ DonationRepository │                 │ │
│  │  └──────────────────┘  └────────────────────┘                 │ │
│  │  - CRUD operations                                              │ │
│  │  - Filtering & pagination                                       │ │
│  │  - Query optimization                                           │ │
│  └────────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────────┐
│                      ELOQUENT MODELS (app/Models)                    │
│  ┌────────────┐  ┌────────────┐  ┌────────────┐  ┌────────────┐   │
│  │   Mosque   │  │  Donation  │  │  Schedule  │  │    User    │   │
│  └────────────┘  └────────────┘  └────────────┘  └────────────┘   │
│  - Table mapping                                                     │
│  - Relationships                                                     │
│  - Casts & accessors                                                 │
└─────────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────────┐
│                         DATABASE (MySQL)                             │
│  ┌────────┐  ┌────────────┐  ┌───────────┐  ┌────────────────┐    │
│  │mosques │  │ donations  │  │ schedules │  │ cash_transactions│   │
│  └────────┘  └────────────┘  └───────────┘  └────────────────┘    │
└─────────────────────────────────────────────────────────────────────┘
```

## Repository Pattern Flow

```
┌──────────────┐
│  Controller  │  1. Receives HTTP request
└──────┬───────┘
       │ inject
       ▼
┌──────────────┐
│   Service    │  2. Contains business logic
└──────┬───────┘     (transaction, validation)
       │ inject
       ▼
┌──────────────────────────┐
│ Repository Interface     │  3. Defines contract
│ (MosqueRepositoryInt.)   │     (what methods exist)
└──────────┬───────────────┘
           │ bound to
           ▼
┌──────────────────────────┐
│ Repository Implementation│  4. Implements data access
│ (MosqueRepository)       │     (how to get data)
└──────────┬───────────────┘
           │ uses
           ▼
┌──────────────────────────┐
│    Eloquent Model        │  5. Represents database table
│    (Mosque)              │     (ORM mapping)
└──────────┬───────────────┘
           │ queries
           ▼
┌──────────────────────────┐
│      Database            │  6. Stores actual data
│      (mosques table)     │
└──────────────────────────┘
```

## Request → Response Flow Example

### Example: Get All Mosques

```
1. Client Request
   GET /api/v1/mosques?status=active&city=Jakarta&per_page=20

2. Route (routes/api.php)
   Route::get('/mosques', [MosqueController::class, 'index']);

3. Controller (app/Http/Controllers/MosqueController.php)
   public function index(MosqueRepositoryInterface $mosque)
   {
       $mosques = $mosque->all(
           filters: request()->only(['status', 'city', 'search']),
           perPage: request('per_page', 15)
       );
       
       return api_paginated($mosques, 'Mosques retrieved successfully');
   }

4. Repository Interface (app/Contracts/Repositories/MosqueRepositoryInterface.php)
   public function all(array $filters = [], int $perPage = 15): LengthAwarePaginator;

5. Repository Implementation (app/Repositories/MosqueRepository.php)
   public function all(array $filters = [], int $perPage = 15): LengthAwarePaginator
   {
       $query = Mosque::query();
       
       if (isset($filters['status'])) {
           $query->where('status', $filters['status']);
       }
       
       if (isset($filters['city'])) {
           $query->where('city', $filters['city']);
       }
       
       return $query->with('admin')->latest()->paginate($perPage);
   }

6. Eloquent Model (app/Models/Mosque.php)
   - Executes query on 'mosques' table
   - Eager loads 'admin' relationship
   - Returns paginated collection

7. Response Helper (app/Helpers/helpers.php)
   function api_paginated($paginator, $message)
   {
       return response()->json([
           'success' => true,
           'message' => $message,
           'data' => $paginator->items(),
           'pagination' => [...]
       ]);
   }

8. JSON Response to Client
   {
     "success": true,
     "message": "Mosques retrieved successfully",
     "data": [
       {
         "id": 1,
         "name": "Masjid Al-Ikhlas",
         "slug": "masjid-al-ikhlas",
         "city": "Jakarta",
         "status": "active",
         "admin": {
           "id": 1,
           "name": "Ahmad"
         }
       }
     ],
     "pagination": {
       "current_page": 1,
       "per_page": 20,
       "total": 100
     }
   }
```

## Repository Binding (Dependency Injection)

```
┌─────────────────────────────────────────────────────────────────┐
│         RepositoryServiceProvider (app/Providers)               │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  public array $bindings = [                                     │
│      MosqueRepositoryInterface::class => MosqueRepository::class│
│      ...                                                        │
│  ];                                                             │
│                                                                 │
│  When Laravel sees:                                             │
│    - Type-hint: MosqueRepositoryInterface                       │
│    - It will inject: MosqueRepository instance                  │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│              Laravel Service Container                          │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  Interface          →         Implementation             │  │
│  ├──────────────────────────────────────────────────────────┤  │
│  │  MosqueRepositoryInterface  →  MosqueRepository          │  │
│  │  DonationRepositoryInterface → DonationRepository        │  │
│  │  ScheduleRepositoryInterface → ScheduleRepository        │  │
│  │  ...                                                     │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Automatic Injection                          │
│                                                                 │
│  class MosqueController extends Controller                      │
│  {                                                              │
│      public function __construct(                               │
│          protected MosqueRepositoryInterface $mosque  // ← Auto│
│      ) {}                                                       │
│  }                                                              │
│                                                                 │
│  Laravel automatically resolves and injects MosqueRepository    │
└─────────────────────────────────────────────────────────────────┘
```

## Repository Methods Organization

### All Repositories Follow This Pattern:

```
┌─────────────────────────────────────────────────────────────────┐
│                    Repository Interface                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Basic CRUD:                                                    │
│  ├── all()        : List with filters & pagination             │
│  ├── find()       : Get by ID                                  │
│  ├── create()     : Create new record                          │
│  ├── update()     : Update existing record                     │
│  └── delete()     : Delete record                              │
│                                                                 │
│  Specialized Finders:                                           │
│  ├── findBy*()    : Find by specific field                     │
│  ├── getBy*()     : Get collection by criteria                 │
│  └── search()     : Search with keyword                        │
│                                                                 │
│  Utility Methods:                                               │
│  ├── exists()     : Check existence                            │
│  ├── count()      : Count records                              │
│  ├── getRecent()  : Get recent records                         │
│  └── getSummary() : Get aggregated data                        │
│                                                                 │
│  Status Methods (where applicable):                             │
│  ├── activate()   : Activate record                            │
│  ├── deactivate() : Deactivate record                          │
│  ├── publish()    : Publish content                            │
│  └── archive()    : Archive content                            │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

## Testing Strategy

```
┌─────────────────────────────────────────────────────────────────┐
│                         Unit Tests                              │
│  - Test each repository method in isolation                     │
│  - Mock database using factories                                │
│  - Test edge cases and error handling                           │
│                                                                 │
│  Example:                                                       │
│    it('can find mosque by slug', function() {                   │
│        $mosque = Mosque::factory()->create(['slug' => 'test']); │
│        $result = $repo->findBySlug('test');                     │
│        expect($result->id)->toBe($mosque->id);                  │
│    });                                                          │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                     Integration Tests                           │
│  - Test repository + database interaction                       │
│  - Test repository + model relationships                        │
│  - Test complex queries and joins                               │
│                                                                 │
│  Example:                                                       │
│    it('loads relationships correctly', function() {             │
│        $mosque = Mosque::factory()->hasMembers(5)->create();    │
│        $result = $repo->find($mosque->id);                      │
│        expect($result->members)->toHaveCount(5);                │
│    });                                                          │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      Feature Tests                              │
│  - Test full request → response flow                            │
│  - Test API endpoints using repositories                        │
│  - Test authorization and validation                            │
│                                                                 │
│  Example:                                                       │
│    it('can get paginated mosques', function() {                 │
│        Mosque::factory(25)->create();                           │
│        $response = $this->get('/api/v1/mosques?per_page=10');   │
│        $response->assertStatus(200)                             │
│                ->assertJsonCount(10, 'data')                    │
│                ->assertJsonStructure(['pagination']);           │
│    });                                                          │
└─────────────────────────────────────────────────────────────────┘
```

## Benefits of Repository Pattern

```
┌─────────────────────────────────────────────────────────────────┐
│  1. Separation of Concerns                                      │
│     ├── Controllers: Handle HTTP                                │
│     ├── Services: Business logic                                │
│     ├── Repositories: Data access                               │
│     └── Models: Data representation                             │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  2. Testability                                                 │
│     ├── Easy to mock repositories in tests                      │
│     ├── Test business logic without database                    │
│     └── Test data layer in isolation                            │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  3. Flexibility                                                 │
│     ├── Switch database without changing controllers            │
│     ├── Add caching layer easily                                │
│     └── Change ORM without affecting business logic             │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  4. Reusability                                                 │
│     ├── Same repository in multiple controllers                 │
│     ├── Share common query logic                                │
│     └── Avoid code duplication                                  │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  5. Consistency                                                 │
│     ├── Standard methods across all repositories                │
│     ├── Consistent API response format                          │
│     └── Predictable behavior                                    │
└─────────────────────────────────────────────────────────────────┘
```

## What's Next?

```
Day 6 ✅ Repository Layer
   └─> Ready for use

Day 7 → Service Layer
   ├── MosqueService (business logic)
   ├── DonationService (payment integration)
   ├── CashTransactionService (balance management)
   └── NotificationService (push notifications)

Day 8 → Controllers & Routes
   ├── MosqueController
   ├── DonationController
   ├── ScheduleController
   └── API routes definition

Day 9 → Form Requests & Validation
   ├── StoreMosqueRequest
   ├── UpdateMosqueRequest
   ├── StoreDonationRequest
   └── Validation rules

Day 10 → API Resources & Policies
   ├── MosqueResource
   ├── DonationResource
   ├── MosquePolicy
   └── DonationPolicy
```
