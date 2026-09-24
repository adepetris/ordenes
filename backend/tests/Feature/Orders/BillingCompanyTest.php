<?php

namespace Tests\Feature\Orders;

use App\Models\BillingCompany;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingCompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_manage_billing_companies_and_duplicate_cuit_is_rejected(): void
    {
        $role = Role::query()->create(['name' => 'usuario_autorizado']);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $this->actingAs($user)->post('/billing-companies', [
            'company_number' => 'EMP-01',
            'name' => 'Empresa Inicial',
            'cuit' => '30712345678',
        ])->assertRedirect('/billing-companies');

        $company = BillingCompany::query()->firstOrFail();
        $this->actingAs($user)->put('/billing-companies/'.$company->id, [
            'company_number' => 'EMP-01',
            'name' => 'Empresa Actualizada',
            'cuit' => '30712345678',
        ])->assertRedirect('/billing-companies');
        $this->assertSame('Empresa Actualizada', $company->fresh()->name);

        $this->actingAs($user)->post('/billing-companies', [
            'company_number' => 'EMP-02',
            'name' => 'Duplicada',
            'cuit' => '30712345678',
        ])->assertSessionHasErrors('cuit');
        $this->assertDatabaseCount('billing_companies', 1);
    }

    public function test_requester_can_select_billing_company_for_order_but_cannot_manage_companies(): void
    {
        $role = Role::query()->create(['name' => 'usuario_solicitante']);
        $user = User::factory()->create();
        $user->roles()->attach($role);
        $supplier = Supplier::query()->create(['tax_id' => 'PRV-01', 'name' => 'Proveedor', 'status' => 'active']);
        $company = BillingCompany::query()->create([
            'company_number' => 'EMP-01', 'name' => 'Empresa Uno', 'cuit' => '30712345678',
        ]);

        $this->actingAs($user)->post('/billing-companies', [
            'company_number' => 'EMP-02', 'name' => 'Otra', 'cuit' => '30712345679',
        ])->assertForbidden();

        $payload = [
            'supplier_id' => $supplier->id,
            'billing_company_id' => $company->id,
            'currency' => 'USD',
            'items' => [[
                'description' => 'Producto',
                'qty' => 1,
                'unit' => 'Un',
                'unit_price' => 0,
            ]],
        ];

        $this->actingAs($user)->post('/orders', array_diff_key($payload, ['billing_company_id' => true]))
            ->assertSessionHasErrors('billing_company_id');

        $this->actingAs($user)->post('/orders', $payload)->assertRedirect();
        $order = PurchaseOrder::query()->firstOrFail();
        $this->assertSame($company->id, $order->billing_company_id);
        $this->actingAs($user)->get('/orders/'.$order->id)
            ->assertOk()
            ->assertSee('Facturar a:')
            ->assertSee('Empresa Uno')
            ->assertSee('30712345678');

        $otherCompany = BillingCompany::query()->create([
            'company_number' => 'EMP-03', 'name' => 'Empresa Tres', 'cuit' => '30712345670',
        ]);
        $payload['billing_company_id'] = $otherCompany->id;
        $this->actingAs($user)->put('/orders/'.$order->id, $payload)->assertRedirect();
        $this->assertSame($otherCompany->id, $order->fresh()->billing_company_id);
    }
}
