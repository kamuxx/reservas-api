<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['uuid' => (string) Str::uuid(), 'name' => 'active'],
            ['uuid' => (string) Str::uuid(), 'name' => 'inactive'],
            ['uuid' => (string) Str::uuid(), 'name' => 'pending'],
            ['uuid' => (string) Str::uuid(), 'name' => 'canceled'],
            ['uuid' => (string) Str::uuid(), 'name' => 'blocked'],
            ['uuid' => (string) Str::uuid(), 'name' => 'maintenance'],
        ];

        foreach ($statuses as $status) {
            Status::create($status);
        }
    }
}
