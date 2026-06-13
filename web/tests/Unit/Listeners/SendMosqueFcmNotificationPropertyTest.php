<?php

// Feature: day11-mosque-suspend-reactivate, Property 8
// Feature: day11-mosque-suspend-reactivate, Property 9

namespace Tests\Unit\Listeners;

use App\Enums\MosqueStatus;
use App\Events\MosqueReactivated;
use App\Events\MosqueSuspended;
use App\Jobs\SendFcmNotificationJob;
use App\Listeners\SendMosqueReactivatedNotification;
use App\Listeners\SendMosqueSuspendedNotification;
use App\Models\FcmToken;
use App\Models\Mosque;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Property-Based Tests: FCM job dispatch per token for suspend and reactivate listeners.
 *
 * **Validates: Requirements 3.1, 3.3, 4.1, 4.3**
 *
 * Feature: day11-mosque-suspend-reactivate, Property 8 & 9
 */
class SendMosqueFcmNotificationPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
    }

    // -------------------------------------------------------------------------
    // Property 8: Notifikasi FCM dikirim ke semua token admin saat suspend
    // Feature: day11-mosque-suspend-reactivate, Property 8
    // Validates: Requirements 3.1, 3.3
    // -------------------------------------------------------------------------

    /**
     * **Validates: Requirements 3.1, 3.3**
     *
     * Property 8: For any mosque with an admin who has N FCM tokens (1 ≤ N ≤ 5),
     * calling SendMosqueSuspendedNotification::handle() MUST dispatch
     * SendFcmNotificationJob exactly N times — one per token.
     *
     * Runs 100 iterations with rand(1, 5) tokens per admin.
     *
     * // Feature: day11-mosque-suspend-reactivate, Property 8
     */
    public function test_property_8_suspended_listener_dispatches_fcm_job_per_token(): void
    {
        // Feature: day11-mosque-suspend-reactivate, Property 8
        $listener = new SendMosqueSuspendedNotification();

        for ($i = 0; $i < 100; $i++) {
            Queue::fake();

            // Arrange: create an admin user and a mosque with that admin
            $admin  = User::factory()->create();
            $mosque = Mosque::factory()->suspended()->create([
                'admin_user_id' => $admin->id,
            ]);

            // Create rand(1, 5) FCM tokens for this admin
            $tokenCount = rand(1, 5);
            FcmToken::factory()->count($tokenCount)->create([
                'user_id' => $admin->id,
            ]);

            $event = new MosqueSuspended($mosque, $admin->id, Carbon::now());

            // Act
            $listener->handle($event);

            // Assert: SendFcmNotificationJob dispatched exactly N times
            Queue::assertPushed(
                SendFcmNotificationJob::class,
                $tokenCount,
                "Iteration {$i}: Expected {$tokenCount} FCM job(s) dispatched on suspend, " .
                "but got a different count. mosque_id={$mosque->id}, admin_user_id={$admin->id}"
            );
        }
    }

    /**
     * **Validates: Requirements 3.1, 3.3**
     *
     * Supplemental check for Property 8: each dispatched job carries the correct
     * FCM token string, title, and body matching the suspended notification copy.
     *
     * // Feature: day11-mosque-suspend-reactivate, Property 8
     */
    public function test_property_8_suspended_listener_dispatches_jobs_with_correct_payload(): void
    {
        // Feature: day11-mosque-suspend-reactivate, Property 8
        Queue::fake();

        $admin  = User::factory()->create();
        $mosque = Mosque::factory()->suspended()->create([
            'name'          => 'Masjid Al-Test',
            'admin_user_id' => $admin->id,
        ]);

        $tokenCount = rand(1, 5);
        $fcmTokens  = FcmToken::factory()->count($tokenCount)->create([
            'user_id' => $admin->id,
        ]);

        $listener = new SendMosqueSuspendedNotification();
        $event    = new MosqueSuspended($mosque, $admin->id, Carbon::now());

        $listener->handle($event);

        $tokenValues = $fcmTokens->pluck('token')->all();

        Queue::assertPushed(SendFcmNotificationJob::class, function (SendFcmNotificationJob $job) use ($tokenValues, $mosque) {
            return in_array($job->token, $tokenValues, true)
                && $job->title === 'Masjid Anda Ditangguhkan'
                && str_contains($job->body, $mosque->name);
        });
    }

    // -------------------------------------------------------------------------
    // Property 9: Notifikasi FCM dikirim ke semua token admin saat reactivate
    // Feature: day11-mosque-suspend-reactivate, Property 9
    // Validates: Requirements 4.1, 4.3
    // -------------------------------------------------------------------------

    /**
     * **Validates: Requirements 4.1, 4.3**
     *
     * Property 9: For any mosque with an admin who has N FCM tokens (1 ≤ N ≤ 5),
     * calling SendMosqueReactivatedNotification::handle() MUST dispatch
     * SendFcmNotificationJob exactly N times — one per token.
     *
     * Runs 100 iterations with rand(1, 5) tokens per admin.
     *
     * // Feature: day11-mosque-suspend-reactivate, Property 9
     */
    public function test_property_9_reactivated_listener_dispatches_fcm_job_per_token(): void
    {
        // Feature: day11-mosque-suspend-reactivate, Property 9
        $listener = new SendMosqueReactivatedNotification();

        for ($i = 0; $i < 100; $i++) {
            Queue::fake();

            // Arrange: create an admin user and a mosque with that admin
            $admin  = User::factory()->create();
            $mosque = Mosque::factory()->create([
                'status'        => MosqueStatus::Active,
                'admin_user_id' => $admin->id,
            ]);

            // Create rand(1, 5) FCM tokens for this admin
            $tokenCount = rand(1, 5);
            FcmToken::factory()->count($tokenCount)->create([
                'user_id' => $admin->id,
            ]);

            $event = new MosqueReactivated($mosque, $admin->id, Carbon::now());

            // Act
            $listener->handle($event);

            // Assert: SendFcmNotificationJob dispatched exactly N times
            Queue::assertPushed(
                SendFcmNotificationJob::class,
                $tokenCount,
                "Iteration {$i}: Expected {$tokenCount} FCM job(s) dispatched on reactivate, " .
                "but got a different count. mosque_id={$mosque->id}, admin_user_id={$admin->id}"
            );
        }
    }

    /**
     * **Validates: Requirements 4.1, 4.3**
     *
     * Supplemental check for Property 9: each dispatched job carries the correct
     * FCM token string, title, and body matching the reactivated notification copy.
     *
     * // Feature: day11-mosque-suspend-reactivate, Property 9
     */
    public function test_property_9_reactivated_listener_dispatches_jobs_with_correct_payload(): void
    {
        // Feature: day11-mosque-suspend-reactivate, Property 9
        Queue::fake();

        $admin  = User::factory()->create();
        $mosque = Mosque::factory()->create([
            'name'          => 'Masjid Al-Test',
            'status'        => MosqueStatus::Active,
            'admin_user_id' => $admin->id,
        ]);

        $tokenCount = rand(1, 5);
        $fcmTokens  = FcmToken::factory()->count($tokenCount)->create([
            'user_id' => $admin->id,
        ]);

        $listener = new SendMosqueReactivatedNotification();
        $event    = new MosqueReactivated($mosque, $admin->id, Carbon::now());

        $listener->handle($event);

        $tokenValues = $fcmTokens->pluck('token')->all();

        Queue::assertPushed(SendFcmNotificationJob::class, function (SendFcmNotificationJob $job) use ($tokenValues, $mosque) {
            return in_array($job->token, $tokenValues, true)
                && $job->title === 'Masjid Anda Diaktifkan Kembali'
                && str_contains($job->body, $mosque->name);
        });
    }
}
