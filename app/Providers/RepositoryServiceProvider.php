<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Repositories\Contracts\SpaceRepositoryInterface;
use Repositories\SpaceRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind the SpaceRepositoryInterface to the SpaceRepository implementation
        $this->app->bind(SpaceRepositoryInterface::class, SpaceRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
