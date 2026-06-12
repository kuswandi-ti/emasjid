<?php

namespace App\Listeners;

use App\Events\MosqueRejected;
use App\Mail\MosqueRejectedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendMosqueRejectedNotification implements ShouldQueue
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
     * Loads the mosque admin, then sends a rejection email containing:
     * - Mosque name
     * - Rejection date
     * - Rejection reason
     * - Support contact link
     *
     * Requirements: 4.4, 4.7, 4.8, 4.9
     */
    public function handle(MosqueRejected $event): void
    {
        $mosque = $event->mosque;

        // Load the mosque admin relationship if not already loaded
        $admin = $mosque->admin ?? $mosque->load('admin')->admin;

        if (! $admin) {
            Log::error('SendMosqueRejectedNotification: Admin user not found for mosque', [
                'mosque_id'  => $mosque->id,
                'mosque_name' => $mosque->name,
            ]);

            return;
        }

        try {
            Mail::to($admin->email)->send(new MosqueRejectedMail(
                mosque: $mosque,
                rejectionDate: $event->rejectedAt,
                rejectionReason: $event->rejectionReason,
            ));
        } catch (\Throwable $e) {
            Log::error('SendMosqueRejectedNotification: Failed to send rejection email', [
                'mosque_id'   => $mosque->id,
                'mosque_name' => $mosque->name,
                'admin_email' => $admin->email,
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
            ]);

            // Re-throw so the queue worker can retry the job
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(MosqueRejected $event, \Throwable $exception): void
    {
        Log::error('SendMosqueRejectedNotification: Listener permanently failed after all retries', [
            'mosque_id'  => $event->mosque->id,
            'mosque_name' => $event->mosque->name,
            'error'      => $exception->getMessage(),
        ]);
    }
}
