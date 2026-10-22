<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar caché de roles
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear Roles
        $roleAdmin = Role::firstOrCreate(['name' => 'administrador']);
        $roleJefeTurno = Role::firstOrCreate(['name' => 'jefe de turno']);
        Role::firstOrCreate(['name' => 'contador']);
        Role::firstOrCreate(['name' => 'solo lectura']);

        // Nunca crear cuentas conocidas fuera del entorno local.
        if (! app()->environment('local')) {
            $this->command?->warn('Entorno no local: no se crean usuarios de ejemplo. Usa php artisan eleam:admin-crear.');

            return;
        }

        $credenciales = [];

        $claveAdmin = Str::password(16, symbols: false);
        $admin = User::firstOrCreate(
            ['email' => 'admin@eleam.cl'],
            [
                'nombre' => 'Administrador',
                'apellido' => 'Sistema',
                'rut' => '11111111-1',
                'password' => $claveAdmin,
            ]
        );
        $admin->assignRole($roleAdmin);

        if ($admin->wasRecentlyCreated) {
            $credenciales[] = ['Administrador', $admin->email, $claveAdmin];
        }

        $claveJefe = Str::password(16, symbols: false);
        $jefe = User::firstOrCreate(
            ['email' => 'hector@eleam.cl'],
            [
                'nombre' => 'Héctor',
                'apellido' => 'Contreras',
                'rut' => '19486694-1',
                'password' => $claveJefe,
            ]
        );
        $jefe->assignRole($roleJefeTurno);

        if ($jefe->wasRecentlyCreated) {
            $credenciales[] = ['Jefe de Turno', $jefe->email, $claveJefe];
        }

        if ($credenciales === []) {
            $this->command?->line('Usuarios demo existentes: se conservaron sus contraseñas actuales.');

            return;
        }

        $this->command?->newLine();
        $this->command?->warn('Contraseñas generadas para usuarios demo (se muestran una sola vez):');

        foreach ($credenciales as [$rol, $email, $clave]) {
            $this->command?->line("  {$rol}: {$email} / {$clave}");
        }

        $this->command?->warn('Guárdalas en un gestor de contraseñas. No las publiques ni las reutilices en producción.');
    }
}
