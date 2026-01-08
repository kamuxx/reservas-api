<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        $this->call([
            RoleSeeder::class,
            StatusSeeder::class,
            SpaceTypeSeeder::class,
            PricingRuleSeeder::class,
            UserAdminSeeder::class,
            SpaceTableSeeder::class,
        ]);

        User::factory()->count(5)->create();

        Schema::enableForeignKeyConstraints();
    }
}
