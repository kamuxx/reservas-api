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
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function ($space) {
            if (!$space->uuid) {
                $space->uuid = Str::uuid()->toString();
            }
        });
    }
    public function getRouteKeyName()
    {
        return 'uuid';
    }

    public function spaceType()
    {
        return $this->belongsTo(SpaceType::class, 'spaces_type_id', 'uuid');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id', 'uuid');
    }

    public function pricingRule()
    {
        return $this->belongsTo(PricingRule::class, 'pricing_rule_id', 'uuid');
    }

    public function scopeActive($query)
    {
        return $query->where('spaces.is_active', true);
    }

    public function scopeByType($query, $spaceTypeId)
    {
        if ($spaceTypeId) {
            return $query->where('spaces.spaces_type_id', $spaceTypeId);
        }
        return $query;
    }

    public function scopeByMinCapacity($query, $minCapacity)
    {
        if ($minCapacity) {
            return $query->where('spaces.capacity', '>=', $minCapacity);
        }
        return $query;
    }

    public function scopeWithAllFeatures($query, $featureIds)
    {
        if ($featureIds && is_array($featureIds) && count($featureIds) > 0) {
            foreach ($featureIds as $featureId) {
                $query->whereExists(function ($subquery) use ($featureId) {
                    $subquery->select(\DB::raw(1))
                        ->from('space_features')
                        ->whereColumn('space_features.space_id', 'spaces.uuid')
                        ->where('space_features.feature_id', $featureId);
                });
            }
        }
        return $query;
    }

    public function scopeByPriceRange($query, $minPrice = null, $maxPrice = null)
    {
        if ($minPrice !== null || $maxPrice !== null) {
            $query->join('pricing_rules', 'spaces.pricing_rule_id', '=', 'pricing_rules.uuid');
            
            if ($minPrice !== null) {
                $query->where('pricing_rules.price_adjustment', '>=', $minPrice);
            }

            if ($maxPrice !== null) {
                $query->where('pricing_rules.price_adjustment', '<=', $maxPrice);
            }
        }
        return $query;
    }

    public function scopeAvailableOnDate($query, $date)
    {
        return $query->where(function ($q) use ($date) {
            $q->whereNotExists(function ($subquery) {
                $subquery->select(\DB::raw(1))
                    ->from('space_availability')
                    ->whereColumn('space_availability.space_id', 'spaces.uuid')
                    ->whereNull('space_availability.deleted_at');
            })
            ->orWhereExists(function ($subquery) use ($date) {
                $subquery->select(\DB::raw(1))
                    ->from('space_availability')
                    ->whereColumn('space_availability.space_id', 'spaces.uuid')
                    ->whereDate('space_availability.available_date', $date)
                    ->where('space_availability.is_available', true)
                    ->whereNull('space_availability.deleted_at');
            });
        });
    }

    public function scopeNotFullyBooked($query, $date)
    {
        $driver = \DB::connection()->getDriverName();
        $timeDiffSql = $driver === 'sqlite'
            ? 'SUM(strftime("%s", end_time) - strftime("%s", start_time))'
            : 'SUM(TIMESTAMPDIFF(SECOND, start_time, end_time))';

        return $query->whereNotExists(function ($subquery) use ($date, $timeDiffSql) {
            $subquery->select(\DB::raw(1))
                ->from('reservation')
                ->whereColumn('reservation.space_id', 'spaces.uuid')
                ->whereDate('reservation.event_date', $date)
                ->whereNotExists(function ($canceledQuery) {
                    $canceledQuery->select(\DB::raw(1))
                        ->from('status')
                        ->whereColumn('status.uuid', 'reservation.status_id')
                        ->where('status.name', 'canceled');
                })
                ->whereNull('reservation.deleted_at')
                ->groupBy('reservation.space_id')
                ->havingRaw("$timeDiffSql >= 86400");
        });
    }
}
