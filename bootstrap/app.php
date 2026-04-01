<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureAdminOrModerator;
use App\Http\Middleware\EnsureOrgAccess;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Custom middleware aliases
        $middleware->alias([
            'org.access'  => EnsureOrgAccess::class,
            'role.admin'  => EnsureAdmin::class,
            'role.staff'  => EnsureAdminOrModerator::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
