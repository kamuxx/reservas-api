<?php 

namespace Repositories;

use Illuminate\Database\Eloquent\Model;

class BaseRepository
{
 
    public function insert(Model $model, array $data): Model
    {
        return $model::create($data);
    }
}