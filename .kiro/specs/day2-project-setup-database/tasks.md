# Implementation Plan: Day 2 Project Setup - Database

## Overview

This plan implements the complete database foundation for the EMasjid platform: folder structure scaffolding (13 directories with `.gitkeep`), 11 Laravel migration files, and 6 PHP Enum classes. All work is within the `web/` directory of the monorepo. The implementation follows a dependency-aware order — folder structure first, then enums, then migrations (platform tables before mosque-scoped tables), and finally verification.

## Tasks

- [x] 1. Create folder structure and `.gitkeep` files
  - [x] 1.1 Create all 13 application directories with `.gitkeep` files
    - Create directories: `app/Http/Controllers/Owner`, `app/Http/Controllers/Admin`, `app/Http/Controllers/Api`, `app/Services`, `app/Repositories`, `app/Contracts/Repositories`, `app/DataTransferObjects`, `app/Enums`, `app/Traits`, `app/Http/Requests/Owner`, `app/Http/Requests/Admin`, `app/Http/Requests/Api`, `app/Http/Resources`
    - Place a `.gitkeep` file in each directory
    - Skip creation for any directory that already exists
    - _Requirements: 1.1–1.16_

- [x] 2. Create PHP Enum classes
  - [x] 2.1 Create `MosqueStatus` enum
    - Create `app/Enums/MosqueStatus.php` with cases: Pending, Active, Suspended, Rejected (string-backed)
    - Include `labels()` method returning Indonesian labels for each case
    - _Requirements: 13.1, 13.7, 13.8, 13.9_

  - [x] 2.2 Create `DonationStatus` enum
    - Create `app/Enums/DonationStatus.php` with cases: Pending, Confirmed, Failed, Expired (string-backed)
    - Include `labels()` method returning Indonesian labels for each case
    - _Requirements: 13.2, 13.7, 13.8, 13.9_

  - [x] 2.3 Create `DonationCategory` enum
    - Create `app/Enums/DonationCategory.php` with cases: Infaq, Zakat, Sadaqah, Waqf (string-backed)
    - Include `labels()` method returning Indonesian labels for each case
    - _Requirements: 13.3, 13.7, 13.8, 13.9_

  - [x] 2.4 Create `TransactionType` enum
    - Create `app/Enums/TransactionType.php` with cases: Income, Expense (string-backed)
    - Include `labels()` method returning Indonesian labels for each case
    - _Requirements: 13.4, 13.7, 13.8, 13.9_

  - [x] 2.5 Create `AnnouncementStatus` enum
    - Create `app/Enums/AnnouncementStatus.php` with cases: Draft, Published, Archived (string-backed)
    - Include `labels()` method returning Indonesian labels for each case
    - _Requirements: 13.5, 13.7, 13.8, 13.9_

  - [x] 2.6 Create `ActivityStatus` enum
    - Create `app/Enums/ActivityStatus.php` with cases: Upcoming, Ongoing, Completed, Cancelled (string-backed)
    - Include `labels()` method returning Indonesian labels for each case
    - _Requirements: 13.6, 13.7, 13.8, 13.9_

- [x] 3. Create platform-level migration files
  - [x] 3.1 Create mosques table migration
    - Create `database/migrations/2026_06_08_000001_create_mosques_table.php`
    - Include all columns as specified in design: name, slug (unique), address, city, province, coordinates, bank info, invitation_code (unique), status (default pending), admin_user_id FK, rejection_reason, approved_at, timestamps, softDeletes
    - Add indexes on `status`, `city`, and composite `[latitude, longitude]`
    - Implement `down()` to drop the table
    - _Requirements: 3.1–3.13_

  - [x] 3.2 Create add active_mosque_id to users migration
    - Create `database/migrations/2026_06_08_000002_add_active_mosque_id_to_users_table.php`
    - Add nullable `active_mosque_id` column with FK to mosques, nullOnDelete
    - Implement `down()` to drop FK and column
    - _Requirements: 2.1–2.4_

  - [x] 3.3 Create platform_settings table migration
    - Create `database/migrations/2026_06_08_000003_create_platform_settings_table.php`
    - Include columns: key (string 100, unique), value (text), description (string 255, nullable), timestamps
    - Implement `down()` to drop the table
    - _Requirements: 4.1–4.4_

- [x] 4. Create mosque-scoped migration files
  - [x] 4.1 Create schedules table migration
    - Create `database/migrations/2026_06_08_000004_create_schedules_table.php`
    - Include mosque_id FK with cascadeOnDelete, date, 5 prayer time columns, 5 iqomah columns, Friday prayer columns, timestamps
    - Add composite index on `[mosque_id, date]`
    - Implement `down()` to drop the table
    - _Requirements: 5.1–5.9_

  - [x] 4.2 Create activities table migration
    - Create `database/migrations/2026_06_08_000005_create_activities_table.php`
    - Include mosque_id FK with cascadeOnDelete, title, description, speaker, location, start_date, start_time, end_time, is_recurring, recurrence_note, status (default upcoming), timestamps
    - Add composite index on `[mosque_id, status, start_date]`
    - Implement `down()` to drop the table
    - _Requirements: 6.1–6.9_

  - [x] 4.3 Create cash_transactions table migration
    - Create `database/migrations/2026_06_08_000006_create_cash_transactions_table.php`
    - Include mosque_id FK with cascadeOnDelete, type, amount (unsignedBigInteger), description, category, transaction_date, notes, recorded_by FK (nullOnDelete), timestamps
    - Add composite indexes on `[mosque_id, type, transaction_date]` and `[mosque_id, transaction_date]`
    - Implement `down()` to drop the table
    - _Requirements: 7.1–7.9_

  - [x] 4.4 Create donations table migration
    - Create `database/migrations/2026_06_08_000007_create_donations_table.php`
    - Include mosque_id FK with cascadeOnDelete, user_id FK (nullOnDelete), category, monetary columns (amount, fee_amount, payment_amount, mosque_receives as unsignedBigInteger), fee_mechanism, status (default pending), is_anonymous, merchant_order_id (unique), payment fields, confirmed_at, expired_at, notes, timestamps
    - Add composite indexes on `[mosque_id, status]` and `[user_id, status]`
    - Implement `down()` to drop the table
    - _Requirements: 8.1–8.15_

  - [x] 4.5 Create announcements table migration
    - Create `database/migrations/2026_06_08_000008_create_announcements_table.php`
    - Include mosque_id FK with cascadeOnDelete, title, content, image, status (default draft), published_at, published_by FK (nullOnDelete), timestamps
    - Add composite index on `[mosque_id, status, published_at]`
    - Implement `down()` to drop the table
    - _Requirements: 9.1–9.9_

  - [x] 4.6 Create staffs table migration
    - Create `database/migrations/2026_06_08_000009_create_staffs_table.php`
    - Include mosque_id FK with cascadeOnDelete, user_id FK with cascadeOnDelete, position, is_active (default true), joined_at (nullable date), timestamps
    - Add unique composite constraint on `[mosque_id, user_id]`
    - Implement `down()` to drop the table
    - _Requirements: 10.1–10.9_

  - [x] 4.7 Create mosque_user pivot table migration
    - Create `database/migrations/2026_06_08_000010_create_mosque_user_table.php`
    - Include mosque_id FK with cascadeOnDelete, user_id FK with cascadeOnDelete, joined_at (nullable timestamp), timestamps
    - Add unique composite constraint on `[mosque_id, user_id]`
    - Implement `down()` to drop the table
    - _Requirements: 11.1–11.7_

  - [x] 4.8 Create fcm_tokens table migration
    - Create `database/migrations/2026_06_08_000011_create_fcm_tokens_table.php`
    - Include user_id FK with cascadeOnDelete, token (text), device_type (string 20), timestamps
    - Add index on `user_id` and unique composite on `[user_id, token]`
    - Implement `down()` to drop the table using `dropIfExists`
    - _Requirements: 12.1–12.7_

- [x] 5. Checkpoint - Verify migrations and enums
  - Ensure all tests pass, ask the user if questions arise.

- [x] 6. Write tests for enums and migrations
  - [x] 6.1 Create enum unit tests
    - Create test files in `tests/Unit/Enums/` for each enum: `MosqueStatusTest.php`, `DonationStatusTest.php`, `DonationCategoryTest.php`, `TransactionTypeTest.php`, `AnnouncementStatusTest.php`, `ActivityStatusTest.php`
    - Test that each enum has correct cases and backing values
    - Test that `labels()` returns non-empty strings for all cases
    - Test `::from()` instantiation and `::tryFrom()` returning null for invalid values
    - _Requirements: 13.1–13.9_

  - [x] 6.2 Create migration integration tests
    - Create `tests/Feature/Database/MigrationRunTest.php`
    - Test that `php artisan migrate` completes without errors
    - Test that `php artisan migrate:rollback` reverses cleanly
    - Test that `php artisan migrate:fresh` succeeds
    - _Requirements: 14.1–14.5_

  - [x] 6.3 Create schema integrity tests
    - Create `tests/Feature/Database/SchemaIntegrityTest.php`
    - Test that all expected tables exist after migration
    - Test that foreign key constraints are properly set up
    - Test that indexes and unique constraints exist on expected columns
    - _Requirements: 14.4, 3.11, 5.8, 6.8, 7.8, 8.14, 9.8, 10.8, 11.6, 12.5, 12.7_

- [x] 7. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- All paths are relative to `web/` within the monorepo
- Migrations use timestamp-based naming (`2026_06_08_XXXXXX`) to ensure correct execution order
- Monetary values stored as `unsignedBigInteger` (Rupiah, smallest unit) to avoid floating-point issues
- All mosque-scoped tables cascade on delete when the parent mosque is removed
- User references in audit columns (recorded_by, published_by) use SET NULL to preserve records
- PHP 8.1+ backed enums provide type safety; database columns use plain string type for flexibility
- No property-based tests are included — this feature is purely declarative infrastructure setup

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1"] },
    { "id": 1, "tasks": ["2.1", "2.2", "2.3", "2.4", "2.5", "2.6"] },
    { "id": 2, "tasks": ["3.1"] },
    { "id": 3, "tasks": ["3.2", "3.3"] },
    { "id": 4, "tasks": ["4.1", "4.2", "4.3", "4.4", "4.5", "4.6", "4.7", "4.8"] },
    { "id": 5, "tasks": ["6.1", "6.2", "6.3"] }
  ]
}
```
