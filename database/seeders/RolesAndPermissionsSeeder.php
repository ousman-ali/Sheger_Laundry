<?php

   namespace Database\Seeders;

   use Illuminate\Database\Seeder;
   use Spatie\Permission\Models\Role;
   use Spatie\Permission\Models\Permission;

   class RolesAndPermissionsSeeder extends Seeder
   {
       public function run(): void
       {
           // Create permissions
           $permissions = [
               'create_customer', 'view_customer', 'edit_customer', 'delete_customer',
               'create_order', 'view_order', 'edit_order', 'delete_order',
               'assign_service', 'update_service_status', 'view_reports',
               'view_users', 'create_users', 'edit_users', 'delete_users', 'manage_settings',
               'view_inventory', 'create_inventory', 'edit_inventory', 'delete_inventory',
           ];

           foreach ($permissions as $permission) {
               Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
           }

           // Create roles and assign permissions
           $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
           $admin->givePermissionTo($permissions);

           $receptionist = Role::firstOrCreate(['name' => 'Receptionist', 'guard_name' => 'web']);
           $receptionist->givePermissionTo([
               'create_customer', 'view_customer', 'edit_customer',
               'create_order', 'view_order', 'edit_order',
           ]);

           $worker = Role::firstOrCreate(['name' => 'Worker', 'guard_name' => 'web']);
           $worker->givePermissionTo(['assign_service', 'update_service_status']);
       }
   }