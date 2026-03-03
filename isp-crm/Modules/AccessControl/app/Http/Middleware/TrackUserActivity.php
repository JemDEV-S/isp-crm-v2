<?php

declare(strict_types=1);

namespace Modules\AccessControl\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\AccessControl\Entities\UserSession;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            // Actualizar o crear sesión del usuario
            UserSession::updateOrCreate(
                [
                    'user_id' => auth()->id(),
                    'ip_address' => $request->ip(),
                ],
                [
                    'user_agent' => $request->userAgent(),
                    'last_activity' => now(),
                ]
            );
        }

        return $next($request);
    }
}
