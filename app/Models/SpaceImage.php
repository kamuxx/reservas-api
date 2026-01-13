<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpaceImage extends Model
{
    use HasFactory;

    protected $table = 'space_images';

    public $timestamps = false; // Based on seeder not inserting timestamps

    protected $fillable = [
        'space_id',
        'image',
        'is_main',
    ];

    public function space()
    {
        return $this->belongsTo(Space::class, 'space_id', 'uuid');
    }
}
