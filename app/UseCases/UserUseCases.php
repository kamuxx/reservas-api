<?php 

namespace UseCases;

use App\Models\Role;
use App\Models\Status;
use App\Models\User;
use Illuminate\Support\Str;
use Repositories\UserRepository;

class UserUseCases
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    public function registerNewUser(array $data): User
    {
        return $this->userRepository->insert($data);
    }
}   