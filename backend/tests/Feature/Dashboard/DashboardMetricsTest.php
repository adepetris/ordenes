<?php

namespace Tests\Feature\Dashboard;

use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_operational_metrics(): void
    {
        $adminRole = Role::query()->create(['name' => 'administrador']);
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole->id);

        $supplier = Supplier::query()->create([
            'tax_id' => '94001',
            'name' => 'Proveedor KPI',
            'status' => 'active',
        ]);

        PurchaseOrder::query()->create([
            'order_number' => 'OC-2026-002001',
            'supplier_id' => $supplier->id,
            'requester_id' => $admin->id,
            'status' => 'pending_approval',
            'currency' => 'USD',
            'subtotal' => 100,
            'tax_total' => 0,
            'grand_total' => 100,
        ]);

        PurchaseOrder::query()->create([
            'order_number' => 'OC-2026-002002',
            'supplier_id' => $supplier->id,
            'requester_id' => $admin->id,
            'status' => 'approved',
            'currency' => 'USD',
            'subtotal' => 200,
            'tax_total' => 0,
            'grand_total' => 200,
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Dashboard operativo');
        $response->assertSee('Pendientes de aprobacion');
        $response->assertSee('OC-2026-002001');
        $response->assertSee('Proveedor KPI');
    }
}
