<?php

namespace Tests\Feature\Orders;

use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderApprovalFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_submit_by_usuario_solicitante_sends_to_pending_approval_even_under_threshold(): void
    {
        config(['purchase_orders.approval_threshold' => 500]);

        $usuario = Role::query()->create(['name' => 'usuario_solicitante']);
        $user = User::factory()->create();
        $user->roles()->attach($usuario->id);

        $supplier = Supplier::query()->create([
            'tax_id' => '92001',
            'name' => 'Proveedor Usuario',
            'status' => 'active',
        ]);

        $order = PurchaseOrder::query()->create([
            'supplier_id' => $supplier->id,
            'requester_id' => $user->id,
            'status' => 'draft',
            'currency' => 'USD',
            'subtotal' => 100,
            'tax_total' => 5,
            'grand_total' => 105,
        ]);

        $response = $this->actingAs($user)->post('/orders/'.$order->id.'/submit');

        $response->assertRedirect('/orders/'.$order->id);
        $this->assertDatabaseHas('purchase_orders', [
            'id' => $order->id,
            'status' => 'pending_approval',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'entity' => 'purchase_order',
            'entity_id' => $order->id,
            'action' => 'order_submitted',
        ]);
    }

    public function test_submit_by_administrador_auto_approves_order(): void
    {
        $administrador = Role::query()->create(['name' => 'administrador']);
        $user = User::factory()->create();
        $user->roles()->attach($administrador->id);

        $supplier = Supplier::query()->create([
            'tax_id' => '92002',
            'name' => 'Proveedor Admin',
            'status' => 'active',
        ]);

        $order = PurchaseOrder::query()->create([
            'supplier_id' => $supplier->id,
            'requester_id' => $user->id,
            'status' => 'draft',
            'currency' => 'USD',
            'subtotal' => 600,
            'tax_total' => 0,
            'grand_total' => 600,
        ]);

        $response = $this->actingAs($user)->post('/orders/'.$order->id.'/submit');

        $response->assertRedirect('/orders/'.$order->id);
        $this->assertDatabaseHas('purchase_orders', [
            'id' => $order->id,
            'status' => 'approved',
            'approved_by' => $user->id,
        ]);
        $this->assertDatabaseHas('approvals', [
            'purchase_order_id' => $order->id,
            'decision' => 'auto_approved',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'entity' => 'purchase_order',
            'entity_id' => $order->id,
            'action' => 'order_auto_approved',
        ]);
    }

    public function test_submit_by_usuario_autorizado_auto_approves_order(): void
    {
        $autorizado = Role::query()->create(['name' => 'usuario_autorizado']);
        $user = User::factory()->create();
        $user->roles()->attach($autorizado->id);

        $supplier = Supplier::query()->create([
            'tax_id' => '92003',
            'name' => 'Proveedor Autorizado',
            'status' => 'active',
        ]);

        $order = PurchaseOrder::query()->create([
            'supplier_id' => $supplier->id,
            'requester_id' => $user->id,
            'status' => 'draft',
            'currency' => 'USD',
            'subtotal' => 900,
            'tax_total' => 0,
            'grand_total' => 900,
        ]);

        $response = $this->actingAs($user)->post('/orders/'.$order->id.'/submit');

        $response->assertRedirect('/orders/'.$order->id);
        $this->assertDatabaseHas('purchase_orders', [
            'id' => $order->id,
            'status' => 'approved',
            'approved_by' => $user->id,
        ]);
        $this->assertDatabaseHas('approvals', [
            'purchase_order_id' => $order->id,
            'decision' => 'auto_approved',
            'approver_id' => $user->id,
        ]);
    }

    public function test_usuario_autorizado_can_approve_pending_order(): void
    {
        $autorizado = Role::query()->create(['name' => 'usuario_autorizado']);
        $user = User::factory()->create();
        $user->roles()->attach($autorizado->id);

        $requester = User::factory()->create();
        $supplier = Supplier::query()->create([
            'tax_id' => '92004',
            'name' => 'Proveedor Aprobar',
            'status' => 'active',
        ]);

        $order = PurchaseOrder::query()->create([
            'supplier_id' => $supplier->id,
            'requester_id' => $requester->id,
            'status' => 'pending_approval',
            'currency' => 'USD',
            'subtotal' => 900,
            'tax_total' => 0,
            'grand_total' => 900,
        ]);

        $response = $this->actingAs($user)->post('/orders/'.$order->id.'/approve');

        $response->assertRedirect('/orders/'.$order->id);
        $this->assertDatabaseHas('purchase_orders', [
            'id' => $order->id,
            'status' => 'approved',
            'approved_by' => $user->id,
        ]);
        $this->assertDatabaseHas('approvals', [
            'purchase_order_id' => $order->id,
            'decision' => 'approved',
            'approver_id' => $user->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'entity' => 'purchase_order',
            'entity_id' => $order->id,
            'action' => 'order_approved',
        ]);
    }

    public function test_usuario_autorizado_can_reject_pending_order_with_comment(): void
    {
        $autorizado = Role::query()->create(['name' => 'usuario_autorizado']);
        $user = User::factory()->create();
        $user->roles()->attach($autorizado->id);

        $requester = User::factory()->create();
        $supplier = Supplier::query()->create([
            'tax_id' => '92005',
            'name' => 'Proveedor Rechazar',
            'status' => 'active',
        ]);

        $order = PurchaseOrder::query()->create([
            'supplier_id' => $supplier->id,
            'requester_id' => $requester->id,
            'status' => 'pending_approval',
            'currency' => 'USD',
            'subtotal' => 900,
            'tax_total' => 0,
            'grand_total' => 900,
        ]);

        $response = $this->actingAs($user)->post('/orders/'.$order->id.'/reject', [
            'comment' => 'Ajustar monto y adjuntar respaldo.',
        ]);

        $response->assertRedirect('/orders/'.$order->id);
        $this->assertDatabaseHas('purchase_orders', [
            'id' => $order->id,
            'status' => 'rejected',
            'rejected_reason' => 'Ajustar monto y adjuntar respaldo.',
        ]);
        $this->assertDatabaseHas('approvals', [
            'purchase_order_id' => $order->id,
            'decision' => 'rejected',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'entity' => 'purchase_order',
            'entity_id' => $order->id,
            'action' => 'order_rejected',
        ]);
    }

    public function test_usuario_solicitante_cannot_approve_pending_order(): void
    {
        $usuario = Role::query()->create(['name' => 'usuario_solicitante']);
        $user = User::factory()->create();
        $user->roles()->attach($usuario->id);

        $requester = User::factory()->create();
        $supplier = Supplier::query()->create([
            'tax_id' => '92006',
            'name' => 'Proveedor Seguridad',
            'status' => 'active',
        ]);

        $order = PurchaseOrder::query()->create([
            'supplier_id' => $supplier->id,
            'requester_id' => $requester->id,
            'status' => 'pending_approval',
            'currency' => 'USD',
            'subtotal' => 900,
            'tax_total' => 0,
            'grand_total' => 900,
        ]);

        $response = $this->actingAs($user)->post('/orders/'.$order->id.'/approve');

        $response->assertForbidden();
    }

    public function test_can_export_single_order_pdf_when_approved(): void
    {
        $solicitante = Role::query()->create(['name' => 'usuario_solicitante']);
        $user = User::factory()->create();
        $user->roles()->attach($solicitante->id);

        $supplier = Supplier::query()->create([
            'tax_id' => '92007',
            'name' => 'Proveedor Export Individual',
            'status' => 'active',
        ]);

        $order = PurchaseOrder::query()->create([
            'order_number' => 'OC-2026-001500',
            'supplier_id' => $supplier->id,
            'requester_id' => $user->id,
            'status' => 'approved',
            'currency' => 'USD',
            'subtotal' => 10,
            'tax_total' => 0,
            'grand_total' => 10,
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/orders/'.$order->id.'/export');

        $response->assertOk();
        $this->assertStringStartsWith('application/pdf', (string) $response->headers->get('content-type'));
        $content = (string) $response->getContent();
        $this->assertStringStartsWith('%PDF', $content);
    }

    public function test_cannot_export_single_order_pdf_if_not_approved(): void
    {
        $solicitante = Role::query()->create(['name' => 'usuario_solicitante']);
        $user = User::factory()->create();
        $user->roles()->attach($solicitante->id);

        $supplier = Supplier::query()->create([
            'tax_id' => '92008',
            'name' => 'Proveedor Sin Aprobar',
            'status' => 'active',
        ]);

        $order = PurchaseOrder::query()->create([
            'supplier_id' => $supplier->id,
            'requester_id' => $user->id,
            'status' => 'draft',
            'currency' => 'USD',
            'subtotal' => 10,
            'tax_total' => 0,
            'grand_total' => 10,
        ]);

        $response = $this->actingAs($user)->get('/orders/'.$order->id.'/export');

        $response->assertStatus(422);
    }
}
