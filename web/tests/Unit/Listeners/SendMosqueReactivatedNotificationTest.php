<?php

namespace Tests\Unit\Listeners;

use App\Enums\MosqueStatus;
use App\Events\MosqueReactivated;
use App\Jobs\SendFcmNotificationJob;
use App\Listeners\SendMosqueReactivatedNotification;
use App\Models\FcmToken;
use App\Models\Mosque;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\PermissionSeeder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Unit tests for SendMosqueReactivatedNotification listener — edge cases.
 *
 * Requirements: 4.5
 */
class SendMosqueReactivatedNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
    }

    // -------------------------------------------------------------------------
    // ShouldQueue contract
    // -------------------------------------------------------------------------

    /**
     * Test listener implements ShouldQueue for asynchronous processing.
     */
    public function test_listener_implements_should_queue(): void
    {
        $listener = new SendMosqueReactivatedNotification();

        $this->assertInstanceOf(ShouldQueue::class, $listener);
    }

    // -------------------------------------------------------------------------
    // Edge case: admin_user_id is null — Requirements: 4.5
    // -------------------------------------------------------------------------

    /**
     * Test no job is dispatched when mosque has no admin (admin_user_id is null).
     *
     * Uses an unsaved Mosque instance to bypass the DB NOT NULL constraint,
     * since this edge case only needs the in-memory model state.
     *
     * Requirements: 4.5
     */
    public function test_no_job_dispatched_when_admin_user_id_is_null(): void
    {
        Queue::fake();

        // Build an unsaved mosque — avoids the NOT NULL DB constraint
        $mosque                = Mosque::factory()->make(['status' => MosqueStatus::Active]);
        $mosque->admin_user_id = null;

        $event    = new MosqueReactivated($mosque, 1, Carbon::now());
        $listener = new SendMosqueReactivatedNotification();

        $listener->handle($event);

        Queue::assertNothingPushed();
    }

    /**
     * Test Log::warning is recorded when mosque has no admin (admin_user_id is null).
     *
     * Requirements: 4.5
     */
    public function test_warning_logged_when_admin_user_id_is_null(): void
    {
        Queue::fake();
        Log::spy();

        // Build an unsaved mosque — avoids the NOT NULL DB constraint
        $mosque                = Mosque::factory()->make(['status' => MosqueStatus::Active]);
        $mosque->id            = 999;
        $mosque->admin_user_id = null;

        $event    = new MosqueReactivated($mosque, 1, Carbon::now());
        $listener = new SendMosqueReactivatedNotification();

        $listener->handle($event);

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn ($message, $context = []) =>
                str_contains($message, 'admin_user_id null')
                && isset($context['mosque_id'])
                && $context['mosque_id'] === $mosque->id
            );
    }

    // -------------------------------------------------------------------------
    // Edge case: admin has no FCM tokens — Requirements: 4.5
    // -------------------------------------------------------------------------

    /**
     * Test no job is dispatched when admin user has no registered FCM tokens.
     *
     * Requirements: 4.5
     */
    public function test_no_job_dispatched_when_admin_has_no_fcm_tokens(): void
    {
        Queue::fake();

        $admin  = User::factory()->create();
        $mosque = Mosque::factory()->create([
            'status'        => MosqueStatus::Active,
            'admin_user_id' => $admin->id,
        ]);

        // Ensure no FCM tokens exist for this admin
        FcmToken::where('user_id', $admin->id)->delete();

        $event    = new MosqueReactivated($mosque, 1, Carbon::now());
        $listener = new SendMosqueReactivatedNotification();

        $listener->handle($event);

        Queue::assertNotPushed(SendFcmNotificationJob::class);
    }

    /**
     * Test Log::warning is recorded with the correct format when admin has no FCM tokens.
     *
     * The warning must match the format: "No FCM token found for mosque admin user_id={id}".
     *
     * Requirements: 4.5
     */
    public function test_warning_logged_with_correct_format_when_admin_has_no_fcm_tokens(): void
    {
        Queue::fake();
        Log::spy();

        $admin  = User::factory()->create();
        $mosque = Mosque::factory()->create([
            'status'        => MosqueStatus::Active,
            'admin_user_id' => $admin->id,
        ]);

        FcmToken::where('user_id', $admin->id)->delete();

        $event    = new MosqueReactivated($mosque, 1, Carbon::now());
        $listener = new SendMosqueReactivatedNotification();

        $listener->handle($event);

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn ($message) =>
                $message === "No FCM token found for mosque admin user_id={$admin->id}"
            );
    }
}
