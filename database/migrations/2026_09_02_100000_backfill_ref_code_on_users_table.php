<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

// A partir de ahora el código de referido se genera solo al crear un
// usuario (ver Admin\UserController::generateRefCode) — esta migración
// solo le pone uno a los usuarios que ya existían antes de ese cambio y
// todavía no tienen ninguno. A los que ya tenían uno cargado a mano
// (columna ref_code no nula) no los toca, para no cambiarles el link que
// ya puedan tener compartido.
return new class extends Migration
{
    public function up(): void
    {
        User::whereNull('ref_code')->get()->each(function (User $user) {
            $base = Str::slug($user->name) ?: 'user'.$user->id;
            $code = $base;
            $i = 2;

            while (User::where('ref_code', $code)->exists()) {
                $code = $base.$i;
                $i++;
            }

            $user->update(['ref_code' => $code]);
        });
    }

    public function down(): void
    {
        // No revertible a propósito: no hay forma de saber cuáles códigos
        // los puso esta migración y cuáles los cargó un admin a mano.
    }
};
