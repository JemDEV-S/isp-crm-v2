<?php

namespace Modules\AccessControl\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\AccessControl\Http\Requests\LoginRequest;
use Modules\AccessControl\Services\AuthService;

class AuthController extends Controller
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
        $this->middleware('guest')->except('logout');
    }

    public function showLoginForm()
    {
        return view('accesscontrol::auth.login');
    }

    public function login(LoginRequest $request)
    {
        $this->authService->login(
            $request->credentials(),
            $request->shouldRemember(),
            $request
        );

        // Redirigir a la intención original o al dashboard por defecto
        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        $this->authService->logout($request);

        return redirect()->route('login');
    }
}
