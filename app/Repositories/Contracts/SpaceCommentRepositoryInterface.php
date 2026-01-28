<?php

namespace App\Repositories\Contracts;

interface SpaceCommentRepositoryInterface
{
    public function store(array $data);
    public function getBySpaceId(string $spaceId);
}
