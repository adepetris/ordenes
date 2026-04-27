<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_user_cannot_access_admin_page(): void
    {
        $solicitante = Role::query()->create(['name' => 'usuario_solicitante']);
        $user = User::factory()->create();
        $user->roles()->attach($solicitante->id);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertForbidden();
    }

    public function test_admin_user_can_access_admin_page(): void
    {
        $admin = Role::query()->create(['name' => 'administrador']);
        $user = User::factory()->create();
        $user->roles()->attach($admin->id);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertOk();
    }
}
