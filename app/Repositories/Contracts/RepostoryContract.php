<?php 

namespace Repositories\Contracts;
use Illuminate\Database\Eloquent\Model;

interface RepostoryContract
{
    public function insert(array $data): Model;
}