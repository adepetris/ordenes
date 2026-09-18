<?php

namespace Tests\Feature\Audit;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AuditLogAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrador_can_open_audit_log_screen(): void
    {
        $adminRole = Role::query()->create(['name' => 'administrador']);
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole->id);

        AuditLog::query()->create([
            'entity' => 'purchase_order',
            'entity_id' => 1,
            'action' => 'order_created',
        ]);

        $response = $this->actingAs($admin)->get('/audit-logs');

        $response->assertOk();
        $response->assertSee('Auditoria del sistema');
        $response->assertSee('order_created');
    }

    public function test_non_administrador_cannot_open_audit_log_screen(): void
    {
        $solicitanteRole = Role::query()->create(['name' => 'usuario_solicitante']);
        $solicitante = User::factory()->create();
        $solicitante->roles()->attach($solicitanteRole->id);

        $response = $this->actingAs($solicitante)->get('/audit-logs');

        $response->assertForbidden();
    }

    public function test_audit_timestamp_is_displayed_in_local_timezone(): void
    {
        $adminRole = Role::query()->create(['name' => 'administrador']);
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole->id);

        Carbon::setTestNow('2026-09-18 17:27:21 UTC');

        AuditLog::query()->create([
            'entity' => 'purchase_order',
            'entity_id' => 1,
            'action' => 'order_created',
        ]);

        $this->actingAs($admin)
            ->get('/audit-logs')
            ->assertOk()
            ->assertSee('2026-09-18 14:27:21');
    }

    public function test_audit_date_filter_uses_local_calendar_day(): void
    {
        $adminRole = Role::query()->create(['name' => 'administrador']);
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole->id);

        Carbon::setTestNow('2026-09-18 02:00:00 UTC');

        AuditLog::query()->create([
            'entity' => 'purchase_order',
            'entity_id' => 99,
            'action' => 'local_previous_day',
        ]);

        $this->actingAs($admin)
            ->get('/audit-logs?date_from=2026-09-17&date_to=2026-09-17')
            ->assertOk()
            ->assertSee('local_previous_day')
            ->assertSee('2026-09-17 23:00:00');
    }

    public function test_administrador_can_export_audit_logs_pdf_with_filters(): void
    {
        $adminRole = Role::query()->create(['name' => 'administrador']);
        $admin = User::factory()->create(['name' => 'Admin Export']);
        $admin->roles()->attach($adminRole->id);

        AuditLog::query()->create([
            'user_id' => $admin->id,
            'entity' => 'purchase_order',
            'entity_id' => 10,
            'action' => 'order_created',
            'diff_json' => ['status' => 'draft'],
        ]);

        AuditLog::query()->create([
            'user_id' => $admin->id,
            'entity' => 'purchase_order_supplier',
            'entity_id' => 20,
            'action' => 'supplier_created',
            'diff_json' => ['status' => 'active'],
        ]);

        $response = $this->actingAs($admin)->get('/audit-logs/export?action=order_created');

        $response->assertOk();
        $this->assertStringStartsWith('application/pdf', (string) $response->headers->get('content-type'));

        $content = (string) $response->getContent();
        $this->assertStringStartsWith('%PDF', $content);
    }

    public function test_non_administrador_cannot_export_audit_logs_pdf(): void
    {
        $solicitanteRole = Role::query()->create(['name' => 'usuario_solicitante']);
        $solicitante = User::factory()->create();
        $solicitante->roles()->attach($solicitanteRole->id);

        $response = $this->actingAs($solicitante)->get('/audit-logs/export');

        $response->assertForbidden();
    }
}
