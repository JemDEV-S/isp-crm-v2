<?php

declare(strict_types=1);

namespace Modules\AccessControl\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Core\Exceptions\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Verificar si el usuario tiene alguno de los roles especificados
        if (!$user->hasAnyRole($roles)) {
            throw new UnauthorizedException(
                'No tienes el rol necesario para acceder a esta sección. Roles requeridos: ' . implode(', ', $roles)
            );
        }

        return $next($request);
    }
}
