<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'organizations.manage', 'display_name' => 'Manage organizations', 'group' => 'organizations'],
            ['name' => 'users.manage', 'display_name' => 'Manage users', 'group' => 'users'],
            ['name' => 'devices.view', 'display_name' => 'View devices', 'group' => 'devices'],
            ['name' => 'devices.manage', 'display_name' => 'Manage devices', 'group' => 'devices'],
            ['name' => 'devices.commands', 'display_name' => 'Issue device commands', 'group' => 'devices'],
            ['name' => 'enrollments.manage', 'display_name' => 'Manage enrollments', 'group' => 'enrollment'],
            ['name' => 'commands.view', 'display_name' => 'View commands', 'group' => 'commands'],
            ['name' => 'audit.view', 'display_name' => 'View audit logs', 'group' => 'audit'],
            ['name' => 'mdm.settings', 'display_name' => 'Manage MDM settings', 'group' => 'mdm'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission['name']], $permission);
        }

        $super = Role::firstOrCreate(
            ['name' => RoleName::SuperAdmin->value],
            ['display_name' => 'Super Admin', 'description' => 'Enterprise-wide control']
        );
        $admin = Role::firstOrCreate(
            ['name' => RoleName::Admin->value],
            ['display_name' => 'Admin', 'description' => 'Organization-scoped admin']
        );

        $super->permissions()->sync(Permission::pluck('id'));
        $admin->permissions()->sync(
            Permission::whereIn('name', [
                'devices.view',
                'devices.manage',
                'devices.commands',
                'enrollments.manage',
                'commands.view',
                'audit.view',
            ])->pluck('id')
        );

        $demoOrg = Organization::firstOrCreate(
            ['slug' => 'acme'],
            ['name' => 'Acme Corp', 'is_active' => true]
        );

        $superUser = User::firstOrCreate(
            ['email' => 'superadmin@mdm.local'],
            [
                'name' => 'Super Admin',
                'password' => 'password',
                'organization_id' => null,
                'is_active' => true,
            ]
        );
        $superUser->roles()->syncWithoutDetaching([$super->id]);

        $adminUser = User::firstOrCreate(
            ['email' => 'admin@mdm.local'],
            [
                'name' => 'Org Admin',
                'password' => 'password',
                'organization_id' => $demoOrg->id,
                'is_active' => true,
            ]
        );
        $adminUser->roles()->syncWithoutDetaching([$admin->id]);

        \App\Models\Device::firstOrCreate(
            ['udid' => '00008030-001A2B3C4D5E6F70'],
            [
                'organization_id' => $demoOrg->id,
                'device_name' => 'iPhone 11',
                'platform' => 'ios',
                'model' => 'iPhone12,1',
                'serial_number' => 'C8PLDEMO0001',
                'os_version' => '18.7.8',
                'supervised' => true,
                'management_status' => 'managed',
                'enrollment_status' => 'enrolled',
                'mdm_engine' => null,
                'is_online' => false,
                'last_contact_at' => now()->subMinutes(15),
            ]
        );
    }
}
