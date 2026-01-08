<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Status;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
    
class UserAdminSeeder extends Seeder
{
    
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::truncate();
        User::factory()->create([
            'name' => 'Admin',
            'uuid' => Str::uuid(),
            'email' => 'admin@admin.com',
            'password' => Hash::make('Admin@123'),
            'role_id' => Role::where('name', 'admin')->first()->uuid,
            'status_id' => Status::where('name', 'active')->first()->uuid,
            'phone' => '584165359897',
        ]);
    }
}
