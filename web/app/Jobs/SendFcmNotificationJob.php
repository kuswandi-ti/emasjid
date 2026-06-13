<?php

namespace App\Jobs;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendFcmNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $token,
        public string $title,
        public string $body,
        public array  $data = [],
    ) {}

    public function handle(): void
    {
        try {
            $accessToken = $this->getFcmAccessToken();

            $projectId = config('services.fcm.project_id');

            Http::withToken($accessToken)
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                    'message' => [
                        'token'        => $this->token,
                        'notification' => [
                            'title' => $this->title,
                            'body'  => $this->body,
                        ],
                        'data' => $this->data,
                    ],
                ])
                ->throw();
        } catch (\Throwable $e) {
            Log::error('SendFcmNotificationJob: Gagal mengirim FCM', [
                'token' => substr($this->token, 0, 20) . '...',
                'title' => $this->title,
                'error' => $e->getMessage(),
            ]);
            // Tidak re-throw — FCM failure tidak boleh merusak alur utama
        }
    }

    protected function getFcmAccessToken(): string
    {
        $credentials = new ServiceAccountCredentials(
            'https://www.googleapis.com/auth/firebase.messaging',
            config('services.fcm.credentials_path'),
        );
        $token = $credentials->fetchAuthToken();
        return $token['access_token'];
    }
}
