<?php

namespace Tests\Feature\Suppliers;

use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_solicitante_can_create_supplier(): void
    {
        $solicitante = Role::query()->create(['name' => 'usuario_solicitante']);
        $user = User::factory()->create();
        $user->roles()->attach($solicitante->id);

        $response = $this->actingAs($user)->post('/suppliers', [
            'name' => 'Proveedor Demo',
            'email' => 'contacto@proveedor.test',
            'phone' => '555-1234',
            'status' => 'active',
        ]);

        $response->assertRedirect('/suppliers');
        $supplier = Supplier::query()->where('name', 'Proveedor Demo')->firstOrFail();
        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'Proveedor Demo',
        ]);
        $this->assertMatchesRegularExpression('/^PRV-\d{6}$/', $supplier->tax_id);
        $this->assertDatabaseHas('audit_logs', [
            'entity' => 'purchase_order_supplier',
            'entity_id' => $supplier->id,
            'action' => 'supplier_created',
        ]);
    }

    public function test_non_permitted_role_cannot_create_supplier(): void
    {
        $sinPermisos = Role::query()->create(['name' => 'sin_permisos']);
        $user = User::factory()->create();
        $user->roles()->attach($sinPermisos->id);

        $response = $this->actingAs($user)->post('/suppliers', [
            'name' => 'No Permitido',
            'status' => 'active',
        ]);

        $response->assertForbidden();
    }

    public function test_index_filters_suppliers_by_search_term(): void
    {
        $solicitante = Role::query()->create(['name' => 'usuario_solicitante']);
        $user = User::factory()->create();
        $user->roles()->attach($solicitante->id);

        Supplier::query()->create([
            'tax_id' => '111',
            'name' => 'Proveedor Andino',
            'status' => 'active',
        ]);

        Supplier::query()->create([
            'tax_id' => '222',
            'name' => 'Logistica Sur',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get('/suppliers?q=andino');

        $response->assertOk();
        $response->assertSee('Proveedor Andino');
        $response->assertDontSee('Logistica Sur');
    }

    public function test_can_export_suppliers_pdf_with_search_filter(): void
    {
        $solicitante = Role::query()->create(['name' => 'usuario_solicitante']);
        $user = User::factory()->create();
        $user->roles()->attach($solicitante->id);

        Supplier::query()->create([
            'tax_id' => '333',
            'name' => 'Proveedor Norte',
            'status' => 'active',
        ]);

        Supplier::query()->create([
            'tax_id' => '444',
            'name' => 'Proveedor Sur',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get('/suppliers/export?q=norte');

        $response->assertOk();
        $this->assertStringStartsWith('application/pdf', (string) $response->headers->get('content-type'));

        $content = (string) $response->getContent();
        $this->assertStringStartsWith('%PDF', $content);
    }

    public function test_supplier_number_is_not_editable(): void
    {
        $adminRole = Role::query()->create(['name' => 'administrador']);
        $user = User::factory()->create();
        $user->roles()->attach($adminRole->id);

        $supplier = Supplier::query()->create([
            'tax_id' => 'PRV-000001',
            'name' => 'Proveedor Original',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->put('/suppliers/'.$supplier->id, [
            'tax_id' => 'PRV-999999',
            'name' => 'Proveedor Editado',
            'email' => 'editado@proveedor.test',
            'phone' => '555-2026',
            'status' => 'active',
        ]);

        $response->assertRedirect('/suppliers');

        $supplier->refresh();
        $this->assertSame('PRV-000001', $supplier->tax_id);
        $this->assertSame('Proveedor Editado', $supplier->name);
    }
}
