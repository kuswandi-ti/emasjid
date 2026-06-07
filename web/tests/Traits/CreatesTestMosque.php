<?php

namespace Tests\Traits;

use App\Models\Mosque;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

trait CreatesTestMosque
{
    /**
     * Create a super-admin user with platform-level role (team_id = 0).
     */
    protected function createSuperAdmin(): User
    {
        $user = User::factory()->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId(0);
        $user->assignRole('super-admin');

        return $user;
    }

    /**
     * Create a mosque-admin user with an active mosque.
     * Sets active_mosque_id and assigns role scoped to the mosque's team ID.
     */
    protected function createActiveMosqueAdmin(): User
    {
        $mosque = Mosque::factory()->create();
        $user = User::factory()->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId($mosque->id);
        $user->assignRole('mosque-admin');

        $user->active_mosque_id = $mosque->id;
        $user->save();

        return $user;
    }

    /**
     * Create a staff user with an active mosque.
     * Sets active_mosque_id and assigns role scoped to the mosque's team ID.
     */
    protected function createStaffUser(): User
    {
        $mosque = Mosque::factory()->create();
        $user = User::factory()->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId($mosque->id);
        $user->assignRole('staff');

        $user->active_mosque_id = $mosque->id;
        $user->save();

        return $user;
    }

    /**
     * Create a plain user with no role assigned.
     */
    protected function createUserWithNoRole(): User
    {
        return User::factory()->create();
    }
}
