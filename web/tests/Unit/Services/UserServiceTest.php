<?php

namespace Tests\Unit\Services;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;
use App\Services\UserService;
use Mockery;
use Tests\TestCase;

/**
 * Property-based unit tests for UserService.
 *
 * Validates: Requirements 2.3, 3.4, 3.7, 4.4
 */
class UserServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Property 2: Validasi Email Duplikat (Create)
    // Validates: Requirements 2.3
    // -------------------------------------------------------------------------

    /**
     * **Validates: Requirements 2.3**
     *
     * Property 2: For any email that is already registered (existsByEmail returns true),
     * createOwner() MUST always throw \InvalidArgumentException with message "Email sudah terdaftar.",
     * regardless of the name or password values.
     */
    public function test_property2_create_rejects_duplicate_email(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $email = 'user' . $i . '_' . rand(1000, 9999) . '@example.com';
            $name  = 'User ' . $i;
            $pass  = str_repeat('x', rand(8, 20));

            /** @var UserRepositoryInterface $repo */
            $repo = Mockery::mock(UserRepositoryInterface::class);
            $repo->shouldReceive('existsByEmail')->andReturn(true);

            $service = new UserService($repo);

            $threw = false;
            try {
                $service->createOwner([
                    'name'     => $name,
                    'email'    => $email,
                    'password' => $pass,
                ]);
            } catch (\InvalidArgumentException $e) {
                $this->assertSame(
                    'Email sudah terdaftar.',
                    $e->getMessage(),
                    "Iteration $i: exception message mismatch"
                );
                $threw = true;
            }

            $this->assertTrue($threw, "Iteration $i: expected InvalidArgumentException for duplicate email '$email'");

            Mockery::close();
        }
    }

    // -------------------------------------------------------------------------
    // Property 4: Password Dipertahankan saat Field Kosong
    // Validates: Requirements 3.4
    // -------------------------------------------------------------------------

    /**
     * **Validates: Requirements 3.4**
     *
     * Property 4: For any update call where the password field is empty string (''),
     * the data array passed to the repository's update() method MUST NOT contain the
     * 'password' key — the old password is preserved.
     */
    public function test_property4_update_with_empty_password_omits_password_key(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $userId        = rand(2, 10000);
            $currentUserId = $userId + rand(1, 500); // always different from $userId
            $name          = 'Name ' . $i;
            $email         = 'update' . $i . '_' . rand(1000, 9999) . '@example.com';

            /** @var User $user */
            $user     = Mockery::mock(User::class)->makePartial();
            $user->id = $userId;

            /** @var UserRepositoryInterface $repo */
            $repo = Mockery::mock(UserRepositoryInterface::class);

            // Email is always unique (no duplicate)
            $repo->shouldReceive('existsByEmail')->andReturn(false);

            // Assert that the 'password' key is NOT present in the update payload
            $repo->shouldReceive('update')
                ->once()
                ->with(
                    $userId,
                    Mockery::on(function (array $data) use ($i) {
                        $this->assertArrayNotHasKey(
                            'password',
                            $data,
                            "Iteration $i: 'password' key must not appear in update data when empty"
                        );
                        return true;
                    })
                )
                ->andReturn(true);

            $service = new UserService($repo);
            $service->updateOwner($user, [
                'name'     => $name,
                'email'    => $email,
                'password' => '',   // empty — must be ignored
            ], $currentUserId);

            Mockery::close();
        }
    }

    // -------------------------------------------------------------------------
    // Property 5 (self-edit): Cegah Self-Edit
    // Validates: Requirements 3.7
    // -------------------------------------------------------------------------

    /**
     * **Validates: Requirements 3.7**
     *
     * Property 5 (self-edit): For any userId that equals currentUserId,
     * updateOwner() MUST always throw \DomainException.
     * The repository's update() must never be called.
     */
    public function test_property5_update_rejects_self_edit(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $selfId = rand(1, 50000);

            /** @var User $user */
            $user     = Mockery::mock(User::class)->makePartial();
            $user->id = $selfId;

            /** @var UserRepositoryInterface $repo */
            $repo = Mockery::mock(UserRepositoryInterface::class);

            // Repository update() must never be called
            $repo->shouldNotReceive('update');

            $service = new UserService($repo);

            $threw = false;
            try {
                $service->updateOwner($user, [
                    'name'     => 'Self ' . $i,
                    'email'    => 'self' . $i . '@example.com',
                    'password' => '',
                ], $selfId); // currentUserId === userId
            } catch (\DomainException $e) {
                $threw = true;
            }

            $this->assertTrue($threw, "Iteration $i: expected DomainException when editing own account (id=$selfId)");

            Mockery::close();
        }
    }

    // -------------------------------------------------------------------------
    // Property 5 (self-delete): Cegah Self-Delete
    // Validates: Requirements 4.4
    // -------------------------------------------------------------------------

    /**
     * **Validates: Requirements 4.4**
     *
     * Property 5 (self-delete): For any userId that equals currentUserId,
     * deleteOwner() MUST always throw \DomainException.
     * The repository's delete() must never be called.
     */
    public function test_property5_delete_rejects_self_delete(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $selfId = rand(1, 50000);

            /** @var User $user */
            $user     = Mockery::mock(User::class)->makePartial();
            $user->id = $selfId;

            // removeRole() should never be called — stub it in case DomainException fires first
            $user->shouldNotReceive('removeRole');

            /** @var UserRepositoryInterface $repo */
            $repo = Mockery::mock(UserRepositoryInterface::class);

            // Repository delete() must never be called
            $repo->shouldNotReceive('delete');

            $service = new UserService($repo);

            $threw = false;
            try {
                $service->deleteOwner($user, $selfId); // currentUserId === userId
            } catch (\DomainException $e) {
                $threw = true;
            }

            $this->assertTrue($threw, "Iteration $i: expected DomainException when deleting own account (id=$selfId)");

            Mockery::close();
        }
    }
}
