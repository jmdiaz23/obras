<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CrearSuperAdmin extends Command
{
    protected $signature = 'obraos:superadmin
                            {email? : Correo del super administrador}
                            {--name= : Nombre visible}
                            {--password= : Contraseña inicial (mínimo 8 caracteres)}';

    protected $description = 'Crea o actualiza un super administrador de la plataforma';

    public function handle(): int
    {
        $email = strtolower((string) ($this->argument('email') ?: $this->ask('Correo del super administrador')));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('El correo no es válido.');

            return self::FAILURE;
        }

        $name = (string) ($this->option('name') ?: $this->ask('Nombre visible', 'Super Administrador'));
        $password = (string) ($this->option('password') ?: $this->secret('Contraseña inicial (mínimo 8 caracteres)'));

        if (strlen($password) < 8) {
            $this->error('La contraseña debe tener al menos 8 caracteres.');

            return self::FAILURE;
        }

        $existente = User::query()->where('email', $email)->first();

        if ($existente && ! $existente->esSuperAdmin()
            && ! $this->confirm("El correo {$email} pertenece a un usuario de empresa. ¿Promoverlo a super administrador?")) {
            $this->warn('Operación cancelada.');

            return self::FAILURE;
        }

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'empresa_id' => null,
                'name' => $name,
                'password' => $password,
                'role' => User::ROLE_SUPER_ADMIN,
                'es_super_admin' => true,
            ],
        );

        $this->info("Super administrador listo: {$user->email}");
        $this->line('Ingresa por /login; serás redirigido al panel de plataforma.');

        return self::SUCCESS;
    }
}
