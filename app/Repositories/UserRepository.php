<?php 

namespace Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Repositories\Contracts\RepostoryContract;

class UserRepository implements RepostoryContract
{    
    public function insert(array $data): User
    {
        return User::create($data);
    }
}