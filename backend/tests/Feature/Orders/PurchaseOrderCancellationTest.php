<?php

namespace Tests\Feature\Orders;

use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrador_can_cancel_an_order(): void
    {
        [$admin, $order] = $this->createUserAndOrder('administrador');

        $response = $this->actingAs($admin)->post(route('orders.cancel', $order));

        $response->assertRedirect(route('orders.show', $order));
        $response->assertSessionHas('status', 'Orden anulada correctamente.');
        $this->assertDatabaseHas('purchase_orders', [
            'id' => $order->id,
            'status' => 'cancelled',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'entity' => 'purchase_order',
            'entity_id' => $order->id,
            'action' => 'order_cancelled',
            'user_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Anulada')
            ->assertDontSee('Anular orden');
    }

    public function test_non_administrador_cannot_cancel_an_order(): void
    {
        [$user, $order] = $this->createUserAndOrder('usuario_autorizado');

        $this->actingAs($user)
            ->post(route('orders.cancel', $order))
            ->assertForbidden();

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $order->id,
            'status' => 'approved',
        ]);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertDontSee('Anular orden');
    }

    /**
     * @return array{User, PurchaseOrder}
     */
    private function createUserAndOrder(string $roleName): array
    {
        $role = Role::query()->create(['name' => $roleName]);
        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        $supplier = Supplier::query()->create([
            'tax_id' => '93001',
            'name' => 'Proveedor Anulacion',
            'status' => 'active',
        ]);

        $order = PurchaseOrder::query()->create([
            'supplier_id' => $supplier->id,
            'requester_id' => $user->id,
            'status' => 'approved',
            'currency' => 'USD',
            'subtotal' => 100,
            'tax_total' => 0,
            'grand_total' => 100,
        ]);

        return [$user, $order];
    }
}
