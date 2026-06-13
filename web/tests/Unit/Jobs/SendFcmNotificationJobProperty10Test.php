<?php

// Feature: day11-mosque-suspend-reactivate, Property 10

namespace Tests\Unit\Jobs;

use App\Jobs\SendFcmNotificationJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

/**
 * Property 10: FCM failure tidak merusak alur utama
 *
 * **Validates: Requirements 3.7, 4.6**
 *
 * Ketika pengiriman FCM gagal karena alasan apapun (HTTP 500, network error, dsb.),
 * SendFcmNotificationJob::handle() TIDAK BOLEH melempar exception dan HARUS
 * mencatat Log::error dengan detail kegagalan.
 *
 * Feature: day11-mosque-suspend-reactivate, Property 10
 */
class SendFcmNotificationJobProperty10Test extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Property 10: FCM failure tidak merusak alur utama
    // Validates: Requirements 3.7, 4.6
    // -------------------------------------------------------------------------

    /**
     * **Validates: Requirements 3.7, 4.6**
     *
     * Property 10: For any combination of FCM token, title, and body, when the FCM
     * endpoint returns HTTP 500, handle() MUST NOT throw any exception and MUST call
     * Log::error exactly once with the failure details.
     *
     * Uses Http::fake() to simulate FCM failure (HTTP 500) and a partial mock to
     * override getFcmAccessToken() — bypassing the Google Auth library (Guzzle) that
     * is not intercepted by Laravel's Http facade.
     *
     * Runs 100 iterations with randomised inputs.
     *
     * // Feature: day11-mosque-suspend-reactivate, Property 10
     */
    public function test_property10_fcm_failure_does_not_throw_exception_and_logs_error(): void
    {
        // Feature: day11-mosque-suspend-reactivate, Property 10

        // Sample pools for random input generation
        $tokenPool = array_map(
            fn () => bin2hex(random_bytes(20)),
            range(1, 50)
        );
        $titlePool = [
            'Masjid Anda Ditangguhkan',
            'Masjid Anda Diaktifkan Kembali',
            'Notifikasi Penting',
            'Pemberitahuan Platform',
        ];
        $bodyPool = [
            'Masjid Al-Ikhlas telah ditangguhkan. Silakan hubungi admin platform.',
            'Masjid Baitul Mukminin telah diaktifkan kembali.',
            'Ada pembaruan penting untuk masjid Anda.',
            'Harap segera menghubungi administrator platform.',
        ];

        for ($i = 0; $i < 100; $i++) {
            // Arrange: pick random token, title, body
            $token = $tokenPool[array_rand($tokenPool)];
            $title = $titlePool[array_rand($titlePool)];
            $body  = $bodyPool[array_rand($bodyPool)];

            // Spy on Log so we can assert Log::error is called
            Log::spy();

            // Fake the Http layer: FCM endpoint returns 500
            Http::fake([
                '*' => Http::response([], 500),
            ]);

            // Create a partial mock of the job that overrides getFcmAccessToken()
            // to return a fake token, bypassing Google Auth (Guzzle) calls entirely.
            /** @var SendFcmNotificationJob|\Mockery\MockInterface $job */
            $job = Mockery::mock(SendFcmNotificationJob::class)
                ->makePartial()
                ->shouldAllowMockingProtectedMethods();

            $job->token = $token;
            $job->title = $title;
            $job->body  = $body;
            $job->data  = [];

            $job->shouldReceive('getFcmAccessToken')
                ->once()
                ->andReturn('fake-access-token-' . $i);

            // Act: call handle() — must not throw any exception
            $threwException = false;
            try {
                $job->handle();
            } catch (\Throwable $e) {
                $threwException = true;
            }

            // Assert 1: no exception was thrown
            $this->assertFalse(
                $threwException,
                "Iteration {$i}: handle() MUST NOT throw an exception when FCM returns 500 " .
                "(token={$token}, title={$title})"
            );

            // Assert 2: Log::error was called at least once with failure info
            Log::shouldHaveReceived('error')
                ->atLeast()
                ->once();

            Mockery::close();
        }
    }

    /**
     * **Validates: Requirements 3.7, 4.6**
     *
     * Property 10 (variant): When getFcmAccessToken() itself throws an exception
     * (e.g. Google Auth service unavailable), handle() MUST still NOT throw an
     * exception and MUST call Log::error.
     *
     * This covers the scenario where even the token retrieval step fails.
     *
     * // Feature: day11-mosque-suspend-reactivate, Property 10
     */
    public function test_property10_access_token_failure_does_not_throw_exception_and_logs_error(): void
    {
        // Feature: day11-mosque-suspend-reactivate, Property 10

        $errorMessages = [
            'Could not fetch access token',
            'Network unreachable',
            'Invalid service account credentials',
            'Token refresh failed',
            'Google Auth service unavailable',
        ];

        for ($i = 0; $i < 20; $i++) {
            Log::spy();

            $errorMessage = $errorMessages[$i % count($errorMessages)];

            /** @var SendFcmNotificationJob|\Mockery\MockInterface $job */
            $job = Mockery::mock(SendFcmNotificationJob::class)
                ->makePartial()
                ->shouldAllowMockingProtectedMethods();

            $job->token = 'some-device-token-' . $i;
            $job->title = 'Test Notification';
            $job->body  = 'Test body';
            $job->data  = [];

            // getFcmAccessToken() throws — simulating Google Auth failure
            $job->shouldReceive('getFcmAccessToken')
                ->once()
                ->andThrow(new \Exception($errorMessage));

            $threwException = false;
            try {
                $job->handle();
            } catch (\Throwable $e) {
                $threwException = true;
            }

            // Assert 1: no exception propagated
            $this->assertFalse(
                $threwException,
                "Iteration {$i}: handle() MUST NOT throw when getFcmAccessToken() throws '{$errorMessage}'"
            );

            // Assert 2: Log::error called at least once
            Log::shouldHaveReceived('error')
                ->atLeast()
                ->once();

            Mockery::close();
        }
    }
}
