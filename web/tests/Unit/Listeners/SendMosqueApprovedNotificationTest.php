<?php

namespace Tests\Unit\Listeners;

use App\Enums\MosqueStatus;
use App\Events\MosqueApproved;
use App\Listeners\SendMosqueApprovedNotification;
use App\Mail\MosqueApprovedMail;
use App\Models\Mosque;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Unit tests for SendMosqueApprovedNotification listener.
 *
 * Requirements: 4.5, 4.9, 4.10
 */
class SendMosqueApprovedNotificationTest extends TestCase
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
        $listener = new SendMosqueApprovedNotification();

        $this->assertInstanceOf(ShouldQueue::class, $listener);
    }

    /**
     * Test listener class implements ShouldQueue interface at the class level.
     *
     * Requirements: 4.10
     */
    public function test_listener_class_is_declared_as_should_queue(): void
    {
        $interfaces = class_implements(SendMosqueApprovedNotification::class);

        $this->assertArrayHasKey(ShouldQueue::class, $interfaces);
    }

    // -------------------------------------------------------------------------
    // Email sending — Requirements: 4.5
    // -------------------------------------------------------------------------

    /**
     * Test listener sends approval email to the mosque admin.
     *
     * Requirements: 4.5
     */
    public function test_handle_sends_email_to_mosque_admin(): void
    {
        Mail::fake();

        // Arrange: create an admin user and a mosque with that admin
        $admin = User::factory()->create(['email' => 'admin@mosque.test']);
        $mosque = Mosque::factory()->create([
            'status' => MosqueStatus::Active,
            'admin_user_id' => $admin->id,
            'invitation_code' => 'ABC123',
            'approved_at' => now(),
        ]);

        $event = new MosqueApproved($mosque, 1, Carbon::now());
        $listener = new SendMosqueApprovedNotification();

        // Act
        $listener->handle($event);

        // Assert — email sent to the mosque admin
        Mail::assertSent(MosqueApprovedMail::class, function (MosqueApprovedMail $mail) use ($admin) {
            return $mail->hasTo($admin->email);
        });
    }

    /**
     * Test listener sends exactly one email per event.
     *
     * Requirements: 4.5
     */
    public function test_handle_sends_exactly_one_email(): void
    {
        Mail::fake();

        $admin = User::factory()->create();
        $mosque = Mosque::factory()->create([
            'status' => MosqueStatus::Active,
            'admin_user_id' => $admin->id,
            'invitation_code' => 'XYZ789',
        ]);

        $event = new MosqueApproved($mosque, 1, Carbon::now());
        $listener = new SendMosqueApprovedNotification();

        $listener->handle($event);

        Mail::assertSentCount(1);
    }

    /**
     * Test the sent email is the correct mailable class.
     *
     * Requirements: 4.5
     */
    public function test_handle_sends_mosque_approved_mail_instance(): void
    {
        Mail::fake();

        $admin = User::factory()->create();
        $mosque = Mosque::factory()->create([
            'status' => MosqueStatus::Active,
            'admin_user_id' => $admin->id,
        ]);

        $event = new MosqueApproved($mosque, 1, Carbon::now());
        $listener = new SendMosqueApprovedNotification();

        $listener->handle($event);

        Mail::assertSent(MosqueApprovedMail::class);
    }

    /**
     * Test listener sends email with correct mosque data.
     *
     * Requirements: 4.5, 4.6
     */
    public function test_handle_sends_email_with_correct_mosque_and_invitation_code(): void
    {
        Mail::fake();

        $admin = User::factory()->create();
        $mosque = Mosque::factory()->create([
            'name' => 'Masjid Al-Ikhlas',
            'status' => MosqueStatus::Active,
            'admin_user_id' => $admin->id,
            'invitation_code' => 'CODE99',
        ]);

        $approvedAt = Carbon::parse('2024-06-01 09:00:00');
        $event = new MosqueApproved($mosque, 1, $approvedAt);
        $listener = new SendMosqueApprovedNotification();

        $listener->handle($event);

        Mail::assertSent(MosqueApprovedMail::class, function (MosqueApprovedMail $mail) use ($mosque, $approvedAt) {
            return $mail->mosque->id === $mosque->id
                && $mail->invitationCode === 'CODE99'
                && $mail->approvalDate->eq($approvedAt);
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
        $mosque = Mosque::factory()->create([
            'status' => MosqueStatus::Active,
            'admin_user_id' => $admin->id,
        ]);

        // Remove the mosque first (to satisfy the FK), then hard-delete the admin.
        // This simulates the admin being deleted before the queued listener runs.
        $mosque->forceDelete();
        $admin->forceDelete();

        // Reconstruct an in-memory mosque that points to the now-deleted admin.
        $orphanMosque = new Mosque(['admin_user_id' => $admin->id]);
        $orphanMosque->setRelation('admin', null);

        $event = new MosqueApproved($orphanMosque, 1, Carbon::now());
        $listener = new SendMosqueApprovedNotification();

        $listener->handle($event);

        Mail::assertNothingSent();
    }
}
