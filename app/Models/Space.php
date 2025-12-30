<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Space extends Model
{
    use HasFactory;
    
    protected $table = 'spaces';

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'capacity',
        'spaces_type_id',
        'status_id',
        'pricing_rule_id',
        'is_active',
        'created_by',
    ];


    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function ($space) {
            $space->uuid = Str::uuid()->toString();
        });
    }
    public function getRouteKeyName()
    {
        return 'uuid';
    }

    public function spaceType()
    {
        return $this->belongsTo(SpaceType::class, 'spaces_type_id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    public function pricingRule()
    {
        return $this->belongsTo(PricingRule::class, 'pricing_rule_id');
    }
}
