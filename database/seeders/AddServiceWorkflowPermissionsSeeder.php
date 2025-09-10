<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddServiceWorkflowPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure the service workflow permissions exist
        $assign = Permission::firstOrCreate(['name' => 'assign_service', 'guard_name' => 'web']);
        $update = Permission::firstOrCreate(['name' => 'update_service_status', 'guard_name' => 'web']);

        // Ensure roles exist (no-op if already created by other seeders)
        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $manager = Role::firstOrCreate(['name' => 'Manager', 'guard_name' => 'web']);

        // Grant permissions
        $admin->givePermissionTo([$assign, $update]);
        $manager->givePermissionTo([$assign]);
    }
}
