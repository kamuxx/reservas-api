<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use UseCases\ReservationUseCases;
use Illuminate\Http\JsonResponse;

class ReservationController extends Controller
{
    public function __construct(private ReservationUseCases $reservationUseCases) {}

    /**
     * List all reservations or filter by space (Admin).
     * GET /api/v1/admin/reservations
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Check for space_id filter
            $spaceId = $request->query('space_id');

            if ($spaceId) {
                $reservations = $this->reservationUseCases->getBySpace($spaceId);
            } else {
                $reservations = $this->reservationUseCases->getAll();
            }

            return $this->success(
                200,
                "Reservas obtenidas exitosamente (Admin)",
                $reservations->toArray()
            );
        } catch (\Exception $e) {
            return $this->serverError($e, "Error al obtener el listado de reservas");
        }
    }
}
