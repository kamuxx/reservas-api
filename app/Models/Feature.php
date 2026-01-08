<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Feature extends Model
{
    protected $table = 'features';

    protected $fillable = [
        'uuid',
        'name',
    ];

    protected $hidden = [
        'id',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function ($feature) {
            if (!$feature->uuid) {
                $feature->uuid = Str::uuid()->toString();
            }
        });
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
