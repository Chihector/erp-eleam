<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar caché de roles
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear Roles
        $roleAdmin = Role::firstOrCreate(['name' => 'administrador']);
        $roleJefeTurno = Role::firstOrCreate(['name' => 'jefe de turno']);
        $roleContador = Role::firstOrCreate(['name' => 'contador']);
        $roleSoloLectura = Role::firstOrCreate(['name' => 'solo lectura']);

        // Crear un usuario Administrador de prueba
        $admin = User::firstOrCreate(
            ['email' => 'admin@eleam.cl'],
            [
                'nombre' => 'Administrador',
                'apellido' => 'Sistema',
                'rut' => '11111111-1',
                'password' => Hash::make('password'),
            ]
        );
        $admin->assignRole($roleAdmin);

        // Crear un usuario de ejemplo con rol de Jefe de Turno
        $jefe = User::firstOrCreate(
            ['email' => 'hector@eleam.cl'],
            [
                'nombre' => 'Héctor',
                'apellido' => 'Contreras',
                'rut' => '19486694-1',
                'password' => Hash::make('password'),
            ]
        );
        $jefe->assignRole($roleJefeTurno);
    }
}
