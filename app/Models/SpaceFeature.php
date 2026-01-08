<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpaceFeature extends Model
{
    protected $table = 'space_features';

    protected $fillable = [
        'space_id',
        'feature_id',
    ];

    public function space()
    {
        return $this->belongsTo(Space::class);
    }

    public function feature()
    {
        return $this->belongsTo(Feature::class);
    }
}
