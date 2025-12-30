<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\Space\RegisterSpaceRequest;
use App\Models\Space;
use Illuminate\Support\Facades\DB;
use UseCases\SpaceUseCases;

class SpaceController extends Controller
{
    public function __construct(private SpaceUseCases $spaceUseCases){}

    public function store(RegisterSpaceRequest $request)
    {
        try {
            $data = $request->validated();
            $data['created_by'] = auth('api')->user()->uuid;
            $space = $this->spaceUseCases->register($data);
            return $this->success(201, "Espacio creado exitosamente", $space->toArray());
        } catch (\Throwable $th) {
            return $this->serverError($th, "Error al crear el espacio");
        }
    }
}
