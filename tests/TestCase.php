<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        // Crear archivo de base de datos si no existe (antes de arrancar la app)
        $dbPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'reservas-testing.db';
        if (!file_exists($dbPath)) {
            touch($dbPath);
        }

        parent::setUp();

        // Ejecutar migraciones una sola vez al inicio
        static $migrated = false;
        if (!$migrated) {
            $this->artisan('migrate:fresh --env=testing');
            $migrated = true;
        }
    }

}
