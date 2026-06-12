<?php

namespace App\Listeners;

use App\Events\MosqueApproved;
use App\Mail\MosqueApprovedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendMosqueApprovedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the queued listener may be attempted.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * Handle the event.
     *
     * Loads the mosque admin, then sends an approval email containing:
     * - Mosque name
     * - Approval date
     * - Invitation code (for congregation members to join)
     * - Admin login link
     *
     * Requirements: 4.3, 4.5, 4.6, 4.9
     */
    public function handle(MosqueApproved $event): void
    {
        $mosque = $event->mosque;

        // Load the mosque admin relationship if not already loaded
        $admin = $mosque->admin ?? $mosque->load('admin')->admin;

        if (! $admin) {
            Log::error('SendMosqueApprovedNotification: Admin user not found for mosque', [
                'mosque_id' => $mosque->id,
                'mosque_name' => $mosque->name,
            ]);

            return;
        }

        try {
            Mail::to($admin->email)->send(new MosqueApprovedMail(
                mosque: $mosque,
                approvalDate: $event->approvedAt,
                invitationCode: $mosque->invitation_code ?? '',
            ));
        } catch (\Throwable $e) {
            Log::error('SendMosqueApprovedNotification: Failed to send approval email', [
                'mosque_id'  => $mosque->id,
                'mosque_name' => $mosque->name,
                'admin_email' => $admin->email,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);

            // Re-throw so the queue worker can retry the job
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(MosqueApproved $event, \Throwable $exception): void
    {
        Log::error('SendMosqueApprovedNotification: Listener permanently failed after all retries', [
            'mosque_id'  => $event->mosque->id,
            'mosque_name' => $event->mosque->name,
            'error'      => $exception->getMessage(),
        ]);
    }
}
