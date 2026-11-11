<?php

namespace App\Providers;

use App\Auth\MultiTableUserProvider;
use App\Integration\Contracts\PrincipalRepositoryInterface;
use App\Integration\Contracts\TeacherRepositoryInterface;
use App\Integration\Repositories\LocalPrincipalRepository;
use App\Integration\Repositories\LocalTeacherRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TeacherRepositoryInterface::class, LocalTeacherRepository::class);
        $this->app->bind(PrincipalRepositoryInterface::class, LocalPrincipalRepository::class);
    }

    public function boot(): void
    {
        Auth::provider('multi-table', function ($app, array $config) {
            return new MultiTableUserProvider($app['hash'], $config['model']);
        });
    }
}
