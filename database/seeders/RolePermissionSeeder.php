<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
    // Reset cached roles and permissions
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Customer permissions
            'view_customers',
            'create_customers',
            'edit_customers',
            'delete_customers',
            
            // Order permissions
            'view_orders',
            'create_orders',
            'edit_orders',
            'delete_orders',
            'update_order_status',
            
            // Service permissions
            'view_services',
            'create_services',
            'edit_services',
            'delete_services',
            
            // Cloth item permissions
            'view_cloth_items',
            'create_cloth_items',
            'edit_cloth_items',
            'delete_cloth_items',

            // Unit permissions
            'view_units',
            'create_units',
            'edit_units',
            'delete_units',
            
            // Pricing permissions
            'view_pricing',
            'create_pricing',
            'edit_pricing',
            'delete_pricing',

            // Urgency tiers permissions
            'view_urgency_tiers',
            'create_urgency_tiers',
            'edit_urgency_tiers',
            'delete_urgency_tiers',
            
            // Inventory permissions
            'view_inventory',
            'create_inventory',
            'edit_inventory',
            'delete_inventory',
            'manage_stock',
            'view_purchases',
            'create_purchases',
            'edit_purchases',
            'delete_purchases',
            'view_stock_transfers',
            'create_stock_transfers',
            'edit_stock_transfers',
            'delete_stock_transfers',
            // Stock usage permissions
            'view_stock_usage',
            'create_stock_usage',
            'edit_stock_usage',
            'delete_stock_usage',
            // Payments permissions
            'view_payments',
            'create_payments',
            'edit_payments',
            'delete_payments',

            // Bank permissions
            'view_banks',
            'create_banks',
            'edit_banks',
            'delete_banks',

            // Export/Print permissions (explicit per module)
            'export_orders','print_orders',
            'export_invoices','print_invoices',
            'export_payments','print_payments',
            'export_reports','print_reports',
            'export_customers','print_customers',
            'export_inventory','print_inventory',
            'export_services','print_services',
            'export_units','print_units',
            'export_cloth_items','print_cloth_items',
            'export_pricing','print_pricing',
            'export_urgency_tiers','print_urgency_tiers',
            'export_stock_usage','print_stock_usage',
            'export_stock_transfers','print_stock_transfers',
            'export_purchases','print_purchases',
            'export_users','print_users',
            'export_activity_logs','print_activity_logs',
            
            // Store permissions
            'view_stores',
            'create_stores',
            'edit_stores',
            'delete_stores',
            
            // User management permissions
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            'assign_roles',
            
            // System permissions
            'view_reports',
            'view_settings',
            'edit_settings',
            'view_activity_logs',

            // Remark presets
            'manage_remarks_presets',

            // Stock-out Requests
            'view_stock_out_requests',
            'create_stock_out_requests',
            'edit_stock_out_requests',
            'export_stock_out_requests', 'print_stock_out_requests',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

    // Create roles
    $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
    $managerRole = Role::firstOrCreate(['name' => 'Manager', 'guard_name' => 'web']);
    $receptionistRole = Role::firstOrCreate(['name' => 'Receptionist', 'guard_name' => 'web']);
    $operatorRole = Role::firstOrCreate(['name' => 'Operator', 'guard_name' => 'web']);

        // Assign permissions to roles
        $adminRole->givePermissionTo(Permission::all());

    $managerRole->givePermissionTo([
            'view_customers', 'create_customers', 'edit_customers',
            'view_orders', 'create_orders', 'edit_orders', 'update_order_status',
            'view_services', 'create_services', 'edit_services',
            'view_cloth_items', 'create_cloth_items', 'edit_cloth_items',
            'view_units', 'create_units', 'edit_units',
            'view_pricing', 'create_pricing', 'edit_pricing',
            'view_urgency_tiers', 'create_urgency_tiers', 'edit_urgency_tiers',
            'view_inventory', 'create_inventory', 'edit_inventory', 'manage_stock',
            'view_purchases', 'create_purchases', 'edit_purchases',
            'view_stock_transfers', 'create_stock_transfers', 'edit_stock_transfers',
            'view_stock_usage', 'create_stock_usage', 'edit_stock_usage',
            'view_payments', 'create_payments', 'edit_payments',
            'view_stores', 'create_stores', 'edit_stores',
            'view_reports', 'view_settings', 'edit_settings',
            // managers can print/export most business lists
            'export_orders','print_orders',
            'export_invoices','print_invoices',
            'export_payments','print_payments',
            'export_reports','print_reports',
            'export_customers','print_customers',
            'export_inventory','print_inventory',
            'export_services','print_services',
            'export_units','print_units',
            'export_cloth_items','print_cloth_items',
            'export_pricing','print_pricing',
            'export_urgency_tiers','print_urgency_tiers',
            'export_stock_usage','print_stock_usage',
            'export_stock_transfers','print_stock_transfers',
            'export_purchases','print_purchases',
            'export_users','print_users',
            // Stock-out Requests (view/export/print)
            'view_stock_out_requests', 'export_stock_out_requests', 'print_stock_out_requests',
            // Allow managers to manage remark presets
            'manage_remarks_presets',
        ]);

    $receptionistRole->givePermissionTo([
            'view_customers', 'create_customers', 'edit_customers',
            'view_orders', 'create_orders', 'edit_orders',
            'view_services', 'view_cloth_items', 'view_pricing', 'view_urgency_tiers',
            'view_units',
            'view_inventory', 'view_stores',
            'view_payments', 'create_payments',
            // Receptionists can export/print customers, orders, and invoices for front-desk ops
            'export_orders','print_orders',
            'export_invoices','print_invoices',
            'export_customers','print_customers',
            // Allow receptionists to use presets (managed elsewhere); no manage permission
        ]);

    $operatorRole->givePermissionTo([
            'view_orders', 'update_order_status',
            'view_services', 'view_cloth_items', 'view_units',
            'view_inventory', 'manage_stock',
            'view_stores',
            'view_stock_usage',
            // Stock-out Requests (Operator flow)
            'view_stock_out_requests', 'create_stock_out_requests', 'edit_stock_out_requests',
            // Operators: no export/print by default
        ]);

        // Seed a few default remark presets if none exist
        if (\App\Models\RemarkPreset::query()->count() === 0) {
            foreach ([
                ['label' => 'Fragile handle with care', 'sort_order' => 1],
                ['label' => 'Color may bleed wash separately', 'sort_order' => 2],
                ['label' => 'Remove stains if possible', 'sort_order' => 3],
            ] as $r) {
                \App\Models\RemarkPreset::create($r + ['created_by' => null, 'is_active' => true]);
            }
        }
    }
}