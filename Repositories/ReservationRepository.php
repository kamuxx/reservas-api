<?php

namespace Repositories;

use App\Models\Reservation;
use App\Models\Status;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection as BaseCollection;

class ReservationRepository extends BaseRepository
{
    const MODEL = Reservation::class;

    public static function getOccupiedSlots(string $spaceUuid, string $startDate, string $endDate): Collection
    {
        $confirmedStatus = Status::where('name', 'confirmada')->first();
        // Since we are using the view, we query against 'status_name' or 'status_uuid'
        // Ideally the view already has the status name. Let's filter by the view's columns.

        $query = \App\Models\ReservationDetailView::query()
            ->where('space_uuid', $spaceUuid)
            ->where('status_name', 'active')
            ->whereDate('event_date', '>=', $startDate)
            ->whereDate('event_date', '<=', $endDate);

        return $query->orderBy('event_date')
            ->orderBy('start_time')
            ->get();
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

    public static function getByUser(string $userUuid): Collection
    {
        return \App\Models\ReservationDetailView::byUser($userUuid)
            ->orderBy('reservation_created_at', 'desc')
            ->get();
    }

    public static function getDetail(string $reservationUuid)
    {
        return \App\Models\ReservationDetailView::where('reservation_uuid', $reservationUuid)->first();
    }

    public static function getAll(string $modelClassName): Collection|BaseCollection
    {
        return \App\Models\ReservationDetailView::query()
            ->orderBy('reservation_created_at', 'desc')
            ->get();
    }

    public static function getBySpace(string $spaceUuid): Collection
    {
        return \App\Models\ReservationDetailView::query()
            ->where('space_uuid', $spaceUuid)
            ->orderBy('event_date', 'asc')
            ->get();
    }
}
