<?php

namespace Tests\Feature\Repository;

use App\Enums\MosqueStatus;
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
}
