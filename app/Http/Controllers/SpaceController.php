<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\Space\RegisterSpaceRequest;
use App\Http\Requests\Space\UpdateSpaceRequest;
use App\Http\Requests\Space\ListSpacesRequest;
use App\Models\Space;
use Illuminate\Support\Facades\DB;
use UseCases\SpaceUseCases;

class SpaceController extends Controller
{
    public function __construct(private SpaceUseCases $spaceUseCases){}

    public function index(ListSpacesRequest $request)
    {
        try {
            $user = auth('api')->user();
            $isAdmin = $user && $user->role && $user->role->name === 'admin';
            
            $spaces = $this->spaceUseCases->list($request->validated(), $isAdmin);
            return $this->success(200, "Listado de espacios obtenido exitosamente", $spaces->toArray());
        } catch (\Throwable $th) {
            return $this->serverError($th, "Error al obtener el listado de espacios");
        }
    }

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

    public function update(UpdateSpaceRequest $request, Space $space)
    {
        try {
            $data = $request->validated();
            $this->spaceUseCases->update($space, $data);
            return $this->success(200, "Espacio actualizado exitosamente");   
        } catch (\Throwable $th) {
            return $this->serverError($th, "Error al actualizar el espacio");
        }
    }

    public function show(string $id)
    {
        try {
            $user = auth('api')->user();
            $isAdmin = $user && $user->role && $user->role->name === 'admin';
            
            $space = $this->spaceUseCases->find($id, $isAdmin);
            
            if (!$space) {
                return $this->error(404, "Espacio no encontrado", null);
            }
            
            return $this->success(200, "Detalle del espacio obtenido exitosamente", $space->toArray());
        } catch (\Throwable $th) {
            return $this->serverError($th, "Error al obtener el detalle del espacio");
        }
    }
}
