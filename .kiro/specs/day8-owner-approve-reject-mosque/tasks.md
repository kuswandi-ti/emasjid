# Implementation Plan: Owner Approve/Reject Mosque & Detail Enhancement

## Overview

This implementation plan covers the development of the mosque approval and rejection functionality for the eMasjid platform owner panel. The feature allows super-admins to approve or reject pending mosque registrations, automatically send notification emails to mosque admins, and display comprehensive mosque detail information. The implementation follows Laravel's MVC architecture with event-driven notifications, Form Request validation, and service layer pattern.

## Tasks

- [ ] 1. Add database columns for approval/rejection tracking
  - [ ] 1.1 Create migration for approval/rejection columns
    - Create migration file: `php artisan make:migration add_approval_rejection_columns_to_mosques_table`
    - Add `approved_at` column: `$table->timestamp('approved_at')->nullable()`
    - Add `approved_by` column: `$table->unsignedBigInteger('approved_by')->nullable()`
    - Add foreign key: `$table->foreign('approved_by')->references('id')->on('users')->onDelete('set null')`
    - Add `rejected_at` column: `$table->timestamp('rejected_at')->nullable()`
    - Add `rejected_by` column: `$table->unsignedBigInteger('rejected_by')->nullable()`
    - Add foreign key: `$table->foreign('rejected_by')->references('id')->on('users')->onDelete('set null')`
    - Add `rejection_reason` column: `$table->text('rejection_reason')->nullable()`
    - Add `invitation_code` column: `$table->string('invitation_code', 6)->nullable()->unique()`
    - Run migration: `php artisan migrate`
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 9.6_

  - [ ] 1.2 Update Mosque model with new attributes
    - Add new columns to `$fillable` array: 'approved_at', 'approved_by', 'rejected_at', 'rejected_by', 'rejection_reason', 'invitation_code'
    - Add to `$casts` array: 'approved_at' => 'datetime', 'rejected_at' => 'datetime'
    - Add `approvedBy()` relationship: `belongsTo(User::class, 'approved_by')`
    - Add `rejectedBy()` relationship: `belongsTo(User::class, 'rejected_by')`
    - Update `app/Models/Mosque.php`
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 9.6_

- [ ] 2. Create Form Request classes for validation
  - [ ] 2.1 Create ApproveMosqueRequest class
    - Create file: `app/Http/Requests/Owner/ApproveMosqueRequest.php`
    - Extend `FormRequest` class
    - Implement `authorize()` method returning true (middleware handles auth)
    - Implement `rules()` method validating mosque_id exists in mosques table with pending status
    - Use `Rule::exists('mosques', 'id')->where('status', MosqueStatus::PENDING->value)`
    - Implement `messages()` method with custom error messages
    - _Requirements: 3.1, 3.2, 3.3_

  - [ ] 2.2 Create RejectMosqueRequest class
    - Create file: `app/Http/Requests/Owner/RejectMosqueRequest.php`
    - Extend `FormRequest` class
    - Implement `authorize()` method returning true
    - Implement `rules()` method validating:
      - mosque_id: required, integer, exists in mosques with pending status
      - rejection_reason: required, string, min:10, max:1000
    - Implement `messages()` method with custom error messages
    - _Requirements: 3.4, 3.5, 3.6, 3.7_

  - [ ] 2.3 Write unit tests for Form Requests
    - Test ApproveMosqueRequest validates mosque_id exists
    - Test ApproveMosqueRequest validates mosque has pending status
    - Test RejectMosqueRequest validates rejection_reason minimum length
    - Test RejectMosqueRequest validates mosque has pending status
    - _Requirements: 3.3, 3.6, 3.7, 3.8_

- [ ] 3. Create Event classes for mosque approval/rejection
  - [ ] 3.1 Create MosqueApproved event class
    - Create file: `app/Events/MosqueApproved.php`
    - Implement constructor with parameters: Mosque $mosque, int $approvedByUserId, Carbon $approvedAt
    - Use property promotion for constructor parameters
    - Implement `SerializesModels` trait
    - _Requirements: 4.1_

  - [ ] 3.2 Create MosqueRejected event class
    - Create file: `app/Events/MosqueRejected.php`
    - Implement constructor with parameters: Mosque $mosque, int $rejectedByUserId, string $rejectionReason, Carbon $rejectedAt
    - Use property promotion for constructor parameters
    - Implement `SerializesModels` trait
    - _Requirements: 4.2_

  - [ ] 3.3 Write unit tests for Event classes
    - Test MosqueApproved event contains correct properties
    - Test MosqueRejected event contains correct properties
    - _Requirements: 4.1, 4.2_

- [ ] 4. Create Listener classes for email notifications
  - [ ] 4.1 Create SendMosqueApprovedNotification listener
    - Create file: `app/Listeners/SendMosqueApprovedNotification.php`
    - Implement `handle(MosqueApproved $event)` method
    - Implement `ShouldQueue` interface for asynchronous processing
    - Load mosque admin user via `$event->mosque->admin` relationship
    - Send email using `Mail::to($admin->email)->send(new MosqueApprovedMail(...))`
    - Email should contain: mosque name, approval date, invitation code, admin login link
    - Log email failures using `Log::error()` for debugging
    - _Requirements: 4.3, 4.5, 4.6, 4.9_

  - [ ] 4.2 Create SendMosqueRejectedNotification listener
    - Create file: `app/Listeners/SendMosqueRejectedNotification.php`
    - Implement `handle(MosqueRejected $event)` method
    - Implement `ShouldQueue` interface for asynchronous processing
    - Load mosque admin user via `$event->mosque->admin` relationship
    - Send email using `Mail::to($admin->email)->send(new MosqueRejectedMail(...))`
    - Email should contain: mosque name, rejection date, rejection reason, support contact link
    - Log email failures using `Log::error()` for debugging
    - _Requirements: 4.4, 4.7, 4.8, 4.9_

  - [ ] 4.3 Create MosqueApprovedMail mailable class
    - Create file: `app/Mail/MosqueApprovedMail.php`
    - Extend `Mailable` class
    - Accept mosque, approvalDate, and invitationCode in constructor
    - Implement `build()` method with subject "Your Mosque Has Been Approved"
    - Use Blade view: `emails.mosque-approved`
    - _Requirements: 4.5, 4.6_

  - [ ] 4.4 Create MosqueRejectedMail mailable class
    - Create file: `app/Mail/MosqueRejectedMail.php`
    - Extend `Mailable` class
    - Accept mosque, rejectionDate, and rejectionReason in constructor
    - Implement `build()` method with subject "Mosque Registration Status"
    - Use Blade view: `emails.mosque-rejected`
    - _Requirements: 4.7, 4.8_

  - [ ] 4.5 Register event listeners in EventServiceProvider
    - Open `app/Providers/EventServiceProvider.php`
    - Add to `$listen` array: `MosqueApproved::class => [SendMosqueApprovedNotification::class]`
    - Add to `$listen` array: `MosqueRejected::class => [SendMosqueRejectedNotification::class]`
    - _Requirements: 4.3, 4.4_

  - [ ] 4.6 Create email Blade templates
    - Create file: `resources/views/emails/mosque-approved.blade.php`
    - Design approval email with mosque name, approval date, invitation code, and login link
    - Create file: `resources/views/emails/mosque-rejected.blade.php`
    - Design rejection email with mosque name, rejection date, rejection reason, and support link
    - _Requirements: 4.6, 4.8_

  - [ ] 4.7 Write unit tests for Listeners
    - Mock Mail facade to verify emails are sent
    - Test SendMosqueApprovedNotification sends email to correct recipient
    - Test SendMosqueRejectedNotification sends email to correct recipient
    - Test listeners implement ShouldQueue for asynchronous processing
    - _Requirements: 4.5, 4.7, 4.9, 4.10_

- [ ] 5. Extend MosqueService with approval/rejection methods
  - [ ] 5.1 Implement generateUniqueInvitationCode method
    - Add private method to MosqueService: `generateUniqueInvitationCode(): string`
    - Generate 6-character uppercase alphanumeric code using `Str::random(6)`
    - Use do-while loop to ensure uniqueness by checking repository
    - Call `$this->mosqueRepository->existsByInvitationCode($code)` in loop condition
    - Return unique code
    - _Requirements: 1.5, 1.6_

  - [ ] 5.2 Implement approve method in MosqueService
    - Add method signature: `approve(int $mosqueId, int $userId): bool`
    - Start database transaction using `DB::beginTransaction()`
    - Find mosque by ID via repository
    - Validate mosque exists and has pending status, throw exception if invalid
    - Generate unique invitation code using `generateUniqueInvitationCode()`
    - Update mosque via repository with: status=ACTIVE, invitation_code, approved_at=now(), approved_by=$userId
    - Refresh mosque model to load updated data
    - Dispatch `MosqueApproved` event with mosque, userId, and timestamp
    - Commit transaction using `DB::commit()`
    - Return true on success
    - Catch exceptions, rollback transaction, and re-throw
    - _Requirements: 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 1.9_

  - [ ] 5.3 Implement reject method in MosqueService
    - Add method signature: `reject(int $mosqueId, string $reason, int $userId): bool`
    - Start database transaction using `DB::beginTransaction()`
    - Find mosque by ID via repository
    - Validate mosque exists and has pending status, throw exception if invalid
    - Update mosque via repository with: status=REJECTED, rejection_reason=$reason, rejected_at=now(), rejected_by=$userId
    - Refresh mosque model to load updated data
    - Dispatch `MosqueRejected` event with mosque, userId, reason, and timestamp
    - Commit transaction using `DB::commit()`
    - Return true on success
    - Catch exceptions, rollback transaction, and re-throw
    - _Requirements: 2.3, 2.4, 2.5, 2.6, 2.7, 2.8, 2.9_

  - [ ] 5.4 Write unit tests for MosqueService methods
    - Mock MosqueRepositoryInterface in tests
    - Test generateUniqueInvitationCode generates unique codes
    - Test approve method updates mosque status to ACTIVE
    - Test approve method generates and saves invitation code
    - Test approve method records approved_by and approved_at
    - Test approve method dispatches MosqueApproved event
    - Test approve method throws exception for non-pending mosque
    - Test reject method updates mosque status to REJECTED
    - Test reject method saves rejection reason
    - Test reject method records rejected_by and rejected_at
    - Test reject method dispatches MosqueRejected event
    - Test reject method throws exception for non-pending mosque
    - Test database rollback on failure
    - _Requirements: 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 1.9, 2.4, 2.5, 2.6, 2.7, 2.8, 2.9_

- [ ] 6. Extend MosqueRepository with invitation code check
  - [ ] 6.1 Add existsByInvitationCode method to MosqueRepositoryInterface
    - Add method signature: `existsByInvitationCode(string $code): bool`
    - Update `app/Contracts/Repositories/MosqueRepositoryInterface.php`
    - _Requirements: 1.6_

  - [ ] 6.2 Implement existsByInvitationCode in MosqueRepository
    - Query mosques table where invitation_code equals $code
    - Return boolean indicating existence using `exists()` method
    - Update `app/Repositories/MosqueRepository.php`
    - _Requirements: 1.6_

  - [ ] 6.3 Write unit tests for repository method
    - Test existsByInvitationCode returns true when code exists
    - Test existsByInvitationCode returns false when code does not exist
    - _Requirements: 1.6_

- [ ] 7. Implement controller methods for approve and reject
  - [ ] 7.1 Add approve method to Owner\MosqueController
    - Add method signature: `approve(ApproveMosqueRequest $request, int $id): RedirectResponse`
    - Extract validated mosque_id from request (or use route parameter $id)
    - Get authenticated user ID via `auth()->id()`
    - Try to call `$this->mosqueService->approve($id, $userId)`
    - On success, redirect to `route('owner.mosques.show', $id)` with success flash message
    - Catch exceptions and redirect back with error flash message
    - _Requirements: 1.2, 1.10, 1.11, 7.3, 7.4, 7.5_

  - [ ] 7.2 Add reject method to Owner\MosqueController
    - Add method signature: `reject(RejectMosqueRequest $request, int $id): RedirectResponse`
    - Extract validated rejection_reason from request
    - Get authenticated user ID via `auth()->id()`
    - Try to call `$this->mosqueService->reject($id, $rejectionReason, $userId)`
    - On success, redirect to `route('owner.mosques.pending')` with success flash message
    - Catch exceptions and redirect back with error flash message
    - _Requirements: 2.3, 2.10, 2.11, 7.8, 7.9, 7.10_

  - [ ] 7.3 Write feature tests for controller methods
    - Test authenticated super-admin can approve pending mosque
    - Test approval redirects to mosque detail with success message
    - Test authenticated super-admin can reject pending mosque
    - Test rejection redirects to pending list with success message
    - Test non-super-admin cannot approve/reject (receives 403)
    - Test unauthenticated user redirects to login
    - Test approving non-pending mosque returns error
    - Test rejecting non-pending mosque returns error
    - Test validation errors return 422 response
    - _Requirements: 1.10, 1.11, 2.10, 2.11, 7.4, 7.5, 7.9, 7.10, 10.1, 10.2, 10.3_

- [ ] 8. Register routes for approve and reject actions
  - [ ] 8.1 Add approve route to web.php
    - Open `routes/web.php`
    - In 'owner' route group, add POST route: `/mosques/{id}/approve` to `MosqueController@approve`
    - Name route: `owner.mosques.approve`
    - Ensure route uses 'auth:web' and 'owner' middleware
    - _Requirements: 8.1, 8.3, 8.4_

  - [ ] 8.2 Add reject route to web.php
    - In 'owner' route group, add POST route: `/mosques/{id}/reject` to `MosqueController@reject`
    - Name route: `owner.mosques.reject`
    - Ensure route uses 'auth:web' and 'owner' middleware
    - _Requirements: 8.2, 8.3, 8.4_

- [ ] 9. Checkpoint - Verify backend implementation
  - Ensure all services, repositories, controllers, events, and listeners compile without errors
  - Ensure all routes are registered correctly
  - Ensure all tests pass, ask the user if questions arise

- [ ] 10. Enhance mosque detail view with action buttons
  - [ ] 10.1 Add flash message display to mosque detail view
    - Open `resources/views/owner/mosques/show.blade.php`
    - At the top of content section, add Blade conditionals for success and error flash messages
    - Use Bootstrap alert components with dismissible buttons
    - Success alerts should have `alert-success` class
    - Error alerts should have `alert-danger` class
    - _Requirements: 6.8, 6.9, 6.10_

  - [ ] 10.2 Add pending status warning card
    - After page header, add conditional Blade directive: `@if($mosque->status === \App\Enums\MosqueStatus::PENDING)`
    - Create Bootstrap card with border-warning and warning icon
    - Add card title "Pending Verification" with warning badge
    - Add card text explaining mosque is awaiting approval
    - Add action buttons div with d-flex and gap-2
    - _Requirements: 5.9, 5.10_

  - [ ] 10.3 Add approve button with SweetAlert confirmation
    - Create button element with btn-success class and onclick handler
    - Button text: "Approve" with check-circle icon
    - Onclick calls JavaScript function: `approveMosque({{ $mosque->id }})`
    - _Requirements: 1.1, 5.9, 6.2, 6.3_

  - [ ] 10.4 Add reject button with SweetAlert modal
    - Create button element with btn-danger class and onclick handler
    - Button text: "Reject" with x-circle icon
    - Onclick calls JavaScript function: `rejectMosque({{ $mosque->id }})`
    - _Requirements: 2.1, 5.10, 6.4, 6.5_

  - [ ] 10.5 Hide action buttons for non-pending mosques
    - Ensure action buttons card is only displayed when status is PENDING
    - Use `@endif` directive to close conditional
    - _Requirements: 5.9, 5.10_

- [ ] 11. Implement JavaScript for SweetAlert modals
  - [ ] 11.1 Add SweetAlert2 CDN script
    - In `@push('scripts')` section, add SweetAlert2 CDN: `<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>`
    - _Requirements: 6.1_

  - [ ] 11.2 Implement approveMosque JavaScript function
    - Create function: `function approveMosque(mosqueId)`
    - Use `Swal.fire()` with title "Approve This Mosque?"
    - Display confirmation text explaining the action
    - Set icon to 'question'
    - Show cancel button
    - Set confirmButtonColor to success green (#28a745)
    - Set cancelButtonColor to gray (#6c757d)
    - Set confirmButtonText to "Yes, Approve"
    - On confirm result, programmatically create and submit POST form to `/owner/mosques/${mosqueId}/approve`
    - Include CSRF token in hidden input field
    - _Requirements: 1.1, 6.2, 6.3, 6.7_

  - [ ] 11.3 Implement rejectMosque JavaScript function
    - Create function: `function rejectMosque(mosqueId)`
    - Use `Swal.fire()` with title "Reject This Mosque?"
    - Display HTML textarea with id 'rejection-reason', placeholder, and styling
    - Set icon to 'warning'
    - Show cancel button
    - Set confirmButtonColor to danger red (#dc3545)
    - Set cancelButtonColor to gray (#6c757d)
    - Set confirmButtonText to "Yes, Reject"
    - Implement `preConfirm` callback to validate textarea has minimum 10 characters
    - Show validation message using `Swal.showValidationMessage()` if invalid
    - On confirm result, programmatically create and submit POST form to `/owner/mosques/${mosqueId}/reject`
    - Include CSRF token and rejection_reason in hidden input fields
    - _Requirements: 2.1, 2.2, 6.4, 6.5, 6.6, 6.7_

- [ ] 12. Display comprehensive mosque detail sections
  - [ ] 12.1 Display basic information section
    - Ensure mosque detail page displays: name, description, address, city, province, postal_code
    - Display mosque photo if available using `@if($mosque->photo)` conditional
    - Display status badge with appropriate color based on status enum
    - _Requirements: 5.1, 5.2, 5.11_

  - [ ] 12.2 Display contact details section
    - Create card section titled "Contact Details"
    - Display admin name: `$mosque->admin->name`
    - Display admin email: `$mosque->admin->email`
    - Display admin phone: `$mosque->admin->phone`
    - _Requirements: 5.1, 5.5_

  - [ ] 12.3 Display banking information section
    - Create card section titled "Banking Information"
    - Display bank name: `$mosque->bank_name`
    - Display account number: `$mosque->bank_account_number`
    - Display account holder: `$mosque->bank_account_holder`
    - _Requirements: 5.1, 5.3_

  - [ ] 12.4 Display location map section
    - Create card section titled "Location"
    - Add conditional: `@if($mosque->latitude && $mosque->longitude)`
    - Embed Google Maps iframe with mosque coordinates
    - Use Google Maps embed API: `https://www.google.com/maps?q={lat},{lng}&output=embed`
    - Display "No location data available" message if coordinates are null
    - _Requirements: 5.4_

  - [ ] 12.5 Display statistics section
    - Create card section titled "Statistics"
    - Display congregation count: `$mosque->members_count` (from withCount)
    - Display total donations: `$mosque->total_donations` formatted as currency using `format_rupiah()` helper
    - _Requirements: 5.6, 5.7_

  - [ ] 12.6 Display registration date and status-specific information
    - Display registration date formatted: `$mosque->created_at->format('d M Y H:i')`
    - Add conditional: `@if($mosque->status === \App\Enums\MosqueStatus::REJECTED)`
    - Display rejection reason in alert-danger card
    - Add conditional: `@if($mosque->status === \App\Enums\MosqueStatus::ACTIVE)`
    - Display approval timestamp formatted
    - _Requirements: 5.8, 5.11_

- [ ] 13. Update mosque detail controller to load required relationships
  - [ ] 13.1 Modify MosqueController@show method
    - Ensure `getMosqueDetail()` service method loads 'admin' relationship
    - Ensure `getMosqueDetail()` service method includes `withCount('members')`
    - Ensure `getMosqueDetail()` service method includes `withSum('donations as total_donations', 'mosque_receives')`
    - Update `app/Services/MosqueService.php`
    - _Requirements: 5.2, 5.6, 5.7_

- [ ] 14. Implement audit logging for approval/rejection actions
  - [ ] 14.1 Add logging to approve method
    - In MosqueService@approve, after successful approval, log action
    - Use `Log::info()` with message: "Mosque approved", context: ['mosque_id', 'approved_by', 'approved_at']
    - _Requirements: 10.3_

  - [ ] 14.2 Add logging to reject method
    - In MosqueService@reject, after successful rejection, log action
    - Use `Log::info()` with message: "Mosque rejected", context: ['mosque_id', 'rejected_by', 'rejected_at', 'reason']
    - _Requirements: 10.3_

- [ ] 15. Implement cache invalidation for dashboard statistics
  - [ ] 15.1 Clear dashboard cache on approval
    - In MosqueService@approve, after successful approval, call `Cache::forget('owner.dashboard.statistics')`
    - Alternatively, inject DashboardService and call `clearStatisticsCache()` method
    - _Requirements: Performance consideration_

  - [ ] 15.2 Clear dashboard cache on rejection
    - In MosqueService@reject, after successful rejection, call `Cache::forget('owner.dashboard.statistics')`
    - Alternatively, inject DashboardService and call `clearStatisticsCache()` method
    - _Requirements: Performance consideration_

- [ ] 16. Write integration tests for end-to-end flow
  - [ ] 16.1 Test approval flow end-to-end
    - Create pending mosque in database
    - Authenticate as super-admin user
    - POST to approve endpoint with mosque ID
    - Assert mosque status changed to ACTIVE in database
    - Assert invitation_code is generated and unique
    - Assert approved_at and approved_by are recorded
    - Assert redirect to mosque detail page with success flash
    - Assert MosqueApproved event was dispatched
    - Queue fake: assert email job was queued
    - _Requirements: 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 1.9, 1.10_

  - [ ] 16.2 Test rejection flow end-to-end
    - Create pending mosque in database
    - Authenticate as super-admin user
    - POST to reject endpoint with mosque ID and rejection reason
    - Assert mosque status changed to REJECTED in database
    - Assert rejection_reason, rejected_at, and rejected_by are recorded
    - Assert redirect to pending list with success flash
    - Assert MosqueRejected event was dispatched
    - Queue fake: assert email job was queued
    - _Requirements: 2.3, 2.4, 2.5, 2.6, 2.7, 2.8, 2.9, 2.10_

  - [ ] 16.3 Test authorization enforcement
    - Create pending mosque in database
    - Authenticate as non-super-admin user
    - POST to approve endpoint
    - Assert response is 403 Forbidden
    - POST to reject endpoint
    - Assert response is 403 Forbidden
    - _Requirements: 10.1, 10.2_

  - [ ] 16.4 Test concurrent approval prevention
    - Create pending mosque in database
    - Use database transactions to simulate concurrent approval attempts
    - Assert only one approval succeeds
    - Assert database consistency is maintained
    - _Requirements: 10.4_

- [ ] 17. Final integration and polish
  - [ ] 17.1 Test responsive layout on mosque detail page
    - Verify page displays correctly on desktop (1920x1080)
    - Verify page displays correctly on tablet (768x1024)
    - Ensure action buttons are accessible on mobile viewports
    - _Requirements: UI/UX consideration_

  - [ ] 17.2 Verify email templates render correctly
    - Send test approval email using `php artisan tinker` or test command
    - Verify email contains all required information and formatting
    - Send test rejection email
    - Verify email contains rejection reason and support link
    - _Requirements: 4.6, 4.8_

  - [ ] 17.3 Test SweetAlert modals in browser
    - Click approve button and verify confirmation modal appears
    - Test cancel button returns without action
    - Test confirm button submits form
    - Click reject button and verify modal with textarea appears
    - Test validation message for short rejection reason
    - Test confirm button submits form with rejection reason
    - _Requirements: 6.2, 6.3, 6.4, 6.5, 6.6, 6.7_

  - [ ] 17.4 Run full test suite
    - Execute `php artisan test` to run all unit and feature tests
    - Ensure all tests pass
    - Fix any failing tests
    - _Requirements: All requirements_

- [ ] 18. Final checkpoint - Ensure all tests pass
  - Verify database migration ran successfully
  - Verify all backend services compile without errors
  - Verify all routes are accessible
  - Verify email notifications are queued correctly
  - Verify frontend displays correctly with all interactions working
  - Ask the user if questions arise

## Notes

- Tasks marked with `*` are optional test-related tasks and can be skipped for faster MVP delivery
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation throughout implementation
- The implementation uses PHP/Laravel with Blade templates, SweetAlert2 for modals, and event-driven architecture
- Database transactions ensure data consistency during approval/rejection
- Queued email notifications prevent blocking HTTP responses
- All owner routes are protected by authentication and super-admin role verification
- Audit logging provides traceability for all approval/rejection actions

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1"] },
    { "id": 1, "tasks": ["1.2", "2.1", "2.2", "3.1", "3.2"] },
    { "id": 2, "tasks": ["2.3", "3.3", "4.1", "4.2", "4.3", "4.4"] },
    { "id": 3, "tasks": ["4.5", "4.6", "4.7", "6.1"] },
    { "id": 4, "tasks": ["6.2", "6.3", "5.1"] },
    { "id": 5, "tasks": ["5.2", "5.3"] },
    { "id": 6, "tasks": ["5.4", "7.1", "7.2"] },
    { "id": 7, "tasks": ["7.3", "8.1", "8.2"] },
    { "id": 8, "tasks": ["10.1", "10.2", "10.3", "10.4", "10.5"] },
    { "id": 9, "tasks": ["11.1", "11.2", "11.3"] },
    { "id": 10, "tasks": ["12.1", "12.2", "12.3", "12.4", "12.5", "12.6"] },
    { "id": 11, "tasks": ["13.1", "14.1", "14.2"] },
    { "id": 12, "tasks": ["15.1", "15.2"] },
    { "id": 13, "tasks": ["16.1", "16.2", "16.3", "16.4"] },
    { "id": 14, "tasks": ["17.1", "17.2", "17.3", "17.4"] }
  ]
}
```

