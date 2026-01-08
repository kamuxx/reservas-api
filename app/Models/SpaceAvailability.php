<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SpaceAvailability extends Model
{
    use SoftDeletes;

    protected $table = 'space_availability';

    protected $fillable = [
        'uuid',
        'space_id',
        'available_date',
        'available_from',
        'available_to',
        'is_available',
        'max_capacity',
        'slot_price',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'available_date' => 'date',
        'available_from' => 'datetime',
        'available_to' => 'datetime',
        'is_available' => 'boolean',
        'max_capacity' => 'integer',
        'slot_price' => 'decimal:2',
    ];

    public function space()
    {
        return $this->belongsTo(Space::class, 'space_id', 'uuid');
    }

    protected static function booted()
    {
        parent::booted();

        static::creating(function ($spaceAvailability) {
            if (!$spaceAvailability->uuid) {
                $spaceAvailability->uuid = Str::uuid()->toString();
            }
        });
    }
}
