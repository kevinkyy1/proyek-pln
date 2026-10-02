<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="h-full">
    <div class="flex min-h-full">
        {{-- Panel kiri — branding --}}
        <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden items-center justify-center"
            style="background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #0ea5e9 100%);">
            {{-- Decorative circles --}}
            <div class="absolute -top-20 -left-20 w-72 h-72 rounded-full opacity-10"
                style="background: radial-gradient(circle, #38bdf8, transparent);"></div>
            <div class="absolute -bottom-32 -right-32 w-96 h-96 rounded-full opacity-10"
                style="background: radial-gradient(circle, #0ea5e9, transparent);"></div>
            <div class="absolute top-1/4 right-1/4 w-48 h-48 rounded-full opacity-5"
                style="background: radial-gradient(circle, #7dd3fc, transparent);"></div>

            <div class="relative z-10 text-center px-12">
                {{-- Logo Icon --}}
                <div class="mx-auto mb-8 flex h-20 w-20 items-center justify-center rounded-2xl shadow-2xl"
                    style="background: rgba(255,255,255,0.1); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.15);">
                    <svg class="h-10 w-10 text-sky-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-white mb-3">Sistem Data PLN</h1>
                <p class="text-sky-200/80 text-lg leading-relaxed max-w-sm mx-auto">
                    Manajemen data pelanggan &amp; pemakaian listrik secara terpusat dan efisien.
                </p>

                {{-- Stats cards --}}
                <div class="mt-10 flex gap-4 justify-center">
                    <div class="rounded-xl px-5 py-3 text-left"
                        style="background: rgba(255,255,255,0.08); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.1);">
                        <div class="text-xs text-sky-300/70 uppercase tracking-wider">Fitur</div>
                        <div class="text-white font-semibold mt-1">Import Data</div>
                    </div>
                    <div class="rounded-xl px-5 py-3 text-left"
                        style="background: rgba(255,255,255,0.08); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.1);">
                        <div class="text-xs text-sky-300/70 uppercase tracking-wider">Fitur</div>
                        <div class="text-white font-semibold mt-1">Analisis Grafik</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Panel kanan — form login --}}
        <div class="flex w-full flex-col items-center justify-center px-6 py-12 lg:w-1/2" style="background: #f8fafc;">

            {{-- Mobile branding --}}
            <div class="lg:hidden mb-8 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-xl"
                    style="background: linear-gradient(135deg, #0f172a, #0ea5e9);">
                    <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <h1 class="text-xl font-bold text-slate-800">Sistem Data PLN</h1>
            </div>

            <div class="w-full max-w-md">
                <div class="rounded-2xl bg-white p-8 shadow-xl" style="border: 1px solid #e2e8f0;">
                    <div class="mb-8 text-center">
                        <h2 class="text-2xl font-bold text-slate-800">Selamat Datang</h2>
                        <p class="mt-2 text-sm text-slate-500">Masuk ke akun Anda untuk melanjutkan</p>
                    </div>

                    {{-- Error alert --}}
                    @if ($errors->any())
                        <div class="mb-6 flex items-start gap-3 rounded-xl p-4"
                            style="background: #fef2f2; border: 1px solid #fecaca;">
                            <svg class="mt-0.5 h-5 w-5 shrink-0 text-red-500" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                            <div>
                                @foreach ($errors->all() as $error)
                                    <p class="text-sm font-medium text-red-700">{{ $error }}</p>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.process') }}" class="space-y-5">
                        @csrf

                        {{-- Username --}}
                        <div>
                            <label for="username"
                                class="mb-1.5 block text-sm font-medium text-slate-700">Username</label>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                                    <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <input type="text" id="username" name="username" value="{{ old('username') }}" required
                                    autofocus autocomplete="username" placeholder="Masukkan username"
                                    class="block w-full rounded-xl border py-3 pl-11 pr-4 text-sm text-slate-800 placeholder-slate-400 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-1"
                                    style="border-color: #cbd5e1; background: #f8fafc;"
                                    onfocus="this.style.borderColor='#0ea5e9'; this.style.boxShadow='0 0 0 3px rgba(14,165,233,0.1)'; this.style.background='#fff';"
                                    onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none'; this.style.background='#f8fafc';">
                            </div>
                        </div>

                        {{-- Password --}}
                        <div>
                            <label for="password"
                                class="mb-1.5 block text-sm font-medium text-slate-700">Password</label>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                                    <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <input type="password" id="password" name="password" required
                                    autocomplete="current-password" placeholder="Masukkan password"
                                    class="block w-full rounded-xl border py-3 pl-11 pr-12 text-sm text-slate-800 placeholder-slate-400 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-1"
                                    style="border-color: #cbd5e1; background: #f8fafc;"
                                    onfocus="this.style.borderColor='#0ea5e9'; this.style.boxShadow='0 0 0 3px rgba(14,165,233,0.1)'; this.style.background='#fff';"
                                    onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none'; this.style.background='#f8fafc';">
                                {{-- Toggle password visibility --}}
                                <button type="button"
                                    onclick="const p=document.getElementById('password'); const isHidden=p.type==='password'; p.type=isHidden?'text':'password'; this.querySelector('.eye-open').classList.toggle('hidden',!isHidden); this.querySelector('.eye-closed').classList.toggle('hidden',isHidden);"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-slate-600 transition-colors">
                                    <svg class="eye-open h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg class="eye-closed hidden h-5 w-5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- Remember me --}}
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="remember"
                                    class="h-4 w-4 rounded border-slate-300 text-sky-500 focus:ring-sky-500/30">
                                <span class="text-sm text-slate-600">Ingat saya</span>
                            </label>
                        </div>

                        {{-- Submit --}}
                        <button type="submit"
                            class="group relative w-full overflow-hidden rounded-xl py-3 text-sm font-semibold text-white shadow-lg transition-all duration-300 hover:shadow-xl active:scale-[0.98]"
                            style="background: linear-gradient(135deg, #0f172a, #0ea5e9);">
                            <span class="relative z-10 flex items-center justify-center gap-2">
                                <svg class="h-5 w-5 transition-transform duration-300 group-hover:translate-x-0.5"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                </svg>
                                Masuk
                            </span>
                            <div
                                class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/10 to-transparent transition-transform duration-700 group-hover:translate-x-full">
                            </div>
                        </button>
                    </form>
                </div>


            </div>
        </div>
    </div>
</body>

</html>