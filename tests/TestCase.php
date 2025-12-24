<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Database\Seeders\StatusSeeder;
use Database\Seeders\RoleSeeder;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        $dbConfig = config('database.connections.sqlite');
        
        // Solo intentamos crear el archivo si no es una base de datos en memoria
        if ($dbConfig['driver'] === 'sqlite' && $dbConfig['database'] !== ':memory:') {
            if (!file_exists($dbConfig['database'])) {
                file_put_contents($dbConfig['database'], '');
            }
        }

        $this->artisan('migrate');
        $this->seed(StatusSeeder::class);
        $this->seed(RoleSeeder::class);
    }

}
