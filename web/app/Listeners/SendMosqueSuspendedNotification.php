<?php

namespace App\Listeners;

use App\Events\MosqueSuspended;
use App\Jobs\SendFcmNotificationJob;
use App\Models\FcmToken;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendMosqueSuspendedNotification implements ShouldQueue
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
     * Loads all FCM tokens for the mosque admin, then dispatches
     * SendFcmNotificationJob per token. Returns early with a log
     * warning if admin_user_id is null or has no registered tokens.
     *
     * Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6
     */
    public function handle(MosqueSuspended $event): void
    {
        $mosque      = $event->mosque;
        $adminUserId = $mosque->admin_user_id;

        if (! $adminUserId) {
            Log::warning('SendMosqueSuspendedNotification: admin_user_id null', [
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
                title: 'Masjid Anda Ditangguhkan',
                body:  "Masjid {$mosque->name} telah ditangguhkan. Silakan hubungi admin platform untuk informasi lebih lanjut.",
            );
        }
    }
}
