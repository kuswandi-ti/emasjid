<?php

namespace App\Listeners;

use App\Events\MosqueReactivated;
use App\Jobs\SendFcmNotificationJob;
use App\Models\FcmToken;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendMosqueReactivatedNotification implements ShouldQueue
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
     * Loads FCM tokens for the mosque admin and dispatches a
     * SendFcmNotificationJob per token to notify the admin that
     * their mosque has been reactivated.
     *
     * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5
     */
    public function handle(MosqueReactivated $event): void
    {
        $mosque       = $event->mosque;
        $adminUserId  = $mosque->admin_user_id;

        if (! $adminUserId) {
            Log::warning('SendMosqueReactivatedNotification: admin_user_id null', [
                'mosque_id' => $mosque->id,
            ]);
            return;
        }

        $tokens = FcmToken::where('user_id', $adminUserId)->get();

        if ($tokens->isEmpty()) {
            Log::warning("No FCM token found for mosque admin user_id={$adminUserId}");
            return;
        }

        foreach ($tokens as $fcmToken) {
            SendFcmNotificationJob::dispatch(
                token: $fcmToken->token,
                title: 'Masjid Anda Diaktifkan Kembali',
                body:  "Masjid {$mosque->name} telah diaktifkan kembali. Masjid Anda kini dapat dikelola kembali.",
            );
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(MosqueReactivated $event, \Throwable $exception): void
    {
        Log::error('SendMosqueReactivatedNotification: Listener permanently failed after all retries', [
            'mosque_id'  => $event->mosque->id,
            'mosque_name' => $event->mosque->name,
            'error'      => $exception->getMessage(),
        ]);
    }
}
