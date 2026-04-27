<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordChangeFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_mandatory_password_change_is_redirected(): void
    {
        $user = User::factory()->create([
            'username' => 'cambio.clave',
            'password' => Hash::make('Secret123!'),
            'must_change_password' => true,
        ]);

        $this->post('/login', [
            'username' => $user->username,
            'password' => 'Secret123!',
        ])->assertRedirect('/password/change');
    }

    public function test_user_can_change_password_and_clear_flag(): void
    {
        $user = User::factory()->create([
            'username' => 'cambio.ok',
            'password' => Hash::make('Secret123!'),
            'must_change_password' => true,
        ]);

        $this->actingAs($user)
            ->put('/password/change', [
                'current_password' => 'Secret123!',
                'password' => 'NuevaClave123!',
                'password_confirmation' => 'NuevaClave123!',
            ])
            ->assertRedirect('/dashboard');

        $user->refresh();

        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('NuevaClave123!', $user->password));
    }
}
