<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Role extends Model
{
    protected $table = 'roles';

    protected $fillable = ['name', 'uuid'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($role) {
            $role->uuid = (string) Str::uuid()->toString();
        });
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
