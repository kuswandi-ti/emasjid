# Requirements Document

## Introduction

This document specifies the requirements for the Owner Mosque Approval/Rejection feature in the eMasjid platform. This feature enables platform owners (super-admins) to approve or reject mosque registration requests, send notification emails to mosque admins, and display detailed mosque information for verification purposes. This is a critical workflow that determines whether a newly registered mosque becomes active on the platform.

## Glossary

- **Owner**: A user with the super-admin role who manages the entire eMasjid platform
- **Mosque_Admin**: The primary administrator user of a registered mosque
- **Approve**: The action of accepting a mosque registration and changing its status from 'pending' to 'active'
- **Reject**: The action of declining a mosque registration and changing its status from 'pending' to 'rejected'
- **Invitation_Code**: A unique 6-character alphanumeric code generated when a mosque is approved, used for jamaah to join the mosque
- **Mosque_Service**: The backend service that handles mosque approval/rejection operations
- **Event**: A Laravel event class that is dispatched when a significant action occurs (e.g., mosque approved)
- **Listener**: A Laravel listener class that responds to events (e.g., sending email notifications)
- **SweetAlert**: A JavaScript library for displaying beautiful modal confirmation dialogs
- **Flash_Message**: A temporary session message displayed to the user after an action completes

## Requirements

### Requirement 1: Mosque Approval Functionality

**User Story:** As a platform owner, I want to approve pending mosque registrations, so that verified mosques can become active on the platform.

#### Acceptance Criteria

1. WHEN the owner clicks the approve button, THE Platform SHALL display a SweetAlert confirmation modal with mosque name
2. WHEN the owner confirms approval, THE Mosque_Controller SHALL invoke the Mosque_Service approve method
3. THE Mosque_Service SHALL validate that the mosque exists and has status 'pending'
4. THE Mosque_Service SHALL change the mosque status from 'pending' to 'active'
5. THE Mosque_Service SHALL generate a unique 6-character alphanumeric invitation_code for the mosque
6. THE Mosque_Service SHALL ensure the generated invitation_code is unique across all mosques
7. THE Mosque_Service SHALL record the approval timestamp in the approved_at column
8. THE Mosque_Service SHALL record the approving user's ID in the approved_by column
9. WHEN the approval is successful, THE Mosque_Controller SHALL dispatch the MosqueApproved event
10. WHEN the approval is successful, THE Platform SHALL redirect the owner to the mosque detail page with a success flash message
11. WHEN the mosque is already approved or does not have pending status, THE Platform SHALL return an error message

### Requirement 2: Mosque Rejection Functionality

**User Story:** As a platform owner, I want to reject mosque registrations with a reason, so that admins understand why their mosque was not approved.

#### Acceptance Criteria

1. WHEN the owner clicks the reject button, THE Platform SHALL display a SweetAlert modal with a textarea for rejection reason
2. THE Platform SHALL validate that the rejection reason is not empty and has a minimum length of 10 characters
3. WHEN the owner confirms rejection, THE Mosque_Controller SHALL invoke the Mosque_Service reject method
4. THE Mosque_Service SHALL validate that the mosque exists and has status 'pending'
5. THE Mosque_Service SHALL change the mosque status from 'pending' to 'rejected'
6. THE Mosque_Service SHALL save the rejection reason in the rejection_reason column
7. THE Mosque_Service SHALL record the rejection timestamp in the rejected_at column
8. THE Mosque_Service SHALL record the rejecting user's ID in the rejected_by column
9. WHEN the rejection is successful, THE Mosque_Controller SHALL dispatch the MosqueRejected event
10. WHEN the rejection is successful, THE Platform SHALL redirect the owner to the pending mosque list page with a success flash message
11. WHEN the mosque is already rejected or does not have pending status, THE Platform SHALL return an error message

### Requirement 3: Form Request Validation

**User Story:** As a backend developer, I want to use Form Requests for validation, so that approval and rejection requests are validated consistently.

#### Acceptance Criteria

1. THE Platform SHALL provide an ApproveMosqueRequest class that validates the mosque_id parameter
2. THE ApproveMosqueRequest SHALL validate that mosque_id is required and exists in the mosques table
3. THE ApproveMosqueRequest SHALL validate that the mosque has status 'pending'
4. THE Platform SHALL provide a RejectMosqueRequest class that validates the mosque_id and rejection_reason parameters
5. THE RejectMosqueRequest SHALL validate that mosque_id is required and exists in the mosques table
6. THE RejectMosqueRequest SHALL validate that the mosque has status 'pending'
7. THE RejectMosqueRequest SHALL validate that rejection_reason is required, is a string, and has a minimum length of 10 characters
8. WHEN validation fails, THE Platform SHALL return a 422 Unprocessable Entity response with validation error messages

### Requirement 4: Event and Notification System

**User Story:** As a mosque admin, I want to receive an email notification when my mosque is approved or rejected, so that I know the status of my registration.

#### Acceptance Criteria

1. THE Platform SHALL provide a MosqueApproved event class with properties: mosque, approvedBy, approvedAt
2. THE Platform SHALL provide a MosqueRejected event class with properties: mosque, rejectedBy, rejectedAt, rejectionReason
3. THE Platform SHALL provide a SendMosqueApprovedNotification listener that responds to the MosqueApproved event
4. THE Platform SHALL provide a SendMosqueRejectedNotification listener that responds to the MosqueRejected event
5. WHEN the MosqueApproved event is dispatched, THE SendMosqueApprovedNotification listener SHALL send an email to the mosque admin
6. THE approval email SHALL contain: mosque name, approval date, invitation code, and a link to the admin login page
7. WHEN the MosqueRejected event is dispatched, THE SendMosqueRejectedNotification listener SHALL send an email to the mosque admin
8. THE rejection email SHALL contain: mosque name, rejection date, rejection reason, and a link to contact platform support
9. THE Platform SHALL queue email notifications using Laravel's queue system to prevent blocking the approval/rejection response
10. THE Platform SHALL log email sending failures for debugging purposes

### Requirement 5: Mosque Detail View Enhancement

**User Story:** As a platform owner, I want to see comprehensive mosque details on the detail page, so that I can make informed approval decisions.

#### Acceptance Criteria

1. WHEN the owner views a mosque detail page, THE Platform SHALL display all mosque profile fields including: name, description, address, city, province, postal_code, phone, email
2. THE Platform SHALL display the mosque photo if available
3. THE Platform SHALL display banking information: bank_name, bank_account_number, bank_account_holder
4. THE Platform SHALL display the mosque location on an embedded Google Maps iframe if latitude and longitude are available
5. THE Platform SHALL display the admin user details: name, email, phone_number
6. THE Platform SHALL display congregation statistics: total members count
7. THE Platform SHALL display financial statistics: total donation amount received
8. THE Platform SHALL display the mosque registration date formatted as 'd M Y H:i'
9. WHEN the mosque status is 'pending', THE Platform SHALL display approve and reject action buttons
10. WHEN the mosque status is not 'pending', THE Platform SHALL hide approve and reject action buttons
11. THE Platform SHALL display the current mosque status with an appropriate status badge (active=success, pending=warning, suspended=danger, rejected=secondary)

### Requirement 6: User Interface and User Experience

**User Story:** As a platform owner, I want intuitive modal confirmations for approve/reject actions, so that I can avoid accidental actions.

#### Acceptance Criteria

1. THE Platform SHALL use SweetAlert2 library for confirmation modals
2. WHEN the owner clicks approve, THE Platform SHALL display a confirmation modal with title "Approve This Mosque?", mosque name, and confirm/cancel buttons
3. THE approve confirmation modal SHALL have a success/green theme
4. WHEN the owner clicks reject, THE Platform SHALL display a modal with title "Reject This Mosque?", a textarea for rejection reason, and confirm/cancel buttons
5. THE reject confirmation modal SHALL have a danger/red theme
6. THE reject modal SHALL validate that rejection reason is at least 10 characters before allowing submission
7. WHEN the owner cancels the modal, THE Platform SHALL not perform any action and remain on the current page
8. WHEN an approval or rejection completes successfully, THE Platform SHALL display a flash message at the top of the page
9. THE success flash message SHALL have a success/green theme and contain the action result (e.g., "Mosque approved successfully")
10. THE error flash message SHALL have a danger/red theme and contain the error message

### Requirement 7: Backend Controller Implementation

**User Story:** As a backend developer, I want to implement approve and reject controller methods, so that the owner can trigger these actions via HTTP requests.

#### Acceptance Criteria

1. THE Mosque_Controller SHALL provide a approve method that accepts a mosque_id parameter
2. THE approve method SHALL use the ApproveMosqueRequest for validation
3. THE approve method SHALL call the Mosque_Service approve method with mosque_id and authenticated user ID
4. THE approve method SHALL return a redirect response to the mosque detail page with a success flash message on success
5. THE approve method SHALL return a redirect response with an error flash message on failure
6. THE Mosque_Controller SHALL provide a reject method that accepts mosque_id and rejection_reason parameters
7. THE reject method SHALL use the RejectMosqueRequest for validation
8. THE reject method SHALL call the Mosque_Service reject method with mosque_id, rejection_reason, and authenticated user ID
9. THE reject method SHALL return a redirect response to the pending mosque list page with a success flash message on success
10. THE reject method SHALL return a redirect response with an error flash message on failure

### Requirement 8: Routing Configuration

**User Story:** As a backend developer, I want to register approve and reject routes, so that the frontend can invoke these actions.

#### Acceptance Criteria

1. THE Platform SHALL register a POST route '/owner/mosques/{id}/approve' to Mosque_Controller@approve with name 'owner.mosques.approve'
2. THE Platform SHALL register a POST route '/owner/mosques/{id}/reject' to Mosque_Controller@reject with name 'owner.mosques.reject'
3. THE routes SHALL be protected by 'auth:web' and 'owner' middleware
4. THE routes SHALL be part of the 'owner' route group with prefix '/owner'

### Requirement 9: Database Schema Requirements

**User Story:** As a backend developer, I want to ensure the mosques table has all required columns for approval/rejection, so that the feature can store all necessary data.

#### Acceptance Criteria

1. THE mosques table SHALL have an approved_at column of type timestamp nullable
2. THE mosques table SHALL have an approved_by column of type unsignedBigInteger nullable with foreign key to users.id
3. THE mosques table SHALL have a rejected_at column of type timestamp nullable
4. THE mosques table SHALL have a rejected_by column of type unsignedBigInteger nullable with foreign key to users.id
5. THE mosques table SHALL have a rejection_reason column of type text nullable
6. THE mosques table SHALL have an invitation_code column of type string(6) nullable unique

### Requirement 10: Security and Authorization

**User Story:** As a platform owner, I want approval/rejection actions to be secure, so that only authorized super-admins can perform these actions.

#### Acceptance Criteria

1. THE Platform SHALL verify that the authenticated user has the 'super-admin' role before allowing approve/reject actions
2. THE Platform SHALL use CSRF token protection for all POST requests to approve and reject routes
3. THE Platform SHALL log all approval and rejection actions with user ID and timestamp for audit purposes
4. THE Platform SHALL prevent concurrent approval/rejection of the same mosque using database transactions

