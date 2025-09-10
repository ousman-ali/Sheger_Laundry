<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExportsXlsxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure permissions exist for guarded routes
        foreach ([
            'view_users', 'view_pricing', 'view_purchases', 'view_stock_transfers',
        ] as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }
        $role = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::all());
    }

    private function newAdmin(): User
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $user->assignRole('Admin');
        return $user;
    }

    public function test_users_xlsx_export_downloads(): void
    {
        $resp = $this->actingAs($this->newAdmin(), 'web')->get(route('users.index', ['export' => 'xlsx']));
        $resp->assertStatus(200);
        $resp->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('.xlsx', $resp->headers->get('Content-Disposition'));
    }

    public function test_pricing_xlsx_export_downloads(): void
    {
        $resp = $this->actingAs($this->newAdmin(), 'web')->get(route('pricing.index', ['export' => 'xlsx']));
        $resp->assertStatus(200);
        $resp->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('.xlsx', $resp->headers->get('Content-Disposition'));
    }

    public function test_purchases_xlsx_export_downloads(): void
    {
        $resp = $this->actingAs($this->newAdmin(), 'web')->get(route('purchases.index', ['export' => 'xlsx']));
        $resp->assertStatus(200);
        $resp->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('.xlsx', $resp->headers->get('Content-Disposition'));
    }

    public function test_stock_transfers_xlsx_export_downloads(): void
    {
        $resp = $this->actingAs($this->newAdmin(), 'web')->get(route('stock-transfers.index', ['export' => 'xlsx']));
        $resp->assertStatus(200);
        $resp->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('.xlsx', $resp->headers->get('Content-Disposition'));
    }
}
