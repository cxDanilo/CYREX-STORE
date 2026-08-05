<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create';

    protected $description = 'Crea un usuario con acceso al panel de administración';

    public function handle(): int
    {
        $name = $this->ask('Nombre');
        $email = $this->ask('Email');
        $password = $this->secret('Contraseña (no se muestra en pantalla)');
        $confirm = $this->secret('Repetí la contraseña');

        $validator = Validator::make(
            compact('name', 'email', 'password'),
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'unique:users,email'],
                'password' => ['required', 'min:8'],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        if ($password !== $confirm) {
            $this->error('Las contraseñas no coinciden.');

            return self::FAILURE;
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $this->info("Usuario admin creado: {$email}");

        return self::SUCCESS;
    }
}
