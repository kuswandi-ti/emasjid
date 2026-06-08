# Design Document

## Introduction

This document provides the architectural design for the Owner Mosque Approval/Rejection feature. The feature enables platform owners to approve or reject pending mosque registrations through a Laravel backend with event-driven notifications. The design follows Laravel best practices including Form Request validation, Service layer pattern, Event-Listener architecture, and Blade templating for views.

## Architecture Overview

### Component Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                         Frontend Layer                          │
│  ┌────────────────┐  ┌────────────────┐  ┌────────────────┐   │
│  │ Mosque Detail  │  │  SweetAlert2   │  │ Flash Messages │   │
│  │    View        │  │    Modals      │  │                │   │
│  └────────────────┘  └────────────────┘  └────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│                        Controller Layer                         │
│  ┌────────────────────────────────────────────────────────┐    │
│  │          Owner\MosqueController                        │    │
│  │  - approve(ApproveMosqueRequest): RedirectResponse    │    │
│  │  - reject(RejectMosqueRequest): RedirectResponse      │    │
│  └────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│                     Form Request Layer                          │
│  ┌──────────────────────┐  ┌──────────────────────┐           │
│  │ ApproveMosqueRequest │  │ RejectMosqueRequest  │           │
│  │ - mosque_id          │  │ - mosque_id          │           │
│  │                      │  │ - rejection_reason   │           │
│  └──────────────────────┘  └──────────────────────┘           │
└─────────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│                        Service Layer                            │
│  ┌────────────────────────────────────────────────────────┐    │
│  │              MosqueService                             │    │
│  │  - approve(int $mosqueId, int $userId): bool          │    │
│  │  - reject(int $mosqueId, string $reason, int $userId) │    │
│  │  - generateUniqueInvitationCode(): string             │    │
│  └────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│                      Repository Layer                           │
│  ┌────────────────────────────────────────────────────────┐    │
│  │          MosqueRepositoryInterface                     │    │
│  │  - findById(int $id): ?Mosque                         │    │
│  │  - update(int $id, array $data): bool                 │    │
│  │  - existsByInvitationCode(string $code): bool         │    │
│  └────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│                        Event Layer                              │
│  ┌──────────────────────┐  ┌──────────────────────┐           │
│  │   MosqueApproved     │  │   MosqueRejected     │           │
│  │   Event              │  │   Event              │           │
│  └──────────────────────┘  └──────────────────────┘           │
└─────────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│                       Listener Layer                            │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │   SendMosqueApprovedNotification                        │   │
│  │   - Sends approval email to mosque admin                │   │
│  └─────────────────────────────────────────────────────────┘   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │   SendMosqueRejectedNotification                        │   │
│  │   - Sends rejection email to mosque admin               │   │
│  └─────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│                      Database Layer                             │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │                    mosques table                        │   │
│  │  - status: enum (pending, active, suspended, rejected)  │   │
│  │  - invitation_code: string(6) unique nullable           │   │
│  │  - approved_at: timestamp nullable                      │   │
│  │  - approved_by: unsignedBigInteger nullable             │   │
│  │  - rejected_at: timestamp nullable                      │   │
│  │  - rejected_by: unsignedBigInteger nullable             │   │
│  │  - rejection_reason: text nullable                      │   │
│  └─────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

### Sequence Diagrams

#### Approval Flow

```
Owner          Controller       FormRequest      Service        Repository      Event         Listener
  │                │                │               │               │              │             │
  │ Click Approve  │                │               │               │              │             │
  ├───────────────►│                │               │               │              │             │
  │                │ Validate       │               │               │              │             │
  │                ├───────────────►│               │               │              │             │
  │                │ ◄──────────────┤               │               │              │             │
  │                │                │               │               │              │             │
  │                │ approve()      │               │               │              │             │
  │                ├────────────────┼──────────────►│               │              │             │
  │                │                │               │ findById()    │              │             │
  │                │                │               ├──────────────►│              │             │
  │                │                │               │ ◄─────────────┤              │             │
  │                │                │               │               │              │             │
  │                │                │               │ generateCode()│              │             │
  │                │                │               ├───────────────┤              │             │
  │                │                │               │               │              │             │
  │                │                │               │ update()      │              │             │
  │                │                │               ├──────────────►│              │             │
  │                │                │               │ ◄─────────────┤              │             │
  │                │                │               │               │              │             │
  │                │                │               │ dispatch event│              │             │
  │                │                │               ├───────────────┼─────────────►│             │
  │                │                │               │               │              │ handle()    │
  │                │                │               │               │              ├────────────►│
  │                │                │               │               │              │             │
  │                │ ◄──────────────┼───────────────┤               │              │  Send Email │
  │                │                │               │               │              │             │
  │◄───────────────┤                │               │               │              │             │
  │  Success Flash │                │               │               │              │             │
```

#### Rejection Flow

```
Owner          Controller       FormRequest      Service        Repository      Event         Listener
  │                │                │               │               │              │             │
  │ Click Reject   │                │               │               │              │             │
  ├───────────────►│                │               │               │              │             │
  │ Enter Reason   │                │               │               │              │             │
  ├───────────────►│                │               │               │              │             │
  │                │ Validate       │               │               │              │             │
  │                ├───────────────►│               │               │              │             │
  │                │ ◄──────────────┤               │               │              │             │
  │                │                │               │               │              │             │
  │                │ reject()       │               │               │              │             │
  │                ├────────────────┼──────────────►│               │              │             │
  │                │                │               │ findById()    │              │             │
  │                │                │               ├──────────────►│              │             │
  │                │                │               │ ◄─────────────┤              │             │
  │                │                │               │               │              │             │
  │                │                │               │ update()      │              │             │
  │                │                │               ├──────────────►│              │             │
  │                │                │               │ ◄─────────────┤              │             │
  │                │                │               │               │              │             │
  │                │                │               │ dispatch event│              │             │
  │                │                │               ├───────────────┼─────────────►│             │
  │                │                │               │               │              │ handle()    │
  │                │                │               │               │              ├────────────►│
  │                │                │               │               │              │             │
  │                │ ◄──────────────┼───────────────┤               │              │  Send Email │
  │                │                │               │               │               │             │
  │◄───────────────┤                │               │               │              │             │
  │  Success Flash │                │               │               │              │             │
```

## Data Structures

### ApproveMosqueRequest

```php
class ApproveMosqueRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'mosque_id' => [
                'required',
                'integer',
                'exists:mosques,id',
                Rule::exists('mosques', 'id')->where('status', MosqueStatus::PENDING->value),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'mosque_id.required' => 'Mosque ID is required',
            'mosque_id.exists' => 'Mosque not found or not in pending status',
        ];
    }
}
```

### RejectMosqueRequest

```php
class RejectMosqueRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'mosque_id' => [
                'required',
                'integer',
                'exists:mosques,id',
                Rule::exists('mosques', 'id')->where('status', MosqueStatus::PENDING->value),
            ],
            'rejection_reason' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'mosque_id.required' => 'Mosque ID is required',
            'mosque_id.exists' => 'Mosque not found or not in pending status',
            'rejection_reason.required' => 'Rejection reason is required',
            'rejection_reason.min' => 'Rejection reason must be at least 10 characters',
            'rejection_reason.max' => 'Rejection reason cannot exceed 1000 characters',
        ];
    }
}
```

### MosqueApproved Event

```php
class MosqueApproved
{
    public function __construct(
        public Mosque $mosque,
        public int $approvedByUserId,
        public Carbon $approvedAt
    ) {}
}
```

### MosqueRejected Event

```php
class MosqueRejected
{
    public function __construct(
        public Mosque $mosque,
        public int $rejectedByUserId,
        public string $rejectionReason,
        public Carbon $rejectedAt
    ) {}
}
```

## Key Algorithms

### Invitation Code Generation

```php
private function generateUniqueInvitationCode(): string
{
    do {
        // Generate 6-character alphanumeric code (uppercase)
        $code = strtoupper(Str::random(6));
        
        // Ensure uniqueness by checking repository
    } while ($this->mosqueRepository->existsByInvitationCode($code));
    
    return $code;
}
```

### Mosque Approval Logic

```php
public function approve(int $mosqueId, int $userId): bool
{
    DB::beginTransaction();
    
    try {
        // Find mosque
        $mosque = $this->mosqueRepository->findById($mosqueId);
        
        if (!$mosque || $mosque->status !== MosqueStatus::PENDING) {
            throw new \Exception('Invalid mosque or status');
        }
        
        // Generate unique invitation code
        $invitationCode = $this->generateUniqueInvitationCode();
        
        // Update mosque
        $updated = $this->mosqueRepository->update($mosqueId, [
            'status' => MosqueStatus::ACTIVE,
            'invitation_code' => $invitationCode,
            'approved_at' => now(),
            'approved_by' => $userId,
        ]);
        
        if (!$updated) {
            throw new \Exception('Failed to update mosque');
        }
        
        // Refresh mosque model
        $mosque->refresh();
        
        // Dispatch event
        event(new MosqueApproved($mosque, $userId, now()));
        
        DB::commit();
        return true;
        
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}
```

### Mosque Rejection Logic

```php
public function reject(int $mosqueId, string $reason, int $userId): bool
{
    DB::beginTransaction();
    
    try {
        // Find mosque
        $mosque = $this->mosqueRepository->findById($mosqueId);
        
        if (!$mosque || $mosque->status !== MosqueStatus::PENDING) {
            throw new \Exception('Invalid mosque or status');
        }
        
        // Update mosque
        $updated = $this->mosqueRepository->update($mosqueId, [
            'status' => MosqueStatus::REJECTED,
            'rejection_reason' => $reason,
            'rejected_at' => now(),
            'rejected_by' => $userId,
        ]);
        
        if (!$updated) {
            throw new \Exception('Failed to update mosque');
        }
        
        // Refresh mosque model
        $mosque->refresh();
        
        // Dispatch event
        event(new MosqueRejected($mosque, $userId, $reason, now()));
        
        DB::commit();
        return true;
        
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}
```

## User Interface Design

### Mosque Detail Page with Action Buttons

```blade
@extends('owner.layouts.app')

@section('content')
<div class="container-fluid py-4">
    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>{{ $mosque->name }}</h2>
            <p class="text-muted">Registered on {{ $mosque->created_at->format('d M Y H:i') }}</p>
        </div>
        <a href="{{ route('owner.mosques.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
    </div>

    {{-- Action Buttons (Only for Pending Status) --}}
    @if($mosque->status === \App\Enums\MosqueStatus::PENDING)
        <div class="card mb-4 border-warning">
            <div class="card-body">
                <h5 class="card-title text-warning">
                    <i class="bi bi-exclamation-triangle"></i> Pending Verification
                </h5>
                <p class="card-text">This mosque is awaiting your approval. Please review the details below.</p>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-success" onclick="approveMosque({{ $mosque->id }})">
                        <i class="bi bi-check-circle"></i> Approve
                    </button>
                    <button type="button" class="btn btn-danger" onclick="rejectMosque({{ $mosque->id }})">
                        <i class="bi bi-x-circle"></i> Reject
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Mosque Details Sections --}}
    {{-- ... (existing detail sections) ... --}}
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function approveMosque(mosqueId) {
    Swal.fire({
        title: 'Approve This Mosque?',
        text: "The mosque will be activated and the admin will receive a notification email.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Approve',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Submit form
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/owner/mosques/${mosqueId}/approve`;
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            
            form.appendChild(csrfToken);
            document.body.appendChild(form);
            form.submit();
        }
    });
}

function rejectMosque(mosqueId) {
    Swal.fire({
        title: 'Reject This Mosque?',
        html: '<textarea id="rejection-reason" class="swal2-textarea" placeholder="Enter rejection reason (min 10 characters)" style="width: 100%; height: 100px;"></textarea>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Reject',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
            const reason = document.getElementById('rejection-reason').value;
            if (!reason || reason.length < 10) {
                Swal.showValidationMessage('Rejection reason must be at least 10 characters');
                return false;
            }
            return reason;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Submit form
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/owner/mosques/${mosqueId}/reject`;
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            
            const reasonInput = document.createElement('input');
            reasonInput.type = 'hidden';
            reasonInput.name = 'rejection_reason';
            reasonInput.value = result.value;
            
            form.appendChild(csrfToken);
            form.appendChild(reasonInput);
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>
@endpush
@endsection
```

## Error Handling

### Validation Errors

- Form Request validation failures return 422 with error messages
- Frontend displays validation errors below input fields
- SweetAlert modals show validation messages inline

### Service Layer Errors

- Database transaction failures are caught and rolled back
- Service methods throw exceptions with descriptive messages
- Controller catches exceptions and returns error flash messages

### Email Sending Failures

- Email sending happens in queued jobs to prevent blocking
- Failed jobs are retried automatically (Laravel queue configuration)
- Email failures are logged to Laravel log files for debugging

## Security Considerations

1. **Authentication**: All routes protected by `auth:web` middleware
2. **Authorization**: `owner` middleware ensures only super-admins can approve/reject
3. **CSRF Protection**: All POST forms include CSRF token
4. **Input Validation**: Form Requests validate all user inputs
5. **Database Transactions**: Prevent race conditions and ensure data consistency
6. **Unique Code Generation**: Prevents duplicate invitation codes
7. **Audit Trail**: Approved_by, rejected_by, and timestamps provide audit capability

## Performance Considerations

1. **Queue System**: Email notifications are queued to prevent blocking HTTP responses
2. **Database Indexing**: Status column is indexed for fast filtering
3. **Eager Loading**: Mosque relationships are eager-loaded in detail view to prevent N+1 queries
4. **Cache Warming**: Dashboard statistics cache is invalidated when mosque status changes
5. **Transaction Isolation**: Database transactions prevent concurrent modification conflicts

## Testing Strategy

### Unit Tests

- Test Form Request validation rules
- Test Service layer methods (approve, reject, generateUniqueInvitationCode)
- Test Event classes contain correct data
- Test Listener classes send emails correctly (using mocked Mail facade)

### Feature Tests

- Test approval flow: POST approve endpoint → mosque status changes → event dispatched
- Test rejection flow: POST reject endpoint → mosque status changes → event dispatched
- Test authorization: non-super-admin cannot approve/reject
- Test validation: invalid inputs return 422 errors
- Test concurrent requests: only one approval/rejection succeeds

### Integration Tests

- Test end-to-end approval: submit approval → database updated → email sent
- Test end-to-end rejection: submit rejection → database updated → email sent

## Deployment Considerations

1. **Migration**: Run migration to add approval/rejection columns to mosques table
2. **Queue Worker**: Ensure queue worker is running to process email jobs
3. **Email Configuration**: Verify SMTP/email configuration in production environment
4. **Event Listener Registration**: Ensure event listeners are registered in EventServiceProvider
5. **Frontend Assets**: Compile and deploy frontend assets (SweetAlert2, CSS, JS)

