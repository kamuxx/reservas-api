<?php

namespace App\Actions\SpaceComment;

use App\Repositories\Contracts\SpaceCommentRepositoryInterface;
use Illuminate\Validation\ValidationException;

class CreateSpaceCommentAction
{
    protected $repository;

    public function __construct(SpaceCommentRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function execute(array $data)
    {
        // Business logic validations could go here

        return $this->repository->store($data);
    }
}
