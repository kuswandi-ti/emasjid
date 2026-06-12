<?php

namespace Tests\Feature\Owner;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tests\Traits\CreatesTestMosque;

/**
 * Feature tests for Owner User Management (CRUD akun owner).
 *
 * Validates: Requirements 1.2, 2.4, 2.5, 3.1, 3.4, 3.7, 4.4
 */
class UserFeatureTest extends TestCase
{
    use CreatesTestMosque, RefreshDatabase;

    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        // The authenticated owner — always present as the baseline super-admin
        $this->owner = $this->createSuperAdmin();
    }

    // ─── Property 1: DataTable Hanya Menampilkan Owner ────────────────────────

    /**
     * Property 1: For any N additional super-admins + M non-super-admins in the DB,
     * the DataTable endpoint SHALL return exactly (ownerCount) super-admin rows.
     *
     * Validates: Requirements 1.2
     */
    public function test_property1_datatable_only_shows_owners(): void
    {
        // ownerCount starts at 1 because $this->owner is already in DB
        $ownerCount = 1;

        for ($i = 0; $i < 30; $i++) {
            $n = rand(1, 3); // extra super-admins to add this iteration
            $m = rand(1, 3); // non-owners to add this iteration

            for ($j = 0; $j < $n; $j++) {
                $this->createSuperAdmin();
            }
            for ($j = 0; $j < $m; $j++) {
                $this->createUserWithNoRole();
            }

            $ownerCount += $n;

            $response = $this->actingAs($this->owner)
                ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
                ->getJson(route('owner.users.index'));

            $response->assertOk();

            $data = $response->json();

            $this->assertEquals(
                $ownerCount,
                $data['recordsTotal'],
                "Iteration $i: expected {$ownerCount} super-admin records, got {$data['recordsTotal']}"
            );

            // Verify none of the returned rows belong to non-owners
            foreach ($data['data'] as $row) {
                $user = User::find($row['id']);
                $this->assertNotNull($user, "Row user id={$row['id']} should exist in DB");
                $this->assertTrue(
                    $user->hasRole('super-admin'),
                    "Row user id={$row['id']} should have role super-admin"
                );
            }
        }
    }

    // ─── Property 3: Validasi Password Pendek ────────────────────────────────

    /**
     * Property 3: For any password with length 1–7 (inclusive), the store endpoint
     * SHALL reject the request with a validation error on the 'password' field.
     *
     * Validates: Requirements 2.4
     */
    public function test_property3_short_password_rejected_on_store(): void
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        for ($length = 1; $length <= 7; $length++) {
            for ($variation = 0; $variation < 10; $variation++) {
                // Generate a random string of exactly $length characters
                $password = '';
                for ($k = 0; $k < $length; $k++) {
                    $password .= $chars[rand(0, strlen($chars) - 1)];
                }

                $response = $this->actingAs($this->owner)
                    ->post(route('owner.users.store'), [
                        'name'     => 'Test User ' . $variation,
                        'email'    => "test_{$length}_{$variation}_" . uniqid() . '@example.com',
                        'password' => $password,
                    ]);

                $response->assertSessionHasErrors('password',
                    "Password of length {$length} (variation {$variation}) should trigger validation error"
                );
            }
        }
    }

    // ─── Property 4: Password Dipertahankan (feature) ────────────────────────

    /**
     * Property 4: For any owner account, sending an update with an empty password field
     * SHALL leave the stored password hash unchanged (Hash::check still works with old pw).
     *
     * Validates: Requirements 3.4
     */
    public function test_property4_password_retained_when_empty_on_update(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $originalPassword = 'Secret' . rand(10000, 99999) . '!';

            // Create a target super-admin with a known password
            $target = User::factory()->create([
                'password' => Hash::make($originalPassword),
            ]);
            $target->assignRole('super-admin');

            // Send update WITHOUT new password (empty string)
            $newName  = 'Updated Name ' . $i;
            $newEmail = "updated_{$i}_" . uniqid() . '@example.com';

            $response = $this->actingAs($this->owner)
                ->put(route('owner.users.update', $target), [
                    'name'     => $newName,
                    'email'    => $newEmail,
                    'password' => '',
                ]);

            $response->assertRedirect(route('owner.users.index'));

            // Reload user from DB
            $target->refresh();

            $this->assertEquals($newName, $target->name,
                "Iteration $i: name should have been updated");
            $this->assertEquals($newEmail, $target->email,
                "Iteration $i: email should have been updated");

            $this->assertTrue(
                Hash::check($originalPassword, $target->password),
                "Iteration $i: original password should still be valid after update without new password"
            );
        }
    }

    // ─── Property 5: Cegah Self-Edit dan Self-Delete ─────────────────────────

    /**
     * Property 5a: For any authenticated owner, attempting to edit their own account
     * SHALL always result in a session error and no data changes in DB.
     *
     * Validates: Requirements 3.7
     */
    public function test_property5a_prevent_self_edit(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $originalName  = $this->owner->name;
            $originalEmail = $this->owner->email;

            $response = $this->actingAs($this->owner)
                ->put(route('owner.users.update', $this->owner), [
                    'name'     => 'New Name ' . $i,
                    'email'    => "newself_{$i}@example.com",
                    'password' => '',
                ]);

            $this->assertTrue(
                $response->getSession()->has('error'),
                "Iteration $i: self-edit should set session error"
            );

            // Reload and verify name/email are unchanged
            $this->owner->refresh();

            $this->assertEquals($originalName, $this->owner->name,
                "Iteration $i: owner name should not change on self-edit");
            $this->assertEquals($originalEmail, $this->owner->email,
                "Iteration $i: owner email should not change on self-edit");
        }
    }

    /**
     * Property 5b: For any authenticated owner, attempting to delete their own account
     * SHALL always result in a session error and the user record still exists in DB.
     *
     * Validates: Requirements 4.4
     */
    public function test_property5b_prevent_self_delete(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $response = $this->actingAs($this->owner)
                ->delete(route('owner.users.destroy', $this->owner));

            $this->assertTrue(
                $response->getSession()->has('error'),
                "Iteration $i: self-delete should set session error"
            );

            // Verify the owner still exists in DB (not soft-deleted)
            $this->assertDatabaseHas('users', [
                'id'         => $this->owner->id,
                'deleted_at' => null,
            ]);
        }
    }

    // ─── Example Tests: Requirements 2.5 and 3.1 ─────────────────────────────

    /**
     * After a successful store, the controller SHALL redirect to the index page.
     *
     * Validates: Requirements 2.5
     */
    public function test_redirects_to_index_after_create(): void
    {
        $response = $this->actingAs($this->owner)
            ->post(route('owner.users.store'), [
                'name'     => 'New Admin',
                'email'    => 'newadmin@example.com',
                'password' => 'password123',
            ]);

        $response->assertRedirect(route('owner.users.index'));
        $response->assertSessionHas('success');
    }

    /**
     * The edit form SHALL be pre-populated with the stored name and email values.
     *
     * Validates: Requirements 3.1
     */
    public function test_edit_form_prepopulated_with_stored_values(): void
    {
        $target = User::factory()->create([
            'name'  => 'Existing Admin',
            'email' => 'existing@example.com',
        ]);
        $target->assignRole('super-admin');

        $response = $this->actingAs($this->owner)
            ->get(route('owner.users.edit', $target));

        $response->assertOk();
        $response->assertViewHas('user');

        $viewUser = $response->viewData('user');

        $this->assertEquals('Existing Admin', $viewUser->name);
        $this->assertEquals('existing@example.com', $viewUser->email);
    }
}
