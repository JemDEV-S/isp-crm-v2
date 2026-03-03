<?php

declare(strict_types=1);

namespace Modules\AccessControl\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Core\Exceptions\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // SuperAdmin tiene todos los permisos
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Verificar si el usuario tiene el permiso
        if (!$user->hasPermission($permission)) {
            throw new UnauthorizedException(
                "No tienes permiso para realizar esta acción. Permiso requerido: {$permission}"
            );
        }

        return $next($request);
    }
}
