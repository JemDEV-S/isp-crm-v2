<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'NORETEL CRM') }} - Iniciar Sesión</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen flex">
        <!-- Left Side - Branding -->
        <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-primary-600 via-primary-500 to-primary-700 relative overflow-hidden">
            <!-- Decorative Elements -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 left-0 w-96 h-96 bg-white rounded-full -translate-x-1/2 -translate-y-1/2"></div>
                <div class="absolute bottom-0 right-0 w-96 h-96 bg-white rounded-full translate-x-1/2 translate-y-1/2"></div>
            </div>

            <!-- Content -->
            <div class="relative z-10 flex flex-col justify-center px-16 w-full">
                <!-- Logo -->
                <div class="flex items-center space-x-3 mb-12">
                    <div class="w-16 h-16 rounded-xl bg-white bg-opacity-20 backdrop-blur-sm flex items-center justify-center">
                        <span class="text-3xl font-bold text-white">N</span>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-white">NORETEL</h1>
                        <p class="text-primary-100">ISP Management System</p>
                    </div>
                </div>

                <!-- Features -->
                <div class="space-y-8">
                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-white bg-opacity-20 backdrop-blur-sm flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold text-white mb-2">Gestión Completa</h3>
                            <p class="text-primary-100">Administra clientes, servicios, facturación e inventario desde un solo lugar</p>
                        </div>
                    </div>

                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-white bg-opacity-20 backdrop-blur-sm flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold text-white mb-2">Operaciones Ágiles</h3>
                            <p class="text-primary-100">Workflows automatizados para instalaciones, soporte técnico y cobranza</p>
                        </div>
                    </div>

                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-white bg-opacity-20 backdrop-blur-sm flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold text-white mb-2">Reportes en Tiempo Real</h3>
                            <p class="text-primary-100">Métricas y análisis para tomar decisiones informadas</p>
                        </div>
                    </div>
                </div>

                <!-- Bottom Text -->
                <div class="mt-16 pt-8 border-t border-white border-opacity-20">
                    <p class="text-primary-100 text-sm">
                        © {{ date('Y') }} NORETEL. Sistema diseñado para proveedores de Internet.
                    </p>
                </div>
            </div>
        </div>

        <!-- Right Side - Login Form -->
        <div class="flex-1 flex items-center justify-center px-6 py-12 bg-secondary-50">
            <div class="w-full max-w-md">
                <!-- Mobile Logo -->
                <div class="lg:hidden flex items-center justify-center space-x-3 mb-8">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center">
                        <span class="text-xl font-bold text-white">N</span>
                    </div>
                    <h1 class="text-2xl font-bold text-secondary-900">NORETEL</h1>
                </div>

                <!-- Login Card -->
                <div class="bg-white rounded-2xl shadow-soft-lg p-8 border border-secondary-200">
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold text-secondary-900 mb-2">Bienvenido de nuevo</h2>
                        <p class="text-secondary-600">Ingresa tus credenciales para acceder al sistema</p>
                    </div>

                    <!-- Session Status -->
                    @if (session('status'))
                        <div class="mb-6 p-4 bg-success-50 border border-success-200 text-success-800 rounded-lg text-sm">
                            {{ session('status') }}
                        </div>
                    @endif

                    <!-- Validation Errors -->
                    @if ($errors->any())
                        <div class="mb-6 p-4 bg-danger-50 border border-danger-200 rounded-lg">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-danger-500 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div class="flex-1">
                                    <h3 class="text-sm font-semibold text-danger-800 mb-1">Error de autenticación</h3>
                                    <ul class="text-sm text-danger-700 list-disc list-inside space-y-1">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" x-data="{ loading: false }" @submit="loading = true">
                        @csrf

                        <!-- Email -->
                        <div class="mb-4">
                            <label for="email" class="block text-sm font-medium text-secondary-700 mb-2">
                                Correo Electrónico
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <svg class="w-5 h-5 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                    </svg>
                                </div>
                                <input
                                    id="email"
                                    type="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    required
                                    autofocus
                                    autocomplete="username"
                                    class="block w-full pl-10 pr-3 py-3 border border-secondary-300 rounded-lg shadow-sm
                                           focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500
                                           text-secondary-900 placeholder-secondary-400 transition-colors"
                                    placeholder="tu@email.com"
                                >
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="mb-4" x-data="{ showPassword: false }">
                            <label for="password" class="block text-sm font-medium text-secondary-700 mb-2">
                                Contraseña
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <svg class="w-5 h-5 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <input
                                    id="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    name="password"
                                    required
                                    autocomplete="current-password"
                                    class="block w-full pl-10 pr-10 py-3 border border-secondary-300 rounded-lg shadow-sm
                                           focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500
                                           text-secondary-900 placeholder-secondary-400 transition-colors"
                                    placeholder="••••••••"
                                >
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-secondary-400 hover:text-secondary-600"
                                >
                                    <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Remember Me & Forgot Password -->
                        <div class="flex items-center justify-between mb-6">
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    name="remember"
                                    class="w-4 h-4 text-primary-600 bg-white border-secondary-300 rounded
                                           focus:ring-primary-500 focus:ring-2 transition-colors"
                                >
                                <span class="ml-2 text-sm text-secondary-600">Recordarme</span>
                            </label>

                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}"
                                   class="text-sm font-medium text-primary-600 hover:text-primary-700 transition-colors">
                                    ¿Olvidaste tu contraseña?
                                </a>
                            @endif
                        </div>

                        <!-- Submit Button -->
                        <button
                            type="submit"
                            class="w-full flex items-center justify-center px-4 py-3 bg-primary-600 hover:bg-primary-700
                                   text-white font-medium rounded-lg shadow-sm
                                   focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2
                                   disabled:opacity-50 disabled:cursor-not-allowed
                                   transition-all duration-150"
                            :disabled="loading"
                        >
                            <span x-show="!loading">Iniciar Sesión</span>
                            <span x-show="loading" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Ingresando...
                            </span>
                        </button>
                    </form>

                    <!-- Divider -->
                    <div class="relative my-6">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-secondary-200"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white text-secondary-500">Información del sistema</span>
                        </div>
                    </div>

                    <!-- System Info -->
                    <div class="grid grid-cols-2 gap-4 text-center">
                        <div class="p-3 bg-secondary-50 rounded-lg">
                            <div class="text-2xl font-bold text-primary-600">{{ date('H:i') }}</div>
                            <div class="text-xs text-secondary-600 mt-1">Hora del servidor</div>
                        </div>
                        <div class="p-3 bg-secondary-50 rounded-lg">
                            <div class="flex items-center justify-center space-x-1">
                                <div class="w-2 h-2 bg-success-500 rounded-full animate-pulse"></div>
                                <span class="text-sm font-semibold text-secondary-900">Online</span>
                            </div>
                            <div class="text-xs text-secondary-600 mt-1">Estado del sistema</div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-6 text-center text-sm text-secondary-500">
                    <p>¿Necesitas ayuda? Contacta al administrador</p>
                    <p class="mt-2">
                        <a href="mailto:soporte@noretel.com" class="text-primary-600 hover:text-primary-700 font-medium">
                            soporte@noretel.com
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
