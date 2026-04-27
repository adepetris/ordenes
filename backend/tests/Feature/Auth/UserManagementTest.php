<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seed_creates_only_admin_user_initially(): void
    {
        $this->seed();

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', [
            'username' => 'admin',
            'must_change_password' => true,
            'is_active' => true,
        ]);
    }

    public function test_administrador_can_delete_non_admin_user(): void
    {
        $adminRole = Role::query()->create(['name' => 'administrador']);
        $userRole = Role::query()->create(['name' => 'usuario_solicitante']);

        $admin = User::factory()->create(['must_change_password' => false]);
        $admin->roles()->attach($adminRole->id);

        $target = User::factory()->create(['must_change_password' => false]);
        $target->roles()->attach($userRole->id);

        $response = $this->actingAs($admin)->delete('/users/'.$target->id);

        $response->assertRedirect('/users');
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_administrador_cannot_delete_admin_user(): void
    {
        $adminRole = Role::query()->create(['name' => 'administrador']);

        $admin = User::factory()->create(['must_change_password' => false]);
        $admin->roles()->attach($adminRole->id);

        $targetAdmin = User::factory()->create(['must_change_password' => false]);
        $targetAdmin->roles()->attach($adminRole->id);

        $response = $this->actingAs($admin)->delete('/users/'.$targetAdmin->id);

        $response->assertSessionHasErrors('users');
        $this->assertDatabaseHas('users', ['id' => $targetAdmin->id]);
    }

    public function test_administrador_cannot_delete_himself(): void
    {
        $adminRole = Role::query()->create(['name' => 'administrador']);

        $admin = User::factory()->create(['must_change_password' => false]);
        $admin->roles()->attach($adminRole->id);

        $response = $this->actingAs($admin)->delete('/users/'.$admin->id);

        $response->assertSessionHasErrors('users');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}
