<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Unit;
use App\Models\Service;
use App\Models\ClothItem;
use App\Models\UrgencyTier;
use App\Models\PricingTier;
use App\Models\Store;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use Illuminate\Support\Facades\Hash;

class InitialDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create units
        $kg = Unit::firstOrCreate(['name' => 'kg']);
        $pcs = Unit::firstOrCreate(['name' => 'pcs']);
        $liters = Unit::firstOrCreate(['name' => 'liters']);
        $grams = Unit::firstOrCreate(
            ['name' => 'grams'],
            ['parent_unit_id' => $kg->id, 'conversion_factor' => 1000]
        );

        // Create services
        $services = [
            ['name' => 'Dry Washing', 'description' => 'Dry cleaning service'],
            ['name' => 'Wet Washing', 'description' => 'Regular washing service'],
            ['name' => 'Ironing', 'description' => 'Ironing and pressing service'],
            ['name' => 'Steaming', 'description' => 'Steam cleaning service'],
            ['name' => 'Packaging', 'description' => 'Packaging and wrapping service'],
        ];

        foreach ($services as $service) {
            Service::firstOrCreate(['name' => $service['name']], $service);
        }

        // Create cloth items
        $clothItems = [
            ['item_code' => 'TSH001', 'name' => 'T-shirt', 'unit_id' => $pcs->id, 'description' => 'Regular T-shirts'],
            ['item_code' => 'SHT001', 'name' => 'Shirt', 'unit_id' => $pcs->id, 'description' => 'Formal shirts'],
            ['item_code' => 'PNT001', 'name' => 'Pants', 'unit_id' => $pcs->id, 'description' => 'Trousers and pants'],
            ['item_code' => 'DRS001', 'name' => 'Dress', 'unit_id' => $pcs->id, 'description' => 'Dresses and gowns'],
            ['item_code' => 'SUT001', 'name' => 'Suit', 'unit_id' => $pcs->id, 'description' => 'Business suits'],
            ['item_code' => 'BLK001', 'name' => 'Blanket', 'unit_id' => $pcs->id, 'description' => 'Bedding blankets'],
            ['item_code' => 'CUR001', 'name' => 'Curtain', 'unit_id' => $pcs->id, 'description' => 'Window curtains'],
            ['item_code' => 'CRP001', 'name' => 'Carpet', 'unit_id' => $pcs->id, 'description' => 'Floor carpets'],
        ];

        foreach ($clothItems as $item) {
            ClothItem::firstOrCreate(['item_code' => $item['item_code']], $item);
        }

        // Create urgency tiers
        $urgencyTiers = [
            ['label' => 'Normal', 'duration_days' => 7, 'multiplier' => 1.00],
            ['label' => '3 Days +50%', 'duration_days' => 3, 'multiplier' => 1.50],
            ['label' => '2 Days +75%', 'duration_days' => 2, 'multiplier' => 1.75],
            ['label' => 'Same Day +100%', 'duration_days' => 1, 'multiplier' => 2.00],
        ];

        foreach ($urgencyTiers as $tier) {
            UrgencyTier::firstOrCreate(['label' => $tier['label']], $tier);
        }

        // Create pricing tiers
        $services = Service::all();
        $clothItems = ClothItem::all();

        foreach ($clothItems as $clothItem) {
            foreach ($services as $service) {
                $basePrice = match($clothItem->name) {
                    'T-shirt' => 15.00,
                    'Shirt' => 25.00,
                    'Pants' => 30.00,
                    'Dress' => 40.00,
                    'Suit' => 80.00,
                    'Blanket' => 50.00,
                    'Curtain' => 35.00,
                    'Carpet' => 60.00,
                    default => 20.00,
                };

                $serviceMultiplier = match($service->name) {
                    'Dry Washing' => 1.5,
                    'Wet Washing' => 1.0,
                    'Ironing' => 0.8,
                    'Steaming' => 1.2,
                    'Packaging' => 0.5,
                    default => 1.0,
                };

                PricingTier::firstOrCreate(
                    ['cloth_item_id' => $clothItem->id, 'service_id' => $service->id],
                    ['price' => $basePrice * $serviceMultiplier]
                );
            }
        }

        // Create stores
        $stores = [
            ['name' => 'Main Store', 'type' => 'main', 'description' => 'Main inventory store'],
            ['name' => 'Washing Room', 'type' => 'sub', 'description' => 'Washing area store'],
            ['name' => 'Drying Room', 'type' => 'sub', 'description' => 'Drying area store'],
            ['name' => 'Ironing Room', 'type' => 'sub', 'description' => 'Ironing area store'],
            ['name' => 'Packaging Room', 'type' => 'sub', 'description' => 'Packaging area store'],
        ];

        foreach ($stores as $store) {
            Store::firstOrCreate(['name' => $store['name']], $store);
        }

        // Seed inventory items and initial stock in Main Store
        $inventoryItems = [
            ['name' => 'Detergent', 'unit_id' => $kg->id, 'minimum_stock' => 10],
            ['name' => 'Softener', 'unit_id' => $liters->id, 'minimum_stock' => 5],
            ['name' => 'Bleach', 'unit_id' => $liters->id, 'minimum_stock' => 5],
            ['name' => 'Starch', 'unit_id' => $kg->id, 'minimum_stock' => 5],
            ['name' => 'Hanger', 'unit_id' => $pcs->id, 'minimum_stock' => 100],
            ['name' => 'Polybag', 'unit_id' => $pcs->id, 'minimum_stock' => 200],
            ['name' => 'Tags', 'unit_id' => $pcs->id, 'minimum_stock' => 500],
        ];

        $createdInventoryItems = [];
        foreach ($inventoryItems as $inv) {
            $createdInventoryItems[] = InventoryItem::firstOrCreate(['name' => $inv['name']], $inv);
        }

        $mainStore = Store::where('type', 'main')->first();
        if ($mainStore) {
            foreach ($createdInventoryItems as $item) {
                $initialQty = match ($item->name) {
                    'Detergent' => 100,
                    'Softener' => 50,
                    'Bleach' => 30,
                    'Starch' => 40,
                    'Hanger' => 1000,
                    'Polybag' => 2000,
                    'Tags' => 5000,
                    default => 100,
                };

                InventoryStock::firstOrCreate(
                    ['inventory_item_id' => $item->id, 'store_id' => $mainStore->id],
                    ['quantity' => $initialQty]
                );
            }
        }

        // Create system settings
        $settings = [
            ['key' => 'vat_percentage', 'value' => '15.00'],
            ['key' => 'penalty_daily_rate', 'value' => '10.00'],
            ['key' => 'company_name', 'value' => 'Shebar Laundry'],
            ['key' => 'company_address', 'value' => '123 Main Street, Addis Ababa'],
            ['key' => 'company_phone', 'value' => '+251 911 123 456'],
            ['key' => 'company_email', 'value' => 'info@shebarlaundry.com'],
        ];

        foreach ($settings as $setting) {
            SystemSetting::firstOrCreate(['key' => $setting['key']], $setting);
        }

        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@shebarlaundry.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'phone' => '+251 911 123 456',
                'email_verified_at' => now(),
            ]
        );

        $admin->assignRole('Admin');
    }
}
