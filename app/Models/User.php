<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    // 'role' queda AFUERA a propósito (se asigna a mano en
    // Admin\UserController, nunca por mass assignment) — es el único
    // campo del modelo que decide permisos de admin, así que un futuro
    // User::create($request->all()) en cualquier otro lado no podría
    // colarlo. Ver auditoría de seguridad, hallazgo H4.
    protected $fillable = [
        'name',
        'email',
        'password',
        'ref_code',
        'whatsapp_number',
    ];

    public const ROLES = ['admin' => 'Administrador', 'editor' => 'Editor'];

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
