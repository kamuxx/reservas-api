<?php 

namespace UseCases;

use Repositories\UserRepository;

class UserUseCases
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    public function registerUser(array $data): User
    {
        return $this->userRepository->insert($data);
    }
}   