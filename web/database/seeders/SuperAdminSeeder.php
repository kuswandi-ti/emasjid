<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'owner@emasjid.com'],
            [
                'name' => 'Super Admin',
                'phone' => '081200000000',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Assign super-admin role (platform-level, no mosque scope)
        if (! $user->hasRole('super-admin')) {
            // Use 0 as sentinel for platform-level role (not bound to any mosque)
            app(PermissionRegistrar::class)->setPermissionsTeamId(0);
            $user->assignRole('super-admin');
        }

        $this->command->info("Super Admin created: owner@emasjid.com / password");
    }
}
