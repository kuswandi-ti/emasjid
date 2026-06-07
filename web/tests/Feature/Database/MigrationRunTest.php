<?php

namespace Tests\Feature\Database;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class MigrationRunTest extends TestCase
{
    /**
     * Test that php artisan migrate completes without errors.
     *
     * Validates: Requirements 14.1, 14.2
     */
    public function test_migrate_command_succeeds(): void
    {
        $exitCode = Artisan::call('migrate', ['--force' => true]);

        $this->assertEquals(0, $exitCode);
    }

    /**
     * Test that php artisan migrate:rollback reverses cleanly.
     *
     * Validates: Requirements 14.3
     */
    public function test_rollback_command_succeeds(): void
    {
        Artisan::call('migrate', ['--force' => true]);

        $exitCode = Artisan::call('migrate:rollback', ['--step' => 1]);

        $this->assertEquals(0, $exitCode);
    }

    /**
     * Test that php artisan migrate:fresh succeeds.
     *
     * Validates: Requirements 14.5
     */
    public function test_migrate_fresh_succeeds(): void
    {
        $exitCode = Artisan::call('migrate:fresh', ['--force' => true]);

        $this->assertEquals(0, $exitCode);
    }
}
