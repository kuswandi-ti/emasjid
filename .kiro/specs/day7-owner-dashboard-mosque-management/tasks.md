# Implementation Plan: Owner Dashboard & Mosque Management

## Overview

This implementation plan covers the development of the Owner Dashboard & Mosque Management feature for the eMasjid platform. The feature provides platform owners (super-admins) with comprehensive tools to monitor platform statistics, view all registered mosques, manage verification requests, and access detailed mosque information. The implementation follows Laravel's MVC architecture with service and repository layers for maintainability.

## Tasks

- [x] 1. Set up middleware and authorization infrastructure
  - [x] 1.1 Create EnsureOwnerAccess middleware
    - Create `app/Http/Middleware/EnsureOwnerAccess.php`
    - Implement authentication check redirecting to login if not authenticated
    - Implement role check using `hasRole('super-admin')` with 403 response for unauthorized users
    - Register middleware alias 'owner' in `bootstrap/app.php` or appropriate Kernel file
    - _Requirements: 1.1, 1.2, 1.3, 1.4_

  - [x] 1.2 Write unit tests for EnsureOwnerAccess middleware
    - Test unauthenticated user redirection to login page
    - Test non-super-admin user receives 403 Forbidden response
    - Test super-admin user successfully passes through middleware
    - _Requirements: 1.2, 1.3, 1.4_

- [x] 2. Extend MosqueRepository with owner dashboard methods
  - [x] 2.1 Add new methods to MosqueRepositoryInterface
    - Add `countByStatus(MosqueStatus $status): int` method signature
    - Add `getTotalCongregationCount(): int` method signature
    - Add `getPending(?string $search, int $perPage): LengthAwarePaginator` method signature
    - Add `findWithRelations(int $id, array $relations): ?Mosque` method signature
    - Update `app/Contracts/Repositories/MosqueRepositoryInterface.php`
    - _Requirements: 2.1, 2.2, 3.1, 3.2, 4.1, 5.1, 5.2_

  - [x] 2.2 Implement countByStatus method in MosqueRepository
    - Query mosques table filtered by status enum value
    - Return integer count of matching records
    - Update `app/Repositories/MosqueRepository.php`
    - _Requirements: 2.1, 2.2, 2.3_

  - [x] 2.3 Implement getTotalCongregationCount method
    - Join mosque_user pivot table with mosques table
    - Filter by active mosque status
    - Return total count of congregation members across all active mosques
    - _Requirements: 2.4_

  - [x] 2.4 Implement getPending method with search support
    - Query mosques with 'pending' status
    - Eager load 'admin' relationship to prevent N+1 queries
    - Include members count using `withCount('members')`
    - Apply optional search filter across name, city, and admin email fields
    - Order results by created_at ascending (oldest first)
    - Return paginated results
    - _Requirements: 4.1, 4.2, 4.6, 8.3_

  - [x] 2.5 Implement findWithRelations method
    - Accept mosque ID and array of relationship names
    - Eager load specified relationships using `with()`
    - Include members count using `withCount('members')`
    - Include total donations sum using `withSum('donations as total_donations', 'mosque_receives')`
    - Return Mosque model or null if not found
    - _Requirements: 5.1, 5.2, 8.3_

  - [x] 2.6 Write unit tests for repository methods
    - Test countByStatus returns correct counts for each status
    - Test getTotalCongregationCount aggregates correctly across active mosques
    - Test getPending filters, searches, and orders correctly
    - Test findWithRelations loads all specified relationships
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 4.1, 5.1_

- [x] 3. Create DashboardService for platform statistics
  - [x] 3.1 Create DashboardService class
    - Create `app/Services/DashboardService.php`
    - Inject MosqueRepositoryInterface via constructor
    - Implement `getStatistics(): array` method
    - Use Cache facade with 'owner.dashboard.statistics' key and 5-minute TTL
    - Calculate total_mosques_active using `countByStatus(MosqueStatus::ACTIVE)`
    - Calculate total_mosques_pending using `countByStatus(MosqueStatus::PENDING)`
    - Calculate total_mosques_suspended using `countByStatus(MosqueStatus::SUSPENDED)`
    - Calculate total_congregation using `getTotalCongregationCount()`
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 8.2_

  - [x] 3.2 Implement platform fee calculation
    - Create protected method `getPlatformFees(): int`
    - Query donations table for records with status 'confirmed'
    - Sum the fee_amount column
    - Return total platform fees as integer
    - Add total_platform_fees and total_platform_fees_formatted to statistics array
    - Use `format_rupiah()` helper for formatted currency value
    - _Requirements: 2.5, 2.6_

  - [x] 3.3 Add cache clearing method
    - Implement `clearStatisticsCache(): void` method
    - Use Cache::forget('owner.dashboard.statistics')
    - _Requirements: 8.2_

  - [x] 3.4 Write unit tests for DashboardService
    - Mock MosqueRepositoryInterface in tests
    - Test getStatistics returns correctly structured array with all required keys
    - Test statistics caching works correctly
    - Test getPlatformFees sums confirmed donation fees correctly
    - Test clearStatisticsCache removes cached data
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 8.2_

- [x] 4. Create MosqueService for owner operations
  - [x] 4.1 Create MosqueService class
    - Create `app/Services/MosqueService.php`
    - Inject MosqueRepositoryInterface via constructor
    - Implement `getMosqueList(array $filters, int $perPage): LengthAwarePaginator`
    - Delegate to repository's `all()` method with filters and pagination
    - _Requirements: 3.1, 3.2_

  - [x] 4.2 Implement getPendingMosques method
    - Create method signature: `getPendingMosques(?string $search, int $perPage): LengthAwarePaginator`
    - Delegate to repository's `getPending()` method
    - _Requirements: 4.1, 4.2_

  - [x] 4.3 Implement getMosqueDetail method
    - Create method signature: `getMosqueDetail(int $id): Mosque`
    - Call repository's `findWithRelations()` with 'admin' and 'members' relationships
    - Throw ModelNotFoundException if mosque not found
    - Return Mosque model with loaded relationships
    - _Requirements: 5.1, 5.2_

  - [x] 4.4 Implement getDaysWaiting helper method
    - Create method signature: `getDaysWaiting(Mosque $mosque): int`
    - Return 0 if mosque status is not PENDING
    - Calculate difference in days between created_at and now using Carbon's diffInDays
    - _Requirements: 4.4_

  - [x] 4.5 Write unit tests for MosqueService
    - Mock MosqueRepositoryInterface in tests
    - Test getMosqueList delegates correctly to repository
    - Test getPendingMosques delegates correctly
    - Test getMosqueDetail throws exception when mosque not found
    - Test getDaysWaiting calculates correctly for pending mosques
    - Test getDaysWaiting returns 0 for non-pending mosques
    - _Requirements: 3.1, 4.1, 5.1, 4.4_

- [x] 5. Checkpoint - Verify backend services and repository
  - Ensure all service and repository methods compile without errors
  - Ensure all tests pass, ask the user if questions arise

- [x] 6. Create Owner DashboardController
  - [x] 6.1 Create DashboardController in Owner namespace
    - Create directory `app/Http/Controllers/Owner/`
    - Create `app/Http/Controllers/Owner/DashboardController.php`
    - Extend base Controller class
    - Inject DashboardService via constructor with property promotion
    - Implement `index(): View` method
    - Call `$this->dashboardService->getStatistics()`
    - Return view 'owner.dashboard' with statistics data
    - _Requirements: 2.1, 2.6, 2.7_

  - [x] 6.2 Register dashboard route with middleware
    - Open `routes/web.php`
    - Create route group with prefix 'owner', name 'owner.', and middleware ['auth:web', 'owner']
    - Register GET route '/dashboard' to `DashboardController@index` with name 'dashboard'
    - _Requirements: 1.1, 1.4, 2.7_

  - [x] 6.3 Write feature test for dashboard access
    - Test unauthenticated access redirects to login
    - Test non-super-admin user receives 403
    - Test super-admin can access dashboard and sees statistics
    - _Requirements: 1.2, 1.3, 2.7_

- [x] 7. Create Owner MosqueController
  - [x] 7.1 Create MosqueController in Owner namespace
    - Create `app/Http/Controllers/Owner/MosqueController.php`
    - Extend base Controller class
    - Inject MosqueService via constructor with property promotion
    - _Requirements: 3.1, 4.1, 5.1_

  - [x] 7.2 Implement index method for mosque list
    - Create `index(Request $request): View` method
    - Extract filters from request: status, search, city
    - Get per_page parameter with default value 15
    - Call `$this->mosqueService->getMosqueList($filters, $perPage)`
    - Return view 'owner.mosques.index' with mosques and filters data
    - _Requirements: 3.1, 3.2, 3.3, 3.4_

  - [x] 7.3 Implement pending method for pending mosque queue
    - Create `pending(Request $request): View` method
    - Get per_page parameter with default value 15
    - Get search parameter from request
    - Call `$this->mosqueService->getPendingMosques($search, $perPage)`
    - Return view 'owner.mosques.pending' with mosques data
    - _Requirements: 4.1, 4.5_

  - [x] 7.4 Implement show method for mosque detail
    - Create `show(int $id): View` method
    - Call `$this->mosqueService->getMosqueDetail($id)`
    - Return view 'owner.mosques.show' with mosque data
    - _Requirements: 5.1, 5.3_

  - [x] 7.5 Register mosque routes
    - Open `routes/web.php`
    - In the 'owner' route group, register GET '/mosques' to `MosqueController@index` with name 'mosques.index'
    - Register GET '/mosques/pending' to `MosqueController@pending` with name 'mosques.pending'
    - Register GET '/mosques/{id}' to `MosqueController@show` with name 'mosques.show'
    - _Requirements: 3.1, 4.1, 5.1_

  - [x] 7.6 Write feature tests for mosque controller
    - Test mosque list displays correctly with pagination
    - Test filtering by status works
    - Test search functionality works
    - Test pending mosque page displays only pending status
    - Test mosque detail page shows complete information
    - Test non-existent mosque returns 404
    - _Requirements: 3.1, 3.5, 3.6, 4.1, 5.1_

- [x] 8. Create DTOs for mosque verification operations
  - [x] 8.1 Create ApproveMosqueDTO
    - Create directory `app/DataTransferObjects/` if not exists
    - Create `app/DataTransferObjects/ApproveMosqueDTO.php`
    - Add properties: mosque_id (int), approved_by_user_id (int), approved_at (Carbon)
    - Implement validation: mosque_id must exist and refer to pending mosque
    - _Requirements: 6.1, 6.3_

  - [x] 8.2 Create RejectMosqueDTO
    - Create `app/DataTransferObjects/RejectMosqueDTO.php`
    - Add properties: mosque_id (int), rejected_by_user_id (int), rejection_reason (string), rejected_at (Carbon)
    - Implement validation: mosque_id must exist and refer to pending mosque
    - Implement validation: rejection_reason minimum length of 10 characters
    - _Requirements: 6.2, 6.4, 6.5_

  - [x] 8.3 Write unit tests for DTOs
    - Test ApproveMosqueDTO validates mosque exists and has pending status
    - Test RejectMosqueDTO validates rejection_reason minimum length
    - Test RejectMosqueDTO validates mosque exists and has pending status
    - _Requirements: 6.3, 6.4, 6.5_

- [x] 9. Checkpoint - Verify backend is complete
  - Ensure all controllers, services, and DTOs are implemented
  - Ensure all routes are registered and accessible
  - Ensure all tests pass, ask the user if questions arise

- [x] 10. Create owner dashboard layout and partials
  - [x] 10.1 Create base layout for owner panel
    - Create directory `resources/views/owner/layouts/`
    - Create `resources/views/owner/layouts/app.blade.php`
    - Add HTML5 doctype and head section with UTF-8 charset and viewport meta tag
    - Add Bootstrap 5.3 CSS CDN link
    - Add Bootstrap Icons CDN link
    - Add `@stack('styles')` for page-specific styles
    - Create wrapper div with flex layout for sidebar and content
    - Include sidebar partial: `@include('owner.partials.sidebar')`
    - Create page-content-wrapper div for navbar and main content
    - Include navbar partial: `@include('owner.partials.navbar')`
    - Add container-fluid for main content with `@yield('content')`
    - Add Bootstrap 5.3 JS bundle CDN script
    - Add Alpine.js 3.x CDN script with defer attribute
    - Add `@stack('scripts')` for page-specific scripts
    - _Requirements: 7.5, 7.6_

  - [x] 10.2 Create sidebar navigation component
    - Create directory `resources/views/owner/partials/`
    - Create `resources/views/owner/partials/sidebar.blade.php`
    - Create dark-themed sidebar with min-height 100vh and width 250px
    - Add sidebar heading "EMasjid Owner"
    - Create navigation list with links: Dashboard, All Mosques, Pending Verification, Settings
    - Add Bootstrap Icons for each menu item
    - Highlight active page using `request()->routeIs()` helper
    - Display pending mosque count badge if pending count > 0
    - Use route helpers: `route('owner.dashboard')`, `route('owner.mosques.index')`, `route('owner.mosques.pending')`
    - _Requirements: 7.1, 7.2, 7.4_

  - [x] 10.3 Create top navbar component
    - Create `resources/views/owner/partials/navbar.blade.php`
    - Add light-themed navbar with border-bottom
    - Display current page title
    - Add user dropdown menu with logout option
    - _Requirements: 7.6_

- [x] 11. Create dashboard statistics view
  - [x] 11.1 Create dashboard blade template
    - Create `resources/views/owner/dashboard.blade.php`
    - Extend 'owner.layouts.app' layout
    - Set page title using `@section('title', 'Dashboard')`
    - Add page header with "Platform Overview" title and description
    - _Requirements: 2.7, 7.3_

  - [x] 11.2 Create statistics cards
    - Create responsive grid with Bootstrap row and col classes
    - Add card for Active Mosques: display `$statistics['total_mosques_active']` with success/green styling
    - Add card for Pending Verification: display `$statistics['total_mosques_pending']` with warning/yellow styling
    - Add card for Suspended: display `$statistics['total_mosques_suspended']` with danger/red styling
    - Add card for Total Members: display formatted `$statistics['total_congregation']` with primary/blue styling
    - Add card for Platform Revenue: display `$statistics['total_platform_fees_formatted']` with info styling
    - Use Bootstrap Icons for each card (building, clock-history, exclamation-triangle, people, cash-stack)
    - Add "Review Pending" button on Pending card if count > 0 linking to pending mosque page
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 7.3_

  - [x] 11.3 Create quick actions section
    - Add card with "Quick Actions" title
    - Add button linking to pending mosques page: "Review Pending Mosques"
    - Add button linking to all mosques page: "View All Mosques"
    - _Requirements: 7.3_

- [x] 12. Create mosque list view with filtering
  - [x] 12.1 Create mosque list blade template
    - Create `resources/views/owner/mosques/index.blade.php`
    - Extend 'owner.layouts.app' layout
    - Set page title using `@section('title', 'All Mosques')`
    - Add DataTables CSS in `@push('styles')` section
    - Add page header with "All Mosques" title and description
    - _Requirements: 3.1, 3.3, 7.5_

  - [x] 12.2 Create filter form
    - Create Alpine.js data context for status filter
    - Create form with GET method to 'owner.mosques.index' route
    - Add status dropdown with options: All Status, Active, Pending, Suspended, Rejected
    - Add search input field for name/city search
    - Add Filter button to submit form
    - Add Clear button to reset filters
    - Pre-populate form fields with current filter values from `$filters`
    - _Requirements: 3.5, 3.6_

  - [x] 12.3 Create mosques data table
    - Create responsive table with columns: Name, City, Status, Admin, Members, Registered, Actions
    - Loop through `$mosques` collection with `@foreach`
    - Display mosque name, city, and registration date
    - Display status badge with color coding: active=success, pending=warning, suspended=danger, rejected=secondary
    - Display admin name if relationship loaded, else show dash
    - Display members_count from relationship count
    - Add "View" button linking to mosque detail page using `route('owner.mosques.show', $mosque->id)`
    - Add pagination links: `{{ $mosques->links() }}`
    - _Requirements: 3.2, 3.3, 3.4, 3.7, 3.8, 3.9, 3.10_

  - [x] 12.4 Add DataTables JavaScript initialization
    - Add jQuery and DataTables JS CDN scripts in `@push('scripts')` section
    - Initialize DataTables on mosquesTable with client-side features
    - _Requirements: 3.3, 3.4, 3.6, 8.4_

- [x] 13. Create pending mosque queue view
  - [x] 13.1 Create pending mosques blade template
    - Create `resources/views/owner/mosques/pending.blade.php`
    - Extend 'owner.layouts.app' layout
    - Set page title using `@section('title', 'Pending Mosques')`
    - Add page header with "Pending Verification" title and description
    - _Requirements: 4.1, 4.3_

  - [x] 13.2 Create search form
    - Create search form with GET method to 'owner.mosques.pending' route
    - Add search input field for name/city/email search
    - Add Search button to submit form
    - Add Clear button to reset search
    - _Requirements: 4.6_

  - [x] 13.3 Create pending mosques table
    - Create table with columns: Name, City, Admin Email, Phone, Registration Date, Days Waiting, Actions
    - Loop through `$mosques` collection
    - Display mosque name, city, and admin details
    - Calculate days waiting using created_at timestamp: `$mosque->created_at->diffInDays(now())`
    - Highlight row with warning indicator if days waiting > 7
    - Format registration date: `$mosque->created_at->format('d M Y')`
    - Add "View Details" button linking to mosque detail page
    - Add pagination links
    - _Requirements: 4.2, 4.3, 4.4, 4.5, 4.7_

- [x] 14. Create mosque detail view
  - [x] 14.1 Create mosque detail blade template
    - Create `resources/views/owner/mosques/show.blade.php`
    - Extend 'owner.layouts.app' layout
    - Set dynamic page title using mosque name
    - Add page header with mosque name and back button to mosque list
    - _Requirements: 5.1, 5.3_

  - [x] 14.2 Display mosque basic information section
    - Create card section titled "Basic Information"
    - Display mosque name, description, address, city, province
    - Display mosque photo if available using conditional Blade directive
    - Display mosque status badge with appropriate color
    - _Requirements: 5.2, 5.4, 5.8_

  - [x] 14.3 Display contact details section
    - Create card section titled "Contact Details"
    - Display admin user name, email, and phone number
    - Use `$mosque->admin` relationship
    - _Requirements: 5.2, 5.6_

  - [x] 14.4 Display banking information section
    - Create card section titled "Banking Information"
    - Display bank name and account number
    - _Requirements: 5.3_

  - [x] 14.5 Display location map section
    - Create card section titled "Location"
    - Display embedded Google Maps iframe if latitude and longitude exist
    - Use conditional Blade directive to check for coordinates
    - _Requirements: 5.5_

  - [x] 14.6 Display statistics section
    - Create card section titled "Statistics"
    - Display congregation member count from `$mosque->members_count`
    - Display total donation amount from `$mosque->total_donations` formatted as currency
    - _Requirements: 5.2, 5.7_

  - [x] 14.7 Display status-specific information
    - If status is 'rejected', display rejection reason
    - If status is 'active', display approval timestamp
    - Use conditional Blade directives
    - _Requirements: 5.8, 5.9, 5.10_

- [x] 15. Add database indexing for performance
  - [x] 15.1 Create migration for mosque status index
    - Create migration: `php artisan make:migration add_indexes_to_mosques_table`
    - Add index on mosques.status column: `$table->index('status')`
    - Add index on mosques.created_at column: `$table->index('created_at')`
    - Add composite index on status and created_at: `$table->index(['status', 'created_at'])`
    - Run migration: `php artisan migrate`
    - _Requirements: 8.1_

- [x] 16. Implement loading states and empty states
  - [x] 16.1 Add loading indicators to views
    - Add loading spinner or skeleton screens to dashboard statistics cards
    - Add loading indicators to data tables while fetching data
    - Use Alpine.js or vanilla JavaScript for loading state management
    - _Requirements: 8.5_

  - [x] 16.2 Add empty state messages
    - Display "No mosques found" message when mosque list is empty
    - Display "No pending mosques" message when pending queue is empty
    - Display "No results found" message when search/filter returns empty results
    - Use conditional Blade directives with `@if($mosques->isEmpty())`
    - _Requirements: 8.6_

- [x] 17. Final integration and testing
  - [x] 17.1 Wire pending count to sidebar badge
    - Modify sidebar partial to fetch pending mosque count
    - Use view composer or share data globally: `View::composer('owner.partials.sidebar', ...)`
    - Pass `$pendingCount` variable to sidebar view
    - _Requirements: 7.4_

  - [x] 17.2 Ensure responsive layout compliance
    - Test all owner panel pages on desktop viewport (1920x1080)
    - Test all owner panel pages on tablet viewport (768x1024)
    - Verify Bootstrap responsive utilities work correctly
    - Ensure tables are scrollable on smaller viewports
    - _Requirements: 7.6, 8.4_

  - [x] 17.3 Run full integration test suite
    - Test complete user flow: login as super-admin → view dashboard → filter mosques → view details
    - Test pending mosque workflow: view pending list → view detail → check days waiting calculation
    - Test search and filtering across all views
    - Verify caching works correctly on dashboard
    - Verify all links and navigation work correctly
    - _Requirements: All requirements_

- [x] 18. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise
  - Verify dashboard loads under 2 seconds with caching
  - Verify mosque lists support pagination for large datasets
  - Verify search and filtering respond in under 1 second

## Notes

- Tasks marked with `*` are optional test-related tasks and can be skipped for faster MVP delivery
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation throughout implementation
- Property-based testing is not applicable to this feature per the design document
- The implementation uses PHP/Laravel with Blade templates for server-side rendering
- Bootstrap 5 and Alpine.js provide responsive and reactive UI components
- All owner routes are protected by authentication and super-admin role verification
- Database indexing is critical for performance with large mosque datasets
- Caching reduces dashboard load time and database queries

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "2.1"] },
    { "id": 1, "tasks": ["1.2", "2.2", "2.3", "8.1", "8.2"] },
    { "id": 2, "tasks": ["2.4", "2.5", "8.3"] },
    { "id": 3, "tasks": ["2.6", "3.1"] },
    { "id": 4, "tasks": ["3.2", "3.3", "4.1"] },
    { "id": 5, "tasks": ["3.4", "4.2", "4.3", "4.4"] },
    { "id": 6, "tasks": ["4.5", "6.1"] },
    { "id": 7, "tasks": ["6.2", "6.3", "7.1"] },
    { "id": 8, "tasks": ["7.2", "7.3", "7.4", "7.5"] },
    { "id": 9, "tasks": ["7.6", "10.1"] },
    { "id": 10, "tasks": ["10.2", "10.3", "11.1"] },
    { "id": 11, "tasks": ["11.2", "11.3"] },
    { "id": 12, "tasks": ["12.1"] },
    { "id": 13, "tasks": ["12.2", "12.3"] },
    { "id": 14, "tasks": ["12.4", "13.1"] },
    { "id": 15, "tasks": ["13.2", "13.3", "14.1"] },
    { "id": 16, "tasks": ["14.2", "14.3", "14.4"] },
    { "id": 17, "tasks": ["14.5", "14.6", "14.7"] },
    { "id": 18, "tasks": ["15.1"] },
    { "id": 19, "tasks": ["16.1", "16.2"] },
    { "id": 20, "tasks": ["17.1", "17.2"] },
    { "id": 21, "tasks": ["17.3"] }
  ]
}
```
