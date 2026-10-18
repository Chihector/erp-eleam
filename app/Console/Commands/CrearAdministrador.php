<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Crea (o restablece) un usuario administrador con contraseña segura.
 *
 * Uso típico en el servidor:
 *   php artisan eleam:admin-crear admin@eleam.cl
 *   php artisan eleam:admin-crear admin@eleam.cl --clave=MiClaveSegura123
 *
 * Si no se entrega --clave, se genera una contraseña aleatoria y se muestra
 * una sola vez en pantalla.
 */
class CrearAdministrador extends Command
{
    protected $signature = 'eleam:admin-crear
                            {email : Correo electrónico del administrador}
                            {--clave= : Contraseña (si se omite, se genera una segura)}
                            {--nombre=Administrador : Nombre}
                            {--apellido=General : Apellido}
                            {--rut= : RUT chileno (requerido solo si el usuario no existe)}';

    protected $description = 'Crea o restablece un usuario administrador con una contraseña segura.';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $clave = (string) ($this->option('clave') ?: Str::password(16, symbols: false));

        $usuario = User::query()->where('email', $email)->first();

        if ($usuario) {
            $usuario->update(['password' => $clave]);
            $this->info("Contraseña actualizada para el usuario existente: {$email}");
        } else {
            $rut = (string) ($this->option('rut') ?: '');

            if ($rut === '') {
                $this->error('El usuario no existe. Debes indicar su RUT con --rut=12345678-9 para crearlo.');

                return self::FAILURE;
            }

            $usuario = User::create([
                'email' => $email,
                'password' => $clave,
                'nombre' => (string) $this->option('nombre'),
                'apellido' => (string) $this->option('apellido'),
                'rut' => $rut,
            ]);

            $this->info("Usuario administrador creado: {$email}");
        }

        // Garantiza que el rol exista y esté asignado.
        $rol = Role::firstOrCreate(['name' => 'administrador']);

        if (! $usuario->hasRole($rol)) {
            $usuario->assignRole($rol);
        }

        $this->newLine();
        $this->line('  ─────────────────────────────────────────────');
        $this->line('   Credenciales de administrador (guárdalas)');
        $this->line('  ─────────────────────────────────────────────');
        $this->line("   Usuario : {$email}");
        $this->line("   Clave   : {$clave}");
        $this->line('  ─────────────────────────────────────────────');
        $this->newLine();

        return self::SUCCESS;
    }
}
