<?php

namespace Tests\Feature\Orders;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderAttachment;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PurchaseOrderAttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_solicitante_can_upload_attachment_to_order(): void
    {
        Storage::fake('local');

        $solicitanteRole = Role::query()->create(['name' => 'usuario_solicitante']);
        $user = User::factory()->create();
        $user->roles()->attach($solicitanteRole->id);

        $supplier = Supplier::query()->create([
            'tax_id' => '93001',
            'name' => 'Proveedor Adjuntos',
            'status' => 'active',
        ]);

        $order = PurchaseOrder::query()->create([
            'order_number' => 'OC-2026-001111',
            'supplier_id' => $supplier->id,
            'requester_id' => $user->id,
            'status' => 'draft',
            'currency' => 'USD',
            'subtotal' => 100,
            'tax_total' => 0,
            'grand_total' => 100,
        ]);

        $file = UploadedFile::fake()->create('cotizacion.pdf', 120, 'application/pdf');

        $response = $this->actingAs($user)->post('/orders/'.$order->id.'/attachments', [
            'file' => $file,
        ]);

        $response->assertRedirect('/orders/'.$order->id);
        $this->assertDatabaseCount('purchase_order_attachments', 1);
        $attachment = PurchaseOrderAttachment::query()->firstOrFail();
        Storage::disk('local')->assertExists($attachment->path);

        $this->assertDatabaseHas('audit_logs', [
            'entity' => 'purchase_order',
            'entity_id' => $order->id,
            'action' => 'attachment_uploaded',
        ]);
    }

    public function test_non_permitted_role_cannot_upload_attachment(): void
    {
        $sinPermisosRole = Role::query()->create(['name' => 'sin_permisos']);
        $user = User::factory()->create();
        $user->roles()->attach($sinPermisosRole->id);

        $supplier = Supplier::query()->create([
            'tax_id' => '93002',
            'name' => 'Proveedor Sin Permiso',
            'status' => 'active',
        ]);

        $order = PurchaseOrder::query()->create([
            'order_number' => 'OC-2026-001112',
            'supplier_id' => $supplier->id,
            'requester_id' => $user->id,
            'status' => 'draft',
            'currency' => 'USD',
            'subtotal' => 50,
            'tax_total' => 0,
            'grand_total' => 50,
        ]);

        $file = UploadedFile::fake()->create('archivo.pdf', 40, 'application/pdf');
        $response = $this->actingAs($user)->post('/orders/'.$order->id.'/attachments', ['file' => $file]);

        $response->assertForbidden();
    }

    public function test_authenticated_user_can_download_attachment(): void
    {
        Storage::fake('local');

        $solicitanteRole = Role::query()->create(['name' => 'usuario_solicitante']);
        $user = User::factory()->create();
        $user->roles()->attach($solicitanteRole->id);

        $supplier = Supplier::query()->create([
            'tax_id' => '93003',
            'name' => 'Proveedor Descarga',
            'status' => 'active',
        ]);

        $order = PurchaseOrder::query()->create([
            'order_number' => 'OC-2026-001113',
            'supplier_id' => $supplier->id,
            'requester_id' => $user->id,
            'status' => 'approved',
            'currency' => 'USD',
            'subtotal' => 80,
            'tax_total' => 0,
            'grand_total' => 80,
        ]);

        $path = 'order_attachments/'.$order->id.'/doc.pdf';
        Storage::disk('local')->put($path, 'contenido');

        $attachment = PurchaseOrderAttachment::query()->create([
            'purchase_order_id' => $order->id,
            'uploaded_by' => $user->id,
            'file_name' => 'doc.pdf',
            'stored_name' => 'doc.pdf',
            'path' => $path,
            'mime_type' => 'application/pdf',
            'size' => 8,
        ]);

        $response = $this->actingAs($user)->get('/orders/'.$order->id.'/attachments/'.$attachment->id);

        $response->assertOk();
        $response->assertDownload('doc.pdf');
    }
}
