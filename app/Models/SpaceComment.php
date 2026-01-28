<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpaceComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'space_id',
        'user_id',
        'comment',
        'rating',
    ];

    public function space()
    {
        return $this->belongsTo(Space::class, 'space_id', 'uuid');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'uuid'); // Assuming uuid is correct based on existing code
    }

    public function scopeByLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
