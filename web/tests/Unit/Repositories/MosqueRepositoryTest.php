<?php

namespace Tests\Unit\Repositories;

use App\Models\Mosque;
use App\Repositories\MosqueRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for MosqueRepository.
 *
 * Requirements: 1.6
 */
class MosqueRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private MosqueRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new MosqueRepository;
    }

    // -------------------------------------------------------------------------
    // existsByInvitationCode() — Requirements: 1.6
    // -------------------------------------------------------------------------

    /**
     * Test existsByInvitationCode returns true when the given code exists in the mosques table.
     *
     * Requirements: 1.6
     */
    public function test_exists_by_invitation_code_returns_true_when_code_exists(): void
    {
        $code = 'ABC123';

        Mosque::factory()->create(['invitation_code' => $code]);

        $result = $this->repository->existsByInvitationCode($code);

        $this->assertTrue($result);
    }

    /**
     * Test existsByInvitationCode returns false when the given code does not exist in the mosques table.
     *
     * Requirements: 1.6
     */
    public function test_exists_by_invitation_code_returns_false_when_code_does_not_exist(): void
    {
        $result = $this->repository->existsByInvitationCode('NONEXISTENT');

        $this->assertFalse($result);
    }
}
