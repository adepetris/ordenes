<?php

namespace Tests\Feature\Orders;

use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderDraftTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_solicitante_can_create_draft_order_with_calculated_totals(): void
    {
        $solicitante = Role::query()->create(['name' => 'usuario_solicitante']);
        $user = User::factory()->create();
        $user->roles()->attach($solicitante->id);

        $supplier = Supplier::query()->create([
            'tax_id' => '91001',
            'name' => 'Proveedor Uno',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post('/orders', [
            'supplier_id' => $supplier->id,
            'currency' => 'usd',
            'notes' => 'Orden de prueba',
            'items' => [
                [
                    'description' => 'Item A',
                    'qty' => 2,
                    'unit_price' => 100,
                    'tax_rate' => 10,
                ],
                [
                    'description' => 'Item B',
                    'qty' => 1,
                    'unit_price' => 50,
                    'tax_rate' => 0,
                ],
            ],
        ]);

        $order = PurchaseOrder::query()->first();

        $response->assertRedirect('/orders/'.$order->id);
        $this->assertDatabaseHas('purchase_orders', [
            'id' => $order->id,
            'status' => 'draft',
            'currency' => 'USD',
            'subtotal' => 250,
            'tax_total' => 20,
            'grand_total' => 270,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'entity' => 'purchase_order',
            'entity_id' => $order->id,
            'action' => 'order_created',
        ]);
        $this->assertMatchesRegularExpression('/^OC-\d{4}-\d{6}$/', (string) $order->order_number);
        $this->assertDatabaseCount('purchase_order_items', 2);
    }

    public function test_order_number_increments_sequentially_in_same_year(): void
    {
        $solicitante = Role::query()->create(['name' => 'usuario_solicitante']);
        $user = User::factory()->create();
        $user->roles()->attach($solicitante->id);

        $supplier = Supplier::query()->create([
            'tax_id' => '91010',
            'name' => 'Proveedor Secuencia',
            'status' => 'active',
        ]);

        $payload = [
            'supplier_id' => $supplier->id,
            'currency' => 'USD',
            'items' => [[
                'description' => 'Item',
                'qty' => 1,
                'unit_price' => 10,
                'tax_rate' => 0,
            ]],
        ];

        $this->actingAs($user)->post('/orders', $payload);
        $this->actingAs($user)->post('/orders', $payload);

        $orders = PurchaseOrder::query()->orderBy('id')->get();

        $first = (string) $orders[0]->order_number;
        $second = (string) $orders[1]->order_number;

        $this->assertMatchesRegularExpression('/^OC-\d{4}-\d{6}$/', $first);
        $this->assertMatchesRegularExpression('/^OC-\d{4}-\d{6}$/', $second);
        $this->assertSame((int) substr($first, -6) + 1, (int) substr($second, -6));
    }

    public function test_non_permitted_role_cannot_create_order(): void
    {
        $sinPermisos = Role::query()->create(['name' => 'sin_permisos']);
        $user = User::factory()->create();
        $user->roles()->attach($sinPermisos->id);

        $supplier = Supplier::query()->create([
            'tax_id' => '91002',
            'name' => 'Proveedor Dos',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post('/orders', [
            'supplier_id' => $supplier->id,
            'currency' => 'USD',
            'items' => [
                [
                    'description' => 'Item C',
                    'qty' => 1,
                    'unit_price' => 10,
                    'tax_rate' => 0,
                ],
            ],
        ]);

        $response->assertForbidden();
    }

    public function test_non_draft_order_cannot_be_updated(): void
    {
        $admin = Role::query()->create(['name' => 'administrador']);
        $user = User::factory()->create();
        $user->roles()->attach($admin->id);

        $supplier = Supplier::query()->create([
            'tax_id' => '91003',
            'name' => 'Proveedor Tres',
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

        $response = $this->actingAs($user)->put('/orders/'.$order->id, [
            'supplier_id' => $supplier->id,
            'currency' => 'USD',
            'items' => [
                [
                    'description' => 'Item Z',
                    'qty' => 1,
                    'unit_price' => 30,
                    'tax_rate' => 0,
                ],
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_can_export_orders_pdf_with_status_filter(): void
    {
        $solicitante = Role::query()->create(['name' => 'usuario_solicitante']);
        $user = User::factory()->create();
        $user->roles()->attach($solicitante->id);

        $supplier = Supplier::query()->create([
            'tax_id' => '91030',
            'name' => 'Proveedor Export',
            'status' => 'active',
        ]);

        PurchaseOrder::query()->create([
            'order_number' => 'OC-2026-000901',
            'supplier_id' => $supplier->id,
            'requester_id' => $user->id,
            'status' => 'draft',
            'currency' => 'USD',
            'subtotal' => 10,
            'tax_total' => 0,
            'grand_total' => 10,
        ]);

        PurchaseOrder::query()->create([
            'order_number' => 'OC-2026-000902',
            'supplier_id' => $supplier->id,
            'requester_id' => $user->id,
            'status' => 'approved',
            'currency' => 'USD',
            'subtotal' => 20,
            'tax_total' => 0,
            'grand_total' => 20,
        ]);

        $response = $this->actingAs($user)->get('/orders/export?status=approved');

        $response->assertOk();
        $this->assertStringStartsWith('application/pdf', (string) $response->headers->get('content-type'));

        $content = (string) $response->getContent();
        $this->assertStringStartsWith('%PDF', $content);
    }
}
