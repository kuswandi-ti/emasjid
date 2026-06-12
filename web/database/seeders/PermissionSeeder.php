<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * All permissions following {resource}.{action} pattern.
     */
    private array $permissions = [
        // Mosque management
        'mosque.edit',
        'mosque.view',

        // Schedule management
        'schedule.view',
        'schedule.create',
        'schedule.edit',
        'schedule.delete',

        // Activity management
        'activity.view',
        'activity.create',
        'activity.edit',
        'activity.delete',

        // Finance (kas masjid)
        'finance.view',
        'finance.create_income',
        'finance.create_expense',
        'finance.export',

        // Donation management
        'donation.view',
        'donation.confirm',

        // Announcement management
        'announcement.view',
        'announcement.create',
        'announcement.edit',
        'announcement.publish',
        'announcement.delete',

        // Congregation (jamaah) management
        'congregation.view',
        'congregation.manage',

        // Staff management
        'staff.view',
        'staff.manage',
    ];

    /**
     * Role definitions with their assigned permissions.
     */
    private function getRolePermissions(): array
    {
        return [
            'super-admin' => [], // Gets all permissions via Gate::before in AuthServiceProvider
            'mosque-admin' => $this->permissions, // All permissions within their mosque
            'staff' => [
                'mosque.view',
                'schedule.view',
                'schedule.create',
                'schedule.edit',
                'activity.view',
                'activity.create',
                'activity.edit',
                'finance.view',
                'donation.view',
                'announcement.view',
                'announcement.create',
                'congregation.view',
                'staff.view',
            ],
            'congregation' => [
                'mosque.view',
                'schedule.view',
                'activity.view',
                'announcement.view',
                'donation.view',
            ],
        ];
    }

    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        foreach ($this->permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // Create roles and assign permissions
        foreach ($this->getRolePermissions() as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            if (! empty($rolePermissions)) {
                $role->syncPermissions($rolePermissions);
            }
        }

        $this->command->info('Permissions and roles seeded successfully.');
    }
}
