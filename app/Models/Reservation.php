<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Reservation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'reservation';

    protected $fillable = [
        'uuid',
        'reserved_by',
        'space_id',
        'status_id',
        'event_name',
        'event_description',
        'event_date',
        'start_time',
        'end_time',
        'event_price',
        'pricing_rule_id',
        'cancellation_reason',
        'cancellation_by',
    ];

    protected $casts = [
        'event_date' => 'date',
        'event_price' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'reserved_by', 'uuid');
    }

    public function space()
    {
        return $this->belongsTo(Space::class, 'space_id', 'uuid');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id', 'uuid');
    }

    public function pricingRule()
    {
        return $this->belongsTo(PricingRule::class, 'pricing_rule_id', 'uuid');
    }
}
