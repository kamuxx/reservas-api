<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\Space\RegisterSpaceRequest;

class SpaceController extends Controller
{
    public function __construct(){}

    public function store(RegisterSpaceRequest $request)
    {
        return $this->success(201);
    }
    
    
}
