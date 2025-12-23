<?php 

namespace Repositories\Contracts;

interface RepostoryContract
{
    public function insert(array $data): Model;
}