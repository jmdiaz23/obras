<?php

use App\Http\Middleware\EnsureEmpresaUser;
use App\Http\Middleware\EnsureSuperAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Nginx/1Panel hace proxy desde localhost o desde la red Docker.
        // Sin esto, Laravel no ve X-Forwarded-Proto=https ni la IP real.
        $middleware->trustProxies(at: ['127.0.0.1', '::1', '172.16.0.0/12']);

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => auth()->user()?->esSuperAdmin()
            ? route('admin.dashboard')
            : route('dashboard'));

        $middleware->alias([
            'empresa' => EnsureEmpresaUser::class,
            'superadmin' => EnsureSuperAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
