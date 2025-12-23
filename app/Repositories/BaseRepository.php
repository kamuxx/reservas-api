<?php 

namespace Repositories;

class BaseRepository
{
 
    public function insert(Model $model, array $data): Model
    {
        return $model::create($data);
    }
}