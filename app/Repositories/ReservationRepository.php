<?php

namespace Repositories;

use App\Models\Reservation;
use App\Models\Status;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ReservationRepository extends BaseRepository
{
    const MODEL = Reservation::class;

    public static function getOccupiedSlots(string $spaceUuid, string $startDate, string $endDate): Collection
    {
        $confirmedStatus = Status::where('name', 'confirmada')->first();
        $statusUuid = $confirmedStatus ? $confirmedStatus->uuid : null;

        $query = self::MODEL::query()
            ->where('space_id', $spaceUuid)
            ->where('status_id', $statusUuid)
            ->whereDate('event_date', '>=', $startDate)
            ->whereDate('event_date', '<=', $endDate);

        return $query->orderBy('event_date')
            ->orderBy('start_time')
            ->get(['event_date', 'start_time', 'end_time']);
    }

    public static function hasOverlap(string $spaceUuid, string $date, string $startTime, string $endTime): bool
    {
        $cancelledStatus = Status::where('name', 'cancelada')->first();
        $cancelledUuid = $cancelledStatus ? $cancelledStatus->uuid : null;

        return self::MODEL::query()
            ->where('space_id', $spaceUuid)
            ->whereDate('event_date', $date)
            ->where(function ($query) use ($cancelledUuid) {
                if ($cancelledUuid) {
                    $query->where('status_id', '!=', $cancelledUuid);
                }
            })
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime, $endTime) {
                    // El evento existente empieza antes de que el nuevo termine
                    // Y el evento existente termina después de que el nuevo empiece
                    $q->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
                });
            })
            ->exists();
    }

    public static function create(array $data): Reservation
    {
        return self::MODEL::create($data);
    }
}
