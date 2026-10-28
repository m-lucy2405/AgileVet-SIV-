<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('agilevet:create-admin')]
#[Description('Create the initial AgileVet administrator interactively')]
class CreateAdministrator extends Command
{
    public function handle(): int
    {
        $roleId = Role::query()
            ->where('nombre', 'Administrador')
            ->value('id');

        if ($roleId === null) {
            $this->error('Run php artisan db:seed --force before creating the administrator.');

            return self::FAILURE;
        }

        $name = trim((string) $this->ask('Nombre del administrador'));

        if ($name === '') {
            $this->error('El nombre del administrador es obligatorio.');

            return self::FAILURE;
        }

        $email = trim((string) $this->ask('Correo del administrador'));

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->error('Ingresa un correo válido.');

            return self::FAILURE;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->error('Ya existe un usuario con ese correo.');

            return self::FAILURE;
        }

        $password = $this->secret('Contraseña (mínimo 12 caracteres)');

        if (! is_string($password) || strlen($password) < 12) {
            $this->error('La contraseña debe tener al menos 12 caracteres.');

            return self::FAILURE;
        }

        $passwordConfirmation = $this->secret('Confirma la contraseña');

        if (! is_string($passwordConfirmation) || $password !== $passwordConfirmation) {
            $this->error('Las contraseñas no coinciden.');

            return self::FAILURE;
        }

        User::query()->create([
            'nombre' => $name,
            'email' => $email,
            'password_hash' => $password,
            'rol_id' => $roleId,
            'estado' => true,
        ]);

        $this->info('Administrador creado correctamente.');

        return self::SUCCESS;
    }
}
