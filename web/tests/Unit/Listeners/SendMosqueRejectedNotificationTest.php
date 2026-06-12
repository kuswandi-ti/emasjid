<?php

namespace Tests\Unit\Listeners;

use App\Enums\MosqueStatus;
use App\Events\MosqueRejected;
use App\Listeners\SendMosqueRejectedNotification;
use App\Mail\MosqueRejectedMail;
use App\Models\Mosque;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Unit tests for SendMosqueRejectedNotification listener.
 *
 * Requirements: 4.7, 4.9, 4.10
 */
class SendMosqueRejectedNotificationTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // ShouldQueue — Requirements: 4.10
    // -------------------------------------------------------------------------

    /**
     * Test listener implements ShouldQueue for asynchronous processing.
     *
     * Requirements: 4.10
     */
    public function test_listener_implements_should_queue(): void
    {
        $listener = new SendMosqueRejectedNotification();

        $this->assertInstanceOf(ShouldQueue::class, $listener);
    }

    /**
     * Test listener class is declared as ShouldQueue at the class level.
     *
     * Requirements: 4.10
     */
    public function test_listener_class_is_declared_as_should_queue(): void
    {
        $interfaces = class_implements(SendMosqueRejectedNotification::class);

        $this->assertArrayHasKey(ShouldQueue::class, $interfaces);
    }

    // -------------------------------------------------------------------------
    // Email sending — Requirements: 4.7
    // -------------------------------------------------------------------------

    /**
     * Test listener sends rejection email to the mosque admin.
     *
     * Requirements: 4.7
     */
    public function test_handle_sends_email_to_mosque_admin(): void
    {
        Mail::fake();

        // Arrange: create admin user and mosque
        $admin = User::factory()->create(['email' => 'admin@mosque.test']);
        $mosque = Mosque::factory()->rejected()->create([
            'admin_user_id' => $admin->id,
        ]);

        $event = new MosqueRejected($mosque, 1, 'Dokumentasi tidak lengkap', Carbon::now());
        $listener = new SendMosqueRejectedNotification();

        // Act
        $listener->handle($event);

        // Assert — email sent to the mosque admin
        Mail::assertSent(MosqueRejectedMail::class, function (MosqueRejectedMail $mail) use ($admin) {
            return $mail->hasTo($admin->email);
        });
    }

    /**
     * Test listener sends exactly one email per event.
     *
     * Requirements: 4.7
     */
    public function test_handle_sends_exactly_one_email(): void
    {
        Mail::fake();

        $admin = User::factory()->create();
        $mosque = Mosque::factory()->rejected()->create([
            'admin_user_id' => $admin->id,
        ]);

        $event = new MosqueRejected($mosque, 1, 'Alasan penolakan yang valid', Carbon::now());
        $listener = new SendMosqueRejectedNotification();

        $listener->handle($event);

        Mail::assertSentCount(1);
    }

    /**
     * Test the sent email is the correct mailable class.
     *
     * Requirements: 4.7
     */
    public function test_handle_sends_mosque_rejected_mail_instance(): void
    {
        Mail::fake();

        $admin = User::factory()->create();
        $mosque = Mosque::factory()->rejected()->create([
            'admin_user_id' => $admin->id,
        ]);

        $event = new MosqueRejected($mosque, 1, 'Alasan penolakan', Carbon::now());
        $listener = new SendMosqueRejectedNotification();

        $listener->handle($event);

        Mail::assertSent(MosqueRejectedMail::class);
    }

    /**
     * Test listener sends email with correct mosque data and rejection reason.
     *
     * Requirements: 4.7, 4.8
     */
    public function test_handle_sends_email_with_correct_mosque_and_rejection_reason(): void
    {
        Mail::fake();

        $admin = User::factory()->create();
        $mosque = Mosque::factory()->rejected()->create([
            'name' => 'Masjid Baitul Mukminin',
            'admin_user_id' => $admin->id,
        ]);

        $rejectedAt = Carbon::parse('2024-06-15 14:00:00');
        $rejectionReason = 'Dokumen pendukung tidak lengkap dan tidak valid';
        $event = new MosqueRejected($mosque, 1, $rejectionReason, $rejectedAt);
        $listener = new SendMosqueRejectedNotification();

        $listener->handle($event);

        Mail::assertSent(MosqueRejectedMail::class, function (MosqueRejectedMail $mail) use ($mosque, $rejectionReason, $rejectedAt) {
            return $mail->mosque->id === $mosque->id
                && $mail->rejectionReason === $rejectionReason
                && $mail->rejectionDate->eq($rejectedAt);
        });
    }

    /**
     * Test listener does not send email when mosque admin relationship resolves to null.
     *
     * Simulates the case where the admin user has been deleted or the relationship
     * is otherwise unresolvable at notification time.
     *
     * Requirements: 4.9
     */
    public function test_handle_does_not_send_email_when_admin_is_missing(): void
    {
        Mail::fake();

        $admin = User::factory()->create();
        $mosque = Mosque::factory()->rejected()->create([
            'admin_user_id' => $admin->id,
        ]);

        // Remove the mosque first (to satisfy the FK), then hard-delete the admin.
        // This simulates the admin being deleted before the queued listener runs.
        $mosque->forceDelete();
        $admin->forceDelete();

        // Reconstruct an in-memory mosque that points to the now-deleted admin.
        $orphanMosque = new Mosque(['admin_user_id' => $admin->id]);
        $orphanMosque->setRelation('admin', null);

        $event = new MosqueRejected($orphanMosque, 1, 'Alasan penolakan', Carbon::now());
        $listener = new SendMosqueRejectedNotification();

        $listener->handle($event);

        Mail::assertNothingSent();
    }
}
