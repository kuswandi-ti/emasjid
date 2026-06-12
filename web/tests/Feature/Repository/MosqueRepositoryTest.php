<?php

namespace Tests\Feature\Repository;

use App\Enums\MosqueStatus;
use App\Models\Donation;
use App\Models\Mosque;
use App\Models\User;
use App\Repositories\MosqueRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MosqueRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected MosqueRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new MosqueRepository();
    }

    public function test_get_total_congregation_count_returns_zero_when_no_members(): void
    {
        // Create mosques with no members
        Mosque::factory()->count(3)->create(['status' => MosqueStatus::Active->value]);

        $count = $this->repository->getTotalCongregationCount();

        $this->assertEquals(0, $count);
    }

    public function test_get_total_congregation_count_counts_only_active_mosque_members(): void
    {
        // Create active mosque with members
        $activeMosque1 = Mosque::factory()->create(['status' => MosqueStatus::Active->value]);
        $activeMosque2 = Mosque::factory()->create(['status' => MosqueStatus::Active->value]);
        
        // Create pending mosque with members (should not be counted)
        $pendingMosque = Mosque::factory()->create(['status' => MosqueStatus::Pending->value]);
        
        // Create suspended mosque with members (should not be counted)
        $suspendedMosque = Mosque::factory()->create(['status' => MosqueStatus::Suspended->value]);

        // Add members to active mosques
        $users1 = User::factory()->count(3)->create();
        foreach ($users1 as $user) {
            $activeMosque1->members()->attach($user->id, ['joined_at' => now()]);
        }

        $users2 = User::factory()->count(2)->create();
        foreach ($users2 as $user) {
            $activeMosque2->members()->attach($user->id, ['joined_at' => now()]);
        }

        // Add members to non-active mosques (should not be counted)
        $pendingUser = User::factory()->create();
        $pendingMosque->members()->attach($pendingUser->id, ['joined_at' => now()]);
        
        $suspendedUser = User::factory()->create();
        $suspendedMosque->members()->attach($suspendedUser->id, ['joined_at' => now()]);

        $count = $this->repository->getTotalCongregationCount();

        // Should only count members from active mosques (3 + 2 = 5)
        $this->assertEquals(5, $count);
    }

    public function test_get_total_congregation_count_aggregates_across_all_active_mosques(): void
    {
        // Create multiple active mosques with varying member counts
        $mosque1 = Mosque::factory()->create(['status' => MosqueStatus::Active->value]);
        $mosque2 = Mosque::factory()->create(['status' => MosqueStatus::Active->value]);
        $mosque3 = Mosque::factory()->create(['status' => MosqueStatus::Active->value]);

        // Add 5 members to mosque1
        $users1 = User::factory()->count(5)->create();
        foreach ($users1 as $user) {
            $mosque1->members()->attach($user->id, ['joined_at' => now()]);
        }

        // Add 10 members to mosque2
        $users2 = User::factory()->count(10)->create();
        foreach ($users2 as $user) {
            $mosque2->members()->attach($user->id, ['joined_at' => now()]);
        }

        // Add 3 members to mosque3
        $users3 = User::factory()->count(3)->create();
        foreach ($users3 as $user) {
            $mosque3->members()->attach($user->id, ['joined_at' => now()]);
        }

        $count = $this->repository->getTotalCongregationCount();

        // Total should be 5 + 10 + 3 = 18
        $this->assertEquals(18, $count);
    }

    public function test_get_total_congregation_count_excludes_rejected_mosques(): void
    {
        // Create active mosque
        $activeMosque = Mosque::factory()->create(['status' => MosqueStatus::Active->value]);
        
        // Create rejected mosque
        $rejectedMosque = Mosque::factory()->create(['status' => MosqueStatus::Rejected->value]);

        // Add members to both
        $activeUser = User::factory()->create();
        $activeMosque->members()->attach($activeUser->id, ['joined_at' => now()]);
        
        $rejectedUser = User::factory()->create();
        $rejectedMosque->members()->attach($rejectedUser->id, ['joined_at' => now()]);

        $count = $this->repository->getTotalCongregationCount();

        // Should only count active mosque member
        $this->assertEquals(1, $count);
    }

    public function test_count_by_status_returns_zero_when_no_mosques_with_status(): void
    {
        // Create mosques with different statuses but not active
        Mosque::factory()->create(['status' => MosqueStatus::Pending->value]);
        Mosque::factory()->create(['status' => MosqueStatus::Suspended->value]);
        Mosque::factory()->create(['status' => MosqueStatus::Rejected->value]);

        $count = $this->repository->countByStatus(MosqueStatus::Active);

        $this->assertEquals(0, $count);
    }

    public function test_count_by_status_returns_correct_count_for_active(): void
    {
        // Create 5 active mosques
        Mosque::factory()->count(5)->create(['status' => MosqueStatus::Active->value]);
        
        // Create mosques with other statuses
        Mosque::factory()->count(2)->create(['status' => MosqueStatus::Pending->value]);
        Mosque::factory()->count(1)->create(['status' => MosqueStatus::Suspended->value]);

        $count = $this->repository->countByStatus(MosqueStatus::Active);

        $this->assertEquals(5, $count);
    }

    public function test_count_by_status_returns_correct_count_for_pending(): void
    {
        // Create 3 pending mosques
        Mosque::factory()->count(3)->create(['status' => MosqueStatus::Pending->value]);
        
        // Create mosques with other statuses
        Mosque::factory()->count(4)->create(['status' => MosqueStatus::Active->value]);
        Mosque::factory()->count(1)->create(['status' => MosqueStatus::Suspended->value]);

        $count = $this->repository->countByStatus(MosqueStatus::Pending);

        $this->assertEquals(3, $count);
    }

    public function test_count_by_status_returns_correct_count_for_suspended(): void
    {
        // Create 2 suspended mosques
        Mosque::factory()->count(2)->create(['status' => MosqueStatus::Suspended->value]);
        
        // Create mosques with other statuses
        Mosque::factory()->count(5)->create(['status' => MosqueStatus::Active->value]);
        Mosque::factory()->count(3)->create(['status' => MosqueStatus::Pending->value]);

        $count = $this->repository->countByStatus(MosqueStatus::Suspended);

        $this->assertEquals(2, $count);
    }

    public function test_count_by_status_returns_correct_count_for_rejected(): void
    {
        // Create 1 rejected mosque
        Mosque::factory()->create(['status' => MosqueStatus::Rejected->value]);
        
        // Create mosques with other statuses
        Mosque::factory()->count(3)->create(['status' => MosqueStatus::Active->value]);
        Mosque::factory()->count(2)->create(['status' => MosqueStatus::Pending->value]);

        $count = $this->repository->countByStatus(MosqueStatus::Rejected);

        $this->assertEquals(1, $count);
    }

    // ─── getPending ───────────────────────────────────────────────────────────

    public function test_get_pending_returns_only_pending_mosques(): void
    {
        Mosque::factory()->count(3)->pending()->create();
        Mosque::factory()->count(2)->create(['status' => MosqueStatus::Active->value]);
        Mosque::factory()->create(['status' => MosqueStatus::Suspended->value]);
        Mosque::factory()->create(['status' => MosqueStatus::Rejected->value]);

        $result = $this->repository->getPending(null, 15);

        $this->assertEquals(3, $result->total());
        $result->each(fn ($mosque) => $this->assertEquals(MosqueStatus::Pending, $mosque->status));
    }

    public function test_get_pending_returns_empty_when_no_pending_mosques(): void
    {
        Mosque::factory()->count(3)->create(['status' => MosqueStatus::Active->value]);

        $result = $this->repository->getPending(null, 15);

        $this->assertEquals(0, $result->total());
    }

    public function test_get_pending_orders_by_created_at_ascending(): void
    {
        $oldest = Mosque::factory()->pending()->create(['created_at' => now()->subDays(10)]);
        $middle = Mosque::factory()->pending()->create(['created_at' => now()->subDays(5)]);
        $newest = Mosque::factory()->pending()->create(['created_at' => now()->subDay()]);

        $result = $this->repository->getPending(null, 15);
        $items = $result->items();

        $this->assertEquals($oldest->id, $items[0]->id);
        $this->assertEquals($middle->id, $items[1]->id);
        $this->assertEquals($newest->id, $items[2]->id);
    }

    public function test_get_pending_searches_by_mosque_name(): void
    {
        Mosque::factory()->pending()->create(['name' => 'Masjid Al-Ikhlas']);
        Mosque::factory()->pending()->create(['name' => 'Masjid Ar-Rahman']);
        Mosque::factory()->pending()->create(['name' => 'Masjid Nurul Iman']);

        $result = $this->repository->getPending('Al-Ikhlas', 15);

        $this->assertEquals(1, $result->total());
        $this->assertEquals('Masjid Al-Ikhlas', $result->items()[0]->name);
    }

    public function test_get_pending_searches_by_city(): void
    {
        Mosque::factory()->pending()->create(['city' => 'Jakarta']);
        Mosque::factory()->pending()->create(['city' => 'Jakarta Selatan']);
        Mosque::factory()->pending()->create(['city' => 'Bandung']);

        $result = $this->repository->getPending('Jakarta', 15);

        $this->assertEquals(2, $result->total());
    }

    public function test_get_pending_searches_by_admin_email(): void
    {
        $adminA = User::factory()->create(['email' => 'imam.masjid@example.com']);
        $adminB = User::factory()->create(['email' => 'other.admin@domain.org']);

        Mosque::factory()->pending()->create(['admin_user_id' => $adminA->id]);
        Mosque::factory()->pending()->create(['admin_user_id' => $adminB->id]);

        $result = $this->repository->getPending('imam.masjid', 15);

        $this->assertEquals(1, $result->total());
        $this->assertEquals($adminA->id, $result->items()[0]->admin_user_id);
    }

    public function test_get_pending_search_is_case_insensitive(): void
    {
        Mosque::factory()->pending()->create(['name' => 'Masjid Al-Ikhlas']);
        Mosque::factory()->pending()->create(['name' => 'Masjid Nurul Iman']);

        $result = $this->repository->getPending('al-ikhlas', 15);

        $this->assertEquals(1, $result->total());
    }

    public function test_get_pending_eager_loads_admin_relationship(): void
    {
        $admin = User::factory()->create();
        Mosque::factory()->pending()->create(['admin_user_id' => $admin->id]);

        $result = $this->repository->getPending(null, 15);
        $mosque = $result->items()[0];

        $this->assertTrue($mosque->relationLoaded('admin'));
        $this->assertEquals($admin->id, $mosque->admin->id);
    }

    public function test_get_pending_includes_members_count(): void
    {
        $mosque = Mosque::factory()->pending()->create();
        $users = User::factory()->count(4)->create();
        foreach ($users as $user) {
            $mosque->members()->attach($user->id, ['joined_at' => now()]);
        }

        $result = $this->repository->getPending(null, 15);
        $item = $result->items()[0];

        $this->assertEquals(4, $item->members_count);
    }

    public function test_get_pending_paginates_results(): void
    {
        Mosque::factory()->count(10)->pending()->create();

        $result = $this->repository->getPending(null, 5);

        $this->assertEquals(5, count($result->items()));
        $this->assertEquals(10, $result->total());
        $this->assertEquals(2, $result->lastPage());
    }

    // ─── findWithRelations ────────────────────────────────────────────────────

    public function test_find_with_relations_returns_null_for_nonexistent_id(): void
    {
        $result = $this->repository->findWithRelations(99999, ['admin']);

        $this->assertNull($result);
    }

    public function test_find_with_relations_returns_correct_mosque(): void
    {
        $mosque = Mosque::factory()->create();

        $result = $this->repository->findWithRelations($mosque->id, []);

        $this->assertNotNull($result);
        $this->assertEquals($mosque->id, $result->id);
    }

    public function test_find_with_relations_loads_admin_relationship(): void
    {
        $admin = User::factory()->create();
        $mosque = Mosque::factory()->create(['admin_user_id' => $admin->id]);

        $result = $this->repository->findWithRelations($mosque->id, ['admin']);

        $this->assertTrue($result->relationLoaded('admin'));
        $this->assertEquals($admin->id, $result->admin->id);
    }

    public function test_find_with_relations_loads_members_relationship(): void
    {
        $mosque = Mosque::factory()->create();
        $users = User::factory()->count(3)->create();
        foreach ($users as $user) {
            $mosque->members()->attach($user->id, ['joined_at' => now()]);
        }

        $result = $this->repository->findWithRelations($mosque->id, ['members']);

        $this->assertTrue($result->relationLoaded('members'));
        $this->assertCount(3, $result->members);
    }

    public function test_find_with_relations_includes_members_count(): void
    {
        $mosque = Mosque::factory()->create();
        $users = User::factory()->count(5)->create();
        foreach ($users as $user) {
            $mosque->members()->attach($user->id, ['joined_at' => now()]);
        }

        $result = $this->repository->findWithRelations($mosque->id, []);

        $this->assertEquals(5, $result->members_count);
    }

    public function test_find_with_relations_includes_total_donations_sum(): void
    {
        $mosque = Mosque::factory()->create(['status' => MosqueStatus::Active->value]);

        // Create confirmed donations with known mosque_receives values
        Donation::factory()->confirmed()->create([
            'mosque_id' => $mosque->id,
            'mosque_receives' => 100_000,
        ]);
        Donation::factory()->confirmed()->create([
            'mosque_id' => $mosque->id,
            'mosque_receives' => 200_000,
        ]);

        $result = $this->repository->findWithRelations($mosque->id, []);

        $this->assertEquals(300_000, $result->total_donations);
    }

    public function test_find_with_relations_total_donations_is_zero_when_no_donations(): void
    {
        $mosque = Mosque::factory()->create();

        $result = $this->repository->findWithRelations($mosque->id, []);

        $this->assertEquals(0, (int) $result->total_donations);
    }

    public function test_find_with_relations_loads_multiple_relations(): void
    {
        $admin = User::factory()->create();
        $mosque = Mosque::factory()->create(['admin_user_id' => $admin->id]);
        $member = User::factory()->create();
        $mosque->members()->attach($member->id, ['joined_at' => now()]);

        $result = $this->repository->findWithRelations($mosque->id, ['admin', 'members']);

        $this->assertTrue($result->relationLoaded('admin'));
        $this->assertTrue($result->relationLoaded('members'));
        $this->assertEquals($admin->id, $result->admin->id);
        $this->assertCount(1, $result->members);
    }
}
