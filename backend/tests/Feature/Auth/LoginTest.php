<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'username' => 'usuario.demo',
            'email' => 'user@ordenes.local',
            'password' => Hash::make('Secret123!'),
            'must_change_password' => false,
        ]);

        $response = $this->post('/login', [
            'username' => $user->username,
            'password' => 'Secret123!',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }
}
