<?php

namespace App\Repositories;

use App\Models\SpaceComment;
use App\Repositories\Contracts\SpaceCommentRepositoryInterface;

class SpaceCommentRepository implements SpaceCommentRepositoryInterface
{
    public function store(array $data)
    {
        return SpaceComment::create($data);
    }

    public function getBySpaceId(string $spaceId)
    {
        return SpaceComment::where('space_id', $spaceId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
