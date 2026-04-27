<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ApproverUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->updateOrCreate(
            ['username' => 'autorizado'],
            [
                'name' => 'Usuario Autorizado',
                'email' => 'autorizado@ordenes.local',
                'password' => Hash::make('Autorizado123!'),
                'must_change_password' => false,
                'is_active' => true,
            ]
        );

        $role = Role::query()->where('name', 'usuario_autorizado')->firstOrFail();
        $user->roles()->syncWithoutDetaching([$role->id]);
    }
}
