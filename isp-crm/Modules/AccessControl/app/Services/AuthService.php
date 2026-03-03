<?php

declare(strict_types=1);

namespace Modules\AccessControl\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Modules\AccessControl\Entities\User;
use Modules\AccessControl\Entities\UserSession;

class AuthService
{
    public function login( array $credentials, bool $remember, Request $request ): User
    {
        if (!Auth::attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas no son correctas.'],
            ]);
        }

        $user = Auth::user();

        if (!$user->is_active) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => ['La cuenta de usuario está desactivada. Contacta al administrador'],
            ]);
        }
        Session::regenerate();

        $this->updateLastLogin($user);

        $this->recordSession($user->id, $request);

        return $user;
    }

    public function logout(Request $request): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    private function updateLastLogin(User $user): void
    {
        $user->timestamps = false;
        $user->last_login_at = now();
        $user->save();
    }
    private function recordSession(int $userId, Request $request): void
    {
        UserSession::create([
            'user_id' => $userId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'last_activity' => now(),
        ]);
    }
}
