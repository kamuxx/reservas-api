<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Carbon\Carbon;

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
            $user->assingInitialRoleAndStatus();            
        });

        static::created(function ($user){
            $user->assignTokenVerification();
        });
    }

    public function role(){
        return $this->belongsTo(Role::class,"role_id","uuid");
    }

    public function status(){
        return $this->belongsTo(Status::class,"status_id","uuid");
    }

    public function activationToken(): HasOne
    {
        return $this->hasOne(UserActivationToken::class,"uuid","uuid");
    }

    public function activate()
    {
        $status = Status::where('name', 'active')->first();
        if($status) $this->fill([
            "status_id" => $status->uuid
        ]);
        $this->save();
    }

    public function assingInitialRoleAndStatus():void
    {
        $role = Role::where('name', 'user')->first();
        if(!$role) throw new \Exception("Role not found");  
        $this->role_id = $role->uuid;
        
        $status = Status::where('name', 'pending')->first();
        if(!$status) throw new \Exception("Status not found");
        $this->status_id = $status->uuid;
    }

    public function assignTokenVerification()
    {
        $expiread_at = Carbon::now()->addDays(1)->format('Y-m-d H:i:s');
        if(!$this->activationToken()->exists()){
            $this->activationToken()->create([
                'token' => Str::random(60),
                'activation_code' => random_int(100000, 999999),
                'email' => $this->email,
                'uuid' => $this->uuid,
                'expiread_at' => $expiread_at,
            ]);
        }
    }

    public function isActive(): bool
    {
        return $this->status_id === Status::where('name', 'active')->first()->uuid;
    }
}
