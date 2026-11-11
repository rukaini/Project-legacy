<?php
// bootstrap/app.php  (Laravel 11+)

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )

    ->withMiddleware(function (Middleware $middleware) {
    // Register all role-based middleware aliases here
    $middleware->alias([
        'auth.hr'        => \App\Http\Middleware\HrAuthenticate::class,
        'auth.teacher'   => \App\Http\Middleware\TeacherAuthenticate::class,
        'auth.principal' => \App\Http\Middleware\PrincipalAuthenticate::class, // Add this
        'auth.staff'     => \App\Http\Middleware\StaffAuthenticate::class,     // Add this
    ]);
})
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
