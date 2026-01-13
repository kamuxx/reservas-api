<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservationDetailView extends Model
{
    protected $table = 'reservation_details_view';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    /**
     * Scope para filtrar por usuario.
     */
    public function scopeByUser($query, string $userUuid)
    {
        return $query->where('user_uuid', $userUuid);
    }
}
