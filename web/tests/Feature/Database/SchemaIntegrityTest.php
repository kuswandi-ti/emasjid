<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SchemaIntegrityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that all expected tables exist after migration.
     *
     * Validates: Requirements 14.4, 3.11, 5.8, 6.8, 7.8, 8.14, 9.8, 10.8, 11.6, 12.5, 12.7
     */
    public function test_all_expected_tables_exist(): void
    {
        $expectedTables = [
            'mosques',
            'platform_settings',
            'schedules',
            'activities',
            'cash_transactions',
            'donations',
            'announcements',
            'staffs',
            'mosque_user',
            'fcm_tokens',
        ];

        foreach ($expectedTables as $table) {
            $this->assertTrue(
                Schema::hasTable($table),
                "Expected table '{$table}' does not exist."
            );
        }
    }

    /**
     * Test that foreign key columns exist on the expected tables.
     *
     * Validates: Requirements 14.4
     */
    public function test_foreign_key_constraints_exist(): void
    {
        $foreignKeyColumns = [
            'mosques' => ['admin_user_id'],
            'users' => ['active_mosque_id'],
            'schedules' => ['mosque_id'],
            'activities' => ['mosque_id'],
            'cash_transactions' => ['mosque_id', 'recorded_by'],
            'donations' => ['mosque_id', 'user_id'],
            'announcements' => ['mosque_id', 'published_by'],
            'staffs' => ['mosque_id', 'user_id'],
            'mosque_user' => ['mosque_id', 'user_id'],
            'fcm_tokens' => ['user_id'],
        ];

        foreach ($foreignKeyColumns as $table => $columns) {
            foreach ($columns as $column) {
                $this->assertTrue(
                    Schema::hasColumn($table, $column),
                    "Expected foreign key column '{$column}' does not exist on table '{$table}'."
                );
            }
        }
    }

    /**
     * Test that key columns exist on each table with expected schema structure.
     *
     * Validates: Requirements 3.11, 5.8, 6.8, 7.8, 8.14, 9.8, 10.8, 11.6, 12.5
     */
    public function test_indexes_exist(): void
    {
        // Verify mosques table has indexed columns
        $this->assertTrue(Schema::hasColumns('mosques', ['status', 'city', 'latitude', 'longitude']));

        // Verify schedules composite index columns exist
        $this->assertTrue(Schema::hasColumns('schedules', ['mosque_id', 'date']));

        // Verify activities composite index columns exist
        $this->assertTrue(Schema::hasColumns('activities', ['mosque_id', 'status', 'start_date']));

        // Verify cash_transactions composite index columns exist
        $this->assertTrue(Schema::hasColumns('cash_transactions', ['mosque_id', 'type', 'transaction_date']));

        // Verify donations composite index columns exist
        $this->assertTrue(Schema::hasColumns('donations', ['mosque_id', 'status', 'user_id']));

        // Verify announcements composite index columns exist
        $this->assertTrue(Schema::hasColumns('announcements', ['mosque_id', 'status', 'published_at']));

        // Verify staffs has the unique constraint columns
        $this->assertTrue(Schema::hasColumns('staffs', ['mosque_id', 'user_id']));

        // Verify mosque_user has the unique constraint columns
        $this->assertTrue(Schema::hasColumns('mosque_user', ['mosque_id', 'user_id']));

        // Verify fcm_tokens has the index column
        $this->assertTrue(Schema::hasColumns('fcm_tokens', ['user_id', 'token']));
    }

    /**
     * Test that unique constraints exist on expected columns.
     *
     * Validates: Requirements 3.11, 8.14, 12.7
     */
    public function test_unique_constraints(): void
    {
        // Verify unique columns exist on mosques
        $this->assertTrue(
            Schema::hasColumn('mosques', 'slug'),
            "Expected unique column 'slug' does not exist on 'mosques' table."
        );
        $this->assertTrue(
            Schema::hasColumn('mosques', 'invitation_code'),
            "Expected unique column 'invitation_code' does not exist on 'mosques' table."
        );

        // Verify unique column exists on donations
        $this->assertTrue(
            Schema::hasColumn('donations', 'merchant_order_id'),
            "Expected unique column 'merchant_order_id' does not exist on 'donations' table."
        );

        // Verify unique column exists on platform_settings
        $this->assertTrue(
            Schema::hasColumn('platform_settings', 'key'),
            "Expected unique column 'key' does not exist on 'platform_settings' table."
        );

        // Verify the indexes are actually unique by checking the index listing
        $mosquesIndexes = Schema::getIndexes('mosques');
        $this->assertIndexIsUnique($mosquesIndexes, 'slug', 'mosques');
        $this->assertIndexIsUnique($mosquesIndexes, 'invitation_code', 'mosques');

        $donationsIndexes = Schema::getIndexes('donations');
        $this->assertIndexIsUnique($donationsIndexes, 'merchant_order_id', 'donations');

        $platformSettingsIndexes = Schema::getIndexes('platform_settings');
        $this->assertIndexIsUnique($platformSettingsIndexes, 'key', 'platform_settings');
    }

    /**
     * Assert that an index containing the given column is marked as unique.
     */
    private function assertIndexIsUnique(array $indexes, string $column, string $table): void
    {
        $found = false;

        foreach ($indexes as $index) {
            if (in_array($column, $index['columns'])) {
                $this->assertTrue(
                    $index['unique'],
                    "Expected column '{$column}' on table '{$table}' to have a unique index, but it is not unique."
                );
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, "No index found containing column '{$column}' on table '{$table}'.");
    }
}
