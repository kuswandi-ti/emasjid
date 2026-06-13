<?php

namespace Tests\Feature\Owner;

// Feature: day11-mosque-suspend-reactivate, Property 12

use App\Enums\MosqueStatus;
use App\Models\Mosque;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Traits\CreatesTestMosque;

/**
 * Property 12: Rendering tombol aksi sesuai status
 *
 * For every MosqueStatus value, renders the owner.mosques.show view and asserts
 * exactly the correct set of action buttons is present — and that buttons
 * belonging to other statuses are absent.
 *
 * Validates: Requirements 6.1, 6.2, 6.3, 6.4
 */
class MosqueShowActionButtonsPropertyTest extends TestCase
{
    use CreatesTestMosque, RefreshDatabase;

    // ─────────────────────────────────────────────────────────────────────────
    // Setup
    // ─────────────────────────────────────────────────────────────────────────

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET the owner.mosques.show page as super-admin for the given mosque.
     */
    private function getShowPage(Mosque $mosque, \App\Models\User $superAdmin): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($superAdmin)
            ->get(route('owner.mosques.show', $mosque->id));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Button ID selectors (used to distinguish button elements from JS strings)
    //
    // The Blade view assigns id="btn-approve", id="btn-reject", id="btn-suspend",
    // id="btn-reactivate" to each action button. The @push('scripts') section
    // always renders JS functions that contain these button texts as string
    // literals (e.g. 'Ya, Tangguhkan'), so we assert on the button IDs to
    // distinguish the actual rendered button elements from inline JS strings.
    // ─────────────────────────────────────────────────────────────────────────

    // ─────────────────────────────────────────────────────────────────────────
    // Requirement 6.1 — Pending status
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Property 12 — Pending status:
     * SHOWS "Setujui Pendaftaran" (btn-approve) and "Tolak Pendaftaran" (btn-reject),
     * DOES NOT show btn-suspend or btn-reactivate.
     *
     * Validates: Requirement 6.1
     *
     * // Feature: day11-mosque-suspend-reactivate, Property 12
     */
    public function test_pending_mosque_shows_approve_and_reject_buttons_only(): void
    {
        // Feature: day11-mosque-suspend-reactivate, Property 12
        Event::fake();

        $superAdmin = $this->createSuperAdmin();

        for ($i = 0; $i < 100; $i++) {
            $mosque = Mosque::factory()->pending()->create();

            $response = $this->getShowPage($mosque, $superAdmin);

            $response->assertOk();

            // MUST be present — button elements with their IDs
            $response->assertSee('id="btn-approve"', escape: false);
            $response->assertSee('id="btn-reject"', escape: false);
            $response->assertSee('Setujui Pendaftaran', escape: false);
            $response->assertSee('Tolak Pendaftaran', escape: false);

            // MUST NOT be present — suspend and reactivate button elements
            $response->assertDontSee('id="btn-suspend"', escape: false);
            $response->assertDontSee('id="btn-reactivate"', escape: false);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Requirement 6.2 — Active status
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Property 12 — Active status:
     * SHOWS btn-suspend ("Tangguhkan"),
     * DOES NOT show btn-approve, btn-reject, or btn-reactivate.
     *
     * Validates: Requirement 6.2
     *
     * // Feature: day11-mosque-suspend-reactivate, Property 12
     */
    public function test_active_mosque_shows_suspend_button_only(): void
    {
        // Feature: day11-mosque-suspend-reactivate, Property 12
        Event::fake();

        $superAdmin = $this->createSuperAdmin();

        for ($i = 0; $i < 100; $i++) {
            $mosque = Mosque::factory()->create(); // default status = active

            $response = $this->getShowPage($mosque, $superAdmin);

            $response->assertOk();

            // MUST be present — suspend button element
            $response->assertSee('id="btn-suspend"', escape: false);

            // MUST NOT be present — approve, reject, reactivate button elements
            $response->assertDontSee('id="btn-approve"', escape: false);
            $response->assertDontSee('id="btn-reject"', escape: false);
            $response->assertDontSee('id="btn-reactivate"', escape: false);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Requirement 6.3 — Suspended status
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Property 12 — Suspended status:
     * SHOWS btn-reactivate ("Aktifkan Kembali"),
     * DOES NOT show btn-approve, btn-reject, or btn-suspend.
     *
     * Validates: Requirement 6.3
     *
     * // Feature: day11-mosque-suspend-reactivate, Property 12
     */
    public function test_suspended_mosque_shows_reactivate_button_only(): void
    {
        // Feature: day11-mosque-suspend-reactivate, Property 12
        Event::fake();

        $superAdmin = $this->createSuperAdmin();

        for ($i = 0; $i < 100; $i++) {
            $mosque = Mosque::factory()->suspended()->create();

            $response = $this->getShowPage($mosque, $superAdmin);

            $response->assertOk();

            // MUST be present — reactivate button element
            $response->assertSee('id="btn-reactivate"', escape: false);
            $response->assertSee('Aktifkan Kembali', escape: false);

            // MUST NOT be present — approve, reject, suspend button elements
            $response->assertDontSee('id="btn-approve"', escape: false);
            $response->assertDontSee('id="btn-reject"', escape: false);
            $response->assertDontSee('id="btn-suspend"', escape: false);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Requirement 6.4 — Rejected status
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Property 12 — Rejected status:
     * DOES NOT show any action button (no btn-approve, btn-reject, btn-suspend,
     * btn-reactivate).
     *
     * Validates: Requirement 6.4
     *
     * // Feature: day11-mosque-suspend-reactivate, Property 12
     */
    public function test_rejected_mosque_shows_no_action_buttons(): void
    {
        // Feature: day11-mosque-suspend-reactivate, Property 12
        Event::fake();

        $superAdmin = $this->createSuperAdmin();

        for ($i = 0; $i < 100; $i++) {
            $mosque = Mosque::factory()->rejected()->create();

            $response = $this->getShowPage($mosque, $superAdmin);

            $response->assertOk();

            // NONE of the action button elements must appear
            $response->assertDontSee('id="btn-approve"', escape: false);
            $response->assertDontSee('id="btn-reject"', escape: false);
            $response->assertDontSee('id="btn-suspend"', escape: false);
            $response->assertDontSee('id="btn-reactivate"', escape: false);
        }
    }
}
