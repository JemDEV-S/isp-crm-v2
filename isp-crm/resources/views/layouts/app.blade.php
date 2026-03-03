<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ sidebarOpen: true, darkMode: false }"
      :class="{ 'dark': darkMode }" x-cloak>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'NORETEL CRM') }} - @yield('title', 'Dashboard')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="font-sans antialiased bg-secondary-50">
    <div class="flex h-screen overflow-hidden">

        <!-- Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-50 flex flex-col transition-all duration-300 bg-white border-r border-secondary-200 shadow-soft"
            :class="sidebarOpen ? 'w-64' : 'w-20'"
        >
            <!-- Logo -->
            <div class="flex items-center justify-between h-16 px-4 border-b border-secondary-200">
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-3" x-show="sidebarOpen">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center">
                        <span class="text-xl font-bold text-white">N</span>
                    </div>
                    <span class="text-xl font-bold text-secondary-900">NORETEL</span>
                </a>
                <div x-show="!sidebarOpen" class="w-10 h-10 rounded-lg bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center">
                    <span class="text-xl font-bold text-white">N</span>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-2 py-4 space-y-1 overflow-y-auto">
                @include('layouts.partials.navigation')
            </nav>

            <!-- User Menu -->
            <div class="border-t border-secondary-200 p-4">
                <div class="flex items-center" :class="sidebarOpen ? 'space-x-3' : 'justify-center'">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center">
                            <span class="text-sm font-semibold text-primary-700">
                                {{ substr(auth()->user()->name, 0, 2) }}
                            </span>
                        </div>
                    </div>
                    <div x-show="sidebarOpen" class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-secondary-900 truncate">
                            {{ auth()->user()->name }}
                        </p>
                        <p class="text-xs text-secondary-500 truncate">
                            {{ auth()->user()->email }}
                        </p>
                    </div>
                    <div x-show="sidebarOpen">
                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button class="text-secondary-400 hover:text-secondary-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                    </svg>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                {{-- <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-secondary-700 hover:bg-secondary-50">
                                    Perfil
                                </a> --}}
                                {{-- <a href="{{ route('settings') }}" class="block px-4 py-2 text-sm text-secondary-700 hover:bg-secondary-50">
                                    Configuración
                                </a> --}}
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-secondary-700 hover:bg-secondary-50">
                                        Cerrar Sesión
                                    </button>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>
                </div>
            </div>

            <!-- Toggle Button -->
            <button
                @click="sidebarOpen = !sidebarOpen"
                class="absolute -right-3 top-20 w-6 h-6 bg-white border border-secondary-200 rounded-full flex items-center justify-center text-secondary-600 hover:bg-secondary-50 shadow-soft"
            >
                <svg class="w-3 h-3 transition-transform" :class="!sidebarOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
        </aside>

        <!-- Main Content -->
        <div class="flex flex-col flex-1 overflow-hidden transition-all duration-300"
             :class="sidebarOpen ? 'ml-64' : 'ml-20'">

            <!-- Top Header -->
            <header class="h-16 bg-white border-b border-secondary-200 shadow-soft">
                <div class="flex items-center justify-between h-full px-6">
                    <!-- Breadcrumb -->
                    <div class="flex items-center space-x-2 text-sm">
                        @yield('breadcrumb')
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center space-x-4">
                        <!-- Search -->
                        <div class="relative" x-data="{ searchOpen: false }">
                            <button @click="searchOpen = !searchOpen"
                                    class="p-2 text-secondary-400 hover:text-secondary-600 rounded-lg hover:bg-secondary-50">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </button>
                        </div>

                        <!-- Notifications -->
                        <div class="relative" x-data="{ notificationsOpen: false }">
                            <button @click="notificationsOpen = !notificationsOpen"
                                    class="relative p-2 text-secondary-400 hover:text-secondary-600 rounded-lg hover:bg-secondary-50">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                <span class="absolute top-1 right-1 w-2 h-2 bg-danger-500 rounded-full"></span>
                            </button>
                        </div>

                        <!-- Quick Actions -->
                        @yield('quick-actions')
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto bg-secondary-50 p-6">
                <!-- Alerts -->
                @if(session('success'))
                    <x-alert variant="success" class="mb-4">
                        {{ session('success') }}
                    </x-alert>
                @endif

                @if(session('error'))
                    <x-alert variant="danger" class="mb-4">
                        {{ session('error') }}
                    </x-alert>
                @endif

                @if($errors->any())
                    <x-alert variant="danger" class="mb-4">
                        <ul class="list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </x-alert>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toast-container" class="fixed bottom-4 right-4 z-50 space-y-2"></div>

    @stack('modals')
    @stack('scripts')
</body>
</html>
