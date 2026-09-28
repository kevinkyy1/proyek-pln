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
        <aside class="shrink-0 bg-slate-800 p-4 text-slate-100 lg:w-56">
            <div class="mb-3 font-semibold lg:mb-6">{{ config('app.name') }}</div>
            <nav class="flex flex-wrap gap-1 text-sm lg:flex-col">
                <a href="{{ route('home') }}"
                   class="block rounded px-3 py-2 {{ request()->routeIs('home') ? 'bg-slate-700 font-medium' : 'hover:bg-slate-700' }}">
                    Data Table
                </a>
            </nav>
        </aside>

        {{-- min-w-0 penting: tanpa ini tabel lebar memaksa halaman ikut melebar --}}
        <main class="min-w-0 flex-1 p-3 sm:p-4 lg:p-6">
            @yield('content')
        </main>
    </div>
</body>
</html>
