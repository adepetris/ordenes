<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'administrador', 'description' => 'Administrador'],
            ['name' => 'usuario_solicitante', 'description' => 'Usuario solicitante'],
            ['name' => 'usuario_autorizado', 'description' => 'Usuario autorizado'],
        ];

        foreach ($roles as $role) {
            Role::query()->updateOrCreate(['name' => $role['name']], $role);
        }

        Role::query()->whereNotIn('name', ['administrador', 'usuario_solicitante', 'usuario_autorizado'])->delete();
    }
}
