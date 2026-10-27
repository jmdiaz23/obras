<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmpresaUser
{
    /**
     * Las rutas de trabajo (proyectos, APUs, catálogos) siempre pertenecen a una
     * empresa. El super-admin administra la plataforma desde su propio panel.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->esSuperAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        if (! $user?->empresa_id) {
            abort(403, 'Tu usuario no está asociado a una empresa.');
        }

        return $next($request);
    }
}
