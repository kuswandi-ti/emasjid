<?php

use App\Http\Middleware\EnsureMosqueActive;
use App\Http\Middleware\EnsureOwnerAccess;
use App\Http\Middleware\IdentifyMosque;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register middleware aliases
        $middleware->alias([
            'mosque' => IdentifyMosque::class,
            'mosque.active' => EnsureMosqueActive::class,
            'mosque.ownership' => EnsureOwnerAccess::class,
            'owner' => EnsureOwnerAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
