<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrador',
                'email' => 'admin@ordenes.local',
                'password' => Hash::make('admin'),
                'must_change_password' => true,
                'is_active' => true,
            ]
        );

        $role = Role::query()->where('name', 'administrador')->firstOrFail();
        $user->roles()->syncWithoutDetaching([$role->id]);
    }
}
