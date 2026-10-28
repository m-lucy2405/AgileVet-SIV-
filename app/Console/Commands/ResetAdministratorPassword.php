<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('agilevet:reset-admin-password')]
#[Description('Reset the password of an AgileVet administrator interactively')]
class ResetAdministratorPassword extends Command
{
    public function handle(): int
    {
        $administratorRoleId = Role::query()
            ->where('nombre', 'Administrador')
            ->value('id');

        if ($administratorRoleId === null) {
            $this->error('No existe el rol Administrador.');

            return self::FAILURE;
        }

        $email = trim((string) $this->ask('Correo del administrador'));

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->error('Ingresa un correo válido.');

            return self::FAILURE;
        }

        $administrator = User::query()
            ->where('email', $email)
            ->where('rol_id', $administratorRoleId)
            ->first();

        if ($administrator === null) {
            $this->error('No existe un administrador con ese correo.');

            return self::FAILURE;
        }

        $password = $this->secret('Nueva contraseña (mínimo 12 caracteres)');

        if (! is_string($password) || strlen($password) < 12) {
            $this->error('La contraseña debe tener al menos 12 caracteres.');

            return self::FAILURE;
        }

        $passwordConfirmation = $this->secret('Confirma la nueva contraseña');

        if (! is_string($passwordConfirmation) || $password !== $passwordConfirmation) {
            $this->error('Las contraseñas no coinciden.');

            return self::FAILURE;
        }

        $administrator->forceFill(['password_hash' => $password])->save();

        $this->info('Contraseña del administrador actualizada correctamente.');

        return self::SUCCESS;
    }
}
