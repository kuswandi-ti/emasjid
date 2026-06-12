# Requirements Document

## Introduction

This feature covers the Day 2 project setup for the EMasjid platform backend. It establishes the complete database structure through Laravel migrations, creates all domain Enum classes, and organizes the folder structure following the architecture defined in `AGENTS.md`. The outcome is a fully migrated database with all tables ready, all enums defined, and a clean layered folder structure in place.

## Glossary

- **Migration_Runner**: The Laravel Artisan migration system that executes migration files to create database tables
- **Enum_Generator**: The system component responsible for creating PHP Enum classes for domain status and type values
- **Folder_Scaffolder**: The system component responsible for creating the application folder structure
- **Platform_Table**: A database table that stores platform-wide data not scoped to any specific mosque (e.g., users, mosques, platform_settings, fcm_tokens)
- **Mosque_Scoped_Table**: A database table that stores data belonging to a specific mosque, requiring a `mosque_id` foreign key column
- **Pivot_Table**: A database table that represents a many-to-many relationship between two entities (e.g., mosque_user)

## Requirements

### Requirement 1: Folder Structure Setup

**User Story:** As a developer, I want the application folder structure organized according to the AGENTS.md architecture, so that all future development follows a consistent layered pattern.

#### Acceptance Criteria

1. THE Folder_Scaffolder SHALL create the `app/Http/Controllers/Owner` directory
2. THE Folder_Scaffolder SHALL create the `app/Http/Controllers/Admin` directory
3. THE Folder_Scaffolder SHALL create the `app/Http/Controllers/Api` directory
4. THE Folder_Scaffolder SHALL create the `app/Services` directory
5. THE Folder_Scaffolder SHALL create the `app/Repositories` directory
6. THE Folder_Scaffolder SHALL create the `app/Contracts/Repositories` directory
7. THE Folder_Scaffolder SHALL create the `app/DataTransferObjects` directory
8. THE Folder_Scaffolder SHALL create the `app/Enums` directory
9. THE Folder_Scaffolder SHALL create the `app/Traits` directory
10. THE Folder_Scaffolder SHALL create the `app/Http/Requests/Owner` directory
11. THE Folder_Scaffolder SHALL create the `app/Http/Requests/Admin` directory
12. THE Folder_Scaffolder SHALL create the `app/Http/Requests/Api` directory
13. THE Folder_Scaffolder SHALL create the `app/Http/Resources` directory
14. THE Folder_Scaffolder SHALL place a `.gitkeep` file in each of the 13 directories listed in criteria 1–13 that does not already contain any other file
15. IF any of the directories listed in criteria 1–13 already exist, THEN THE Folder_Scaffolder SHALL skip creation of that directory without producing an error
16. WHEN all directories have been processed, THE Folder_Scaffolder SHALL report the count of directories created and the count of directories that were already present

### Requirement 2: Users Table Migration

**User Story:** As a developer, I want the existing users table modified to include an `active_mosque_id` column, so that the system can track which mosque a congregation member is currently active in.

#### Acceptance Criteria

1. THE Migration_Runner SHALL add a nullable `active_mosque_id` column of type `unsignedBigInteger` to the existing `users` table, constrained as a foreign key referencing the `id` column of the `mosques` table
2. THE Migration_Runner SHALL set the `active_mosque_id` foreign key constraint to set the column value to null when the referenced mosque record is deleted
3. THE Migration_Runner SHALL support reversal by dropping the foreign key constraint on `active_mosque_id` and then removing the `active_mosque_id` column in the `down()` method
4. THE Migration_Runner SHALL execute this migration after the `mosques` table migration has been applied, so that the foreign key reference is valid

### Requirement 3: Mosques Table Migration

**User Story:** As a developer, I want a mosques table created, so that the platform can store registered mosque data with all profile and status information.

#### Acceptance Criteria

1. THE Migration_Runner SHALL create a `mosques` table with auto-increment `id` (bigIncrements) as primary key
2. THE Migration_Runner SHALL include non-nullable columns: `name` (string 255), `slug` (string 255, unique), `address` (text), `city` (string 100), `province` (string 100)
3. THE Migration_Runner SHALL include nullable columns: `postal_code` (string 10), `phone` (string 20), `email` (string 255), `description` (text), `photo` (string 255)
4. THE Migration_Runner SHALL include nullable coordinate columns: `latitude` (decimal 10,7) and `longitude` (decimal 10,7)
5. THE Migration_Runner SHALL include nullable bank columns: `bank_name` (string 100), `bank_account_name` (string 255), `bank_account_number` (string 50), `qris_image` (string 255)
6. THE Migration_Runner SHALL include a non-nullable unique `invitation_code` column (string 20) for joining via code
7. THE Migration_Runner SHALL include a non-nullable `status` column (string 20) defaulting to 'pending', constrained to values: pending, active, suspended, rejected
8. THE Migration_Runner SHALL include a non-nullable foreign key `admin_user_id` referencing the `users` table with a `constrained()` constraint
9. THE Migration_Runner SHALL include nullable columns: `rejection_reason` (text), `approved_at` (timestamp)
10. THE Migration_Runner SHALL include `timestamps` and `softDeletes`
11. THE Migration_Runner SHALL add indexes on `status`, `city`, and a composite index on `latitude` and `longitude`
12. THE Migration_Runner SHALL support reversal by dropping the `mosques` table in the `down()` method
13. IF the `users` table does not exist when the migration runs, THEN THE Migration_Runner SHALL fail with a foreign key constraint error, ensuring this migration is ordered after the users table migration

### Requirement 4: Platform Settings Table Migration

**User Story:** As a developer, I want a platform_settings table created, so that global configuration values like fee percentage and mechanism can be stored.

#### Acceptance Criteria

1. THE Migration_Runner SHALL create a `platform_settings` table with a `bigIncrements` `id` column as primary key
2. THE Migration_Runner SHALL include columns: `key` (string, max 100 characters, unique, not null), `value` (text, not null), `description` (string, max 255 characters, nullable)
3. THE Migration_Runner SHALL include `created_at` and `updated_at` timestamp columns using Laravel's `timestamps()` method
4. THE Migration_Runner SHALL support reversal by dropping the `platform_settings` table in the `down()` method

### Requirement 5: Schedules Table Migration

**User Story:** As a developer, I want a schedules table created, so that mosque prayer times and Friday prayer information can be stored per date.

#### Acceptance Criteria

1. THE Migration_Runner SHALL create a `schedules` table with auto-increment `id` as primary key
2. THE Migration_Runner SHALL include a `mosque_id` foreign key column with `constrained()->cascadeOnDelete()`
3. THE Migration_Runner SHALL include a `date` column (date type) for the applicable date
4. THE Migration_Runner SHALL include non-nullable time columns for five daily prayers: `subuh`, `dzuhur`, `ashar`, `maghrib`, `isya`
5. THE Migration_Runner SHALL include nullable time columns for iqomah: `subuh_iqomah`, `dzuhur_iqomah`, `ashar_iqomah`, `maghrib_iqomah`, `isya_iqomah`
6. THE Migration_Runner SHALL include nullable Friday prayer columns: `jumat_time` (time), `jumat_khatib` (string 255), `jumat_imam` (string 255)
7. THE Migration_Runner SHALL include `timestamps`
8. THE Migration_Runner SHALL add a non-unique composite index on columns `mosque_id` and `date`
9. THE Migration_Runner SHALL support reversal by dropping the `schedules` table in the `down()` method

### Requirement 6: Activities Table Migration

**User Story:** As a developer, I want an activities table created, so that mosque events and recurring activities can be tracked with status.

#### Acceptance Criteria

1. THE Migration_Runner SHALL create an `activities` table with auto-increment `id` as primary key
2. THE Migration_Runner SHALL include a `mosque_id` foreign key column with `constrained()->cascadeOnDelete()`
3. THE Migration_Runner SHALL include columns: `title` (string 255), nullable `description` (text), nullable `speaker` (string 255), nullable `location` (string 255)
4. THE Migration_Runner SHALL include columns: `start_date` (date), nullable `start_time` (time), nullable `end_time` (time)
5. THE Migration_Runner SHALL include columns: `is_recurring` (boolean, default false), nullable `recurrence_note` (string 255)
6. THE Migration_Runner SHALL include a `status` column (string 20) defaulting to 'upcoming'
7. THE Migration_Runner SHALL include `timestamps`
8. THE Migration_Runner SHALL add a composite index on `mosque_id`, `status`, and `start_date`
9. THE Migration_Runner SHALL support reversal by dropping the `activities` table in the `down()` method

### Requirement 7: Cash Transactions Table Migration

**User Story:** As a developer, I want a cash_transactions table created, so that mosque income and expense records can be stored with proper categorization.

#### Acceptance Criteria

1. THE Migration_Runner SHALL create a `cash_transactions` table with auto-increment `id` as primary key
2. THE Migration_Runner SHALL include a `mosque_id` foreign key column with `constrained()->cascadeOnDelete()`
3. THE Migration_Runner SHALL include columns: `type` (string 20) for income or expense, `amount` (unsignedBigInteger) in Rupiah, `description` (string 255)
4. THE Migration_Runner SHALL include nullable columns: `category` (string 100), `notes` (text)
5. THE Migration_Runner SHALL include a `transaction_date` (date) column
6. THE Migration_Runner SHALL include a nullable `recorded_by` foreign key referencing the `users` table with null on delete
7. THE Migration_Runner SHALL include `timestamps`
8. THE Migration_Runner SHALL add composite indexes on `mosque_id`, `type`, `transaction_date` and on `mosque_id`, `transaction_date`
9. THE Migration_Runner SHALL support reversal by dropping the `cash_transactions` table in the `down()` method

### Requirement 8: Donations Table Migration

**User Story:** As a developer, I want a donations table created, so that online donation transactions from congregations to mosques can be fully tracked including fee snapshots and payment gateway references.

#### Acceptance Criteria

1. THE Migration_Runner SHALL create a `donations` table with auto-increment `id` as primary key
2. THE Migration_Runner SHALL include a `mosque_id` foreign key column with `constrained()->cascadeOnDelete()`
3. THE Migration_Runner SHALL include a `user_id` foreign key referencing the `users` table with null on delete
4. THE Migration_Runner SHALL include a `category` column (string 20) for donation category
5. THE Migration_Runner SHALL include monetary columns as `unsignedBigInteger`: `amount`, `fee_amount`, `payment_amount`, `mosque_receives`
6. THE Migration_Runner SHALL include a `fee_mechanism` column (string 30) to snapshot the fee mechanism at time of creation
7. THE Migration_Runner SHALL include a `status` column (string 20) defaulting to 'pending'
8. THE Migration_Runner SHALL include a `is_anonymous` column (boolean, default false)
9. THE Migration_Runner SHALL include a unique `merchant_order_id` column (string 50) for Duitku order tracking
10. THE Migration_Runner SHALL include nullable columns: `payment_url` (text), `reference` (string 100), `payment_method` (string 50)
11. THE Migration_Runner SHALL include nullable timestamp columns: `confirmed_at`, `expired_at`
12. THE Migration_Runner SHALL include a nullable `notes` (text) column
13. THE Migration_Runner SHALL include `timestamps`
14. THE Migration_Runner SHALL add composite indexes on `mosque_id`, `status` and on `user_id`, `status`
15. THE Migration_Runner SHALL support reversal by dropping the `donations` table in the `down()` method

### Requirement 9: Announcements Table Migration

**User Story:** As a developer, I want an announcements table created, so that mosque announcements can be stored with publication status tracking.

#### Acceptance Criteria

1. THE Migration_Runner SHALL create an `announcements` table with auto-increment `id` as primary key
2. THE Migration_Runner SHALL include a `mosque_id` foreign key column with `constrained()->cascadeOnDelete()`
3. THE Migration_Runner SHALL include columns: `title` (string 255), `content` (text)
4. THE Migration_Runner SHALL include nullable columns: `image` (string 255), `published_at` (timestamp)
5. THE Migration_Runner SHALL include a `status` column (string 20) defaulting to 'draft'
6. THE Migration_Runner SHALL include a nullable `published_by` foreign key referencing the `users` table with null on delete
7. THE Migration_Runner SHALL include `timestamps`
8. THE Migration_Runner SHALL add a composite index on `mosque_id`, `status`, and `published_at`
9. THE Migration_Runner SHALL support reversal by dropping the `announcements` table in the `down()` method

### Requirement 10: Staffs Table Migration

**User Story:** As a developer, I want a staffs table created, so that mosque committee members can be tracked with their positions and active status.

#### Acceptance Criteria

1. THE Migration_Runner SHALL create a `staffs` table with auto-increment `id` as primary key using `bigIncrements`
2. THE Migration_Runner SHALL include a non-nullable `mosque_id` foreign key column referencing the `mosques` table with `constrained()->cascadeOnDelete()`
3. THE Migration_Runner SHALL include a non-nullable `user_id` foreign key column referencing the `users` table with `constrained()->cascadeOnDelete()`
4. THE Migration_Runner SHALL include a non-nullable `position` column of type `string` with a maximum length of 100 characters
5. THE Migration_Runner SHALL include an `is_active` column of type `boolean` with a default value of `true`
6. THE Migration_Runner SHALL include a nullable `joined_at` column of type `date`
7. THE Migration_Runner SHALL include `timestamps` columns (`created_at` and `updated_at`)
8. THE Migration_Runner SHALL add a unique composite constraint on the combination of `mosque_id` and `user_id` to prevent duplicate staff assignments
9. THE Migration_Runner SHALL support reversal by dropping the `staffs` table in the `down()` method

### Requirement 11: Mosque User Pivot Table Migration

**User Story:** As a developer, I want a mosque_user pivot table created, so that the many-to-many relationship between congregations and mosques can be tracked.

#### Acceptance Criteria

1. THE Migration_Runner SHALL create a `mosque_user` table with auto-increment `id` as primary key
2. THE Migration_Runner SHALL include a `mosque_id` foreign key column referencing the `mosques` table with `constrained()->cascadeOnDelete()`
3. THE Migration_Runner SHALL include a `user_id` foreign key column referencing the `users` table with `constrained()->cascadeOnDelete()`
4. THE Migration_Runner SHALL include a nullable `joined_at` (timestamp) column
5. THE Migration_Runner SHALL include `timestamps` (`created_at` and `updated_at`)
6. THE Migration_Runner SHALL add a unique composite constraint on the pair (`mosque_id`, `user_id`) to prevent duplicate memberships
7. THE Migration_Runner SHALL support reversal by dropping the `mosque_user` table in the `down()` method

### Requirement 12: FCM Tokens Table Migration

**User Story:** As a developer, I want an fcm_tokens table created, so that push notification tokens for mobile devices can be stored per user.

#### Acceptance Criteria

1. THE Migration_Runner SHALL create an `fcm_tokens` table with `bigIncrements` `id` as primary key
2. THE Migration_Runner SHALL include a non-nullable `user_id` foreign key referencing the `users` table with `constrained()->cascadeOnDelete()`
3. THE Migration_Runner SHALL include columns: `token` (text, not nullable), `device_type` (string 20, not nullable)
4. THE Migration_Runner SHALL include `timestamps` (`created_at` and `updated_at`)
5. THE Migration_Runner SHALL add an index on the `user_id` column
6. THE Migration_Runner SHALL support reversal by calling `dropIfExists('fcm_tokens')` in the `down()` method
7. THE Migration_Runner SHALL add a unique composite index on `user_id` and `token` to prevent duplicate token registration per user

### Requirement 13: Enum Classes

**User Story:** As a developer, I want all domain Enum classes created, so that status and type values are type-safe and consistently used across the application.

#### Acceptance Criteria

1. THE Enum_Generator SHALL create a `MosqueStatus` enum in `app/Enums` with cases: `Pending`, `Active`, `Suspended`, `Rejected` backed by string values `pending`, `active`, `suspended`, `rejected`
2. THE Enum_Generator SHALL create a `DonationStatus` enum in `app/Enums` with cases: `Pending`, `Confirmed`, `Failed`, `Expired` backed by string values `pending`, `confirmed`, `failed`, `expired`
3. THE Enum_Generator SHALL create a `DonationCategory` enum in `app/Enums` with cases: `Infaq`, `Zakat`, `Sadaqah`, `Waqf` backed by string values `infaq`, `zakat`, `sadaqah`, `waqf`
4. THE Enum_Generator SHALL create a `TransactionType` enum in `app/Enums` with cases: `Income`, `Expense` backed by string values `income`, `expense`
5. THE Enum_Generator SHALL create an `AnnouncementStatus` enum in `app/Enums` with cases: `Draft`, `Published`, `Archived` backed by string values `draft`, `published`, `archived`
6. THE Enum_Generator SHALL create an `ActivityStatus` enum in `app/Enums` with cases: `Upcoming`, `Ongoing`, `Completed`, `Cancelled` backed by string values `upcoming`, `ongoing`, `completed`, `cancelled`
7. THE Enum_Generator SHALL implement each enum as a PHP 8.1+ backed enum with `string` type under the `App\Enums` namespace
8. THE Enum_Generator SHALL create each enum as a standalone PHP file following PSR-4 autoloading convention
9. THE Enum_Generator SHALL include a `labels(): array` method on each enum that returns a human-readable label for each case for UI display purposes

### Requirement 14: Migration Execution

**User Story:** As a developer, I want all migrations to execute successfully, so that the database schema is fully created and ready for use.

#### Acceptance Criteria

1. WHEN `php artisan migrate` is executed on a fresh database, THE Migration_Runner SHALL create the following tables without returning a non-zero exit code: `mosques`, `platform_settings`, `schedules`, `activities`, `cash_transactions`, `donations`, `announcements`, `staffs`, `mosque_user`, `fcm_tokens`, and the modified `users` table with `active_mosque_id`
2. WHEN `php artisan migrate` is executed, THE Migration_Runner SHALL execute migrations in an order that creates referenced tables before dependent tables, so that all foreign key constraints are established without integrity errors
3. WHEN `php artisan migrate:rollback --step=1` is executed against the last migration batch, THE Migration_Runner SHALL reverse the table creations from that batch without returning a non-zero exit code, leaving no orphan tables or dangling foreign key references
4. WHEN all migrations have been executed, THE Migration_Runner SHALL have established foreign key constraints including: `mosques.admin_user_id` → `users.id`, `users.active_mosque_id` → `mosques.id`, `schedules.mosque_id` → `mosques.id`, `activities.mosque_id` → `mosques.id`, `cash_transactions.mosque_id` → `mosques.id`, `cash_transactions.recorded_by` → `users.id`, `donations.mosque_id` → `mosques.id`, `donations.user_id` → `users.id`, `announcements.mosque_id` → `mosques.id`, `announcements.published_by` → `users.id`, `staffs.mosque_id` → `mosques.id`, `staffs.user_id` → `users.id`, `mosque_user.mosque_id` → `mosques.id`, `mosque_user.user_id` → `users.id`, `fcm_tokens.user_id` → `users.id`
5. WHEN `php artisan migrate:fresh` is executed, THE Migration_Runner SHALL drop all tables and re-run all migrations to completion without returning a non-zero exit code
