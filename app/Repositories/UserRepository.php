<?php 

namespace Repositories;

use App\Models\User;

class UserRepository extends BaseRepository implements RepostoryContract
{
    
    public function insert(array $data): User
    {
        return parent::insert(User::class, $data);
    }
}