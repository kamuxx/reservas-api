<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSpaceRequest;
use App\Http\Requests\Admin\UpdateSpaceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use UseCases\Admin\CreateSpaceUseCase;
use UseCases\Admin\DeleteSpaceUseCase;
use UseCases\Admin\GetSpacesUseCase;
use UseCases\Admin\UpdateSpaceUseCase;

class SpaceController extends Controller
{
    public function __construct(
        private GetSpacesUseCase $getSpacesUseCase,
        private CreateSpaceUseCase $createSpaceUseCase,
        private UpdateSpaceUseCase $updateSpaceUseCase,
        private DeleteSpaceUseCase $deleteSpaceUseCase
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['name', 'spaces_type_id', 'status_id']);
        $perPage = $request->input('per_page', 15);


        $spaces = $this->getSpacesUseCase->execute($filters, $perPage);

        return response()->json($spaces);
    }

    public function store(StoreSpaceRequest $request): JsonResponse
    {
        $data = $request->validated();
        // Add created_by from authenticated user
        $data['created_by'] = $request->user()->uuid;

        $space = $this->createSpaceUseCase->execute($data);

        return response()->json($space, 201);
    }

    public function update(UpdateSpaceRequest $request, string $uuid): JsonResponse
    {
        $data = $request->validated();

        try {
            $space = $this->updateSpaceUseCase->execute($uuid, $data);
            if (!$space) {
                return response()->json(['message' => 'Space not found or update failed'], 404);
            }
            return response()->json($space);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function destroy(string $uuid): JsonResponse
    {
        try {
            $deleted = $this->deleteSpaceUseCase->execute($uuid);
            if (!$deleted) {
                return response()->json(['message' => 'Space not found'], 404);
            }
            return response()->json(['message' => 'Space deleted successfully']);
        } catch (\Exception $e) {
            $code = $e->getCode() === 409 ? 409 : 500;
            return response()->json(['message' => $e->getMessage()], $code);
        }
    }
}
