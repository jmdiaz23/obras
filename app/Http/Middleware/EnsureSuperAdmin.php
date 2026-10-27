<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->esSuperAdmin()) {
            abort(403, 'No tienes permisos para acceder a la administración de la plataforma.');
        }

        return $next($request);
    }
}
