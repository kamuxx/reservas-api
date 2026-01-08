<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::truncate();
        $roles = [
            ['uuid' => (string) Str::uuid(), 'name' => 'admin'],
            ['uuid' => (string) Str::uuid(), 'name' => 'user'],
            ['uuid' => (string) Str::uuid(), 'name' => 'manager'],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
