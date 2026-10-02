<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-full bg-gray-100 text-gray-800">
    <div class="flex min-h-screen flex-col lg:flex-row">
        <aside class="shrink-0 bg-slate-800 p-4 text-slate-100 lg:w-56 flex flex-col">
            <div class="mb-3 font-semibold lg:mb-6">{{ config('app.name') }}</div>
            <nav class="flex flex-wrap gap-1 text-sm lg:flex-col">
                <a href="{{ route('home') }}"
                    class="block rounded px-3 py-2 {{ request()->routeIs('home') ? 'bg-slate-700 font-medium' : 'hover:bg-slate-700' }}">
                    Data Pelanggan
                </a>
            </nav>

            {{-- User info & logout --}}
            <div class="mt-4 lg:mt-auto border-t border-slate-700 pt-4">
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-sky-500/20 text-sky-300 text-sm font-bold shrink-0">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium truncate">{{ Auth::user()->name }}</p>
                        <p class="text-xs text-slate-400 capitalize">{{ Auth::user()->role }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button type="submit"
                        class="flex w-full items-center gap-2 rounded px-3 py-2 text-sm text-slate-300 hover:bg-slate-700 hover:text-white transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Keluar
                    </button>
                </form>
            </div>
        </aside>

        {{-- min-w-0 penting: tanpa ini tabel lebar memaksa halaman ikut melebar --}}
        <main class="min-w-0 flex-1 p-3 sm:p-4 lg:p-6">
            @yield('content')
        </main>
    </div>
</body>

</html>