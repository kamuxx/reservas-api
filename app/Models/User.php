<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',      // Identificador único (UUID v4)
        'name',      // Nombre del usuario
        'email',     // Correo electrónico
        'phone',     // Teléfono (faltaba en el modelo)
        'password',  // Contraseña (hasheada)
        'role_id',   // UUID de la tabla roles (antes 'rol')
        'status_id', // UUID de la tabla status (antes 'active')
    ];

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

    protected static function booted(){
        parent::booted();
        static::creating(function ($user) {
            $user->uuid = Str::uuid()->toString();
            
            $role = Role::where('name', 'user')->first();
            if($role) $user->role()->associate($role);
            
            $status = Status::where('name', 'pending')->first();
            if($status) $user->status()->associate($status);
        });
    }

    public function role(){
        return $this->belongsTo(Role::class,"role_id","uuid");
    }

    public function status(){
        return $this->belongsTo(Status::class,"status_id","uuid");
    }
}
