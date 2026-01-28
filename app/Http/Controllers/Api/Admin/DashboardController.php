<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use UseCases\Admin\GetDashboardStatsUseCase;

class DashboardController extends Controller
{
    public function __construct(
        private GetDashboardStatsUseCase $getDashboardStatsUseCase
    ) {}

    public function index(): JsonResponse
    {
        try {
            $stats = $this->getDashboardStatsUseCase->execute();
            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error retrieving stats'], 500);
        }
    }
}
